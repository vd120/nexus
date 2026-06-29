<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuspiciousLoginController extends Controller
{
    protected \App\Services\ActivityService $activityService;

    public function __construct(\App\Services\ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Show the suspicious challenge view
     */
    public function show($uuid)
    {
        $challenge = Cache::get('suspicious_challenge_' . $uuid);
        if (!$challenge) {
            return redirect()->route('login.view')->with('error', __('auth.login_session_expired'));
        }

        return view('auth.suspicious-challenge', [
            'uuid' => $uuid,
            'type' => $challenge['type'],
            'email' => User::find($challenge['user_id'])->email
        ]);
    }

    /**
     * Verify the challenge
     */
    public function verify(Request $request, $uuid)
    {
        $challenge = Cache::get('suspicious_challenge_' . $uuid);
        if (!$challenge) {
            return redirect()->route('login.view')->with('error', __('auth.login_session_expired'));
        }

        $limiterKey = 'suspicious_verify_user_' . $challenge['user_id'];

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($limiterKey);
            $field = ($challenge['type'] === 'manual' || $challenge['type'] === '2fa') ? 'code' : 'password';
            return back()->withErrors([
                $field => __('auth.throttle', ['seconds' => $seconds])
            ]);
        }

        $user = User::find($challenge['user_id']);

        if ($challenge['type'] === 'manual') {
            $request->validate(['code' => 'required|string|size:6']);
            if ($request->code !== $challenge['email_code']) {
                \Illuminate\Support\Facades\RateLimiter::hit($limiterKey, 300);
                return back()->withErrors(['code' => __('auth.invalid_verification_code')]);
            }
        } elseif ($challenge['type'] === '2fa') {
            $request->validate(['code' => 'required|string']);
            $google2fa = app('pragmarx.google2fa');
            $code = trim($request->code);
            $verified = false;

            if (strlen($code) === 6 && ctype_digit($code)) {
                if ($google2fa->verifyKey($user->two_factor_secret, $code)) {
                    $verified = true;
                }
            }

            if (!$verified) {
                $recoveryCodes = $user->two_factor_recovery_codes ?? [];
                foreach ($recoveryCodes as $index => $hashed) {
                    if (Hash::check($code, $hashed)) {
                        $recoveryCodes[$index] = null;
                        $user->two_factor_recovery_codes = array_values(array_filter($recoveryCodes));
                        $user->save();
                        $verified = true;
                        break;
                    }
                }
            }

            if (!$verified) {
                \Illuminate\Support\Facades\RateLimiter::hit($limiterKey, 300);
                return back()->withErrors(['code' => __('auth.two_factor_invalid_code')]);
            }

            session(['two_factor_confirmed' => true]);
        } else {
            $request->validate(['password' => 'required|string']);
            if (!$user->password || !Hash::check($request->password, $user->password)) {
                \Illuminate\Support\Facades\RateLimiter::hit($limiterKey, 300);
                return back()->withErrors(['password' => __('auth.invalid_account_password')]);
            }
        }

        // Clear limit upon success
        \Illuminate\Support\Facades\RateLimiter::clear($limiterKey);

        // Verification successful!
        // Mark the login activity as verified (optional but good for history)
        if (isset($challenge['activity_id'])) {
            \App\Models\ActivityLog::where('id', $challenge['activity_id'])->update(['action' => 'login']);
        }

        // IMPORTANT: Check for Concurrent Session BEFORE final login
        if ($user->hasOtherActiveSessions() && !$user->is_admin) {
            $loginUuid = (string) Str::uuid();

            Cache::put('login_challenge_' . $loginUuid, [
                'user_id' => $user->id,
                'remember' => $challenge['remember'] ?? true,
                'status' => 'pending',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ], 600);

            // Trigger real-time approval request
            app(SocketEmitService::class)->emitToUser($user->id, 'security:challenge', [
                'uuid' => $loginUuid,
                'ip' => $request->ip(),
                'device' => $this->activityService->getDeviceName($request->userAgent()),
                'except_session' => $request->session()->getId()
            ]);

            return redirect()->route('login.challenge', $loginUuid);
        }

        // No concurrent session (or offline), proceed to login
        Auth::login($user, $challenge['remember'] ?? true);
        $request->session()->regenerate();
        $this->activityService->logActivity('login', $user->id);

        return redirect()->intended('/');
    }

    /**
     * Resend code
     */
    public function resend($uuid)
    {
        $challenge = Cache::get('suspicious_challenge_' . $uuid);
        if (!$challenge || $challenge['type'] !== 'manual') return response()->json(['success' => false]);

        $limiterKey = 'suspicious_resend_' . $challenge['user_id'];
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($limiterKey, 3)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($limiterKey);
            return response()->json(['success' => false, 'message' => __('auth.throttle', ['seconds' => $seconds])], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($limiterKey, 600);

        $user = User::find($challenge['user_id']);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $challenge['email_code'] = $code;
        Cache::put('suspicious_challenge_' . $uuid, $challenge, 600);

        $originalLocale = app()->getLocale();
        if ($user->language) app()->setLocale($user->language);
        \Illuminate\Support\Facades\Mail::to($user->email, $user->name)->send(new VerificationCodeMail($user, $code));
        app()->setLocale($originalLocale);

        return response()->json(['success' => true]);
    }


}
