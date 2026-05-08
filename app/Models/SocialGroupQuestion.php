<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialGroupQuestion extends Model
{
    protected $fillable = [
        'social_group_id',
        'question',
        'order',
    ];

    public function socialGroup()
    {
        return $this->belongsTo(SocialGroup::class);
    }
}
