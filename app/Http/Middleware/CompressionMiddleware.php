<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if compression is already handled or not needed
        if (ini_get('zlib.output_compression') || !function_exists('gzencode')) {
            return $response;
        }

        // Only compress text-based responses
        $contentType = $response->headers->get('Content-Type');
        if (!is_string($contentType) || !preg_match('/(text|javascript|json|xml)/i', $contentType)) {
            return $response;
        }

        // Check if the client supports gzip
        if (!$request->acceptsHtml() || !str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            return $response;
        }

        $content = $response->getContent();
        if (strlen($content) < 1000) { // Don't compress very small responses
            return $response;
        }

        $response->setContent(gzencode($content, 6));
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
