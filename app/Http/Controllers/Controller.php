<?php

namespace App\Http\Controllers;

abstract class Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * Helper to get a readable device name from User Agent
     */
    protected function getDeviceName($userAgent)
    {
        if (strpos($userAgent, 'Mobile') !== false) return 'Mobile Device';
        if (strpos($userAgent, 'iPhone') !== false) return 'iPhone';
        if (strpos($userAgent, 'Android') !== false) return 'Android Phone';
        if (strpos($userAgent, 'Windows') !== false) return 'Windows PC';
        if (strpos($userAgent, 'Macintosh') !== false) return 'Mac';
        if (strpos($userAgent, 'Linux') !== false) return 'Linux PC';
        return 'Unknown Device';
    }
}
