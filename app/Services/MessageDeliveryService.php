<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageReceipt;

/**
 * Single source of truth for confirming a message's delivery to one recipient,
 * used by both the HTTP confirm-delivery route (app open) and the push job
 * (app closed). Keeps 1-1 and group receipt logic — and the chat:delivered
 * broadcast — identical across both entry points.
 */
class MessageDeliveryService
{
    public function __construct(private SocketEmitService $socket) {}

    /**
     * Mark $message delivered to $userId and broadcast chat:delivered.
     * Returns whether the message is now delivered to ALL recipients.
     */
    public function confirm(Message $message, int $userId): bool
    {
        $now = now();
        $isAllDelivered = false;

        if ($message->conversation->is_group) {
            MessageReceipt::updateOrCreate(
                ['message_id' => $message->id, 'user_id' => $userId],
                ['delivered_at' => $now]
            );
            // Global delivered_at only flips once EVERY recipient has received it.
            $isAllDelivered = (bool) $message->updateDeliveryStatusIfAllDelivered();
        } else {
            if (!$message->delivered_at) {
                $message->update(['delivered_at' => $now]);
            }
            $isAllDelivered = true;
        }

        $emitData = [
            'message_id'       => $message->id,
            'user_id'          => $userId,
            'conversation_id'  => $message->conversation_id,
            'delivered_at'     => $now->toISOString(),
            'is_all_delivered' => $isAllDelivered,
        ];

        // Emit to conversation room (sender has chat open) AND directly to sender's
        // user room (handles brief socket reconnects where sender left the conv room).
        $this->socket->emitToConversation($message->conversation_id, 'chat:delivered', $emitData);
        $this->socket->emitToUser($message->sender_id, 'chat:delivered', $emitData);

        return $isAllDelivered;
    }
}
