<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialGroupRule extends Model
{
    protected $fillable = [
        'social_group_id',
        'title',
        'description',
        'order',
    ];

    public function socialGroup()
    {
        return $this->belongsTo(SocialGroup::class);
    }
}
