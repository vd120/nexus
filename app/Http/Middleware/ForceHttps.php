<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production or if APP_FORCE_HTTPS is set
        // Skip HTTPS enforcement in local development
        if (!$request->secure() && (app()->environment('production') || config('app.force_https', false))) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
