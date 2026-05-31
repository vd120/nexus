<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule story cleanup every hour (auto-delete stories after 24 hours)
Schedule::command('stories:cleanup')->hourly();

// Schedule inactive user reminders - runs daily at 10 AM
Schedule::command('users:remind-inactive --days=3')->dailyAt('10:00');
Schedule::command('users:remind-inactive --days=7')->weeklyOn(1, '10:00');
Schedule::command('users:remind-inactive --days=30 --subject="We miss you! Come back"')->monthlyOn(1, '10:00');

// Schedule unverified user pruning - runs every minute
Schedule::command('nexus:prune-unverified')->everyMinute();

// Rotate the daily Pulse prompt at midnight local time.
Schedule::command('pulse:rotate')->dailyAt('00:01');

// Rotate the weekly Memory Prompt every Sunday at 00:05 local time.
Schedule::command('pulse:rotate --type=memory')->weeklyOn(0, '00:05');


