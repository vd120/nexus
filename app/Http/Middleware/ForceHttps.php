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
        // Enforce HTTPS and secure configuration for all requests
        config(['session.secure' => true]);
        config(['session.domain' => null]);
        
        $request->server->set('HTTPS', 'on');
        $request->server->set('SERVER_PORT', 443);
        
        $currentUrl = 'https://' . $request->getHost();
        \Illuminate\Support\Facades\URL::forceRootUrl($currentUrl);
        \Illuminate\Support\Facades\URL::forceScheme('https');
        
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;

        return $next($request);
    }
}
