<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales
     */
    const SUPPORTED_LOCALES = ['en', 'ar'];

    /**
     * Default locale
     */
    const DEFAULT_LOCALE = 'en';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from various sources in order of priority:
        // 1. Route parameter (e.g., /lang/ar)
        // 2. Cookie (available before session)
        // 3. Session
        // 4. Authenticated user's preference
        // 5. Browser's preferred language
        // 6. Default locale from config
        
        $locale = $this->determineLocale($request);
        
        // Set the application locale
        App::setLocale($locale);
        
        // Set locale for Carbon (dates)
        \Carbon\Carbon::setLocale($locale);
        
        // Store locale in request for later use
        $request->setLocale($locale);
        
        // Share locale with all views
        view()->share('currentLocale', $locale);
        view()->share('direction', $locale === 'ar' ? 'rtl' : 'ltr');

        $response = $next($request);

        // Set cookie for locale (skip for streamed responses like CSV exports)
        if ($response instanceof \Symfony\Component\HttpFoundation\Response && 
            !$response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            $response->withCookie(cookie('locale', $locale, 43200)); // 30 days
        }

        return $response;
    }

    /**
     * Determine the locale from various sources
     */
    private function determineLocale(Request $request): string
    {
        // 1. Check route parameter (handled by route middleware)
        if ($request->route('locale')) {
            $locale = $request->route('locale');
            if ($this->isValidLocale($locale)) {
                Session::put('locale', $locale);
                return $locale;
            }
        }

        // 2. HIGHEST PRIORITY: Check authenticated user's preference in DB
        // This ensures Octane workers stay in sync even if sessions drift
        if (auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }
        
        // 3. Check session
        if (Session::has('locale')) {
            $locale = Session::get('locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // 4. Check cookie
        if ($request->cookie('locale')) {
            $locale = $request->cookie('locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }
        
        // 5. Check browser's preferred language
        $preferredLanguage = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);
        if ($preferredLanguage && $this->isValidLocale($preferredLanguage)) {
            return $preferredLanguage;
        }
        
        // 6. Return default locale
        return self::DEFAULT_LOCALE;
    }

    /**
     * Check if the locale is supported
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }
}
