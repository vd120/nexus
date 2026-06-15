<?php

namespace App\Observers;

use App\Models\Like;
use App\Models\Post;

class LikeObserver
{
    public function created(Like $like): void
    {
        Post::where('id', $like->post_id)->increment('cached_likes_count');
    }

    public function deleted(Like $like): void
    {
        Post::where('id', $like->post_id)->decrement('cached_likes_count');
    }
}
