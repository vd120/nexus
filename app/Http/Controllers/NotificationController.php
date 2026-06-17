<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display the notifications page for the authenticated user
     */
    public function index(Request $request)
    {
        \Log::info('NotificationController@index called. Is AJAX? ' . ($request->ajax() ? 'Yes' : 'No') . ' Expects JSON? ' . ($request->expectsJson() ? 'Yes' : 'No'));
        
        if ($request->expectsJson() || $request->ajax()) {
            $user = Auth::user();
            
            if (!$user) {
                \Log::warning('NotificationController@index: Request is unauthenticated');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $notifications = $user->notifications()
                ->orderByRaw('read_at IS NULL DESC')
                ->latest()
                ->limit(50)
                ->get();

            \Log::info('NotificationController@index: Returning ' . $notifications->count() . ' notifications for user ' . $user->id);

            return response()->json([
                'notifications' => $notifications->map(function ($n) {
                    try {
                        return [
                            'id' => $n->id,
                            'type' => $n->type,
                            'message' => $n->message ?? 'New notification',
                            'link' => $n->link,
                            'read_at' => $n->read_at ? $n->read_at->toISOString() : null,
                            'created_at' => $n->created_at ? $n->created_at->toISOString() : now()->toISOString(),
                        ];
                    } catch (\Exception $e) {
                        \Log::error('Error mapping notification ' . $n->id . ': ' . $e->getMessage());
                        return null;
                    }
                })->filter()->values(),
                'unread_count' => $user->notifications()->unread()->count()
            ]);
        }

        $user = Auth::user();
        $notifications = $user->notifications()
            ->orderByRaw('read_at IS NULL DESC')
            ->latest()
            ->limit(50)
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $count = Auth::user()->notifications()->unread()->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . Auth::id());

        // Emit updated count
        app(\App\Services\SocketEmitService::class)->emitToUser(Auth::id(), 'notification:count', [
            'unread_count' => Auth::user()->notifications()->unread()->count(),
        ]);

        return response()->json([
            'message' => __('messages.notification_marked_as_read'),
            'notification' => $notification
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->notifications()->unread()->update(['read_at' => now()]);

        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . Auth::id());

        // Emit updated count
        app(\App\Services\SocketEmitService::class)->emitToUser(Auth::id(), 'notification:count', [
            'unread_count' => 0,
        ]);

        return response()->json([
            'message' => __('messages.all_notifications_marked_as_read')
        ]);
    }

    /**
     * Mark all notifications for a specific conversation as read
     */
    public function markConversationNotificationsAsRead($conversationId): JsonResponse
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->unread()
            ->where(function($query) use ($conversationId) {
                $query->where('data->conversation_id', $conversationId)
                      ->orWhere('data->conversation_id', (int)$conversationId);
            })
            ->get();

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . $user->id);

        // Emit updated count
        app(\App\Services\SocketEmitService::class)->emitToUser($user->id, 'notification:count', [
            'unread_count' => $user->notifications()->unread()->count(),
        ]);

        return response()->json([
            'message' => 'Conversation notifications marked as read',
            'marked_count' => $notifications->count()
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . Auth::id());

        // Emit updated count
        app(\App\Services\SocketEmitService::class)->emitToUser(Auth::id(), 'notification:count', [
            'unread_count' => Auth::user()->notifications()->unread()->count(),
        ]);

        return response()->json([
            'message' => __('messages.notification_deleted')
        ]);
    }

    /**
     * Clear all notifications for the authenticated user
     */
    public function clearAll(): JsonResponse
    {
        Auth::user()->notifications()->delete();

        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . Auth::id());

        // Emit updated count
        app(\App\Services\SocketEmitService::class)->emitToUser(Auth::id(), 'notification:count', [
            'unread_count' => 0,
        ]);

        return response()->json([
            'message' => __('messages.notifications_cleared')
        ]);
    }

    /**
     * Create a notification for a user
     */
    public static function createNotification($userId, $type, $data, $relatedModel = null): Notification
    {
        $recipient = \App\Models\User::find($userId);
        
        // Anti-Leak & Preference check for group context
        if ($relatedModel) {
            $group = null;
            $isAnonymous = false;
            $actorId = null;

            if ($relatedModel instanceof \App\Models\Post) {
                $group = $relatedModel->socialGroup;
                $isAnonymous = $relatedModel->is_anonymous;
                $actorId = $relatedModel->user_id;
            } elseif ($relatedModel instanceof \App\Models\Comment) {
                $group = $relatedModel->socialGroup;
                $isAnonymous = $relatedModel->is_anonymous;
                $actorId = $relatedModel->user_id;
            } elseif ($relatedModel instanceof \App\Models\PostReaction) {
                $group = $relatedModel->socialGroup;
                $isAnonymous = $relatedModel->is_anonymous;
                $actorId = $relatedModel->user_id;
            } elseif ($relatedModel instanceof \App\Models\CommentLike) {
                $group = $relatedModel->socialGroup;
                $isAnonymous = $relatedModel->is_anonymous;
                $actorId = $relatedModel->user_id;
            }

            // Anti-Leak Logic (Unified for Groups & Regular Feed)
            if ($isAnonymous) {
                $alias = 'Anonymous Participant';
                
                // Replace actor info in data
                foreach (['commenter_name', 'liker_name', 'reactor_username', 'sender_name', 'mentioner_name', 'author_name'] as $key) {
                    if (isset($data[$key])) $data[$key] = $alias;
                }
                foreach (['commenter_username', 'liker_username', 'sender_username', 'mentioner_username', 'author_username'] as $key) {
                    if (isset($data[$key])) $data[$key] = $alias;
                }
                foreach (['commenter_id', 'liker_id', 'reactor_id', 'sender_id', 'mentioner_id', 'author_id'] as $key) {
                    if (isset($data[$key])) $data[$key] = null;
                }
                foreach (['sender_avatar', 'reactor_avatar', 'mentioner_avatar', 'author_avatar'] as $key) {
                    if (isset($data[$key])) $data[$key] = null;
                }
            }

            if ($group) {
                // Preference Check
                $member = \App\Models\SocialGroupMember::where('social_group_id', $group->id)
                    ->where('user_id', $userId)
                    ->first();

                if ($member) {
                    if ($member->notification_preference === 'none') {
                        return new Notification(); // Skip saving/sending
                    }
                    // Highlights logic could be added here
                }
            }
        }

        $notificationData = [
            'user_id' => $userId,
            'type' => $type,
            'data' => $data
        ];

        if ($relatedModel) {
            $notificationData['related_id'] = $relatedModel->id;
            $notificationData['related_type'] = get_class($relatedModel);
        }

        $notification = Notification::create($notificationData);

        // Save original locale and set to recipient's locale
        $originalLocale = app()->getLocale();
        $recipient = \App\Models\User::find($userId);
        if ($recipient && $recipient->language) {
            app()->setLocale($recipient->language);
        }

        $translatedMessage = $notification->message;

        // Restore original locale
        app()->setLocale($originalLocale);

        // Emit real-time events
        $socketService = app(\App\Services\SocketEmitService::class);

        // Bust the cached unread count so next page load reflects the new notification
        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . $userId);

        // 1. Send the full notification payload
        $socketService->emitToUser($userId, 'notification:new', [
            'id' => $notification->id,
            'type' => $notification->type,
            'message' => $translatedMessage, // This assumes the model has a message attribute or we compute it
            'link' => $notification->link,
            'data' => $notification->data,
            'created_at' => $notification->created_at->toISOString(),
        ]);

        // 2. Send the updated unread count
        $socketService->emitToUser($userId, 'notification:count', [
            'unread_count' => Notification::where('user_id', $userId)->unread()->count(),
        ]);

        // 3. Send push notification (dispatched to queue — never blocks the HTTP response)
        try {
            $pushService = @app(\App\Services\PushNotificationService::class);
            if ($pushService && $pushService->isConfigured() && $recipient && $recipient->pushSubscriptions()->exists()) {
                $originalLocale = app()->getLocale();
                if ($recipient->language) app()->setLocale($recipient->language);

                [$pushTitle, $pushBody, $pushUrl] = (new class {
                    use \App\Traits\SendsPushNotifications;
                    public function resolve($n) { return $this->getNotificationData($n); }
                })->resolve($notification);

                app()->setLocale($originalLocale);

                \App\Jobs\SendPushNotificationJob::dispatch(
                    $recipient->id,
                    $pushTitle,
                    $pushBody,
                    $pushUrl,
                    [
                        'type'            => $notification->type,
                        'notification_id' => $notification->id,
                        'message_id'      => $notification->data['message_id'] ?? null,
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Push] Failed to dispatch push for notification ' . $notification->id . ': ' . $e->getMessage());
        }

        return $notification;
    }

    /**
     * Create message notification
     */
    public static function createMessageNotification($recipientId, $sender, $message): ?Notification
    {
        // Check if conversation is muted for recipient
        $conversationId = $message->conversation_id;
        if ($conversationId) {
            $isMuted = \App\Models\ConversationMute::where('user_id', $recipientId)
                ->where('conversation_id', $conversationId)
                ->exists();
            if ($isMuted) {
                return null;
            }
        }

        // Generate appropriate preview text based on message type
        $messagePreview = '';
        $messageType = $message->type ?? 'text';
        
        if ($messageType === 'text' || empty($messageType)) {
            $rawContent = $message->content ?? '';
            
            // If the message is a reply, extract the actual content from the JSON
            if (str_starts_with($rawContent, '{"__nexus_reply__":true')) {
                $decoded = json_decode($rawContent, true);
                if ($decoded && isset($decoded['content'])) {
                    $rawContent = '↩ ' . $decoded['content'];
                }
            }
            
            $messagePreview = mb_substr($rawContent, 0, 35) . (mb_strlen($rawContent) > 35 ? '...' : '');
        } else {
            // For media messages, show type instead of content
            $messagePreview = self::getMessageTypeText($messageType);
        }
        
        return self::createNotification(
            $recipientId,
            'message',
            [
                'sender_name' => $sender->username ?? 'Someone',
                'sender_username' => $sender->username ?? 'Unknown',
                'sender_id' => $sender->id,
                'sender_avatar' => $sender->avatar_url,
                'message_preview' => $messagePreview,
                'message_type' => $messageType,
                'message_id' => $message->id ?? null,
                'conversation_id' => $message->conversation_id ?? null,
                'is_group' => $message->conversation->is_group ?? false,
                'group_name' => ($message->conversation && $message->conversation->is_group) ? $message->conversation->display_name : null,
            ],
            $message
        );
    }

    /**
     * Create chat reaction notification
     */
    public static function createChatReactionNotification($recipientId, $reactor, $message, $reactionType): ?Notification
    {
        // Check if conversation is muted for recipient
        $conversationId = $message->conversation_id;
        if ($conversationId) {
            $isMuted = \App\Models\ConversationMute::where('user_id', $recipientId)
                ->where('conversation_id', $conversationId)
                ->exists();
            if ($isMuted) {
                return null;
            }
        }

        // Truncate message content for notification
        $content = $message->content;
        if ($message->type !== 'text') {
            $content = '[' . ucfirst($message->type) . ']';
        }
        
        // Handle reply JSON
        if (str_starts_with($content, '{"__nexus_reply__":true')) {
            $decoded = json_decode($content, true);
            $content = $decoded['content'] ?? '';
        }
        
        $truncatedContent = mb_substr(strip_tags($content), 0, 30);
        if (mb_strlen($content) > 30) $truncatedContent .= '...';

        return self::createNotification(
            $recipientId,
            'chat_reaction',
            [
                'reactor_name' => $reactor->username ?? 'Someone',
                'reactor_username' => $reactor->username ?? 'Unknown',
                'reactor_id' => $reactor->id,
                'reactor_avatar' => $reactor->avatar_url,
                'reaction_type' => $reactionType,
                'message_id' => $message->id,
                'message_content' => $truncatedContent,
                'conversation_id' => $message->conversation_id ?? null,
                'is_group' => $message->conversation->is_group ?? false,
                'group_name' => ($message->conversation && $message->conversation->is_group) ? $message->conversation->display_name : null,
            ],
            $message
        );
    }
    
    /**
     * Get human-readable text for message type
     */
    private static function getMessageTypeText($type): string
    {
        return match($type) {
            'image' => 'sent an image',
            'video' => 'sent a video',
            'audio' => 'sent an audio',
            'voice' => 'sent a voice message',
            'document' => 'sent a document',
            'gif' => 'sent a GIF',
            'sticker' => 'sent a sticker',
            'story_reply' => 'replied to your story',
            'group_invite' => 'sent a group invite',
            'system' => 'system message',
            default => 'sent a message'
        };
    }
}
