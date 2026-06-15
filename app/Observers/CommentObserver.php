<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Post;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        Post::where('id', $comment->post_id)->increment('cached_comments_count');
    }

    public function deleted(Comment $comment): void
    {
        Post::where('id', $comment->post_id)->decrement('cached_comments_count');
    }
}
