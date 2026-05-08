<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'creator_id',
        'avatar',
        'is_private',
        'slug',
        'invite_link',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name) . '-' . Str::random(5);
            }
            if (empty($group->invite_link)) {
                $group->invite_link = Str::random(32);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class, 'group_id')->where('is_group', true);
    }

    public function isMember(User $user)
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isAdmin(User $user)
    {
        return $this->members()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }

    public function hasMember(User $user)
    {
        return $this->isMember($user);
    }
}
