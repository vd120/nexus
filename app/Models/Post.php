<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\SocialGroupMember;

class Post extends Model
{
    use SoftDeletes;

    public const REACTION_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡'];

    public const REACTION_IMAGES = [
        '👍' => '/images/reactions/like.svg',
        '❤️' => '/images/reactions/love.svg',
        '😂' => '/images/reactions/haha.svg',
        '😮' => '/images/reactions/wow.svg',
        '😢' => '/images/reactions/sad.svg',
        '😡' => '/images/reactions/angry.svg',
    ];



    public static function getReactionLabels(): array
    {
        return [
            '👍' => __('messages.reaction_like'),
            '❤️' => __('messages.reaction_love'),
            '😂' => __('messages.reaction_haha'),
            '😮' => __('messages.reaction_wow'),
            '😢' => __('messages.reaction_sad'),
            '😡' => __('messages.reaction_angry'),
        ];
    }

    protected $fillable = [
        'user_id',
        'content',
        'slug',
        'media_type',
        'media_path',
        'media_thumbnail',
        'is_private',
        'pinned_at',
        'social_group_id',
        'social_group_topic_id',
        'is_anonymous',
        'is_approved',
        'is_comments_disabled',
    ];
    
    protected $appends = ['author'];

    protected $casts = [
        'pinned_at' => 'datetime',
        'is_private' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_approved' => 'boolean',
        'is_comments_disabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function generateUniqueSlug()
    {
        do {
            $slug = Str::random(24);
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    public function getContentHtmlAttribute(): string
    {
        if (!$this->content) {
            return '';
        }
        return app(\App\Services\HashtagService::class)->convertToLinks($this->content);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function reactions()
    {
        return $this->hasMany(PostReaction::class);
    }

    public function userReaction()
    {
        return $this->hasOne(PostReaction::class)->where('user_id', auth()->id());
    }

    public function userLike()
    {
        return $this->hasOne(Like::class)->where('user_id', auth()->id());
    }

    public function userSavedPost()
    {
        return $this->hasOne(SavedPost::class)->where('user_id', auth()->id());
    }

    public function likedBy(User $user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function reactedBy(User $user)
    {
        return $this->reactions()->where('user_id', $user->id)->first();
    }

    public function getGroupedReactions()
    {
        // Use the loaded reactions collection
        $reactions = $this->reactions;

        if ($reactions->isEmpty()) {
            return collect();
        }

        return $reactions->groupBy('reaction_type')->map(function ($group) {
            return [
                'reaction_type' => $group->first()->reaction_type,
                'count' => $group->count(),
            ];
        });
    }

    public function savedBy(User $user)
    {
        return $this->savedPosts()->where('user_id', $user->id)->exists();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class)->ordered();
    }

    public function savedPosts()
    {
        return $this->hasMany(SavedPost::class);
    }

    public function reports()
    {
        return $this->hasMany(PostReport::class);
    }

    public function pendingReports()
    {
        return $this->hasMany(PostReport::class)->where('status', PostReport::STATUS_PENDING);
    }

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class)->withTimestamps();
    }



    /**
     * Check if the post is pinned
     */
    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    /**
     * Pin the post
     */
    public function pin(): void
    {
        $this->update(['pinned_at' => now()]);
    }

    /**
     * Unpin the post
     */
    public function unpin(): void
    {
        $this->update(['pinned_at' => null]);
    }

    /**
     * Scope for pinned posts
     */
    public function scopePinned($query)
    {
        return $query->whereNotNull('pinned_at');
    }

    /**
     * Scope for non-pinned posts
     */
    public function scopeNotPinned($query)
    {
        return $query->whereNull('pinned_at');
    }

    /**
     * Scope for approved posts.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function socialGroup()
    {
        return $this->belongsTo(SocialGroup::class);
    }

    public function socialGroupTopic()
    {
        return $this->belongsTo(SocialGroupTopic::class);
    }

    public function member()
    {
        return $this->hasOne(SocialGroupMember::class, 'user_id', 'user_id');
    }

    public function getAuthorRoleAttribute()
    {
        if ($this->is_anonymous || !$this->social_group_id) return null;
        
        $member = SocialGroupMember::where('social_group_id', $this->social_group_id)
            ->where('user_id', $this->user_id)
            ->first();
            
        return $member ? $member->role : null;
    }

    public function getRoleBadgeHtmlAttribute()
    {
        $role = $this->author_role;
        if (!$role) return '';

        if ($role === 'admin') {
            return '<span class="role-badge-pill admin-pill" title="' . __('messages.community_admin') . '"><i class="fas fa-crown"></i> <span>' . __('messages.role_admin') . '</span></span>';
        } elseif ($role === 'moderator') {
            return '<span class="role-badge-pill moderator-pill" title="' . __('messages.community_moderator') . '"><i class="fas fa-shield-alt"></i> <span>' . __('messages.role_moderator') . '</span></span>';
        }
        
        return '';
    }

    public function getAuthorAttribute()
    {
        if ($this->is_anonymous) {
            return new User([
                'name' => 'Anonymous Participant',
                'username' => 'Anonymous Participant',
            ]);
        }

        return $this->user;
    }

    /**
     * Check if a user has permission to delete this post.
     */
    public function canDelete(?User $user): bool
    {
        if (!$user) return false;
        
        // 1. Author can delete
        if ($this->user_id === $user->id) return true;
        
        // 2. Platform Admin can delete
        if ($user->is_admin) return true;
        
        // 3. Community Moderator/Admin can delete
        if ($this->social_group_id) {
            $member = SocialGroupMember::where('social_group_id', $this->social_group_id)
                ->where('user_id', $user->id)
                ->first();
            return $member && in_array($member->role, ['admin', 'moderator']) && $member->status === 'approved';
        }
        
        return false;
    }
}