<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ActivityService
{
    /**
     * Log user activity - uses Cloudflare headers (INSTANT - no external API calls)
     */
    public function logActivity(string $action, ?int $userId = null, ?string $sessionId = null): ActivityLog
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $request = request();
        $ipAddress = $this->getIpAddress($request);
        $sessionId = $sessionId ?? $request->session()->getId();

        // Get location from Cloudflare headers (INSTANT - no API calls)
        $locationData = $this->getLocationFromCloudflare($request);

        // Ensure session has user_id set
        $this->updateSessionUserId($sessionId, $userId);

        return ActivityLog::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent() ?? '',
            'device_type' => $this->getDeviceType($request),
            'browser' => $this->getBrowser($request),
            'os' => $this->getOS($request),
            'country' => $locationData['country'] ?? null,
            'city' => $locationData['city'] ?? null,
            'region' => $locationData['region'] ?? null,
            'isp' => null,
            'timezone' => null,
            'latitude' => $locationData['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? null,
            'logged_at' => now(),
        ]);
    }

    /**
     * Update session with user_id if not already set
     */
    private function updateSessionUserId(string $sessionId, int $userId): void
    {
        \DB::table('sessions')
            ->where('id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);
    }

    /**
     * Get location from Cloudflare headers (instant - no external APIs)
     */
    private function getLocationFromCloudflare(Request $request): array
    {
        $cfCountry = $request->header('CF-IPCountry');
        $cfCity = $request->header('CF-IPCity');
        $cfRegion = $request->header('CF-Region');
        $cfLat = $request->header('CF-IPLatitude');
        $cfLon = $request->header('CF-IPLongitude');

        if ($cfCountry && $cfCountry !== '-') {
            return [
                'country' => $this->getCountryName($cfCountry),
                'countryCode' => $cfCountry,
                'city' => $cfCity ?: null,
                'region' => $cfRegion ?: null,
                'latitude' => $cfLat ?: null,
                'longitude' => $cfLon ?: null,
            ];
        }

        return [
            'country' => null,
            'city' => null,
            'region' => null,
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * Get IP address from request (works with Cloudflare, proxies, and load balancers)
     */
    private function getIpAddress(Request $request): string
    {
        // Priority 1: Cloudflare specific headers (most reliable for Cloudflare tunnel)
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }
        
        // Priority 2: Cloudflare alternative header
        if ($request->header('CF-IPCountry')) {
            // If Cloudflare is adding country, also check for their IP header
            $cfConnectingIp = $request->header('X-Forwarded-For');
            if ($cfConnectingIp) {
                $ips = explode(',', $cfConnectingIp);
                // Cloudflare appends the real IP at the end of X-Forwarded-For
                return trim(end($ips));
            }
        }

        // Priority 3: Standard X-Forwarded-For header (proxy/load balancer)
        if ($request->header('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            // Get the first IP (original client IP)
            return trim($ips[0]);
        }

        // Priority 4: X-Real-IP header (nginx)
        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }
        
        // Priority 5: True-Client-IP (some CDNs and load balancers)
        if ($request->header('True-Client-IP')) {
            return $request->header('True-Client-IP');
        }

        // Priority 6: Fall back to Laravel's IP detection
        return $request->ip() ?? 'unknown';
    }

    /**
     * Map country code to country name
     */
    private function getCountryName(string $code): string
    {
        $countries = [
            'EG' => 'Egypt', 'US' => 'United States', 'GB' => 'United Kingdom',
            'SA' => 'Saudi Arabia', 'AE' => 'UAE', 'DE' => 'Germany',
            'FR' => 'France', 'IN' => 'India', 'CN' => 'China',
            'BR' => 'Brazil', 'RU' => 'Russia', 'JP' => 'Japan',
            'AU' => 'Australia', 'CA' => 'Canada', 'IT' => 'Italy',
            'ES' => 'Spain', 'NL' => 'Netherlands', 'PL' => 'Poland',
            'TR' => 'Turkey', 'MX' => 'Mexico', 'AR' => 'Argentina',
            'ID' => 'Indonesia', 'TH' => 'Thailand', 'VN' => 'Vietnam',
            'PH' => 'Philippines', 'MY' => 'Malaysia', 'SG' => 'Singapore',
            'PK' => 'Pakistan', 'BD' => 'Bangladesh', 'NG' => 'Nigeria',
            'KE' => 'Kenya', 'GH' => 'Ghana', 'ZA' => 'South Africa',
            'KR' => 'South Korea',
        ];
        return $countries[$code] ?? $code;
    }

    /**
     * Check if IP is a private/local address
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Detect device type from user agent
     */
    private function getDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        // Mobile detection
        if (preg_match('/(android|webos|iphone|ipad|ipod|blackberry|windows phone)/i', $userAgent)) {
            return 'mobile';
        }

        // Tablet detection
        if (preg_match('/(tablet|ipad|playbook)/i', $userAgent) || 
            (preg_match('/(android)/i', $userAgent) && !preg_match('/(mobile)/i', $userAgent))) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Get a human-readable device/platform name
     */
    public function getDeviceName(?string $userAgent = null): string
    {
        $ua = strtolower($userAgent ?? request()->userAgent() ?? '');
        
        if (str_contains($ua, 'android')) return 'Android Device';
        if (str_contains($ua, 'iphone')) return 'iPhone';
        if (str_contains($ua, 'ipad')) return 'iPad';
        if (str_contains($ua, 'windows')) return 'Windows PC';
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os x')) return 'Mac';
        if (str_contains($ua, 'linux')) return 'Linux PC';
        
        if (str_contains($ua, 'mobile')) return 'Mobile Device';
        if (str_contains($ua, 'tablet')) return 'Tablet Device';
        
        return 'Unknown Device';
    }

    /**
     * Detect browser from user agent
     */
    private function getBrowser(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        if (preg_match('/Edg\/(\d+)/i', $userAgent, $matches)) {
            return 'Edge ' . ($matches[1] ?? '');
        }

        if (preg_match('/Chrome\/(\d+)/i', $userAgent, $matches)) {
            return 'Chrome ' . ($matches[1] ?? '');
        }

        if (preg_match('/Firefox\/(\d+)/i', $userAgent, $matches)) {
            return 'Firefox ' . ($matches[1] ?? '');
        }

        if (preg_match('/Safari\/(\d+)/i', $userAgent, $matches) && !preg_match('/Chrome/i', $userAgent)) {
            return 'Safari ' . ($matches[1] ?? '');
        }

        if (preg_match('/MSIE (\d+)/i', $userAgent, $matches) || preg_match('/Trident\/.*rv:(\d+)/i', $userAgent, $matches)) {
            return 'IE ' . ($matches[1] ?? '');
        }

        if (preg_match('/Opera|OPR\//i', $userAgent)) {
            return 'Opera';
        }

        return 'Other';
    }

    /**
     * Detect operating system from user agent
     */
    private function getOS(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        if (preg_match('/Windows NT 10\.0/i', $userAgent)) {
            return 'Windows 10/11';
        }

        if (preg_match('/Windows NT 6\.3/i', $userAgent)) {
            return 'Windows 8.1';
        }

        if (preg_match('/Windows NT 6\.2/i', $userAgent)) {
            return 'Windows 8';
        }

        if (preg_match('/Windows NT 6\.1/i', $userAgent)) {
            return 'Windows 7';
        }

        if (preg_match('/Mac OS X (\d+[._]\d+)/i', $userAgent, $matches)) {
            return 'macOS ' . str_replace('_', '.', $matches[1] ?? '');
        }

        if (preg_match('/Android (\d+)/i', $userAgent, $matches)) {
            return 'Android ' . ($matches[1] ?? '');
        }

        if (preg_match('/iPhone OS (\d+)/i', $userAgent, $matches)) {
            return 'iOS ' . ($matches[1] ?? '');
        }

        if (preg_match('/iPad.*OS (\d+)/i', $userAgent, $matches)) {
            return 'iPadOS ' . ($matches[1] ?? '');
        }

        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        }

        if (preg_match('/Ubuntu/i', $userAgent)) {
            return 'Ubuntu';
        }

        return 'Unknown';
    }

    /**
     * Get user's recent activity
     */
    public function getUserActivity(int $userId, int $limit = 50, int $days = 30)
    {
        return ActivityLog::forUser($userId)
            ->recent($days)
            ->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's login history
     */
    public function getUserLoginHistory(int $userId, int $limit = 20)
    {
        return ActivityLog::forUser($userId)
            ->action('login')
            ->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's active sessions derived from activity_logs.
     * SESSION_DRIVER=redis means the sessions DB table is stale — we use activity_logs instead.
     * Strategy: most-recent login per (ip+ua) fingerprint, within 30 days, not followed
     * by a logout from the same fingerprint.
     */
    public function getActiveSessions(int $userId)
    {
        $currentSessionId = request()->session()->getId();
        $currentIp        = $this->getIpAddress(request());
        $currentUA        = request()->userAgent() ?? '';

        // All logins in last 30 days, newest first
        $loginLogs = ActivityLog::where('user_id', $userId)
            ->where('action', 'login')
            ->where('logged_at', '>=', now()->subDays(30))
            ->orderBy('logged_at', 'desc')
            ->get();

        // All logouts in last 30 days — keyed by ip+ua fingerprint, value = latest logout time
        $logoutTimes = ActivityLog::where('user_id', $userId)
            ->where('action', 'logout')
            ->where('logged_at', '>=', now()->subDays(30))
            ->orderBy('logged_at', 'asc')
            ->get()
            ->mapWithKeys(fn($log) => [
                md5(($log->ip_address ?? '') . '|' . ($log->user_agent ?? '')) => $log->logged_at
            ]);

        // Deduplicate by fingerprint — keep only the latest login per ip+ua
        $seen = [];
        $activeSessions = collect();

        foreach ($loginLogs as $log) {
            $fingerprint = md5(($log->ip_address ?? '') . '|' . ($log->user_agent ?? ''));

            if (isset($seen[$fingerprint])) continue;
            $seen[$fingerprint] = true;

            // Skip if there's a logout AFTER this login for same fingerprint
            $logoutAt = $logoutTimes->get($fingerprint);
            if ($logoutAt && $logoutAt->gt($log->logged_at)) continue;

            $mockRequest = new class($log->user_agent ?? '') extends \Illuminate\Http\Request {
                private string $ua;
                public function __construct(string $ua) { $this->ua = $ua; }
                public function userAgent(): string { return $this->ua; }
            };

            // Primary: session_id match (works for logins after this fix)
            // Fallback: ip+ua match (covers old logs with pre-regenerate session_id)
            $isCurrent = ($log->session_id === $currentSessionId)
                || ($log->ip_address === $currentIp && $log->user_agent === $currentUA);

            $activeSessions->push((object) [
                'id'                 => $log->session_id,
                'ip_address'         => $log->ip_address,
                'user_agent'         => $log->user_agent,
                'device_type'        => $this->getDeviceType($mockRequest),
                'browser'            => $this->getBrowser($mockRequest),
                'os'                 => $this->getOS($mockRequest),
                'country'            => $log->country,
                'city'               => $log->city,
                'logged_at'          => $log->logged_at,
                'is_current_session' => $isCurrent,
            ]);
        }

        return $activeSessions->values();
    }

    /**
     * Get detailed active sessions with metadata for display
     * Includes activity_log.id for session termination
     */
    public function getActiveSessionsWithDetails(int $userId)
    {
        $sessions = $this->getActiveSessions($userId);

        // Get activity logs for these sessions to get activity_log.id
        $sessionIds = $sessions->pluck('id')->toArray();
        $activityLogs = ActivityLog::where('user_id', $userId)
            ->action('login')
            ->whereIn('session_id', $sessionIds)
            ->orderBy('logged_at', 'desc')
            ->get()
            ->keyBy('session_id');

        return $sessions->map(function($session) use ($activityLogs) {
            $activityLog = $activityLogs->get($session->id);
            return [
                'id' => $session->id,
                'activity_log_id' => $activityLog?->id,
                'ip_address' => $session->ip_address,
                'device_type' => $session->device_type ?? 'desktop',
                'browser' => $session->browser ?? 'Unknown',
                'os' => $session->os ?? 'Unknown',
                'country' => $session->country ?? null,
                'city' => $session->city ?? null,
                'last_active' => $session->logged_at->diffForHumans(),
                'login_time' => $session->logged_at->format('M d, Y h:i a'),
                'is_current' => $session->is_current_session ?? false,
            ];
        })->filter(function($session) {
            // Filter out sessions without proper data
            return $session['ip_address'] !== null || $session['is_current'];
        })->values();
    }

    /**
     * Check if this session is the current one
     */
    private function isCurrentSession($session, ?string $currentSessionId = null)
    {
        if ($currentSessionId === null) {
            $request = request();
            $currentSessionId = $request->session()->getId();
        }

        // Primary check: Compare session IDs (most accurate)
        // Check both session_id (from activity_log) and id (from sessions table)
        if ($session->session_id && $session->session_id === $currentSessionId) {
            return true;
        }

        // Also check against the session's database ID
        if (isset($session->id) && $session->id === $currentSessionId) {
            return true;
        }

        // Fallback: Check if IP matches and session is recent (within 30 minutes)
        $currentIp = $this->getIpAddress(request());
        $isSameIp = $session->ip_address === $currentIp;
        $isRecent = $session->logged_at->diffInMinutes(now()) < 30;

        // Also check user agent matches
        $currentUA = request()->userAgent() ?? '';
        $isSameUA = $session->user_agent === $currentUA;

        return $isSameIp && $isRecent && $isSameUA;
    }

    /**
     * Log activity with explicit session context (for logging on behalf of another session,
     * e.g. recording a logout when terminating a remote session).
     */
    public function logActivityForSession(
        string $action,
        int    $userId,
        string $sessionId,
        string $ip,
        string $userAgent
    ): ActivityLog {
        $mockRequest = \Illuminate\Http\Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => $userAgent]);

        $locationData = $this->getLocationFromCloudflare(request()); // best-effort from current request

        return ActivityLog::create([
            'user_id'     => $userId,
            'session_id'  => $sessionId,
            'action'      => $action,
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
            'device_type' => $this->getDeviceType($mockRequest),
            'browser'     => $this->getBrowser($mockRequest),
            'os'          => $this->getOS($mockRequest),
            'country'     => $locationData['country'] ?? null,
            'city'        => $locationData['city'] ?? null,
            'region'      => $locationData['region'] ?? null,
            'isp'         => null,
            'timezone'    => null,
            'latitude'    => $locationData['latitude'] ?? null,
            'longitude'   => $locationData['longitude'] ?? null,
            'logged_at'   => now(),
        ]);
    }

    /**
     * Log login activity
     */
    public function logLogin(int $userId): ActivityLog
    {
        return $this->logActivity('login', $userId);
    }

    /**
     * Log logout activity
     */
    public function logLogout(int $userId): ActivityLog
    {
        return $this->logActivity('logout', $userId);
    }

    /**
     * Log password change
     */
    public function logPasswordChange(int $userId): ActivityLog
    {
        return $this->logActivity('password_change', $userId);
    }

    /**
     * Log profile update
     */
    public function logProfileUpdate(int $userId): ActivityLog
    {
        return $this->logActivity('profile_update', $userId);
    }

    /**
     * Log username change
     */
    public function logUsernameChange(int $userId): ActivityLog
    {
        return $this->logActivity('username_change', $userId);
    }

    /**
     * Log email verification
     */
    public function logEmailVerification(int $userId): ActivityLog
    {
        return $this->logActivity('email_verification', $userId);
    }

    /**
     * Log failed login attempt (for non-existent users or wrong credentials)
     */
    public function logFailedLogin(string $email): ActivityLog
    {
        // Create activity log without user_id for failed attempts
        $request = request();
        $ipAddress = $this->getIpAddress($request);
        $locationData = $this->getLocationFromCloudflare($request);

        // Capture session ID if available
        $sessionId = null;
        try {
            $sessionId = $request->session()->getId();
        } catch (\Exception $e) {
            // Session not available in some contexts
        }

        return ActivityLog::create([
            'user_id' => null,
            'session_id' => $sessionId,
            'action' => 'failed_login',
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent(),
            'device_type' => $this->getDeviceType($request),
            'browser' => $this->getBrowser($request),
            'os' => $this->getOS($request),
            'country' => $locationData['country'] ?? null,
            'city' => $locationData['city'] ?? null,
            'region' => $locationData['region'] ?? null,
            'isp' => null,
            'timezone' => null,
            'latitude' => $locationData['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? null,
            'logged_at' => now(),
        ]);
    }
}
