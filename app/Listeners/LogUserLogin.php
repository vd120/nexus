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

        // Mark user as online in the database
        $event->user->update([
            'is_online' => true,
            'last_active' => now()
        ]);

        // NOTE: login activity is logged by the controller AFTER session()->regenerate()
        // so the recorded session_id matches the live session. Do NOT log here.
    }
}
