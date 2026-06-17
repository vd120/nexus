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
        if (!$user || !$user->pushSubscriptions()->exists()) {
            return;
        }

        $pushService = app(PushNotificationService::class);
        if (!$pushService->isConfigured()) {
            return;
        }

        $sent = $pushService->sendToUser($user, $this->title, $this->body, $this->url, $this->data);

        // App-closed delivery: when a MESSAGE push is accepted by the gateway, the
        // message has reached this recipient's device. The service-worker callback to
        // /chat/confirm-delivery is unreliable when the app is fully closed (esp. iOS),
        // so confirm delivery here too. Idempotent with the SW path; only flips the
        // sender's checkmark to delivered (never read).
        if ($sent && ($this->data['type'] ?? null) === 'message' && !empty($this->data['message_id'])) {
            $message = \App\Models\Message::find($this->data['message_id']);
            if ($message && $message->sender_id !== $this->userId
                && $message->conversation && $message->conversation->isMember($this->userId)) {
                app(\App\Services\MessageDeliveryService::class)->confirm($message, $this->userId);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::warning('[Push] Job failed for user ' . $this->userId . ': ' . $e->getMessage());
    }
}
