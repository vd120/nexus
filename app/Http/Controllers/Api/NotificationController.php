<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.unauthenticated')
                ], 401);
            }

            // Get active conversation ID from request
            $activeConversationId = $request->input('active_conversation_id');

            // Build query with EAGER LOADING to fix N+1 problem
            $query = Notification::with(['user', 'related'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(50);

            // If user is viewing a chat, exclude message notifications from that conversation
            if ($activeConversationId) {
                $query->where(function($q) use ($activeConversationId) {
                    $q->where('type', '!=', 'message')
                      ->orWhereRaw("JSON_EXTRACT(data, '$.conversation_id') != ?", [$activeConversationId]);
                });
            }

            $notifications = $query->get();

            // Pre-fetch all necessary related users for notification types that use data['follower_id'] etc.
            // This is an additional safeguard for data-only notifications
            $triggerUserIds = [];
            foreach ($notifications as $n) {
                if ($n->type === 'follow' && isset($n->data['follower_id'])) $triggerUserIds[] = $n->data['follower_id'];
                if ($n->type === 'mention' && isset($n->data['mentioner_id'])) $triggerUserIds[] = $n->data['mentioner_id'];
            }
            $preFetchedUsers = User::whereIn('id', array_unique($triggerUserIds))->get()->keyBy('id');

            // Build query for unread count (also exclude active conversation)
            $unreadQuery = Notification::where('user_id', $user->id)->whereNull('read_at');
            if ($activeConversationId) {
                $unreadQuery->where(function($q) use ($activeConversationId) {
                    $q->where('type', '!=', 'message')
                      ->orWhereRaw("JSON_EXTRACT(data, '$.conversation_id') != ?", [$activeConversationId]);
                });
            }
            $unreadCount = $unreadQuery->count();

            return response()->json([
                'success' => true,
                'notifications' => $notifications->map(function ($notification) use ($preFetchedUsers) {
                    try {
                        $triggerUser = null;
                        
                        // Try to get user from pre-fetched list first
                        if ($notification->type === 'follow') {
                            $triggerUser = $preFetchedUsers->get($notification->data['follower_id'] ?? null);
                        } elseif ($notification->type === 'mention') {
                            $triggerUser = $preFetchedUsers->get($notification->data['mentioner_id'] ?? null);
                        }

                        // Generate link based on notification type using EAGER LOADED relationships
                        $link = null;
                        if ($notification->type === 'follow' && $triggerUser) {
                            $link = '/users/' . ($triggerUser->username ?? $triggerUser->id);
                        } elseif ($notification->type === 'like' && $notification->related_id) {
                            $post = $notification->related_type === 'App\Models\Post' ? $notification->related : null;
                            $link = $post ? '/posts/' . $post->slug : null;
                        } elseif ($notification->type === 'comment' && $notification->related_id) {
                            $comment = $notification->related_type === 'App\Models\Comment' ? $notification->related : null;
                            $post = $comment ? $comment->post : null;
                            $link = $post ? '/posts/' . $post->slug : null;
                        } elseif ($notification->type === 'mention' && $notification->related_id) {
                            if ($notification->related_type === 'App\\Models\\Post') {
                                $post = $notification->related;
                                $link = $post ? '/posts/' . $post->slug : null;
                            } elseif ($notification->related_type === 'App\\Models\\Comment') {
                                $comment = $notification->related;
                                $post = $comment ? $comment->post : null;
                                $link = $post ? '/posts/' . $post->slug . '#comment-' . $comment->id : null;
                            } else {
                                $link = $triggerUser ? '/users/' . ($triggerUser->username ?? $triggerUser->id) : null;
                            }
                        } elseif ($notification->type === 'message' && ($notification->data['conversation_id'] ?? null)) {
                            $conversation = $notification->related_type === 'App\Models\Conversation' ? $notification->related : null;
                            $link = $conversation ? '/chat/' . $conversation->slug : null;
                        } elseif ($notification->type === 'group_invite' && ($notification->data['conversation_id'] ?? null)) {
                            $conversation = $notification->related_type === 'App\Models\Conversation' ? $notification->related : null;
                            $link = $conversation ? '/chat/' . $conversation->slug : null;
                        }

                        return [
                            'id' => $notification->id,
                            'type' => $notification->type,
                            'message' => $notification->message,
                            'link' => $link,
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at,
                            'related_type' => $notification->related_type,
                            'related_id' => $notification->related_id,
                            'user' => $triggerUser ? [
                                'id' => $triggerUser->id,
                                'username' => $triggerUser->username,
                                'avatar' => $triggerUser->avatar_url,
                            ] : null,
                        ];
                    } catch (\Exception $e) {
                        return [
                            'id' => $notification->id,
                            'type' => $notification->type,
                            'message' => $notification->message,
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at,
                            'related_type' => $notification->related_type,
                            'related_id' => $notification->related_id,
                            'user' => null,
                        ];
                    }
                }),
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.unauthenticated')
                ], 401);
            }

            $unreadCount = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting unread count: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Notification $notification)
    {
        
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $notification->markAsRead();

        $unreadCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . auth()->id());

        return response()->json([
            'success' => true,
            'unread_count' => 0
        ]);
    }

    public function deleteAll(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.unauthenticated')
                ], 401);
            }

            
            $totalCount = Notification::where('user_id', $user->id)->count();

            
            $deletedCount = Notification::where('user_id', $user->id)->delete();

            \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . $user->id);

            return response()->json([
                'success' => true,
                'message' => __('messages.notifications_cleared'),
                'deleted_count' => $deletedCount,
                'total_count' => $totalCount,
                'unread_count' => 0
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Notification $notification)
    {
        
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $notification->delete();

        \Illuminate\Support\Facades\Cache::forget('unread_notifications_' . auth()->id());

        $unreadCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }
}
