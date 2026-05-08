<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialGroupInvite extends Model
{
    protected $fillable = [
        'social_group_id',
        'inviter_id',
        'invitee_id',
        'status',
    ];

    public function socialGroup()
    {
        return $this->belongsTo(SocialGroup::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }
}
