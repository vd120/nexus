<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustCloudflare
{
    /**
     * Cloudflare IP ranges (updated regularly)
     * Source: https://www.cloudflare.com/ips/
     */
    protected array $cloudflareIps = [
        // IPv4
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        // IPv6 (simplified - checking prefix)
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Only process if request appears to come from Cloudflare
        if ($this->isFromCloudflare($request)) {
            // Trust Cloudflare headers
            $this->setTrustedHeaders($request);
        }

        return $next($request);
    }

    /**
     * Check if request is from Cloudflare
     */
    protected function isFromCloudflare(Request $request): bool
    {
        $clientIp = $request->server->get('REMOTE_ADDR');
        
        if (!$clientIp) {
            return false;
        }

        foreach ($this->cloudflareIps as $cfIpRange) {
            if ($this->ipInRange($clientIp, $cfIpRange)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set trusted headers when request is from Cloudflare
     */
    protected function setTrustedHeaders(Request $request): void
    {
        if ($request->header('CF-Connecting-IP')) {
            $request->server->set('REMOTE_ADDR', $request->header('CF-Connecting-IP'));
        }

        // CF-Visitor tells us the scheme the browser actually used (http or https).
        // Without this, Flexible SSL makes Laravel think every request is HTTP,
        // causing Secure cookies to be rejected by the browser.
        $cfVisitor = $request->header('CF-Visitor');
        if ($cfVisitor && str_contains($cfVisitor, '"scheme":"https"')) {
            $request->server->set('HTTPS', 'on');
            $request->server->set('HTTP_X_FORWARDED_PROTO', 'https');
        }
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);

        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6InRange($ip, $subnet, (int)$bits);
        }

        // Handle IPv4
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * Check if IPv6 is in range (simplified check)
     */
    protected function ipv6InRange(string $ip, string $subnet, int $bits): bool
    {
        // Simplified: just check if it starts with the same prefix
        $ipPrefix = substr($ip, 0, strlen($subnet) - 5); // Remove ::/XX
        $subnetPrefix = substr($subnet, 0, strlen($subnet) - 5);
        
        return strpos($ip, $subnetPrefix) === 0;
    }
}
