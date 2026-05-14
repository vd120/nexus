<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (!$event->user) {
            return;
        }

        // Update the session with user_id
        $sessionId = request()->session()->getId();
        
        DB::table('sessions')
            ->where('id', $sessionId)
            ->update(['user_id' => $event->user->id]);

        // Mark user as online in the database
        $event->user->update([
            'is_online' => true,
            'last_active' => now()
        ]);

        // Log the activity
        try {
            app(\App\Services\ActivityService::class)->logActivity('login', $event->user->id);
        } catch (\Exception $e) {
            \Log::error('Failed to log login activity: ' . $e->getMessage());
        }
    }
}
