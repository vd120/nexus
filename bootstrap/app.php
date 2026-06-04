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
        // Global middleware - prepend ensures environment is sanitized early
        $middleware->prepend([
            \App\Http\Middleware\CorsMiddleware::class,
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\TrustCloudflare::class,
        ]);


        $middleware->web(append: [
            \App\Http\Middleware\CompressionMiddleware::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Trust proxies for proper header handling (Cloudflare, etc.)
        $middleware->trustProxies(at: '*');

        // Set locale for multilingual support
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Optimized heartbeat for user online status
        $middleware->web(append: [
            \App\Http\Middleware\LogRealTimeRequests::class,
        ]);

        // Enable Sanctum's stateful API authentication for sessions
        // $middleware->statefulApi();

        // Except session cookies from encryption for handover compatibility
        $middleware->validateCsrfTokens(except: [
            // No exceptions needed for standard web flow
        ]);

        $middleware->encryptCookies(except: [
            // Standard encryption for all cookies
        ]);

        // Admin middleware alias
        $middleware->alias([
            'admin'      => \App\Http\Middleware\AdminMiddleware::class,
            'suspended'  => \App\Http\Middleware\CheckUserSuspended::class,
            'verified'   => \App\Http\Middleware\CheckEmailVerified::class,
            'password.set' => \App\Http\Middleware\RequirePasswordSet::class,
            '2fa'        => \App\Http\Middleware\EnsureTwoFactorAuthenticated::class,
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

        // Sentry error reporting
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
