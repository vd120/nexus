<?php

namespace App\Observers;

use App\Models\LifeChapter;
use App\Models\Post;

class PostObserver
{
    /**
     * Auto-tag the post to the user's currently active (ongoing) Life Chapter
     * if one wasn't explicitly provided. The newest ongoing chapter wins.
     */
    public function creating(Post $post): void
    {
        if ($post->life_chapter_id) {
            return;
        }
        if (!$post->user_id) {
            return;
        }

        $chapter = LifeChapter::where('user_id', $post->user_id)
            ->whereNull('ends_on')
            ->orderByDesc('id')
            ->first();

        if ($chapter) {
            $post->life_chapter_id = $chapter->id;
        }
    }
}
