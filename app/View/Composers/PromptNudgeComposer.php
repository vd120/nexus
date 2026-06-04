<?php

namespace App\View\Composers;

use App\Models\PulsePrompt;
use App\Models\PulseAnswer;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PromptNudgeComposer
{
    public function compose(View $view): void
    {
        if (!auth()->check()) {
            $view->with('showPulseNudge', false)->with('showMemoryNudge', false);
            return;
        }

        $userId = auth()->id();
        $today  = now()->format('Y-m-d');
        $week   = now()->format('Y-W');

        $onPulsePage  = request()->routeIs('pulse.*') || request()->is('pulse*');
        $onMemoryPage = request()->routeIs('memories.*') || request()->is('memories*');

        if ($onPulsePage)  session(['pulse_nudge_dismissed' => $today]);
        if ($onMemoryPage) session(['memory_nudge_dismissed' => $week]);

        $pulseDismissed  = session('pulse_nudge_dismissed') === $today;
        $memoryDismissed = session('memory_nudge_dismissed') === $week;

        $showPulse = false;
        if (!$pulseDismissed) {
            // Cache the prompt lookup for 10 min (prompt changes at most once/day)
            $daily = Cache::remember('pulse_daily_prompt', 600, fn() => PulsePrompt::currentDaily());
            if ($daily) {
                // Cache whether THIS user answered — bust when they submit an answer
                $showPulse = !Cache::remember("pulse_answered_{$userId}_{$daily->id}", 300,
                    fn() => PulseAnswer::where('prompt_id', $daily->id)->where('user_id', $userId)->exists()
                );
            }
        }

        $showMemory = false;
        if (!$memoryDismissed) {
            $memory = Cache::remember('pulse_memory_prompt', 600, fn() => PulsePrompt::currentMemory());
            if ($memory) {
                $showMemory = !Cache::remember("memory_answered_{$userId}_{$memory->id}", 300,
                    fn() => PulseAnswer::where('prompt_id', $memory->id)->where('user_id', $userId)->exists()
                );
            }
        }

        $view->with('showPulseNudge', $showPulse)
             ->with('showMemoryNudge', $showMemory);
    }
}
