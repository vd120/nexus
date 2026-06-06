<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\Message;
use App\Services\SocketEmitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallTimeoutJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $callId)
    {
    }

    public function handle(SocketEmitService $socketEmitService): void
    {
        $call = Call::find($this->callId);

        if (!$call || $call->status !== 'initiated') {
            return;
        }

        $call->update(['status' => 'missed']);

        // Notify caller (no answer) and callee (dismiss ringing screen)
        $socketEmitService->emitToUser($call->caller_id, 'call:no-answer', [
            'callId' => $call->id,
        ]);
        $socketEmitService->emitToUser($call->callee_id, 'call:no-answer', [
            'callId' => $call->id,
        ]);

        $systemMessage = Message::create([
            'conversation_id' => $call->conversation_id,
            'sender_id'       => $call->caller_id,
            'type'            => 'system',
            'content'         => __('messages.system_missed_call'),
        ]);

        // Push the system message to both parties' chat views in real-time
        $socketEmitService->emitToConversation($call->conversation_id, 'chat:message', [
            'id'              => $systemMessage->id,
            'conversation_id' => $call->conversation_id,
            'sender_id'       => $call->caller_id,
            'type'            => 'system',
            'content'         => $systemMessage->content,
            'created_at'      => $systemMessage->created_at->toISOString(),
            'sender'          => null,
        ]);
    }
}
