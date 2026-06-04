<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    protected $fillable = ['poll_id', 'text', 'votes_count'];

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class, 'option_id');
    }

    public function percentage(int $totalVotes): float
    {
        if ($totalVotes === 0) return 0;
        return round(($this->votes_count / $totalVotes) * 100, 1);
    }
}
