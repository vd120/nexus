<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendLoginEmailJob;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::user();

            // Emit account:status to all existing sessions BEFORE starting the new one
            app(\App\Services\SocketEmitService::class)->emitToUser($user->id, 'account:status', [
                'status' => 'concurrent_session',
                'message' => __('messages.another_session_started'),
                'redirect_url' => route('login.view')
            ]);

            $request->session()->regenerate();

            // Log login activity
            try {
                $activity = $this->activityService->logActivity('login', $user->id);
                
                // If login is suspicious, force verification even if already verified
                if ($activity->is_suspicious) {
                    // Send security email
                    SendLoginEmailJob::dispatch($user);
                    
                    // Generate code and set session flag for the middleware
                    $user->generateVerificationCode();
                    session(['auth.suspicious' => true]);
                    
                    return redirect()->route('verification.notice')
                        ->with('message', __('messages.suspicious_login'));
                }
            } catch (\Exception $e) {
                \Log::error('Failed to log login activity: ' . $e->getMessage());
            }

            if ($user->is_suspended) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login.view')->with('suspended', true);
            }

            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')
                    ->with('message', __('messages.please_verify_email'));
            }

            session([
                'session_id' => $request->session()->getId(),
                'last_activity' => now()->timestamp,
                'login_time' => now()->toDateTimeString()
            ]);

            return redirect()->intended('/');
        }

        // Log failed login attempt
        try {
            $this->activityService->logFailedLogin($request->input('email'));
        } catch (\Exception $e) {
            \Log::error('Failed to log failed_login activity: ' . $e->getMessage());
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    public function logoutWithMessage(Request $request, $type = 'concurrent')
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $messageKey = $type === 'deleted' ? 'account_deleted' : 'concurrent_login';

        return redirect()->route('login.view')->with($messageKey, true);
    }
}
