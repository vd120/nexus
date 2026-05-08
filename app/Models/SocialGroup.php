<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_photo',
        'avatar',
        'creator_id',
        'privacy_level',
        'is_discoverable',
        'posting_permission',
        'new_member_restriction_days',
        'require_post_approval',
        'allow_anonymous_posts',
        'is_paused',
        'welcome_message',
    ];

    protected $casts = [
        'is_discoverable' => 'boolean',
        'require_post_approval' => 'boolean',
        'allow_anonymous_posts' => 'boolean',
        'is_paused' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->hasMany(SocialGroupMember::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function rules()
    {
        return $this->hasMany(SocialGroupRule::class);
    }

    public function questions()
    {
        return $this->hasMany(SocialGroupQuestion::class);
    }

    public function topics()
    {
        return $this->hasMany(SocialGroupTopic::class);
    }

    public function badges()
    {
        return $this->hasMany(SocialGroupBadge::class);
    }

    public function invites()
    {
        return $this->hasMany(SocialGroupInvite::class);
    }

    public function isPaused(): bool
    {
        return $this->is_paused;
    }

    public function isPublic(): bool
    {
        return $this->privacy_level === 'public';
    }

    public function isMember(?User $user): bool
    {
        if (!$user) return false;
        return $this->members()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();
    }

    public function isAdmin(?User $user): bool
    {
        if (!$user) return false;
        return $this->members()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->where('status', 'approved')
            ->exists();
    }

    public function canPost(?User $user): bool
    {
        if (!$user) return false;
        if ($this->is_paused) return false;

        $member = $this->members()->where('user_id', $user->id)->where('status', 'approved')->first();
        if (!$member) return false;

        if ($this->posting_permission === 'anyone') {
            return true;
        }

        return in_array($member->role, ['admin', 'moderator']);
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=5e60ce&color=fff';
    }
}
