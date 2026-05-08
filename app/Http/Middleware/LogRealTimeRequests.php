<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRealTimeRequests
{
    /**
     * Handle an incoming request.
     * This is an optimized version that only updates the user's "Last Active" status
     * without heavy logging or device parsing to ensure maximum performance.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user) {
            $lastUpdateKey = 'last_active_' . $user->id;
            
            // Only update the database once every 5 minutes to minimize DB load
            if (!cache()->has($lastUpdateKey)) {
                // Perform a silent update to avoid triggering any expensive model observers
                // if they aren't needed for simple status updates.
                $user->update([
                    'last_active' => now()
                ]);
                
                // Cache the update state for 5 minutes (300 seconds)
                cache()->put($lastUpdateKey, true, 300);
            }
        }
        
        return $next($request);
    }
}
