<?php

namespace App\Http\Controllers;

use App\Jobs\CallTimeoutJob;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Services\SocketEmitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallController extends Controller
{
    public function __construct(
        private SocketEmitService $socketEmitService,
        private PushNotificationService $pushNotificationService,
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'callee_id'       => 'required|exists:users,id',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        $myId     = Auth::id();
        $calleeId = (int) $request->callee_id;
        $me       = Auth::user();

        // Auto-expire calls stuck in initiated/accepted for more than 5 minutes
        // (handles server restarts and network failures that skip the normal end flow)
        Call::whereIn('status', ['initiated', 'accepted'])
            ->where('created_at', '<', now()->subMinutes(5))
            ->update(['status' => 'ended', 'ended_at' => now()]);

        // Prevent calling yourself
        if ($myId === $calleeId) {
            return response()->json(['error' => 'invalid'], 422);
        }

        // Check if callee or caller is already in an active call (within last 5 minutes only —
        // prevents stale records from server restarts blocking new calls indefinitely)
        $busy = Call::where(function ($q) use ($calleeId) {
                $q->where('caller_id', $calleeId)->orWhere('callee_id', $calleeId);
            })->whereIn('status', ['initiated', 'accepted'])
              ->where('created_at', '>=', now()->subMinutes(5))
              ->exists()
            || Call::where(function ($q) use ($myId) {
                $q->where('caller_id', $myId)->orWhere('callee_id', $myId);
            })->whereIn('status', ['initiated', 'accepted'])
              ->where('created_at', '>=', now()->subMinutes(5))
              ->exists();

        if ($busy) {
            return response()->json(['error' => 'busy'], 409);
        }

        // Resolve or create conversation
        if ($request->filled('conversation_id')) {
            $conversation = Conversation::findOrFail($request->conversation_id);
            // Verify caller actually belongs to this conversation
            if ($conversation->user1_id !== $myId && $conversation->user2_id !== $myId) {
                abort(403);
            }
        } else {
            $conversation = Conversation::where(function ($q) use ($myId, $calleeId) {
                $q->where('user1_id', $myId)->where('user2_id', $calleeId);
            })->orWhere(function ($q) use ($myId, $calleeId) {
                $q->where('user1_id', $calleeId)->where('user2_id', $myId);
            })->where('is_group', false)->first();

            if (!$conversation) {
                $conversation = Conversation::createConversation($myId, $calleeId);
            }
        }

        // Create the call record
        $call = Call::create([
            'caller_id'       => $myId,
            'callee_id'       => $calleeId,
            'conversation_id' => $conversation->id,
            'status'          => 'initiated',
        ]);

        // Notify callee via socket (real-time, if page is open)
        $this->socketEmitService->emitToUser($calleeId, 'call:incoming', [
            'callId'          => $call->id,
            'callerId'        => $me->id,
            'callerName'      => $me->name,
            'callerUsername'  => $me->username,
            'callerVerified'  => (bool) $me->is_verified,
            'callerAvatar'    => $me->avatar_url,
            'conversationId'  => $conversation->id,
        ]);

        // Notify callee via push notification — works even when screen is locked,
        // browser is in background, or page hasn't been interacted with yet.
        // This triggers the OS ring tone + screen wake on mobile.
        try {
            $callee = User::find($calleeId);
            $conversationUrl = route('chat.show', $conversation->slug ?? $conversation->id);
            $this->pushNotificationService->sendToUser(
                $callee,
                __('messages.incoming_call'),
                $me->name,
                $conversationUrl,
                [
                    'type'                => 'call',
                    'tag'                 => 'incoming-call-' . $call->id,
                    'require_interaction' => true,   // notification stays until dismissed
                    'silent'              => false,
                    'callId'              => $call->id,
                    'callerId'            => $me->id,
                    'callerName'          => $me->name,
                    'callerAvatar'        => $me->avatar_url,
                ]
            );
        } catch (\Exception $e) {
            // Push failing must never block the call — log and continue
            \Log::warning('CallController: push notification failed', ['error' => $e->getMessage()]);
        }

        // Schedule timeout job — only when queue can actually delay jobs.
        // On the sync driver delay is ignored (job runs instantly), so we skip it
        // and let the caller's browser handle the 30-second no-answer timeout instead.
        if (config('queue.default') !== 'sync') {
            CallTimeoutJob::dispatch($call->id)->delay(now()->addSeconds(30));
        }

        $callee = User::find($calleeId);

        return response()->json([
            'callId'          => $call->id,
            'conversationId'  => $conversation->id,
            'calleeName'      => $callee?->name,
            'calleeUsername'  => $callee?->username,
            'calleeVerified'  => (bool) $callee?->is_verified,
            'calleeAvatar'    => $callee?->avatar_url,
        ]);
    }

    public function accept(Request $request, Call $call): JsonResponse
    {
        if ($call->callee_id !== Auth::id()) {
            abort(403);
        }

        $call->refresh();

        if ($call->status !== 'initiated') {
            return response()->json(['error' => 'call_not_ringing'], 422);
        }

        $call->update([
            'status'     => 'accepted',
            'started_at' => now(),
        ]);

        $this->socketEmitService->emitToUser($call->caller_id, 'call:accepted', [
            'callId' => $call->id,
        ]);

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, Call $call): JsonResponse
    {
        if ($call->callee_id !== Auth::id()) {
            abort(403);
        }

        $call->refresh();

        if ($call->status !== 'initiated') {
            return response()->json(['error' => 'call_not_ringing'], 422);
        }

        $call->update(['status' => 'rejected']);

        $this->socketEmitService->emitToUser($call->caller_id, 'call:rejected', [
            'callId' => $call->id,
        ]);

        $systemMessage = Message::create([
            'conversation_id' => $call->conversation_id,
            'sender_id'       => Auth::id(),
            'type'            => 'system',
            'content'         => __('messages.system_call_declined'),
        ]);
        $this->broadcastSystemMessage($systemMessage, $call->conversation_id);

        return response()->json(['success' => true]);
    }

    public function end(Request $request, Call $call): JsonResponse
    {
        $myId = Auth::id();

        if ($call->caller_id !== $myId && $call->callee_id !== $myId) {
            abort(403);
        }

        if (in_array($call->status, ['ended', 'rejected', 'missed'])) {
            return response()->json(['success' => true]);
        }

        $duration = 0;
        if ($call->started_at) {
            // diffInSeconds can return a negative float depending on operand order;
            // use abs() and floor() to get a clean non-negative integer
            $duration = max(0, (int) abs($call->started_at->diffInSeconds(now())));
        }

        $previousStatus = $call->status;

        $call->update([
            'status'   => 'ended',
            'ended_at' => now(),
            'duration' => $duration,
        ]);

        $otherUserId = ($call->caller_id === $myId) ? $call->callee_id : $call->caller_id;

        $this->socketEmitService->emitToUser($otherUserId, 'call:ended', [
            'callId'   => $call->id,
            'duration' => $duration,
        ]);

        $messageContent = $duration > 0
            ? __('messages.system_call_ended', ['duration' => $this->formatDuration($duration)])
            : __('messages.system_missed_call');

        if ($previousStatus !== 'rejected') {
            $systemMessage = Message::create([
                'conversation_id' => $call->conversation_id,
                'sender_id'       => $myId,
                'type'            => 'system',
                'content'         => $messageContent,
            ]);
            $this->broadcastSystemMessage($systemMessage, $call->conversation_id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Return any call currently ringing for the authenticated user (callee).
     * Used on page load to restore the incoming call modal after a refresh.
     */
    public function pending(Request $request): JsonResponse
    {
        $call = Call::where('callee_id', Auth::id())
            ->where('status', 'initiated')
            ->where('created_at', '>=', now()->subSeconds(30))
            ->with('caller:id,name')
            ->latest()
            ->first();

        if (!$call) {
            return response()->json(['pending' => false]);
        }

        $caller = $call->caller;

        return response()->json([
            'pending'        => true,
            'callId'         => $call->id,
            'callerId'       => $caller?->id,
            'callerName'     => $caller?->name,
            'callerAvatar'   => $caller?->avatar_url,
            'conversationId' => $call->conversation_id,
        ]);
    }

    public function checkAvailability(Request $request, User $user): JsonResponse
    {
        $busy = Call::where(function ($q) use ($user) {
            $q->where('caller_id', $user->id)->orWhere('callee_id', $user->id);
        })->whereIn('status', ['initiated', 'accepted'])->exists();

        return response()->json(['available' => !$busy]);
    }

    /**
     * Emit a system message to the conversation's socket room so the chat
     * page updates in real-time without a reload.
     */
    private function broadcastSystemMessage(Message $message, int $conversationId): void
    {
        $this->socketEmitService->emitToConversation($conversationId, 'chat:message', [
            'id'              => $message->id,
            'conversation_id' => $conversationId,
            'sender_id'       => $message->sender_id,
            'type'            => 'system',
            'content'         => $message->content,
            'created_at'      => $message->created_at->toISOString(),
            'sender'          => null,
        ]);
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs    = $seconds % 60;

        return "{$minutes}m {$secs}s";
    }
}
