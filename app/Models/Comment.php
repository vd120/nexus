<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['user_id', 'post_id', 'parent_id', 'content', 'is_anonymous'];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function likes()
    {
        return $this->hasMany(CommentLike::class);
    }

    public function likedBy(User $user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function socialGroup()
    {
        // A comment belongs to a group via its parent post
        return $this->hasOneThrough(SocialGroup::class, Post::class, 'id', 'id', 'post_id', 'social_group_id');
    }

    public function member()
    {
        return $this->hasOne(SocialGroupMember::class, 'user_id', 'user_id');
    }

    /**
     * Universal Author Logic (Anti-Leak)
     */
    public function getAuthorAttribute()
    {
        if ($this->is_anonymous) {
            $fakeUser = new User([
                'name' => 'Anonymous Participant',
                'username' => 'Anonymous Participant',
            ]);
            $fakeUser->id = null;
            return $fakeUser;
        }

        return $this->user;
    }

    public function getAuthorRoleAttribute()
    {
        if ($this->is_anonymous) return null;
        
        $postId = $this->post_id;
        $userId = $this->user_id;
        
        if (!$postId || !$userId) return null;

        $member = \Illuminate\Support\Facades\DB::table('social_group_members')
            ->join('posts', 'posts.social_group_id', '=', 'social_group_members.social_group_id')
            ->where('posts.id', $postId)
            ->whereNotNull('posts.social_group_id') // CRITICAL: Only in group context
            ->where('social_group_members.user_id', $userId)
            ->select('social_group_members.role')
            ->first();
            
        return $member ? $member->role : null;
    }

    public function getRoleBadgeHtmlAttribute()
    {
        $role = $this->author_role;
        if (!$role) return '';

        if ($role === 'admin') {
            return '<span class="role-badge-pill admin-pill mini" title="' . __('messages.community_admin') . '"><i class="fas fa-crown"></i> <span>' . __('messages.role_admin') . '</span></span>';
        } elseif ($role === 'moderator') {
            return '<span class="role-badge-pill moderator-pill mini" title="' . __('messages.community_moderator') . '"><i class="fas fa-shield-alt"></i> <span>' . __('messages.role_moderator') . '</span></span>';
        }
        
        return '';
    }
}
