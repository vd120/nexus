<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SocialGroupMember extends Model
{
    protected $fillable = [
        'social_group_id',
        'user_id',
        'role',
        'status',
        'notification_preference',
        'is_anonymous_default',
        'anonymous_username',
        'muted_until',
    ];

    protected $casts = [
        'is_anonymous_default' => 'boolean',
        'muted_until' => 'datetime',
    ];

    protected static function booted()
    {
        // Unique anonymous username generation removed in favor of global "Anonymous Participant"
    }

    public function group()
    {
        return $this->belongsTo(SocialGroup::class, 'social_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isMuted(): bool
    {
        return $this->muted_until && $this->muted_until->isFuture();
    }

    public function canPost(): bool
    {
        $group = $this->group;

        if ($group->isPaused()) {
            return false;
        }

        if ($this->isMuted()) {
            return false;
        }

        if ($group->posting_permission === 'admins_only' && !in_array($this->role, ['admin', 'moderator'])) {
            return false;
        }

        if ($group->new_member_restriction_days > 0) {
            $joinDate = $this->created_at ?? now();
            if ($joinDate->copy()->addDays($group->new_member_restriction_days)->isFuture() && !in_array($this->role, ['admin', 'moderator'])) {
                return false;
            }
        }

        return true;
    }

    public function badges()
    {
        return $this->belongsToMany(SocialGroupBadge::class, 'social_group_member_badges', 'user_id', 'badge_id', 'user_id', 'id')
            ->where('social_group_member_badges.social_group_id', $this->social_group_id);
    }
}
