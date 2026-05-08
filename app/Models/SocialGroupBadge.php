<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialGroupBadge extends Model
{
    protected $fillable = [
        'social_group_id',
        'name',
        'icon',
        'color',
    ];

    public function socialGroup()
    {
        return $this->belongsTo(SocialGroup::class);
    }
}
