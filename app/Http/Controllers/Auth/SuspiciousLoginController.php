<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
            $field = $challenge['type'] === 'manual' ? 'code' : 'password';
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
            ], 120);

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

        return redirect()->intended('/');
    }

    /**
     * Resend code
     */
    public function resend($uuid)
    {
        $challenge = Cache::get('suspicious_challenge_' . $uuid);
        if (!$challenge || $challenge['type'] !== 'manual') return response()->json(['success' => false]);

        $user = User::find($challenge['user_id']);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $challenge['email_code'] = $code;
        Cache::put('suspicious_challenge_' . $uuid, $challenge, 600);

        $originalLocale = app()->getLocale();
        if ($user->language) app()->setLocale($user->language);
        $secSubject = __('emails.verification_code_security_subject', ['app' => config('app.name')]);
        \Illuminate\Support\Facades\Mail::send('emails.verification-code', ['verificationCode' => $code], function ($message) use ($user, $secSubject) {
            $message->to($user->email)->subject($secSubject);
        });
        app()->setLocale($originalLocale);

        return response()->json(['success' => true]);
    }


}
