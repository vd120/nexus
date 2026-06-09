<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    /**
     * Get the push notification service (with error suppression for GMP/BCMath warning).
     */
    protected function getPushService(): \App\Services\PushNotificationService
    {
        // Suppress the GMP/BCMath warning - library works without them
        return @app(\App\Services\PushNotificationService::class);
    }

    /**
     * Get VAPID public key.
     */
    public function getVapidKey(): JsonResponse
    {
        // Directly return config without initializing WebPush service
        $publicKey = config('services.vapid.public_key');
        $configured = !empty($publicKey);

        return response()->json([
            'public_key' => $publicKey,
            'configured' => $configured,
        ]);
    }

    /**
     * Store a new push subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
            'p256dh' => 'required|string',
            'auth' => 'required|string',
            'content_encoding' => 'nullable|string|in:aesgcm,aes128gcm',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthenticated'),
            ], 401);
        }

        // Check if subscription already exists
        $subscription = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $request->endpoint)
            ->first();

        if ($subscription) {
            // Update existing subscription
            $subscription->update([
                'p256dh' => $request->p256dh,
                'auth' => $request->auth,
                'content_encoding' => $request->content_encoding ?? 'aesgcm',
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.push_subscription_updated'),
                'subscription' => $subscription,
            ]);
        }

        // Create new subscription
        $subscription = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $request->endpoint,
            'p256dh' => $request->p256dh,
            'auth' => $request->auth,
            'content_encoding' => $request->content_encoding ?? 'aesgcm',
            'settings' => PushSubscription::getDefaultSettings(),
            'last_used_at' => now(),
        ]);

        Log::info('New push subscription created', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.push_subscription_created'),
            'subscription' => $subscription,
        ], 201);
    }

    /**
     * Update notification preferences.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.likes' => 'boolean',
            'settings.comments' => 'boolean',
            'settings.follows' => 'boolean',
            'settings.messages' => 'boolean',
            'settings.mentions' => 'boolean',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthenticated'),
            ], 401);
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.no_push_subscription'),
            ], 404);
        }

        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'settings' => array_merge(
                    $subscription->settings ?? PushSubscription::getDefaultSettings(),
                    $request->settings
                ),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.push_settings_updated'),
        ]);
    }

    /**
     * Get current notification preferences.
     */
    public function getSettings(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthenticated'),
            ], 401);
        }

        $subscription = PushSubscription::where('user_id', $user->id)->first();

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'subscribed' => false,
                'settings' => PushSubscription::getDefaultSettings(),
            ]);
        }

        return response()->json([
            'success' => true,
            'subscribed' => true,
            'settings' => $subscription->settings ?? PushSubscription::getDefaultSettings(),
        ]);
    }

    /**
     * Delete a push subscription.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthenticated'),
            ], 401);
        }

        $endpoint = $request->input('endpoint');

        if ($endpoint) {
            PushSubscription::where('user_id', $user->id)
                ->where('endpoint', $endpoint)
                ->delete();
        } else {
            PushSubscription::where('user_id', $user->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.push_subscription_deleted'),
        ]);
    }

    /**
     * Test push notification (for development).
     */
    public function test(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthenticated'),
            ], 401);
        }

        $pushService = $this->getPushService();
        
        if (!$pushService) {
            return response()->json([
                'success' => false,
                'message' => 'Push notification service not available. Please install BCMath or GMP PHP extension.',
            ], 503);
        }

        try {
            $sent = $pushService->sendToUser(
                $user,
                __('notifications.test_title'),
                __('notifications.test_body'),
                url('/notifications'),
                ['type' => 'test']
            );

            if ($sent) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.test_notification_sent'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('messages.no_push_subscription'),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Push notification test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
