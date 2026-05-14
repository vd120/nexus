<?php

namespace App\Listeners;

use App\Services\ActivityService;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            try {
                // Mark user as offline in database
                $event->user->update(['is_online' => false]);
                
                $activityService = app(ActivityService::class);
                $activityService->logActivity('logout', $event->user->id);
            } catch (\Exception $e) {
                \Log::error('Failed to log logout activity: ' . $e->getMessage());
            }
        }
    }
}
