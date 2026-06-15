<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ManifestController extends Controller
{
    public function show(): Response
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.base.json')), true);

        $locale = app()->getLocale();
        $manifest['lang'] = $locale;
        $manifest['dir']  = $locale === 'ar' ? 'rtl' : 'ltr';

        return response(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }
}
