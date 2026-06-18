<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected WebPush $webPush;
    protected string $vapidPublicKey;
    protected string $vapidPrivateKey;
    protected string $vapidSubject;

    public function __construct()
    {
        $this->vapidPublicKey = config('services.vapid.public_key');
        $this->vapidPrivateKey = config('services.vapid.private_key');
        $this->vapidSubject = config('services.vapid.subject');

        // Check for required extensions
        if (!extension_loaded('gmp') && !extension_loaded('bcmath')) {
            Log::warning('Push notifications: BCMath or GMP extension required for VAPID key operations');
        }

        $this->webPush = new WebPush(
            [
                'VAPID' => [
                    'subject'    => $this->vapidSubject,
                    'publicKey'  => $this->vapidPublicKey,
                    'privateKey' => $this->vapidPrivateKey,
                ],
            ],
            [],
            10,
            ['connect_timeout' => 5]
        );
    }

    /**
     * Send a push notification to a user.
     */
    public function sendToUser(User $user, string $title, string $body, string $url = '/', array $data = []): bool
    {
        $subscriptions = $user->pushSubscriptions()->whereHas('user')->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $payload = $this->buildPayload($title, $body, $url, $data);
        $sent = false;

        foreach ($subscriptions as $subscription) {
            if (!$subscription->isValid()) {
                continue;
            }

            $pushSubscription = new Subscription(
                $subscription->endpoint,
                $subscription->p256dh,
                $subscription->auth,
                $subscription->content_encoding
            );

            $this->webPush->queueNotification($pushSubscription, $payload, [
                'urgency' => 'high',
                'TTL'     => 86400,
            ]);
            $sent = true;
        }

        // Send all queued notifications
        if ($sent) {
            $this->processQueue();
        }

        return $sent;
    }

    /**
     * Send a push notification to multiple users.
     */
    public function sendToUsers(array $users, string $title, string $body, string $url = '/', array $data = []): int
    {
        $sentCount = 0;

        foreach ($users as $user) {
            if ($this->sendToUser($user, $title, $body, $url, $data)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Send a push notification to all subscribers.
     */
    public function sendToAll(string $title, string $body, string $url = '/', array $data = []): int
    {
        $subscriptions = PushSubscription::all();
        $payload = $this->buildPayload($title, $body, $url, $data);
        $sentCount = 0;

        foreach ($subscriptions as $subscription) {
            if (!$subscription->isValid()) {
                continue;
            }

            $pushSubscription = new Subscription(
                $subscription->endpoint,
                $subscription->p256dh,
                $subscription->auth,
                $subscription->content_encoding
            );

            $this->webPush->queueNotification($pushSubscription, $payload, [
                'urgency' => 'high',
                'TTL'     => 86400,
            ]);
            $sentCount++;
        }

        $this->processQueue();

        return $sentCount;
    }

    /**
     * Build the notification payload.
     */
    protected function buildPayload(string $title, string $body, string $url, array $data = []): string
    {
        return json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => asset('images/icons/nexus-icon-192.png') . '?v=3',
            'badge' => asset('images/icons/nexus-notification-badge.png'),
            'tag' => $data['tag'] ?? 'nexus-notification',
            'requireInteraction' => $data['require_interaction'] ?? false,
            'silent' => $data['silent'] ?? false,
            'data' => array_merge([
                'url' => $url,
                'timestamp' => now()->timestamp,
            ], $data),
        ]);
    }

    /**
     * Process the notification queue.
     */
    protected function processQueue(): void
    {
        $reports = [];

        foreach ($this->webPush->flush() as $report) {
            $reports[] = $report;

            if ($report->isSuccess()) {
                Log::info('Push notification sent successfully', [
                    'endpoint' => $report->getEndpoint(),
                ]);
            } elseif ($report->isSubscriptionExpired()) {
                Log::warning('Push subscription expired', [
                    'endpoint' => $report->getEndpoint(),
                ]);
                // Delete expired subscription
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
            } else {
                Log::error('Push notification failed', [
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }

    /**
     * Get VAPID public key for frontend.
     */
    public function getVapidPublicKey(): string
    {
        return $this->vapidPublicKey;
    }

    /**
     * Check if push notifications are configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->vapidPublicKey) && 
               !empty($this->vapidPrivateKey) && 
               !empty($this->vapidSubject);
    }
}
