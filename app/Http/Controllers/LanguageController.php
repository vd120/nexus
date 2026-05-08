<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class LanguageController extends Controller
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
     * Change the application language
     *
     * @param Request $request
     * @param string $locale
     * @return RedirectResponse
     */
    public function switch(Request $request, string $locale)
    {
        // Validate the locale
        if (!$this->isValidLocale($locale)) {
            Log::warning("Invalid locale attempted: {$locale}");
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unsupported language'], 400);
            }
            abort(400, __('messages.unsupported_language', ['locale' => $locale]));
        }
        
        // Store locale in session
        Session::put('locale', $locale);
        
        // ALSO store in cookie (for error pages!)
        $cookie = cookie('locale', $locale, 43200); // 30 days
        
        // Set the application locale for this request
        App::setLocale($locale);
        
        // If user is authenticated, save to database
        if (auth()->check()) {
            auth()->user()->update(['language' => $locale]);
        }

        // Return JSON if AJAX
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'locale' => $locale,
                'direction' => self::getSupportedLocales()[$locale]['direction'] ?? 'ltr'
            ])->withCookie($cookie);
        }
        
        // Get return URL from query parameter or use previous URL
        $returnUrl = $request->query('return');
        
        if (!$returnUrl) {
            $returnUrl = Session::previousUrl() ?? route('home');
        }
        
        // Parse the URL to remove any existing locale prefix
        $parsedUrl = parse_url($returnUrl);
        $path = $parsedUrl['path'] ?? '/';
        
        // Remove any existing locale from the path (e.g., /lang/ar -> remove)
        foreach (self::SUPPORTED_LOCALES as $supportedLocale) {
            if (strpos($path, "/lang/{$supportedLocale}") === 0) {
                $path = '/';
                break;
            }
        }
        
        // Ensure path starts with /
        if (!$path) {
            $path = '/';
        }
        
        // Build the final URL
        $finalUrl = $path;
        if (!empty($parsedUrl['query'])) {
            // Remove 'return' parameter from query if present
            $queryParts = [];
            parse_str($parsedUrl['query'], $queryParts);
            unset($queryParts['return']);
            if (!empty($queryParts)) {
                $finalUrl .= '?' . http_build_query($queryParts);
            }
        }

        // Redirect to the final URL with cookie
        return redirect($finalUrl)->withCookie($cookie);
    }

    /**
     * Get the language name for display
     */
    private function getLanguageName(string $locale): string
    {
        return match($locale) {
            'en' => 'English',
            'ar' => 'العربية',
            default => $locale,
        };
    }

    /**
     * Check if the locale is supported
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * Get all supported locales with their names
     *
     * @return array
     */
    public static function getSupportedLocales(): array
    {
        return [
            'en' => [
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'flag' => '🇬🇧',
            ],
            'ar' => [
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'flag' => '🇸🇦',
            ],
        ];
    }
}
