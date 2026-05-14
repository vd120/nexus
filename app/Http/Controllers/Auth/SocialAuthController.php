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

            // Concurrent Session Check (BEFORE Login)
            if ($user->hasOtherActiveSessions() && !$user->is_admin) {
                $challengeUuid = (string) Str::uuid();
                
                Cache::put('login_challenge_' . $challengeUuid, [
                    'user_id' => $user->id,
                    'remember' => true,
                    'status' => 'pending',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ], 120);

                app(\App\Services\SocketEmitService::class)->emitToUser($user->id, 'security:challenge', [
                    'uuid' => $challengeUuid,
                    'ip' => request()->ip(),
                    'device' => $this->getDeviceName(request()->userAgent()),
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

    /**
     * Helper to get device name from user agent
     */
    protected function getDeviceName($userAgent)
    {
        if (str_contains($userAgent, 'Android')) return 'Android Device';
        if (str_contains($userAgent, 'iPhone')) return 'iPhone';
        if (str_contains($userAgent, 'iPad')) return 'iPad';
        if (str_contains($userAgent, 'Windows')) return 'Windows PC';
        if (str_contains($userAgent, 'Macintosh')) return 'Mac';
        return 'Unknown Device';
    }
}
