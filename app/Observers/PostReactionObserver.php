<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\PostReaction;

class PostReactionObserver
{
    public function created(PostReaction $reaction): void
    {
        Post::where('id', $reaction->post_id)->increment('cached_reactions_count');
    }

    public function deleted(PostReaction $reaction): void
    {
        Post::where('id', $reaction->post_id)->decrement('cached_reactions_count');
    }
}
