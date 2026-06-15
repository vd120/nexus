<?php

namespace App\Console\Commands;

use App\Mail\TwoFactorReminderMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class Send2FAReminders extends Command
{
    protected $signature = 'users:remind-2fa';

    protected $description = 'Send reminder emails to users who have not enabled two-factor authentication';

    public function handle(): int
    {
        $this->info('Finding users without 2FA enabled...');

        $users = User::whereNotNull('email_verified_at')
            ->whereNull('two_factor_secret')
            ->where(function ($query) {
                $query->whereNull('two_factor_reminder_sent_at')
                      ->orWhere('two_factor_reminder_sent_at', '<', now()->subDays(3));
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users to remind.');
            return 0;
        }

        $this->info("Found {$users->count()} users to remind.");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $sentCount   = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                $originalLocale = app()->getLocale();
                app()->setLocale($user->language ?? 'en');

                Mail::to($user->email, $user->name)->send(new TwoFactorReminderMail($user));

                app()->setLocale($originalLocale);

                $user->update(['two_factor_reminder_sent_at' => now()]);

                $sentCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to send to {$user->email}: " . $e->getMessage());
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✓ Sent: {$sentCount} emails");

        if ($failedCount > 0) {
            $this->warn("✗ Failed: {$failedCount} emails");
        }

        return 0;
    }
}
