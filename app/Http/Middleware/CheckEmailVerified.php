<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEmailVerified
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user has verified their email address.
     * Unverified users are redirected to the verification page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for verification-related routes
        if ($request->routeIs('verification.*') ||
            $request->routeIs('auth.verify-email') ||
            $request->routeIs('logout') ||
            $request->routeIs('auth.suspended')) {
            return $next($request);
        }

        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user has verified their email OR if they have a suspicious login pending verification
            if (!$user->hasVerifiedEmail() || session('auth.suspicious')) {
                // For API requests, return JSON error
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => session('auth.suspicious') 
                            ? 'Suspicious login detected. Please verify your identity.' 
                            : 'Please verify your email address to access this feature.',
                        'redirect' => route('verification.notice')
                    ], 403);
                }

                // Redirect to verification notice page
                $message = session('auth.suspicious') 
                    ? 'Suspicious login detected. Please verify your identity to continue.' 
                    : 'Please verify your email address to continue.';

                return redirect()->route('verification.notice')
                    ->with('message', $message);
            }
        }

        return $next($request);
    }
}