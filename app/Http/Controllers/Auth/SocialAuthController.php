<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendLoginEmailJob;
use App\Services\ActivityService;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Redirect the user to Google OAuth.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google callback.
     */
    public function handleGoogleCallback()
    {
        try {
            // Clear any stale session data first
            session()->forget('pending_verification_user_id');

            $googleUser = null;
            try {
                // Try stateless first, fall back to regular method
                try {
                    $googleUser = Socialite::driver('google')->stateless()->user();
                } catch (\Exception $e) {
                    // Fallback to regular OAuth flow
                    $googleUser = Socialite::driver('google')->user();
                }
            } catch (\Exception $e) {
                \Log::error('Google OAuth Error: ' . $e->getMessage());
                return redirect()->route('login')->withErrors(['email' => __('auth.google_login_error')]);
            }

            \Log::info('Google OAuth callback - Email: ' . $googleUser->email . ', Name: ' . $googleUser->name);

            // Find existing user by email
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                \Log::info('Existing user found - ID: ' . $user->id . ', email_verified_at: ' . ($user->email_verified_at ?? 'null') . ', password: ' . ($user->password ?? 'null'));
            } else {
                \Log::info('No existing user found - will create new user');
            }

            // Case 1: New user - email doesn't exist in database
            if (!$user) {
                // Generate a unique username from name (remove spaces and special characters)
                $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $googleUser->name));

                // If username is empty after removing special chars, use base from email
                if (empty($baseUsername)) {
                    $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $googleUser->email)[0]));
                }

                // Limit base length to leave room for numbers
                $baseUsername = substr($baseUsername, 0, 20);

                // If still empty, use 'user'
                if (empty($baseUsername)) {
                    $baseUsername = 'user';
                }

                $username = $baseUsername;
                $counter = 1;

                // Ensure uniqueness and minimum length
                while (User::where('username', $username)->exists() || strlen($username) < 3) {
                    // If username is too short, pad it
                    if (strlen($username) < 3) {
                        $username = $baseUsername . $counter;
                    } else {
                        $username = substr($baseUsername, 0, 20 - strlen($counter)) . $counter;
                    }
                    $counter++;
                }

                // Create new user WITHOUT email verification (needs verification)
                // Set password to null to indicate Google-only login initially
                $user = User::create([
                    'username' => $username,
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => null, // NULL - will set password on next step
                    'email_verified_at' => null, // NOT verified - needs verification code
                ]);

                // Verify the user was created with null email_verified_at
                $user = $user->fresh();
                \Log::info('User created - ID: ' . $user->id . ', email_verified_at: ' . ($user->email_verified_at ?? 'null'));

                // Store user ID in session for pending verification
                session(['pending_verification_user_id' => $user->id]);

                // Log in the user
                Auth::login($user);

                // Regenerate session
                request()->session()->regenerate();

                // Log login activity (uses Cloudflare headers - instant)
                try {
                    $activity = $this->activityService->logActivity('login', $user->id);
                    
                    // Dispatch email job if login is suspicious
                    if ($activity->is_suspicious && $user->hasVerifiedEmail()) {
                        SendLoginEmailJob::dispatch($user);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to log Google OAuth login activity: ' . $e->getMessage());
                }

                \Log::info('New Google OAuth user created: ' . $user->email . ' (ID: ' . $user->id . '), redirecting to verification');

                // Redirect to verification page FIRST
                return redirect()->route('verification.notice')->with('message', __('messages.please_verify_email'));
            }

            // Case 2: User exists but email is NOT verified (email_verified_at is null)
            if (is_null($user->email_verified_at)) {
                // Check if user is suspended BEFORE redirecting to verification
                if ($user->is_suspended) {
                    return redirect()->route('login')->with('suspended', true);
                }

                // Store user ID in session for pending verification
                session(['pending_verification_user_id' => $user->id]);

                \Log::info('Existing unverified Google OAuth user: ' . $user->email . ' (ID: ' . $user->id . '), redirecting to verification');

                // Redirect to verification page
                return redirect()->route('verification.notice')->with('message', __('messages.please_verify_email'));
            }

            // Case 3: User exists and email IS verified - log in and redirect to home

            // Check if user is suspended
            if ($user->is_suspended) {
                return redirect()->route('login')->with('suspended', true);
            }

            Auth::login($user);

            // Regenerate session
            request()->session()->regenerate();

            // Log login activity (uses Cloudflare headers - instant)
            try {
                $activity = $this->activityService->logActivity('login', $user->id);
                
                // If login is suspicious, force verification even if already verified
                if ($activity->is_suspicious) {
                    // Send security email
                    SendLoginEmailJob::dispatch($user);
                    
                    // Generate code and set session flag for the middleware
                    $user->generateVerificationCode();
                    session(['auth.suspicious' => true]);
                    
                    \Log::info('Suspicious Google OAuth login detected for user: ' . $user->email);
                    
                    return redirect()->route('verification.notice')
                        ->with('message', __('messages.suspicious_login'));
                }
            } catch (\Exception $e) {
                \Log::error('Failed to log Google OAuth login activity: ' . $e->getMessage());
            }

            \Log::info('Existing verified Google OAuth user logged in: ' . $user->email . ' (ID: ' . $user->id . ')');

            return redirect()->intended('/')->with('message', __('messages.welcome_back', ['username' => $user->username]));
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => __('auth.google_login_error')]);
        }
    }
}
