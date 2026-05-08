<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialGroupTopic extends Model
{
    protected $fillable = [
        'social_group_id',
        'name',
        'slug',
    ];

    public function socialGroup()
    {
        return $this->belongsTo(SocialGroup::class);
    }
}
