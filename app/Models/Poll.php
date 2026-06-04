<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $fillable = ['post_id', 'question', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function options()
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    public function totalVotes(): int
    {
        return $this->options->sum('votes_count');
    }

    public function userVote(?int $userId): ?PollVote
    {
        if (!$userId) return null;
        return $this->votes()->where('user_id', $userId)->first();
    }
}
