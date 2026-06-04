<?php

namespace App\Http\Controllers;

use App\Models\GlobalMessage;
use App\Models\GlobalMessageReaction;
use App\Services\SocketEmitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlobalChatController extends Controller
{
    protected $socketService;

    public function __construct(SocketEmitService $socketService)
    {
        $this->socketService = $socketService;
    }

    public function index()
    {
        $messages = GlobalMessage::with(['user', 'reactions'])
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return view('chat.global', compact('messages'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string|max:1000',
            'reply_to' => 'nullable|integer',
            'media' => 'nullable|array|max:20',
            'media.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,mp4,mov,avi,webm|max:51200',
        ]);

        if (!$request->filled('content') && !$request->hasFile('media')) {
            return response()->json(['success' => false, 'message' => 'Message content or media is required'], 422);
        }

        $textContent = $request->content ?? '';
        $replyToData = null;
        $mediaItems = [];
        $type = 'text';

        if ($request->reply_to) {
            $parent = GlobalMessage::find($request->reply_to);
            if ($parent) {
                $parentContent = $parent->display_content;
                if ($parent->content === '---MESSAGE_DELETED---') {
                    $parentContent = 'This message was deleted';
                }
                
                $replyToData = [
                    'id' => $parent->id,
                    'username' => $parent->user->username,
                    'content' => \Illuminate\Support\Str::limit($parentContent, 50)
                ];
            }
        }

        if ($request->hasFile('media')) {
            $files = $request->file('media');
            if (!is_array($files)) $files = [$files];

            foreach ($files as $file) {
                $mimeType = $file->getMimeType();
                $path = $file->store('global-chat/media', 'public');
                $itemType = str_starts_with($mimeType, 'image/') ? 'image' : (str_starts_with($mimeType, 'video/') ? 'video' : 'file');
                $mediaItems[] = [
                    'type' => $itemType,
                    'path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
                if ($type === 'text') $type = $itemType;
            }
        }

        $finalContent = $textContent;
        if ($replyToData || !empty($mediaItems)) {
            $finalContent = json_encode([
                'reply_to' => $replyToData,
                'text' => $textContent,
                'media' => !empty($mediaItems) ? $mediaItems : null,
                'type' => $type
            ]);
        }

        $message = GlobalMessage::create([
            'user_id' => auth()->id(),
            'content' => $finalContent,
        ]);

        $payload = [
            'id' => $message->id,
            'conversation_id' => 'global-chat',
            'sender_id' => auth()->id(),
            'content' => $message->display_content,
            'reply_data' => $message->reply_data,
            'media' => $message->media,
            'type' => $message->type,
            'created_at' => $message->created_at->toISOString(),
            'sender' => [
                'id' => auth()->id(),
                'username' => auth()->user()->username,
                'name' => auth()->user()->name,
                'avatar_url' => auth()->user()->avatar_url,
                'is_verified' => (bool)auth()->user()->is_verified,
            ],
            'reactions' => []
        ];

        $this->socketService->emit('conversation:global-chat', 'chat:message', $payload);

        return response()->json($payload);
    }

    public function react(Request $request, GlobalMessage $message)
    {
        $request->validate([
            'reaction' => 'required|string',
        ]);

        $existing = GlobalMessageReaction::where([
            'global_message_id' => $message->id,
            'user_id' => auth()->id(),
        ])->first();

        if ($existing) {
            if ($existing->reaction === $request->reaction) {
                $existing->delete();
            } else {
                $existing->update(['reaction' => $request->reaction]);
            }
        } else {
            GlobalMessageReaction::create([
                'global_message_id' => $message->id,
                'user_id' => auth()->id(),
                'reaction' => $request->reaction,
            ]);
        }

        // Get updated counts and the list of user IDs who reacted (to keep UI in sync)
        $reactions = $message->reactions()
            ->get()
            ->groupBy('reaction')
            ->map(function ($group) {
                return [
                    'reaction' => $group->first()->reaction,
                    'count' => $group->count(),
                    'user_ids' => $group->pluck('user_id')->toArray(),
                ];
            })
            ->values();

        $payload = [
            'message_id' => $message->id,
            'conversation_id' => 'global-chat',
            'reactions' => $reactions,
        ];

        $this->socketService->emit('conversation:global-chat', 'chat:reaction', $payload);

        return response()->json($payload);
    }

    public function getReactions(GlobalMessage $message)
    {
        $reactions = $message->reactions()
            ->with('user')
            ->get()
            ->groupBy('reaction')
            ->map(function ($group) {
                return [
                    'reaction_type' => $group->first()->reaction,
                    'count' => $group->count(),
                    'users' => $group->map(function ($r) {
                        return [
                            'id' => $r->user->id,
                            'username' => $r->user->username,
                            'name' => $r->user->name,
                            'avatar_url' => $r->user->avatar_url,
                            'is_verified' => (bool) $r->user->is_verified,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'reactions' => $reactions
        ]);
    }

    public function destroy(GlobalMessage $message)
    {
        if ($message->user_id !== auth()->id() && !auth()->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $message->update([
            'content' => '---MESSAGE_DELETED---'
        ]);

        $payload = [
            'message_id' => $message->id,
            'conversation_id' => 'global-chat',
            'content' => '---MESSAGE_DELETED---'
        ];

        $this->socketService->emit('conversation:global-chat', 'chat:message:deleted', $payload);

        return response()->json(['success' => true]);
    }
    public function clear()
    {
        Log::info('Clear chat method reached by user: ' . auth()->id());
        if (!auth()->user()->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        GlobalMessage::query()->delete();

        $this->socketService->emit('conversation:global-chat', 'chat:cleared', [
            'conversation_id' => 'global-chat'
        ]);

        return response()->json(['success' => true]);
    }
}
