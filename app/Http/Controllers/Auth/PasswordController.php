<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Change the user's password.
     */
    public function change(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Log password change activity (uses Cloudflare headers - instant)
        try {
            $this->activityService->logPasswordChange($request->user()->id);
        } catch (\Exception $e) {
            \Log::error('Failed to log password_change activity: ' . $e->getMessage());
        }

        return back()->with('success', __('messages.password_changed_success'));
    }

    /**
     * Show set password page for Google OAuth users.
     */
    public function showSetPassword()
    {
        $user = auth()->user();
        
        // Only allow users who registered via Google (no password set)
        if (!$user || $user->password !== null) {
            return redirect()->route('home');
        }

        return view('auth.set-password');
    }

    /**
     * Set password for Google OAuth users.
     */
    public function setPassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Only allow users who registered via Google
        if (!$user || $user->password !== null) {
            return redirect()->route('home');
        }

        // Verify email is verified before allowing password set
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('message', __('messages.please_verify_email'));
        }

        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Clear any pending verification session
        session()->forget('pending_verification_user_id');

        session()->save();

        return redirect()->route('home')->with('success', __('messages.password_set_success'));
    }
}
