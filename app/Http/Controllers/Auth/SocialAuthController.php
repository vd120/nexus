<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    protected \App\Services\ActivityService $activityService;

    public function __construct(\App\Services\ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Redirect to Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->with([
                'prompt' => 'select_account consent', 
                'access_type' => 'offline'
            ])
            ->redirect();
    }

    /**
     * Handle Google Callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'avatar' => $googleUser->getAvatar(),
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            // Log the attempt to check for suspicious activity
            $activity = $this->activityService->logActivity('login_attempt', $user->id);

            // Check for Suspicion (Exclude admins)
            if ($activity->is_suspicious && !$user->is_admin) {
                $challengeUuid = (string) Str::uuid();
                
                Cache::put('suspicious_challenge_' . $challengeUuid, [
                    'user_id' => $user->id,
                    'type' => 'oauth',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'remember' => true,
                    'activity_id' => $activity->id
                ], 600);

                // Send Security Alert Email
                $originalLocale = app()->getLocale();
                if ($user->language) app()->setLocale($user->language);
                $alertSubject = __('emails.security_alert_subject', ['app' => config('app.name')]);
                \Illuminate\Support\Facades\Mail::send('emails.login-security-alert', ['activity' => $activity, 'user' => $user], function ($message) use ($user, $alertSubject) {
                    $message->to($user->email)->subject($alertSubject);
                });
                app()->setLocale($originalLocale);

                return redirect()->route('login.suspicious.view', $challengeUuid);
            }

            // Concurrent Session Check (BEFORE Login)
            if ($user->hasOtherActiveSessions() && !$user->is_admin) {
                $challengeUuid = (string) Str::uuid();
                
                Cache::put('login_challenge_' . $challengeUuid, [
                    'user_id' => $user->id,
                    'remember' => true,
                    'status' => 'pending',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ], 600);

                app(\App\Services\SocketEmitService::class)->emitToUser($user->id, 'security:challenge', [
                    'uuid' => $challengeUuid,
                    'ip' => request()->ip(),
                    'device' => $this->activityService->getDeviceName(request()->userAgent()),
                    'except_session' => request()->session()->getId()
                ]);

                return redirect()->route('login.challenge', $challengeUuid);
            }

            Auth::login($user, true);
            request()->session()->regenerate();
            
            return redirect()->intended('/');

        } catch (\Exception $e) {
            \Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Google authentication failed.']);
        }
    }


}
