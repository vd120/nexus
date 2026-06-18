<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = 3;
    public $timeout = 30;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public string $url,
        public array $data = [],
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        // Confirm message delivery before push attempt — delivery receipt is independent
        // of push success. The job being dispatched means the recipient has a registered
        // device. SW callback to /chat/confirm-delivery is unreliable when session expires
        // (app fully closed), so server-side is the primary delivery confirmation path.
        $this->tryConfirmDelivery();

        // Empty title = delivery-only dispatch (e.g. message type with no push subscription,
        // or muted conversation). Delivery is confirmed above; skip push entirely.
        if (empty($this->title) && empty($this->body)) return;

        if (!$user->pushSubscriptions()->exists()) return;

        $pushService = app(PushNotificationService::class);
        if (!$pushService->isConfigured()) return;

        $pushService->sendToUser($user, $this->title, $this->body, $this->url, $this->data);
    }

    private function tryConfirmDelivery(): void
    {
        if (($this->data['type'] ?? null) !== 'message' || empty($this->data['message_id'])) {
            return;
        }

        $message = \App\Models\Message::find($this->data['message_id']);
        if (!$message || $message->sender_id === $this->userId) return;

        $conversation = $message->conversation;
        if (!$conversation || !$conversation->isMember($this->userId)) return;

        app(\App\Services\MessageDeliveryService::class)->confirm($message, $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        \Log::warning('[Push] Job failed for user ' . $this->userId . ': ' . $e->getMessage());
    }
}
