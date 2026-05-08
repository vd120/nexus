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

        // Get notification data based on type in the recipient's language
        $originalLocale = app()->getLocale();
        if ($user->language) {
            app()->setLocale($user->language);
        }

        [$title, $body, $url] = $this->getNotificationData($notification);

        // Restore original locale
        app()->setLocale($originalLocale);

        // Send push notification
        $pushService->sendToUser($user, $title, $body, $url, [
            'type' => $this->getNotificationType($notification->type),
            'notification_id' => $notification->id,
        ]);
    }

    /**
     * Get notification data for push notification.
     */
    protected function getNotificationData(Notification $notification): array
    {
        $data = $notification->data ?? [];

        return match($notification->type) {
            'like' => [
                __('notifications.liked_your_post', ['user' => $data['liker_name'] ?? 'Someone']),
                __('notifications.liked_your_post', ['user' => $data['liker_name'] ?? 'Someone']),
                route('posts.show', ['post' => $data['post_slug'] ?? '#']),
            ],
            'comment' => [
                __('notifications.commented_on_your_post', ['user' => $data['commenter_name'] ?? 'Someone']),
                $data['comment_preview'] ?? __('notifications.commented_on_your_post', ['user' => $data['commenter_name'] ?? 'Someone']),
                route('posts.show', ['post' => $data['post_slug'] ?? '#']),
            ],
            'comment_reply' => [
                __('notifications.replied_to_your_comment', ['user' => $data['commenter_name'] ?? 'Someone']),
                $data['comment_preview'] ?? __('notifications.replied_to_your_comment', ['user' => $data['commenter_name'] ?? 'Someone']),
                route('posts.show', ['post' => $data['post_slug'] ?? '#']),
            ],
            'comment_like' => [
                ($data['is_reply'] ?? false) ? __('notifications.liked_your_reply', ['user' => $data['liker_name'] ?? 'Someone']) : __('notifications.liked_your_comment', ['user' => $data['liker_name'] ?? 'Someone']),
                ($data['is_reply'] ?? false) ? __('notifications.liked_your_reply', ['user' => $data['liker_name'] ?? 'Someone']) : __('notifications.liked_your_comment', ['user' => $data['liker_name'] ?? 'Someone']),
                route('posts.show', ['post' => $data['post_slug'] ?? '#']),
            ],
            'follow' => [
                __('notifications.new_follower', ['user' => $data['follower_name'] ?? 'Someone']),
                __('notifications.new_follower', ['user' => $data['follower_name'] ?? 'Someone']),
                route('users.show', ['user' => $data['follower_username'] ?? '#']),
            ],
            'mention' => [
                __('notifications.mentioned_you', ['user' => $data['mentioner_name'] ?? 'Someone']),
                $data['mention_preview'] ?? __('notifications.mentioned_you', ['user' => $data['mentioner_name'] ?? 'Someone']),
                route('posts.show', ['post' => $data['post_slug'] ?? '#']),
            ],
            'message' => [
                __('notifications.sent_you_message', ['user' => $data['sender_username'] ?? 'Someone']),
                $data['message_preview'] ?? __('notifications.sent_you_message', ['user' => $data['sender_username'] ?? 'Someone']),
                route('chat.show', ['conversation' => $data['conversation_slug'] ?? '#']),
            ],
            default => [
                config('app.name'),
                __('notifications.notifications_cleared'),
                route('notifications.index'),
            ],
        };
    }

    /**
     * Get notification type for settings.
     */
    protected function getNotificationType(string $type): string
    {
        return match($type) {
            'like', 'post_reaction', 'comment_like' => 'likes',
            'comment', 'comment_reply' => 'comments',
            'follow' => 'follows',
            'mention' => 'mentions',
            'message' => 'messages',
            default => 'other',
        };
    }
}
   }

    /**
     * Get notification type for settings.
     */
    protected function getNotificationType(string $type): string
    {
        return match($type) {
            'like', 'comment_like' => 'likes',
            'comment', 'comment_reply' => 'comments',
            'follow' => 'follows',
            'mention' => 'mentions',
            'message' => 'messages',
            default => 'other',
        };
    }
}
