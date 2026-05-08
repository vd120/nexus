<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentLike extends Model
{
    protected $fillable = ['user_id', 'comment_id', 'is_anonymous'];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function socialGroup()
    {
        // Requires two hops, implemented via a custom method or simplified by checking the post directly
        if ($this->comment && $this->comment->post) {
            return $this->comment->post->socialGroup();
        }
        return null;
    }

    /**
     * Universal Author Logic (Anti-Leak)
     */
    public function getAuthorAttribute()
    {
        if ($this->is_anonymous) {
            $fakeUser = new User([
                'name' => 'Anonymous Participant',
                'username' => 'Anonymous Participant',
            ]);
            $fakeUser->id = null;
            return $fakeUser;
        }

        return $this->user;
    }
}
