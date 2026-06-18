<?php

namespace App\Traits;

use App\Models\Notification;
use App\Services\PushNotificationService;

trait SendsPushNotifications
{
    /**
     * Send a push notification when a notification is created.
     */
    public function sendPushNotification(Notification $notification): void
    {
        $pushService = app(PushNotificationService::class);

        if (!$pushService->isConfigured()) {
            return;
        }

        $user = $notification->user;

        if (!$user || !$user->pushSubscriptions()->exists()) {
            return;
        }

        $originalLocale = app()->getLocale();
        if ($user->language) {
            app()->setLocale($user->language);
        }

        [$title, $body, $url] = $this->getNotificationData($notification);

        app()->setLocale($originalLocale);

        $pushService->sendToUser(
            $user,
            $title,
            $body,
            $url,
            [
                'type'            => $notification->type,
                'notification_id' => $notification->id,
                'message_id'      => $notification->data['message_id'] ?? null,
            ]
        );
    }

    /**
     * Get notification data for push notification.
     */
    protected function getNotificationData(Notification $notification): array
    {
        $data = $notification->data ?? [];

        $url = $notification->link ?? route('notifications.index');

        return match($notification->type) {
            'like' => [
                __('notifications.liked_your_post', ['user' => $data['liker_name'] ?? 'Someone']),
                $data['post_content'] ?? '',
                $url,
            ],
            'comment' => [
                __('notifications.commented_on_your_post', ['user' => $data['commenter_name'] ?? 'Someone']),
                $data['comment_content'] ?? $data['comment_preview'] ?? '',
                $url,
            ],
            'comment_reply' => [
                __('notifications.replied_to_your_comment', ['user' => $data['commenter_name'] ?? 'Someone']),
                $data['comment_content'] ?? $data['comment_preview'] ?? '',
                $url,
            ],
            'comment_like' => [
                ($data['is_reply'] ?? false)
                    ? __('notifications.liked_your_reply',   ['user' => $data['liker_name'] ?? 'Someone'])
                    : __('notifications.liked_your_comment', ['user' => $data['liker_name'] ?? 'Someone']),
                $data['comment_content'] ?? '',
                $url,
            ],
            'follow' => [
                __('notifications.new_follower', ['user' => $data['follower_name'] ?? 'Someone']),
                '',
                $url,
            ],
            'mention' => [
                __('notifications.mentioned_you', ['user' => $data['mentioner_name'] ?? 'Someone']),
                $data['mention_preview'] ?? $data['comment_content'] ?? '',
                $url,
            ],
            'message' => [
                __('notifications.sent_you_message', ['user' => $data['sender_username'] ?? 'Someone']),
                $data['message_preview'] ?? '',
                $url,
            ],
            'call', 'incoming_call' => [
                $data['caller_name'] ?? 'Someone',
                __('notifications.incoming_call', ['user' => $data['caller_name'] ?? 'Someone']),
                $url,
            ],
            'post_reaction' => [
                __('notifications.reacted_to_your_post', ['user' => $data['reactor_username'] ?? 'Someone', 'reaction' => $data['reaction_type'] ?? '❤️']),
                $data['post_content'] ?? '',
                $url,
            ],
            'story_reaction' => [
                __('notifications.story_reaction', ['user' => $data['reactor_name'] ?? 'Someone']),
                ($data['reaction_type'] ?? '❤️'),
                $url,
            ],
            'chat_reaction' => [
                __('notifications.reacted_to_your_message', ['user' => $data['reactor_name'] ?? 'Someone', 'reaction' => $data['reaction_type'] ?? '❤️', 'content' => $data['message_content'] ?? 'message']),
                $data['message_content'] ?? '',
                $url,
            ],
            'group_post_pending' => [
                config('app.name'),
                __('notifications.group_post_pending', ['user' => $data['author_name'] ?? 'Someone', 'group' => $data['group_name'] ?? '']),
                $url,
            ],
            'group_join_requested' => [
                config('app.name'),
                __('notifications.group_join_requested', ['user' => $data['requester_name'] ?? 'Someone', 'group' => $data['group_name'] ?? '']),
                $url,
            ],
            'group_join_accepted' => [
                config('app.name'),
                __('notifications.group_join_accepted', ['group' => $data['group_name'] ?? '']),
                $url,
            ],
            'group_post_approved' => [
                config('app.name'),
                __('notifications.group_post_approved', ['group' => $data['group_name'] ?? '']),
                $url,
            ],
            'group_post_rejected' => [
                config('app.name'),
                __('notifications.group_post_rejected', ['group' => $data['group_name'] ?? '']),
                $url,
            ],
            'group_member_role_changed' => [
                config('app.name'),
                match($data['new_role'] ?? 'member') {
                    'admin'     => __('notifications.group_member_role_changed_admin',     ['group' => $data['group_name'] ?? '']),
                    'moderator' => __('notifications.group_member_role_changed_moderator', ['group' => $data['group_name'] ?? '']),
                    default     => __('notifications.group_member_role_changed_member',    ['group' => $data['group_name'] ?? '']),
                },
                $url,
            ],
            'report_accepted' => [
                config('app.name'),
                $data['message'] ?? __('notifications.report_accepted_message'),
                $url,
            ],
            'report_rejected' => [
                config('app.name'),
                $data['message'] ?? __('notifications.report_rejected_message'),
                $url,
            ],
            'report_action_owner' => [
                config('app.name'),
                $data['message'] ?? __('notifications.report_action_owner_message'),
                $url,
            ],
            'admin_post_deleted' => [
                config('app.name'),
                __('notifications.admin_post_deleted', ['reason' => $data['reason'] ?? __('notifications.no_reason_provided')]),
                $url,
            ],
            'community_report_new' => [
                config('app.name'),
                $data['message'] ?? __('notifications.community_report_new_message'),
                $url,
            ],
            default => [
                config('app.name'),
                __('notifications.new_notification'),
                $url,
            ],
        };
    }

    /**
     * Get notification type for settings.
     */
    protected function getNotificationType(string $type): string
    {
        return match($type) {
            'like', 'post_reaction', 'comment_like', 'story_reaction', 'chat_reaction' => 'likes',
            'comment', 'comment_reply' => 'comments',
            'follow' => 'follows',
            'mention' => 'mentions',
            'message' => 'messages',
            default => 'other',
        };
    }
}
