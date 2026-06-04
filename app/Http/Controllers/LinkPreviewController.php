<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LinkPreviewController extends Controller
{
    // Private/loopback IP ranges to block (SSRF protection)
    private const BLOCKED_RANGES = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '::1/128',
        'fc00::/7',
    ];

    public function fetch(Request $request)
    {
        $request->validate(['url' => 'required|url|max:2048']);

        $url = $request->input('url');

        // Validate scheme (http/https only)
        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return response()->json(['error' => 'Invalid URL scheme.'], 422);
        }

        // SSRF: resolve hostname and check against blocked ranges
        $host = $parsed['host'] ?? '';
        if ($this->isPrivateHost($host)) {
            return response()->json(['error' => 'URL not allowed.'], 422);
        }

        $cacheKey = 'link_preview_' . md5($url);

        // Only cache successful results — don't lock users out for 24h on a failed fetch
        if (Cache::has($cacheKey)) {
            $preview = Cache::get($cacheKey);
        } else {
            $preview = null;
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; NexusBot/1.0; +https://nexusocial.qzz.io)',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.5',
                    ])
                    ->get($url);

                if ($response->successful()) {
                    $preview = $this->extractOgTags($response->body(), $url);
                    if ($preview && ($preview['title'] || $preview['image'])) {
                        Cache::put($cacheKey, $preview, now()->addHours(6));
                    }
                }
            } catch (\Exception $e) {
                // Don't cache failures
            }
        }

        if (!$preview || (!$preview['title'] && !$preview['image'])) {
            return response()->json(['error' => 'Could not fetch preview.'], 422);
        }

        return response()->json($preview);
    }

    private function isPrivateHost(string $host): bool
    {
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false; // couldn't resolve, let it through for now
        }

        // Check localhost names
        if (in_array(strtolower($host), ['localhost', 'ip6-localhost', 'ip6-loopback'])) {
            return true;
        }

        // Check if it's a private IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            foreach (self::BLOCKED_RANGES as $range) {
                if ($this->ipInRange($ip, $range)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong     = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $mask       = -1 << (32 - (int) $bits);
            return ($ipLong & $mask) === ($subnetLong & $mask);
        }
        return false;
    }

    private function extractOgTags(string $html, string $sourceUrl): array
    {
        // Suppress HTML parse errors
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new \DOMXPath($doc);

        $get = function (string $property) use ($xpath): string {
            $nodes = $xpath->query("//meta[@property='$property']/@content | //meta[@name='$property']/@content");
            return $nodes->length ? trim($nodes->item(0)->nodeValue) : '';
        };

        $title       = $get('og:title') ?: $get('twitter:title');
        $description = $get('og:description') ?: $get('twitter:description') ?: $get('description');
        $image       = $get('og:image') ?: $get('twitter:image');

        // Fallback: page <title>
        if (!$title) {
            $titles = $doc->getElementsByTagName('title');
            if ($titles->length) $title = trim($titles->item(0)->textContent);
        }

        // Make image URL absolute
        if ($image && !str_starts_with($image, 'http')) {
            $base = ($parsed = parse_url($sourceUrl)) ? $parsed['scheme'] . '://' . $parsed['host'] : '';
            $image = str_starts_with($image, '/') ? $base . $image : $base . '/' . $image;
        }

        return [
            'url'         => $sourceUrl,
            'title'       => mb_substr($title, 0, 200),
            'description' => mb_substr($description, 0, 400),
            'image'       => $image,
            'domain'      => parse_url($sourceUrl, PHP_URL_HOST),
        ];
    }
}
