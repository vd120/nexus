<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSuspended
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user is suspended and redirects to suspended page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for suspended route
        if ($request->routeIs('auth.suspended')) {
            return $next($request);
        }
        
        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user is suspended
            if ($user->is_suspended) {
                // Log out the user
                Auth::logout();
                
                // Invalidate session
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect to suspended page or login with message
                if ($request->expectsJson()) {
                    return response()->json(['error' => __('messages.account_suspended_json')], 403);
                }
                
                return redirect()->route('auth.suspended');
            }
        }

        return $next($request);
    }
}
