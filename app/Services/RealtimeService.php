<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * RealtimeService - Handles Socket.IO configuration and logging.
 * Legacy polling logic has been deprecated in favor of window.NexusSocket.
 */
class RealtimeService
{
    /**
     * Check if real-time infrastructure is active
     */
    public function isRealtimeAvailable(): bool
    {
        return !empty(config('app.socket_io_url'));
    }

    /**
     * Get real-time configuration for frontend
     * Returns Socket.IO configuration for NexusSocket manager
     */
    public function getRealtimeConfig(): array
    {
        return [
            'enabled' => true,
            'driver' => 'socketio',
            'url' => config('app.socket_io_url', 'http://localhost:3001'),
        ];
    }

    /**
     * Log real-time activity for debugging
     */
    public function logRealtimeActivity(string $action, array $data = []): void
    {
        Log::info('Realtime Activity (Socket.IO): ' . $action, $data);
    }
}
