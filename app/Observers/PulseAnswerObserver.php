<?php

namespace App\Observers;

use App\Models\LifeChapter;
use App\Models\PulseAnswer;

class PulseAnswerObserver
{
    /**
     * Auto-tag the answer to the user's currently active (ongoing) Life Chapter
     * if one wasn't explicitly provided. The newest ongoing chapter wins.
     */
    public function creating(PulseAnswer $answer): void
    {
        if ($answer->life_chapter_id) {
            return;
        }
        if (!$answer->user_id) {
            return;
        }

        $chapter = LifeChapter::where('user_id', $answer->user_id)
            ->whereNull('ends_on')
            ->orderByDesc('id')
            ->first();

        if ($chapter) {
            $answer->life_chapter_id = $chapter->id;
        }
    }
}
