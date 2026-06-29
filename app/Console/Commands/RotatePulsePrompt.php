<?php

namespace App\Console\Commands;

use App\Models\PulsePrompt;
use Illuminate\Console\Command;

/**
 * Rotate a Pulse prompt of the given type.
 *
 *   php artisan pulse:rotate                 # daily (default, 24h window)
 *   php artisan pulse:rotate --type=memory   # memory (7-day window)
 *
 * Picks the prompt with the oldest last_used_at (or null first) within the
 * requested type, marks it as active for the type's window, and deactivates
 * the previously active prompt of that same type. Each type cycles
 * independently — daily rotation never disturbs memory and vice versa.
 */
class RotatePulsePrompt extends Command
{
    protected $signature = 'pulse:rotate
        {--type=daily : Which prompt type to rotate (daily|memory)}
        {--force : Rotate even if the type already has an active prompt}';

    protected $description = 'Rotate the active Pulse prompt for the next window';

    public function handle(): int
    {
        $type = $this->option('type');
        if (!in_array($type, ['daily', 'memory'], true)) {
            $this->error("Invalid --type={$type}. Use 'daily' or 'memory'.");
            return self::FAILURE;
        }

        $now = now();

        $current = $type === 'memory'
            ? PulsePrompt::currentMemory()
            : PulsePrompt::currentDaily();

        if ($current && !$this->option('force')) {
            $this->info("Active {$type} prompt #" . $current->id . ' is still valid until ' . $current->ends_at);
            return self::SUCCESS;
        }

        // Deactivate anything currently flagged active *for this type only*
        PulsePrompt::where('type', $type)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ends_at'   => $now,
            ]);

        // Pick the oldest unused of this type (last_used_at NULL goes first naturally)
        $next = PulsePrompt::where('type', $type)
            ->orderByRaw('last_used_at IS NULL DESC')
            ->orderBy('last_used_at', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if (!$next) {
            $this->warn("No prompts of type '{$type}' in pulse_prompts table. Run db:seed first.");
            return self::FAILURE;
        }

        // Align prompt rotation duration to end exactly when the scheduler next runs
        $endsAt = $type === 'memory'
            ? $now->copy()->next('Sunday')->setTime(0, 5, 0)
            : $now->copy()->addDay()->setTime(0, 1, 0);

        $next->update([
            'is_active'    => true,
            'starts_at'    => $now,
            'ends_at'      => $endsAt,
            'last_used_at' => $now,
        ]);

        $this->info("Activated {$type} prompt #" . $next->id . ': ' . substr($next->question_en, 0, 60));
        return self::SUCCESS;
    }
}
