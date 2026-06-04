<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendLoginEmailJob;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();
        
        if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            // Log the attempt to check for suspicious activity
            $activity = $this->activityService->logActivity('login_attempt', $user->id);
            
            // Check for Suspicion (Exclude admins)
            if ($activity->is_suspicious && !$user->is_admin) {
                $challengeUuid = (string) \Illuminate\Support\Str::uuid();
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                
                \Illuminate\Support\Facades\Cache::put('suspicious_challenge_' . $challengeUuid, [
                    'user_id' => $user->id,
                    'type' => 'manual',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'remember' => $request->boolean('remember'),
                    'email_code' => $code,
                    'activity_id' => $activity->id
                ], 600);

                // Send Security Code Email
                $originalLocale = app()->getLocale();
                if ($user->language) app()->setLocale($user->language);
                $verifySubject = __('emails.verification_code_security_subject', ['app' => config('app.name')]);
                \Illuminate\Support\Facades\Mail::send('emails.verification-code', ['verificationCode' => $code], function ($message) use ($user, $verifySubject) {
                    $message->to($user->email)->subject($verifySubject);
                });
                app()->setLocale($originalLocale);

                return redirect()->route('login.suspicious.view', $challengeUuid);
            }

            // Concurrent Session Check (If not suspicious or already verified)
            if ($user->hasOtherActiveSessions() && !$user->is_admin) {
                $challengeUuid = (string) \Illuminate\Support\Str::uuid();
                
                \Illuminate\Support\Facades\Cache::put('login_challenge_' . $challengeUuid, [
                    'user_id' => $user->id,
                    'remember' => $request->boolean('remember'),
                    'status' => 'pending',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ], 120);

                app(\App\Services\SocketEmitService::class)->emitToUser($user->id, 'security:challenge', [
                    'uuid' => $challengeUuid,
                    'ip' => $request->ip(),
                    'device' => $this->activityService->getDeviceName($request->userAgent()),
                    'except_session' => $request->session()->getId()
                ]);

                return redirect()->route('login.challenge', $challengeUuid);
            }
        }

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::user();
            $request->session()->regenerate();

            if ($user->is_suspended) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login.view');
            }

            if (!$user->hasVerifiedEmail()) {
                if ($user->created_at->addMinutes(10)->isPast()) {
                    $user->delete();
                    Auth::logout();
                    return redirect()->route('login.view')->with('error', 'Registration expired.');
                }
                return redirect()->route('verification.notice');
            }
            
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        Auth::logout();
        
        // If the user logs out without verifying their email, delete the record
        // so they (or others) can use the username/email again immediately.
        if ($user && is_null($user->email_verified_at)) {
            $user->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function challengeView($uuid)
    {
        $challenge = Cache::get('login_challenge_' . $uuid);
        if (!$challenge) {
            return redirect()->route('login.view')->with('error', 'Login session expired.');
        }

        return view('auth.login-challenge', [
            'uuid' => $uuid,
            'device' => $this->activityService->getDeviceName($challenge['user_agent'])
        ]);
    }

    public function challengeStatus($uuid)
    {
        $challenge = Cache::get('login_challenge_' . $uuid);
        if (!$challenge) return response()->json(['status' => 'expired']);

        if ($challenge['status'] === 'approved') {
            $user = \App\Models\User::find($challenge['user_id']);
            if ($user) {
                Auth::login($user, $challenge['remember']);
                request()->session()->regenerate();
                return response()->json(['status' => 'approved', 'redirect' => route('home')]);
            }
        }

        return response()->json(['status' => $challenge['status']]);
    }

    public function approveChallenge(Request $request, $uuid)
    {
        $challenge = Cache::get('login_challenge_' . $uuid);
        if (!$challenge || Auth::id() != $challenge['user_id']) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or expired.'], 403);
        }

        $challenge['status'] = 'approved';
        Cache::put('login_challenge_' . $uuid, $challenge, 120);

        app(\App\Services\SocketEmitService::class)->emitToUser(Auth::id(), 'security:approved', ['uuid' => $uuid]);
        
        return response()->json(['success' => true]);
    }

    public function denyChallenge(Request $request, $uuid)
    {
        $challenge = Cache::get('login_challenge_' . $uuid);
        if (!$challenge || Auth::id() != $challenge['user_id']) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or expired.'], 403);
        }

        $challenge['status'] = 'denied';
        Cache::put('login_challenge_' . $uuid, $challenge, 120);

        app(\App\Services\SocketEmitService::class)->emitToUser(Auth::id(), 'security:denied', ['uuid' => $uuid]);

        return response()->json(['success' => true]);
    }

    public function sendEmailChallenge(Request $request, $uuid)
    {
        $challenge = Cache::get('login_challenge_' . $uuid);
        if (!$challenge) {
            return response()->json(['success' => false, 'message' => __('auth.login_session_expired')], 422);
        }

        $user = \App\Models\User::find($challenge['user_id']);
        if (!$user) {
            return response()->json(['success' => false, 'message' => __('auth.login_session_expired')], 422);
        }

        // Generate a 6-digit code for this challenge
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $challenge['email_code'] = $code;
        $challenge['email_code_expires_at'] = now()->addMinutes(10)->timestamp;
        Cache::put('login_challenge_' . $uuid, $challenge, 120);

        // Send the email in the user's language
        $originalLocale = app()->getLocale();
        if ($user->language) app()->setLocale($user->language);
        $loginSubject = __('emails.verification_code_login_subject', ['app' => config('app.name')]);
        \Illuminate\Support\Facades\Mail::send('emails.verification-code', ['verificationCode' => $code], function ($message) use ($user, $loginSubject) {
            $message->to($user->email)->subject($loginSubject);
        });
        app()->setLocale($originalLocale);

        return response()->json(['success' => true, 'message' => __('auth.verification_code_sent_to_email')]);
    }

    public function verifyEmailChallenge(Request $request, $uuid)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $challenge = Cache::get('login_challenge_' . $uuid);
        if (!$challenge) {
            return response()->json(['success' => false, 'message' => __('auth.login_session_expired')], 422);
        }

        if (!isset($challenge['email_code']) || $challenge['email_code'] !== $request->code) {
            return response()->json(['success' => false, 'message' => __('auth.invalid_verification_code')], 422);
        }

        if ($challenge['email_code_expires_at'] < now()->timestamp) {
            return response()->json(['success' => false, 'message' => __('auth.verification_code_expired')], 422);
        }

        // Mark as approved
        $challenge['status'] = 'approved';
        Cache::put('login_challenge_' . $uuid, $challenge, 120);

        // Emit to the user's other sessions to close the approval modal
        app(\App\Services\SocketEmitService::class)->emitToUser($challenge['user_id'], 'security:approved', ['uuid' => $uuid]);

        return response()->json(['success' => true]);
    }


}
