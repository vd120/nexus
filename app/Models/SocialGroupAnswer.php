<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialGroupAnswer extends Model
{
    protected $fillable = [
        'social_group_id',
        'user_id',
        'question_id',
        'answer',
    ];

    public function socialGroup()
    {
        return $this->belongsTo(SocialGroup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(SocialGroupQuestion::class);
    }
}
