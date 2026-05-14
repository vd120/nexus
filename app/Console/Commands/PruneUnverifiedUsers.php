<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:prune-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete unverified user accounts that were abandoned during registration.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Delete users who haven't verified their email within 5 minutes
        $count = \App\Models\User::whereNull('email_verified_at')
            ->where('created_at', '<', now()->subMinutes(5))
            ->delete();

        if ($count > 0) {
            $this->info("Successfully pruned {$count} unverified users.");
        }
    }
}
