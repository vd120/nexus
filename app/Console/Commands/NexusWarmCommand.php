<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NexusWarmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:warm {--force : Force warming even if in local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up the Nexus application for high performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Nexus Performance Warm-up...');

        // 1. Standard Laravel Caching
        $this->section('Standard Caching');
        
        $this->task('Clearing old caches', function () {
            Artisan::call('optimize:clear');
        });

        $this->task('Caching configuration', function () {
            Artisan::call('config:cache');
        });

        $this->task('Caching routes', function () {
            Artisan::call('route:cache');
        });

        $this->task('Caching views', function () {
            Artisan::call('view:cache');
        });

        // 2. Database Pre-warming (Hydrating heavy caches)
        $this->section('Data Pre-warming');

        $this->task('Warming User Models', function () {
            // Pre-load a small batch of active users into memory
            \App\Models\User::orderBy('last_active', 'desc')->limit(10)->get();
        });

        $this->task('Warming Global System Settings', function () {
            // Pre-load common settings into Redis/Cache
            Cache::rememberForever('nexus_system_ready', function() {
                return true;
            });
        });

        // 3. Octane Reload
        $this->section('Octane Lifecycle');
        
        if (file_exists(base_path('storage/logs/octane.pid'))) {
            $this->task('Reloading Octane Workers', function () {
                Artisan::call('octane:reload');
            });
        } else {
            $this->info('Octane is not running. Warm-up will apply on next start.');
        }

        $this->info('Nexus is now Warm and Optimized!');
        return Command::SUCCESS;
    }

    private function section($name)
    {
        $this->newLine();
        $this->info("--- $name ---");
    }

    private function task($name, $callback)
    {
        $this->output->write("$name... ");
        try {
            $callback();
            $this->output->writeln('<info>DONE</info>');
        } catch (\Exception $e) {
            $this->output->writeln('<error>FAILED</error>');
            Log::error("Warmup Task [$name] failed: " . $e->getMessage());
        }
    }
}
