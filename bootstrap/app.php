<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withEvents(discover: [
        __DIR__.'/../app/Listeners',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\CompressionMiddleware::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Trust proxies for proper header handling (Cloudflare, etc.)
        $middleware->trustProxies(at: '*');

        // Trust Cloudflare IPs and use their headers
        $middleware->web(append: [
            \App\Http\Middleware\TrustCloudflare::class,
        ]);

        // Set locale for multilingual support
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Optimized heartbeat for user online status
        $middleware->web(append: [
            \App\Http\Middleware\LogRealTimeRequests::class,
        ]);

        // Force HTTPS to prevent browser security warnings
        $middleware->web(append: [
            \App\Http\Middleware\ForceHttps::class,
        ]);

        // Enable Sanctum's stateful API authentication for sessions
        $middleware->statefulApi();

        // Admin middleware alias
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'suspended' => \App\Http\Middleware\CheckUserSuspended::class,
            'verified' => \App\Http\Middleware\CheckEmailVerified::class,
            'password.set' => \App\Http\Middleware\RequirePasswordSet::class,
        ]);
    })
    ->withCommands([
        \App\Console\Commands\BackfillIpLocations::class,

        \App\Console\Commands\Troubleshoot::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            return response()->view('errors.404', [], 404);
        });
    })->create();
