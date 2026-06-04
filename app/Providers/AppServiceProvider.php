<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce HTTPS and Root URL globally (covers console and workers)
        $appUrl = config('app.url');
        if (!empty($appUrl)) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        // Register rate limiters
        $this->registerRateLimiters();

        // Register model observers (auto-tag Life Chapter on Post/PulseAnswer creation)
        \App\Models\Post::observe(\App\Observers\PostObserver::class);
        \App\Models\PulseAnswer::observe(\App\Observers\PulseAnswerObserver::class);

        // Pulse & memory reminder nudge
        View::composer('layouts.app', \App\View\Composers\PromptNudgeComposer::class);
    }

    /**
     * Register rate limiters for throttle middleware
     */
    private function registerRateLimiters(): void
    {
        // Auth throttle - 5 attempts per minute
        \Illuminate\Support\Facades\RateLimiter::for('auth', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip());
        });

        // Email verification throttle - 3 attempts per minute
        \Illuminate\Support\Facades\RateLimiter::for('verification', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(3)->by($request->ip());
        });

        // Posts throttle - 20 posts per minute
        \Illuminate\Support\Facades\RateLimiter::for('posts', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($request->user()?->id ?? $request->ip());
        });

        // Comments throttle - 30 comments per minute
        \Illuminate\Support\Facades\RateLimiter::for('comments', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($request->user()?->id ?? $request->ip());
        });
    }
}
