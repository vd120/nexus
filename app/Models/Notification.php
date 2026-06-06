<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'data',
        'read_at',
        'related_id',
        'related_type'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Note: Real-time notifications are now handled via polling
        // No WebSocket events needed
    }

    /**
     * Relationship with User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic relationship)
     */
    public function related()
    {
        return $this->morphTo();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Get notification message based on type
     */
    public function getMessageAttribute(): string
    {
        return match($this->type) {
            'message' => $this->getMessageNotificationMessage(),
            'like' => $this->getLikeNotificationMessage(),
            'comment' => $this->getCommentNotificationMessage(),
            'comment_reply' => $this->getCommentReplyNotificationMessage(),
            'comment_like' => $this->getCommentLikeNotificationMessage(),
            'follow' => $this->getFollowNotificationMessage(),
            'mention' => $this->getMentionNotificationMessage(),
            'group_invite' => $this->getGroupInviteNotificationMessage(),
            'story_reaction' => $this->getStoryReactionNotificationMessage(),
            'post_reaction' => $this->getPostReactionNotificationMessage(),
            'chat_reaction' => $this->getChatReactionNotificationMessage(),
            'report_accepted' => $this->getReportAcceptedNotificationMessage(),
            'report_rejected' => __('notifications.report_rejected_message'),
            'group_join_requested'      => $this->getGroupJoinRequestedMessage(),
            'group_join_accepted'       => $this->getGroupJoinAcceptedMessage(),
            'group_post_approved'       => $this->getGroupPostApprovedMessage(),
            'group_post_rejected'       => $this->getGroupPostRejectedMessage(),
            'group_post_pending'        => $this->getGroupPostPendingMessage(),
            'group_member_role_changed' => $this->getGroupMemberRoleChangedMessage(),
            'community_report_new'      => $this->getCommunityReportNewMessage(),
            'report_action_owner' => $this->data['message'] ?? __('messages.new_notification'),
            default => __('messages.new_notification')
        };
    }

    /**
     * Get message notification text
     */
    private function getMessageNotificationMessage(): string
    {
        $sender = $this->data['sender_username'] ?? __('chat.user');
        $preview = $this->data['message_preview'] ?? '';
        $messageType = $this->data['message_type'] ?? 'text';

        // Check if this is a story reply
        if (str_starts_with($preview, '📸 Reply to your story:')) {
            $preview = trim(str_replace('📸 Reply to your story:', '', $preview));
            return __('notifications.story_reply_message', ['sender' => $sender, 'preview' => $preview]);
        }

        // Check if this is a group message
        $isGroup = $this->data['is_group'] ?? false;
        $groupName = $this->data['group_name'] ?? null;

        if ($isGroup && $groupName) {
            return "{$groupName} - {$sender}: {$preview}";
        }

        // For text messages, show the preview
        return "{$sender}: {$preview}";
    }

    /**
     * Get like notification text
     */
    private function getLikeNotificationMessage(): string
    {
        $liker = $this->data['liker_username'] ?? $this->data['liker_name'] ?? __('chat.user');
        return __('notifications.liked_your_post', ['user' => $liker]);
    }

    /**
     * Get comment notification text
     */
    private function getCommentNotificationMessage(): string
    {
        $commenter = $this->data['commenter_username'] ?? $this->data['commenter_name'] ?? __('chat.user');
        return __('notifications.commented_on_your_post', ['user' => $commenter]);
    }

    /**
     * Get comment reply notification text
     */
    private function getCommentReplyNotificationMessage(): string
    {
        $commenter = $this->data['commenter_username'] ?? $this->data['commenter_name'] ?? __('chat.user');
        return __('notifications.replied_to_your_comment', ['user' => $commenter]);
    }
    
    /**
     * Get comment like notification text
     */
    private function getCommentLikeNotificationMessage(): string
    {
        $liker = $this->data['liker_username'] ?? $this->data['liker_name'] ?? __('chat.user');
        $isReply = $this->data['is_reply'] ?? false;
        
        return $isReply 
            ? __('notifications.liked_your_reply', ['user' => $liker])
            : __('notifications.liked_your_comment', ['user' => $liker]);
    }

    /**
     * Get follow notification text
     */
    private function getFollowNotificationMessage(): string
    {
        $follower = $this->data['follower_username'] ?? $this->data['follower_name'] ?? __('chat.user');
        return __('notifications.new_follower', ['user' => $follower]);
    }

    /**
     * Get mention notification text
     */
    private function getMentionNotificationMessage(): string
    {
        $mentioner = $this->data['mentioner_username'] ?? $this->data['mentioner_name'] ?? __('chat.user');
        $mentionableType = $this->data['mentionable_type'] ?? 'post';

        if ($mentionableType === 'App\\Models\\Post') {
            return __('notifications.mentioned_you_in_post', ['user' => $mentioner]);
        } elseif ($mentionableType === 'App\\Models\\Comment') {
            return __('notifications.mentioned_you_in_comment', ['user' => $mentioner]);
        }

        return __('notifications.mentioned_you', ['user' => $mentioner]);
    }

    /**
     * Get group invite notification text
     */
    private function getGroupInviteNotificationMessage(): string
    {
        if (empty($this->data) || !is_array($this->data)) {
            return __('notifications.invited_to_group_generic');
        }

        $inviter = $this->data['inviter_username'] ?? null;
        $groupName = $this->data['group_name'] ?? null;

        // If data is not loaded yet, return generic message
        if (!$inviter && !$groupName) {
            return __('notifications.invited_to_group_generic');
        }

        $inviterText = $inviter ?? __('chat.someone');
        $groupNameText = $groupName ?? __('chat.group');

        return __('notifications.invited_to_group', ['user' => $inviterText, 'group' => $groupNameText]);
    }

    /**
     * Get story reaction notification text
     */
    private function getStoryReactionNotificationMessage(): string
    {
        $reactor = $this->data['reactor_name'] ?? $this->data['reactor_username'] ?? __('chat.user');
        return __('notifications.story_reaction', ['user' => $reactor]);
    }

    /**
     * Get report accepted notification text
     */
    private function getReportAcceptedNotificationMessage(): string
    {
        $title = $this->data['title'] ?? __('messages.report_accepted');
        $message = $this->data['message'] ?? __('messages.your_report_was_accepted_and');
        return "{$title}: {$message}";
    }

    /**
     * Get report rejected notification text
     */
    private function getReportRejectedNotificationMessage(): string
    {
        $title = $this->data['title'] ?? __('messages.report_rejected');
        $message = $this->data['message'] ?? __('messages.your_report_was_reviewed_but_not_accepted');
        return "{$title}: {$message}";
    }



    /**
     * Get post reaction notification text
     */
    private function getPostReactionNotificationMessage(): string
    {
        $reactor = $this->data['reactor_username'] ?? __('chat.user');
        $reaction = $this->data['reaction_type'] ?? '❤️';
        return __('notifications.reacted_to_your_post', ['user' => $reactor, 'reaction' => $reaction]);
    }

    /**
     * Get chat reaction notification text
     */
    private function getChatReactionNotificationMessage(): string
    {
        $reactor = $this->data['reactor_username'] ?? __('chat.user');
        $reaction = $this->data['reaction_type'] ?? '❤️';
        $content = $this->data['message_content'] ?? 'message';
        $isGroup = $this->data['is_group'] ?? false;
        $groupName = $this->data['group_name'] ?? null;
        
        $baseMessage = __('notifications.reacted_to_your_message', ['user' => $reactor, 'reaction' => $reaction, 'content' => $content]);
        
        if ($isGroup && $groupName) {
            return "{$groupName} - {$baseMessage}";
        }
        
        return $baseMessage;
    }

    private function getGroupJoinRequestedMessage(): string
    {
        $user  = $this->data['requester_name'] ?? __('chat.someone');
        $group = $this->data['group_name'] ?? __('messages.a_community');
        return __('notifications.group_join_requested', ['user' => $user, 'group' => $group]);
    }

    private function getGroupJoinAcceptedMessage(): string
    {
        $group = $this->data['group_name'] ?? __('messages.a_community');
        return __('notifications.group_join_accepted', ['group' => $group]);
    }

    private function getGroupPostApprovedMessage(): string
    {
        $group = $this->data['group_name'] ?? __('messages.a_community');
        return __('notifications.group_post_approved', ['group' => $group]);
    }

    private function getGroupPostRejectedMessage(): string
    {
        $group = $this->data['group_name'] ?? __('messages.a_community');
        return __('notifications.group_post_rejected', ['group' => $group]);
    }

    private function getGroupPostPendingMessage(): string
    {
        $user  = $this->data['author_name'] ?? __('chat.someone');
        $group = $this->data['group_name'] ?? __('messages.a_community');
        return __('notifications.group_post_pending', ['user' => $user, 'group' => $group]);
    }

    private function getGroupMemberRoleChangedMessage(): string
    {
        $group   = $this->data['group_name'] ?? __('messages.a_community');
        $newRole = $this->data['new_role'] ?? 'member';
        $key = match ($newRole) {
            'admin'     => 'notifications.group_member_role_changed_admin',
            'moderator' => 'notifications.group_member_role_changed_moderator',
            default     => 'notifications.group_member_role_changed_member',
        };
        return __($key, ['group' => $group]);
    }

    private function getCommunityReportNewMessage(): string
    {
        $user  = $this->data['reporter_name'] ?? __('chat.someone');
        $group = $this->data['group_name'] ?? __('messages.a_community');
        return __('notifications.community_report_new', ['user' => $user, 'group' => $group]);
    }



    public function getLinkAttribute(): ?string
    {
        $link = match($this->type) {
            // Chat — base link to the conversation; message anchor added below
            'message', 'chat_reaction' => ($this->data['conversation_id'] ?? null) ? url('/chat/' . $this->data['conversation_id']) : null,
            'like', 'post_reaction', 'comment', 'comment_reply', 'comment_like', 'mention' => 
                ($this->data['post_slug'] ?? null) 
                    ? url('/posts/' . $this->data['post_slug']) 
                    : (($this->data['post_id'] ?? null) ? url('/posts/' . $this->data['post_id']) : null),
            'follow' => ($this->data['follower_name'] ?? null) ? url('/users/' . $this->data['follower_name']) : null,
            'group_invite' => $this->data['invite_link'] ?? null,
            'story_reaction' => ($this->data['story_slug'] ?? null) ? url('/stories/' . ($this->data['story_owner_username'] ?? 'user') . '/' . $this->data['story_slug'] . '/viewers') : null,
            'report_accepted', 'report_rejected', 'report_action_owner' => url('/my-reports'),
            // Admin receives → go straight to the moderation queue
            'group_post_pending' => ($this->data['group_slug'] ?? null)
                ? url('/communities/' . $this->data['group_slug'] . '/admin/moderation/posts')
                : null,
            'group_join_requested' => ($this->data['group_slug'] ?? null)
                ? url('/communities/' . $this->data['group_slug'] . '/admin/moderation/members')
                : null,
            'community_report_new' => ($this->data['group_slug'] ?? null)
                ? url('/communities/' . $this->data['group_slug'] . '/admin/reports')
                : null,

            // Member receives → go to the specific post or community page
            'group_post_approved' => ($this->data['post_slug'] ?? null)
                ? url('/posts/' . $this->data['post_slug'])
                : (($this->data['group_slug'] ?? null) ? url('/communities/' . $this->data['group_slug']) : null),
            'group_join_accepted'      => ($this->data['group_slug'] ?? null) ? url('/communities/' . $this->data['group_slug']) : null,
            'group_post_rejected'      => ($this->data['group_slug'] ?? null) ? url('/communities/' . $this->data['group_slug']) : null,
            'group_member_role_changed' => ($this->data['group_slug'] ?? null) ? url('/communities/' . $this->data['group_slug']) : null,
            default => null
        };

        if (!$link) return null;

        // Add anchors for comment-related notifications
        if (in_array($this->type, ['comment', 'comment_reply'])) {
            $commentId = $this->related_id ?? $this->data['comment_id'] ?? null;
            if ($commentId) {
                $link .= '#comment-' . $commentId;
            }
        } elseif ($this->type === 'comment_like') {
            $commentId = $this->data['comment_id'] ?? null;
            if ($commentId) {
                $link .= '#comment-' . $commentId;
            }
        } elseif ($this->type === 'mention' && ($this->data['comment_id'] ?? null)) {
            $link .= '#comment-' . $this->data['comment_id'];
        }

        // Add anchors for chat message / reaction notifications
        // chat_reaction stores message_id in data; message type stores it in related_id
        if ($this->type === 'chat_reaction') {
            $messageId = $this->data['message_id'] ?? $this->related_id ?? null;
            if ($messageId) {
                $link .= '#message-' . $messageId;
            }
        } elseif ($this->type === 'message') {
            $messageId = $this->related_id ?? $this->data['message_id'] ?? null;
            if ($messageId) {
                $link .= '#message-' . $messageId;
            }
        }

        return $link;
    }
}
