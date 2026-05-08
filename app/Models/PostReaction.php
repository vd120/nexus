<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostReaction extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'reaction_type',
        'is_anonymous',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function socialGroup()
    {
        return $this->hasOneThrough(SocialGroup::class, Post::class, 'id', 'id', 'post_id', 'social_group_id');
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
