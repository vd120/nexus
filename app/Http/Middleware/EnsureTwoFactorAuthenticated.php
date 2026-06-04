<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->two_factor_secret) {
            return $next($request);
        }

        if (session('two_factor_confirmed')) {
            return $next($request);
        }

        // Store intended URL for redirect after challenge
        session(['url.intended' => $request->url()]);

        return redirect()->route('2fa.challenge');
    }
}
