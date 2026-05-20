<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReceipt;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Display chat index with conversation list
     */
    public function index()
    {
        // Get direct conversations
        $directConversations = Conversation::where(function($q) {
                $q->where('user1_id', auth()->id())
                  ->orWhere('user2_id', auth()->id());
            })
            ->with(['user1', 'user2', 'latestMessage.sender'])
            ->orderByRaw('last_message_at IS NULL, last_message_at DESC')
            ->orderBy('id', 'desc')->limit(50)->get()->reverse()->values();

        // Get group conversations
        $groupIds = auth()->user()->groupMemberships()->pluck('group_id');
        $groupConversations = Conversation::where('is_group', true)
            ->whereIn('group_id', $groupIds)
            ->with(['group.members.user', 'latestMessage.sender'])
            ->orderByRaw('last_message_at IS NULL, last_message_at DESC')
            ->orderBy('id', 'desc')->limit(50)->get()->reverse()->values();

        // Merge and sort by last message
        $conversations = $directConversations->merge($groupConversations)
            ->sortByDesc('last_message_at');

        return view('chat.index', compact('conversations'));
    }

    /**
     * Get conversations list (REST endpoint)
     */
    public function getConversations()
    {
        $directConversations = Conversation::where(function($q) {
                $q->where('user1_id', auth()->id())
                  ->orWhere('user2_id', auth()->id());
            })
            ->with(['user1', 'user2', 'latestMessage.sender'])
            ->orderByRaw('last_message_at IS NULL, last_message_at DESC')
            ->orderBy('id', 'desc')->limit(50)->get()->reverse()->values();

        $groupIds = auth()->user()->groupMemberships()->pluck('group_id');
        $groupConversations = Conversation::where('is_group', true)
            ->whereIn('group_id', $groupIds)
            ->with(['group.members.user', 'latestMessage.sender'])
            ->orderByRaw('last_message_at IS NULL, last_message_at DESC')
            ->orderBy('id', 'desc')->limit(50)->get()->reverse()->values();

        $conversations = $directConversations->merge($groupConversations)
            ->sortByDesc('last_message_at')
            ->values()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'slug' => $conversation->slug,
                    'is_group' => (bool) $conversation->is_group,
                    'last_message_at' => $conversation->last_message_at ? \Carbon\Carbon::parse($conversation->last_message_at)->toISOString() : null,
                    'unread_count' => $conversation->unread_count,
                    'other_user' => $conversation->other_user ? [
                            'id' => $conversation->other_user->id,
                            'username' => $conversation->other_user->username,
                            'avatar_url' => $conversation->other_user->avatar_url,
                        ] : null,
                    'latest_message' => $conversation->latestMessage ? [
                        'id' => $conversation->latestMessage->id,
                        'content' => strip_tags($conversation->latestMessage->content),
                        'type' => $conversation->latestMessage->type,
                        'sender_id' => $conversation->latestMessage->sender_id,
                        'created_at' => $conversation->latestMessage->created_at ? \Carbon\Carbon::parse($conversation->latestMessage->created_at)->toISOString() : null,
                    ] : null,
                    'is_muted' => $conversation->isMutedBy(auth()->id()),
                ];
            });

        return response()->json(['success' => true, 'conversations' => $conversations]);
    }

    /**
     * Get messages for a conversation (REST endpoint)
     */
    public function getMessages(Conversation $conversation, Request $request)
    {
        if (!$conversation->isMember(auth()->id())) abort(403);

        $afterId = $request->query('after_id');
        $query = Message::where('conversation_id', $conversation->id)
            ->with('sender.profile')
            ->where(function($q) {
                $q->whereNull('visible_to')->orWhere('visible_to', auth()->id());
            })
            ->orderBy('id', 'desc');

        if ($afterId) $query->where('id', '>', $afterId);

        $messages = $query->limit(50)->get()->reverse()->values()->map(function ($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'type' => $message->type,
                'media_path' => $message->media_path,
                'duration' => $message->duration,
                'waveform_peaks' => $message->waveform_peaks,
                'sender' => [
                    'id' => $message->sender->id,
                    'username' => $message->sender->username,
                    'avatar_url' => $message->sender->avatar_url,
                ],
            ];
        });

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /**
     * Display specific conversation
     */
    public function show($conversation)
    {
        // Try to find by ID first (for numeric), then by slug
        if (is_numeric($conversation)) {
            $conversation = Conversation::findOrFail($conversation);
        } else {
            $conversation = Conversation::where('slug', $conversation)->firstOrFail();
        }
        
        // Check if user has access to this conversation
        if (!$conversation->isMember(auth()->id())) {
            abort(403);
        }


        $userId = auth()->id();

        // Load messages - exclude messages deleted for current user
        $messages = Message::where('conversation_id', $conversation->id)
            ->with(['conversation', 'sender.profile', 'reactions.user', 'receipts' => function($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->withTrashed()
            ->where(function($q) use ($userId) {
                $q->whereNull('visible_to')
                  ->orWhere('visible_to', $userId);
            })
            ->where(function($q) use ($userId) {
                $q->whereNull('deleted_for')
                  ->orWhereJsonDoesntContain('deleted_for', $userId)
                  ->orWhere('deleted_by_sender', true);
            })
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->reverse();

        if (request()->wantsJson() || request()->header('X-Mini-Chat')) {
            return view('chat.partials.messages', compact('conversation', 'messages'))->render();
        }

        return view('chat.show', compact('conversation', 'messages'));
    }

    /**
     * Display mini-view for chat
     */
    public function miniShow($slug)
    {
        $conversation = Conversation::where('slug', $slug)->firstOrFail();
        
        if (!$conversation->isMember(auth()->id())) {
            abort(403);
        }

        $userId = auth()->id();

        // Load messages
        $messages = Message::where('conversation_id', $conversation->id)
            ->with(['sender'])
            ->where(function($q) use ($userId) {
                $q->whereNull('visible_to')
                  ->orWhere('visible_to', $userId);
            })
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->reverse();

        return view('chat.mini-show', compact('conversation', 'messages'));
    }

    /**
     * Store a new message
     */
    public function store(Request $request, Conversation $conversation)
    {
        // Check if user has access
        if (!$conversation->isMember(auth()->id())) {
            abort(403);
        }

        $currentUser = auth()->user();
        
        // Check blocks for 1-1 chats only
        if (!$conversation->is_group) {
            foreach ($conversation->participants as $participant) {
                if ($participant->id !== $currentUser->id) {
                    if ($currentUser->isBlocking($participant)) {
                        return response()->json(['success' => false, 'error' => __('messages.cannot_send_message_blocked_user')], 403);
                    }
                    if ($participant->isBlocking($currentUser)) {
                        return response()->json(['success' => false, 'error' => __('messages.user_has_blocked_you')], 403);
                    }
                }
            }
        }

        $request->validate([
            'content' => 'nullable|string|max:1000',
            'media' => 'nullable|array',
            'media.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,mp4,mov,avi,webm,ogg,wav,weba|max:51200',
            'voice_message' => 'nullable|file|mimes:ogg,wav,weba,webm|max:10240',
            'duration' => 'nullable|integer|min:1|max:300',
            'waveform_peaks' => 'nullable|string',
        ]);

        if (!$request->filled('content') && !$request->hasFile('media') && !$request->hasFile('voice_message')) {
            return response()->json(['success' => false, 'message' => __('messages.message_content_required')], 422);
        }

        $content = $request->content ?? '';

        // Handle reply to message
        if ($request->filled('reply_to_id')) {
            $parentMessage = Message::find($request->reply_to_id);
            if ($parentMessage && $parentMessage->conversation_id === $conversation->id) {
                $parentContent = $parentMessage->content;
                if ($parentMessage->type !== 'text') {
                    $parentContent = '[' . ucfirst($parentMessage->type) . ']';
                }
                
                // If parent is also a reply, extract its content
                if (str_starts_with($parentContent, '{"__nexus_reply__":true')) {
                    $parentData = json_decode($parentContent, true);
                    $parentContent = $parentData['content'] ?? '';
                }

                $replyData = [
                    '__nexus_reply__' => true,
                    'reply_to' => [
                        'id' => $parentMessage->id,
                        'username' => $parentMessage->sender?->username ?? __('chat.user'),
                        'sender_name' => $parentMessage->sender?->username ?? __('chat.user'),
                        'user' => $parentMessage->sender?->username ?? __('chat.user'),
                        'content' => \Illuminate\Support\Str::limit($parentContent, 100),
                        'type' => $parentMessage->type
                    ],
                    'content' => $content
                ];
                $content = json_encode($replyData);
            }
        }

        $messageData = [
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'content' => $content,
            'type' => 'text',
        ];

        // Media handling
        if ($request->hasFile('media')) {
            $mediaItems = [];
            $files = $request->file('media');
            if (!is_array($files)) $files = [$files];

            foreach ($files as $file) {
                $mimeType = $file->getMimeType();
                $path = $file->store('chat/media', 'public');
                $mediaItems[] = [
                    'type' => str_starts_with($mimeType, 'image/') ? 'image' : (str_starts_with($mimeType, 'video/') ? 'video' : 'file'),
                    'path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
            $messageData['type'] = $mediaItems[0]['type'];
            $messageData['media_path'] = json_encode($mediaItems);
        }

        // Voice handling
        if ($request->hasFile('voice_message')) {
            $messageData['type'] = 'voice';
            $messageData['media_path'] = $request->file('voice_message')->store('chat/voice', 'public');
            $messageData['duration'] = $request->duration;
            $messageData['waveform_peaks'] = $request->waveform_peaks;
            $messageData['content'] = '';
        }

        $message = Message::create($messageData);
        $conversation->update(['last_message_at' => now()]);

        // Immediate delivery confirmation for online recipients
        $recipientIds = $conversation->getRecipients(auth()->id());
        
        // Handle delivery status for 1-1 chats immediately if online
        if (!$conversation->is_group) {
            foreach ($recipientIds as $recipientId) {
                $recipient = \App\Models\User::find($recipientId);
                if ($recipient && $recipient->is_online && !$message->delivered_at) {
                    $message->update(['delivered_at' => now()]);
                }
            }
        }
        
        foreach ($recipientIds as $recipientId) {
            \App\Http\Controllers\NotificationController::createMessageNotification($recipientId, $currentUser, $message);
        }

        // Real-time broadcast
        $message->load('sender.profile');
        if ($message->sender) $message->sender->append('avatar_url');
        
        $messagePayload = $this->formatMessagePayload($message);
        $socketService = app(\App\Services\SocketEmitService::class);
        $socketService->emitToConversation($conversation->id, 'chat:message', $messagePayload);

        $participants = array_unique(array_merge($recipientIds, [auth()->id()]));
        foreach ($participants as $participantId) {
            $participant = \App\Models\User::find($participantId);
            $originalLocale = app()->getLocale();
            if ($participant && $participant->language) {
                app()->setLocale($participant->language);
            }

            $previewData = $this->getConversationPreviewForUser($conversation, $participantId);

            $socketService->emitToUser($participantId, 'chat:conversation:updated', [
                'conversation_id' => $conversation->id,
                'conversation_slug' => $conversation->slug,
                'is_group' => $conversation->is_group,
                'display_name' => $conversation->is_group ? $conversation->display_name : auth()->user()->username,
                'avatar_url' => $conversation->is_group ? ($conversation->group?->avatar ? asset('storage/' . $conversation->group->avatar) : null) : auth()->user()->avatar_url,
                'last_message' => $previewData['text'],
                'last_message_id' => $previewData['id'] ?? null,
                'show_checkmarks' => $previewData['show_checkmarks'],
                'last_message_time' => $message->created_at->toISOString(),
                'unread_count' => $conversation->unreadCountFor($participantId),
            ]);

            app()->setLocale($originalLocale);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Start a new conversation
     */
    public function startConversation($userId)
    {
        $user = User::findOrFail($userId);
        $currentUser = auth()->user();

        if ($user->id === auth()->id()) {
            return request()->expectsJson() ? response()->json(['error' => __('messages.cannot_chat_with_self')], 400) : redirect()->back()->with('error', __('messages.cannot_chat_with_self'));
        }

        if ($currentUser->isBlocking($user) || $user->isBlocking($currentUser)) {
            $msg = $currentUser->isBlocking($user) ? __('messages.cannot_chat_with_blocked_user') : __('messages.user_has_blocked_you');
            return request()->expectsJson() ? response()->json(['error' => $msg], 403) : redirect()->back()->with('error', $msg);
        }

        $conversation = Conversation::getConversationBetween(auth()->id(), $user->id) ?: Conversation::createConversation(auth()->id(), $user->id);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'slug' => $conversation->slug, 'conversation_id' => $conversation->id, 'url' => route('chat.show', $conversation)]);
        }

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(Conversation $conversation)
    {
        $userId = auth()->id();
        if (!$conversation->isMember($userId)) abort(403);
        
        $now = now();
        $messagesToMarkQuery = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId);
            
        if ($conversation->is_group) {
            $messagesToMark = $messagesToMarkQuery->whereDoesntHave('receipts', function($q) use ($userId) {
                $q->where('user_id', $userId)->whereNotNull('read_at');
            })->pluck('id');
        } else {
            $messagesToMark = $messagesToMarkQuery->whereNull('read_at')->pluck('id');
        }
            
        $count = $messagesToMark->count();
        $readMessagesData = [];
        if ($count > 0) {
            if (!$conversation->is_group) {
                Message::markConversationAsRead($conversation->id, $userId);
                foreach ($messagesToMark as $messageId) {
                    $readMessagesData[] = ['id' => $messageId, 'is_all_read' => true];
                }
            } else {
                // For group chats, per-user receipts are the source of truth
                foreach ($messagesToMark as $messageId) {
                    MessageReceipt::updateOrCreate(
                        ['message_id' => $messageId, 'user_id' => $userId],
                        ['read_at' => $now, 'delivered_at' => $now]
                    );

                    // Check if EVERYONE in the group has read it
                    $msg = Message::find($messageId);
                    $isAllRead = false;
                    if ($msg) {
                        $isAllRead = $msg->updateReadStatusIfAllRead();
                    }
                    $readMessagesData[] = ['id' => $messageId, 'is_all_read' => $isAllRead];
                }
            }
        }
        
        // Always prepare emit data to ensure UI sync for the reader
        $emitData = [
            'conversation_id' => $conversation->id,
            'reader_id' => $userId,
            'read_messages' => $readMessagesData, // New format with all_read info
            'read_message_ids' => $messagesToMark->toArray(), // Keep for compatibility
            'read_at' => $now->toISOString()
        ];
        
        // Emit to the conversation and the reader themselves to sync sidebar
        app(\App\Services\SocketEmitService::class)->emitToConversation($conversation->id, 'chat:read', $emitData);
        app(\App\Services\SocketEmitService::class)->emitToUser($userId, 'chat:read', $emitData);
        
        // Also emit to the sender specifically
        $lastMessage = Message::where('conversation_id', $conversation->id)->orderBy('created_at', 'desc')->first();
        if ($lastMessage && $lastMessage->sender_id !== $userId) {
            app(\App\Services\SocketEmitService::class)->emitToUser($lastMessage->sender_id, 'chat:read', $emitData);
        }

        // Also mark notifications for this conversation as read
        $notifications = auth()->user()->notifications()
            ->unread()
            ->where(function($query) use ($conversation) {
                $query->where('data->conversation_id', $conversation->id)
                      ->orWhere('data->conversation_id', (int)$conversation->id)
                      ->orWhere('data->message->conversation_id', $conversation->id)
                      ->orWhere('data->message->conversation_id', (int)$conversation->id);
            })
            ->get();

        if ($notifications->count() > 0) {
            foreach ($notifications as $notification) {
                $notification->markAsRead();
            }
            
            // Emit updated count
            app(\App\Services\SocketEmitService::class)->emitToUser(auth()->id(), 'notification:count', [
                'unread_count' => auth()->user()->notifications()->unread()->count(),
            ]);
        }
                                                                                              
        return response()->json(['success' => true, 'read_count' => $count, 'read_message_ids' => $messagesToMark->toArray()]);
    }

    /**
     * Delete a message
     */
    public function destroy(Request $request, Message $message)
    {
        $userId = auth()->id();
        $conversation = $message->conversation;

        if ($conversation->is_group) {
            if (!$conversation->group || !$conversation->group->hasMember(auth()->user())) abort(403);
        } else {
            if ($conversation->user1_id != $userId && $conversation->user2_id != $userId) abort(403);
        }

        $deleteType = $request->input('type', 'me');
        if ($deleteType === 'everyone') {
            if ($message->sender_id != $userId) abort(403);
            $message->update(['deleted_by_sender' => true]);
            $message->delete();
            
            $latest = Message::where('conversation_id', $conversation->id)->whereNull('deleted_at')->orderBy('created_at', 'desc')->first();
            $conversation->update(['last_message_at' => $latest ? $latest->created_at : null]);
            $this->broadcastMessageDeleted($message, $conversation);
        } else {
            $deletedFor = $message->deleted_for ?? [];
            if (!in_array($userId, $deletedFor)) {
                $deletedFor[] = $userId;
                $message->update(['deleted_for' => $deletedFor]);
            }
        }

        return response()->json(['success' => true, 'deleted_message_id' => $message->id, 'conversation_slug' => $conversation->slug, 'delete_type' => $deleteType]);
    }

    /**
     * Broadcast message deleted event and update sidebar
     */
    private function broadcastMessageDeleted($message, $conversation)
    {
        // 1. Notify chat window to remove the bubble
        app(\App\Services\SocketEmitService::class)->emitToConversation($conversation->id, 'chat:message:deleted', [
            'message_id' => $message->id,
            'delete_type' => 'everyone',
            'deleted_for' => $message->deleted_for
        ]);

        // 2. Update sidebar preview for everyone
        if ($conversation->is_group && $conversation->group) {
            $participants = $conversation->group->members->pluck('user_id')->toArray();
        } else {
            $participants = array_filter([$conversation->user1_id, $conversation->user2_id]);
        }
        
        $socketService = app(\App\Services\SocketEmitService::class);

        foreach ($participants as $participantId) {
            $participant = \App\Models\User::find($participantId);
            $originalLocale = app()->getLocale();
            if ($participant && $participant->language) {
                app()->setLocale($participant->language);
            }

            $previewData = $this->getConversationPreviewForUser($conversation, $participantId);

            $socketService->emitToUser($participantId, 'chat:conversation:updated', [
                'conversation_id' => $conversation->id,
                'last_message' => $previewData['text'],
                'last_message_id' => $previewData['id'] ?? null,
                'show_checkmarks' => $previewData['show_checkmarks'],
                'last_message_time' => now()->toISOString(), // Use now as it was a recent deletion action
                'unread_count' => $conversation->unreadCountFor($participantId),
            ]);

            app()->setLocale($originalLocale);
        }
    }

    /**
     * Clear all messages in conversation
     */
    public function clearChat(Conversation $conversation)
    {
        if (!$conversation->isMember(auth()->id())) abort(403);

        $user = auth()->user();
        
        // Force delete all existing messages
        Message::where('conversation_id', $conversation->id)->forceDelete();
        
        // Create a system message indicating who cleared the chat
        $systemMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'system_cleared', // Use content as a flag
            'type' => 'system', // Use 'system' since it is now in the DB enum
        ]);

        $conversation->update(['last_message_at' => now()]);

        $socketService = app(\App\Services\SocketEmitService::class);
        $payload = [
            'conversation_id' => $conversation->id,
            'username' => $user->username,
            'user_id' => $user->id,
            'message_id' => $systemMessage->id,
            'time' => $systemMessage->created_at->format('h:i a'),
            'type' => 'system',
            'content' => 'system_cleared'
        ];

        // Broadcast to the conversation room so everyone currently in the chat gets the real-time update
        $socketService->emitToConversation($conversation->id, 'chat:cleared', $payload);

        // Still update sidebar previews for everyone individually to ensure their individual counters/previews are correct
        $recipientIds = $conversation->getRecipients($user->id);
        $allParticipants = array_unique(array_merge($recipientIds, [$user->id]));

        foreach ($allParticipants as $participantId) {
            $participant = \App\Models\User::find($participantId);
            $originalLocale = app()->getLocale();
            if ($participant && $participant->language) {
                app()->setLocale($participant->language);
            }

            $previewData = $this->getConversationPreviewForUser($conversation, $participantId);
            
            $socketService->emitToUser($participantId, 'chat:conversation:updated', [
                'conversation_id' => $conversation->id,
                'last_message' => $previewData['text'],
                'last_message_id' => $previewData['id'] ?? null,
                'show_checkmarks' => $previewData['show_checkmarks'],
                'last_message_time' => $systemMessage->created_at->toISOString(),
                'unread_count' => 0,
                'type' => 'text',
                'content' => 'system_cleared',
                'username' => $user->username
            ]);

            app()->setLocale($originalLocale);
        }

        return response()->json(['success' => true]);
    }

    public function deleteConversation(Conversation $conversation)
    {
        if (!$conversation->isMember(auth()->id())) abort(403);
        
        // Groups should be deleted via GroupController or left, not cleared this way
        if ($conversation->is_group) {
            return response()->json(['success' => false, 'message' => 'Groups cannot be deleted from here'], 403);
        }

        $conversationId = $conversation->id;
        
        // Broadcast deletion event BEFORE deleting from DB
        // We broadcast to the conversation room so all participants get it
        app(\App\Services\SocketEmitService::class)->emitToConversation($conversationId, 'chat:conversation:deleted', [
            'conversation_id' => $conversationId,
            'deleted_by' => auth()->id()
        ]);

        // Delete all messages first
        $conversation->messages()->forceDelete();
        
        // Delete the conversation record
        $conversation->delete();

        return response()->json(['success' => true]);
    }

    public function toggleMute(Conversation $conversation)
    {
        if (!$conversation->isMember(auth()->id())) abort(403);

        $userId = auth()->id();
        $isMuted = false;

        $mute = \App\Models\ConversationMute::where('user_id', $userId)
            ->where('conversation_id', $conversation->id)
            ->first();

        if ($mute) {
            $mute->delete();
            $isMuted = false;
        } else {
            \App\Models\ConversationMute::create([
                'user_id' => $userId,
                'conversation_id' => $conversation->id
            ]);
            $isMuted = true;
        }

        return response()->json([
            'success' => true,
            'is_muted' => $isMuted,
            'message' => $isMuted ? __('chat.notifications_muted') : __('chat.notifications_unmuted')
        ]);
    }

    public function markAllAsDelivered()
    {
        $userId = auth()->id();
        $groupIds = auth()->user()->groupMemberships()->pluck('group_id');
        
        $conversationIds = Conversation::where(function($q) use ($userId) {
                $q->where('user1_id', $userId)
                  ->orWhere('user2_id', $userId);
            })
            ->orWhere(function($q) use ($groupIds) {
                $q->where('is_group', true)
                  ->whereIn('group_id', $groupIds);
            })
            ->pluck('id');

        $now = now();
        $messages = Message::with('conversation')->whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $userId)
            ->where(function($q) use ($userId) {
                // 1-1 chats without delivered_at
                $q->where(function($q) {
                    $q->whereHas('conversation', function($c) {
                        $c->where('is_group', false);
                    })->whereNull('delivered_at');
                })
                // Group chats where this user hasn't delivered
                ->orWhere(function($q) use ($userId) {
                    $q->whereHas('conversation', function($c) {
                        $c->where('is_group', true);
                    })->whereDoesntHave('receipts', function($r) use ($userId) {
                        $r->where('user_id', $userId)->whereNotNull('delivered_at');
                    });
                });
            })
            ->get();

        foreach ($messages as $message) {
            if (!$message->conversation->is_group) {
                $message->update(['delivered_at' => $now]);
            } else {
                MessageReceipt::updateOrCreate(
                    ['message_id' => $message->id, 'user_id' => $userId],
                    ['delivered_at' => $now]
                );
            }
            
            $deliveredTime = $message->conversation->is_group ? $now : $message->delivered_at;
            
            $emitData = [
                'message_id' => $message->id,
                'user_id' => $userId,
                'conversation_id' => $message->conversation_id,
                'delivered_at' => $deliveredTime->toISOString()
            ];
            
            app(\App\Services\SocketEmitService::class)->emitToConversation($message->conversation_id, 'chat:delivered', $emitData);
            app(\App\Services\SocketEmitService::class)->emitToUser($message->sender_id, 'chat:delivered', $emitData);
        }

        return response()->json(['success' => true, 'count' => $messages->count()]);
    }

    /**
     * Confirm message delivery
     */
    public function confirmDelivery(Request $request)
    {
        $request->validate(['message_id' => 'required|exists:messages,id']);
        $message = Message::find($request->message_id);
        $userId = auth()->id();
        
        if (!$message->conversation->isMember($userId)) return response()->json(['success' => false], 403);
        
        $now = now();
        
        // Handle per-user receipt for group chats
        $isAllDelivered = false;
        if ($message->conversation->is_group) {
            MessageReceipt::updateOrCreate(
                ['message_id' => $message->id, 'user_id' => $userId],
                ['delivered_at' => $now]
            );
            
            // Only update global delivered_at if EVERYONE in the group has received it
            $isAllDelivered = $message->updateDeliveryStatusIfAllDelivered();
        } else {
            // For 1-1 chats, update the message columns directly
            if (!$message->delivered_at) {
                $message->update(['delivered_at' => $now]);
            }
            $isAllDelivered = true;
        }
        
        $emitData = [
            'message_id' => $message->id,
            'user_id' => $userId,
            'conversation_id' => $message->conversation_id,
            'delivered_at' => $now->toISOString(),
            'is_all_delivered' => $isAllDelivered
        ];
        
        // Emit delivered event
        app(\App\Services\SocketEmitService::class)->emitToConversation($message->conversation_id, 'chat:delivered', $emitData);
        app(\App\Services\SocketEmitService::class)->emitToUser($message->sender_id, 'chat:delivered', $emitData);

        return response()->json(['success' => true]);
    }

    /**
     * Send typing indicator
     */
    public function sendTypingIndicator(Request $request, Conversation $conversation)
    {
        $request->validate(['is_typing' => 'boolean']);
        if (!$conversation->isMember(auth()->id())) return response()->json(['success' => false], 403);

        $cacheKey = "typing:{$conversation->id}:" . auth()->id();
        if ($request->is_typing) {
            cache()->put($cacheKey, ['user_id' => auth()->id(), 'username' => auth()->user()->username, 'timestamp' => now()->timestamp], 5);
        } else {
            cache()->forget($cacheKey);
        }

        return response()->json(['success' => true, 'is_typing' => $request->is_typing]);
    }

    /**
     * Format a message for real-time broadcasting
     */
    protected function formatMessagePayload(Message $message)
    {
        return $message->toPayload();
    }

    /**
     * Toggle a reaction on a message (add/update/remove)
     */
    public function toggleReaction(Request $request, Message $message)
    {
        $conversation = $message->conversation;
        if (!$conversation->isMember(auth()->id())) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reaction_type' => 'required|string|max:10',
        ]);

        $userId = auth()->id();
        $emoji = $request->reaction_type;
        $allowedEmojis = ['👍', '❤️', '😂', '😮', '😢', '😡'];

        if (!in_array($emoji, $allowedEmojis)) {
            return response()->json(['success' => false, 'error' => 'Invalid reaction'], 422);
        }

        $existing = \App\Models\MessageReaction::where('user_id', $userId)
            ->where('message_id', $message->id)
            ->first();

        $action = 'added';

        if ($existing) {
            if ($existing->reaction_type === $emoji) {
                // Same reaction → remove it
                $existing->delete();
                $action = 'removed';
            } else {
                // Different reaction → update it
                $existing->update(['reaction_type' => $emoji]);
                $action = 'updated';
            }
        } else {
            // No existing → create
            \App\Models\MessageReaction::create([
                'user_id' => $userId,
                'message_id' => $message->id,
                'reaction_type' => $emoji,
            ]);
        }

        // Re-fetch grouped reactions for the response
        $message->load(['reactions.user', 'sender', 'conversation']);
        $groupedReactions = $message->getGroupedReactions();
        $currentUser = auth()->user();

        // Broadcast to all participants in real-time for the chat window
        $socketService = app(\App\Services\SocketEmitService::class);
        $reactionPayload = [
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'username' => $currentUser->username,
            'reaction_type' => $emoji,
            'action' => $action,
            'reactions' => $groupedReactions,
        ];

        $socketService->emitToConversation($conversation->id, 'chat:reaction', $reactionPayload);

        // 1. Create notification for the message sender (if not the reactor)
        if (($action === 'added' || $action === 'updated') && $message->sender_id !== $userId) {
            \App\Http\Controllers\NotificationController::createChatReactionNotification(
                $message->sender_id,
                $currentUser,
                $message,
                $emoji
            );
        }

        // 2. Update sidebar preview for all participants
        $recipientIds = $conversation->getRecipients($userId);
        $allParticipants = array_unique(array_merge($recipientIds, [$userId]));

        foreach ($allParticipants as $participantId) {
            $participant = \App\Models\User::find($participantId);
            $originalLocale = app()->getLocale();
            if ($participant && $participant->language) {
                app()->setLocale($participant->language);
            }

            $previewData = $this->getConversationPreviewForUser($conversation, $participantId);

            $socketService->emitToUser($participantId, 'chat:conversation:updated', [
                'conversation_id' => $conversation->id,
                'conversation_slug' => $conversation->slug,
                'is_group' => $conversation->is_group,
                'display_name' => $conversation->is_group ? $conversation->display_name : ($conversation->other_user->username ?? $currentUser->username),
                'last_message' => $previewData['text'],
                'last_message_id' => $previewData['id'] ?? null,
                'show_checkmarks' => $previewData['show_checkmarks'],
                'checkmark_class' => $previewData['checkmark_class'] ?? null,
                'last_message_time' => $message->created_at->toISOString(),
                'unread_count' => $conversation->unreadCountFor($participantId),
                'no_reorder' => true // Don't jump to top for reactions
            ]);

            app()->setLocale($originalLocale);
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'reactions' => $groupedReactions,
        ]);
    }

    /**
     * Get the formatted preview text for a conversation from a specific user's perspective.
     */
    private function getConversationPreviewForUser($conversation, $userId)
    {
        $latestMessage = \App\Models\Message::where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->with('sender')
            ->latest()
            ->first();
            
        $latestReaction = \App\Models\MessageReaction::whereIn('message_id', $conversation->messages()->pluck('id'))
            ->with(['message', 'user'])
            ->latest('updated_at')
            ->first();

        $showReaction = false;
        if ($latestReaction && (!$latestMessage || $latestReaction->updated_at > $latestMessage->created_at)) {
            $showReaction = true;
        }


        if ($showReaction) {
            $reactor = $latestReaction->user;
            $name = ($reactor->id === $userId) ? __('chat.you') : ($reactor->username ?? 'User');
            
            $content = strip_tags($latestReaction->message->content);
            if (str_starts_with($content, '{"__nexus_reply__":true')) {
                $replyData = json_decode($content, true);
                $content = $replyData['content'] ?? '';
            }
            
            $content = \Illuminate\Support\Str::limit(strip_tags($content), 15);
            if (empty($content) && $latestReaction->message->type !== 'text') {
                $content = '[' . ucfirst($latestReaction->message->type) . ']';
            }

            return [
                'text' => ($name . ' ') . __('chat.reacted_on_message', [
                    'emoji' => $latestReaction->reaction_type,
                    'content' => $content
                ]),
                'id' => $latestReaction->message_id,
                'show_checkmarks' => false
            ];

        } elseif ($latestMessage) {
            $isOwn = $latestMessage->sender_id === $userId;
            $name = $isOwn ? __('chat.you') : ($latestMessage->sender->username ?? 'User');
            
            $content = strip_tags($latestMessage->content);
            $icon = '';
            
            if ($content === 'system_cleared') {
                return [
                    'text' => $isOwn ? __('chat.you_cleared_the_chat') : __('chat.cleared_the_chat', ['user' => $latestMessage->sender->username ?? 'User']),
                    'id' => $latestMessage->id,
                    'show_checkmarks' => false
                ];
            }
            
            if (str_starts_with($content, '{"__nexus_reply__":true')) {
                $replyData = json_decode($content, true);
                $content = $replyData['content'] ?? '';
                $icon = '↩ ';
            }
            
            if (empty($content) && $latestMessage->type !== 'text') {
                $content = match($latestMessage->type) {
                    'image' => __('chat.sent_photo'),
                    'video' => __('chat.sent_video'),
                    'voice' => __('chat.sent_voice_message'),
                    default => __('chat.sent_a_message'),
                };
            }
            
            $truncated = mb_substr($content, 0, 30);
            if (mb_strlen($content) > 30) $truncated .= '...';
            
            return [
                'text' => ($conversation->is_group ? ($name . ': ') : '') . $icon . $truncated,
                'id' => $latestMessage->id,
                'show_checkmarks' => $isOwn && !in_array($latestMessage->type, ['system']),
                'checkmark_class' => $latestMessage->read_at ? 'fa-check-double read' : ($latestMessage->delivered_at ? 'fa-check-double sent' : 'fa-check sent')
            ];
        }
        
        return [
            'text' => __('chat.no_messages_yet'),
            'show_checkmarks' => false
        ];
    }

    /**
     * Get all reactions for a message
     */
    public function getMessageReactions(Message $message)
    {
        $conversation = $message->conversation;
        if (!$conversation->isMember(auth()->id())) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $message->load('reactions.user');

        return response()->json([
            'success' => true,
            'reactions' => $message->getGroupedReactions(),
        ]);
    }

    /**
     * Get read and delivery info for a message
     */
    public function getMessageInfo(Message $message)
    {
        $conversation = $message->conversation;
        if (!$conversation->isMember(auth()->id())) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        // Only the author can see full info (optional, but standard in WhatsApp)
        // If the user wants everyone to see, I can remove this check.
        // The user said: "for the user message author click on it to see which users see this message"
        if ($message->sender_id !== auth()->id()) {
            return response()->json(['success' => false, 'error' => 'Only the author can see message info'], 403);
        }

        $message->load(['receipts.user', 'conversation.group.members.user']);

        $info = [
            'read' => [],
            'delivered' => [],
            'remaining' => []
        ];

        $receipts = $message->receipts->keyBy('user_id');
        $participants = $conversation->participants;

        foreach ($participants as $user) {
            if ($user->id === $message->sender_id) continue;
            
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ];

            if (!$conversation->is_group) {
                // For 1-1 chats, use the message columns directly
                if ($message->read_at) {
                    $userData['time'] = $message->read_at->toISOString();
                    $info['read'][] = $userData;
                } elseif ($message->delivered_at) {
                    $userData['time'] = $message->delivered_at->toISOString();
                    $info['delivered'][] = $userData;
                } else {
                    $info['remaining'][] = $userData;
                }
            } else {
                // For group chats, use the receipts
                $receipt = $receipts->get($user->id);
                
                if ($receipt && $receipt->read_at) {
                    $userData['time'] = $receipt->read_at->toISOString();
                    $info['read'][] = $userData;
                } elseif ($receipt && $receipt->delivered_at) {
                    $userData['time'] = $receipt->delivered_at->toISOString();
                    $info['delivered'][] = $userData;
                } else {
                    $info['remaining'][] = $userData;
                }
            }
        }

        return response()->json([
            'success' => true,
            'info' => $info
        ]);
    }
}
