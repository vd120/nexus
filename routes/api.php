<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
\Log::info('API routes file loaded');
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushNotificationController;

use App\Http\Controllers\SocialGroupController;
use App\Http\Controllers\SocialGroupAdminController;
use App\Http\Controllers\SocialGroupInviteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Social Group Routes
    Route::prefix('groups')->group(function () {
        Route::get('/', [SocialGroupController::class, 'index']);
        Route::post('/', [SocialGroupController::class, 'store']);
        Route::get('/{slug}', [SocialGroupController::class, 'show']);
        Route::post('/{slug}/join', [SocialGroupController::class, 'join']);
        Route::post('/{slug}/leave', [SocialGroupController::class, 'leave']);
        Route::patch('/{slug}/preferences', [SocialGroupController::class, 'updateUserPreferences']);

        // Admin Routes
        Route::prefix('{slug}/admin')->group(function () {
            Route::patch('/settings', [SocialGroupAdminController::class, 'updateSettings']);
            Route::post('/members/{userId}/approve', [SocialGroupAdminController::class, 'approveMember']);
            Route::post('/members/{userId}/reject', [SocialGroupAdminController::class, 'rejectMember']);
            Route::post('/members/{userId}/mute', [SocialGroupAdminController::class, 'muteMember']);
            Route::get('/reports', [SocialGroupAdminController::class, 'reports']);

            // Meta Management (Rules, Topics, Badges) — explicit CRUD
            Route::get('/rules',                [SocialGroupAdminController::class, 'apiIndexRules']);
            Route::post('/rules',               [SocialGroupAdminController::class, 'apiStoreRule']);
            Route::put('/rules/{id}',           [SocialGroupAdminController::class, 'apiUpdateRule']);
            Route::patch('/rules/{id}',         [SocialGroupAdminController::class, 'apiUpdateRule']);
            Route::delete('/rules/{id}',        [SocialGroupAdminController::class, 'apiDestroyRule']);

            Route::get('/topics',               [SocialGroupAdminController::class, 'apiIndexTopics']);
            Route::post('/topics',              [SocialGroupAdminController::class, 'apiStoreTopic']);
            Route::put('/topics/{id}',          [SocialGroupAdminController::class, 'apiUpdateTopic']);
            Route::patch('/topics/{id}',        [SocialGroupAdminController::class, 'apiUpdateTopic']);
            Route::delete('/topics/{id}',       [SocialGroupAdminController::class, 'apiDestroyTopic']);

            Route::get('/badges',               [SocialGroupAdminController::class, 'apiIndexBadges']);
            Route::post('/badges',              [SocialGroupAdminController::class, 'apiStoreBadge']);
            Route::put('/badges/{id}',          [SocialGroupAdminController::class, 'apiUpdateBadge']);
            Route::patch('/badges/{id}',        [SocialGroupAdminController::class, 'apiUpdateBadge']);
            Route::delete('/badges/{id}',       [SocialGroupAdminController::class, 'apiDestroyBadge']);
        });

        // Invites
        Route::post('/{slug}/invite/{userId}', [SocialGroupInviteController::class, 'invite']);
        Route::post('/invites/{inviteId}/accept', [SocialGroupInviteController::class, 'acceptInvite']);
        Route::post('/invites/{inviteId}/decline', [SocialGroupInviteController::class, 'declineInvite']);
    });
});

// Internal Socket.IO Status Updates
Route::post('/internal/user/status', function (Request $request) {
    $secret = config('services.socket.secret');
    if (str_starts_with($secret, 'base64:')) {
        $secret = base64_decode(substr($secret, 7));
    }

    $signature = $request->header('X-Hub-Signature-256');
    $payload = $request->getContent();
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

    if (!$signature || $signature !== $expectedSignature) {
        \Log::error('Socket internal auth failed', [
            'received' => $signature,
            'expected' => $expectedSignature,
            'payload' => $payload
        ]);
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $userId = $request->input('user_id');
    $status = $request->input('status'); // 'online' or 'offline'
    $action = $request->input('action'); // 'clear_all_online'

    if ($action === 'clear_all_online') {
        \App\Models\User::where('is_online', true)->update(['is_online' => false]);
        return response()->json(['success' => true, 'message' => 'All users marked offline']);
    }

    $user = \App\Models\User::find($userId);
    if ($user) {
        $updateData = [
            'is_online' => $status === 'online',
            'last_active' => now(),
        ];
        
        $user->update($updateData);
        
        // Get followers
        $followerIds = $user->followers()->pluck('follower_id')->toArray();
        
        // Get conversation partners
        $directPartners = \App\Models\Conversation::where('user1_id', $user->id)
            ->pluck('user2_id')->toArray();
        $directPartners2 = \App\Models\Conversation::where('user2_id', $user->id)
            ->pluck('user1_id')->toArray();
            
        // Merge and make unique
        $notifyUserIds = array_unique(array_merge($followerIds, $directPartners, $directPartners2));
        
        // Remove current user ID if somehow included
        $notifyUserIds = array_diff($notifyUserIds, [$user->id]);

        return response()->json([
            'success' => true,
            'notify_user_ids' => array_values($notifyUserIds),
            'last_active' => $user->last_active->toIso8601String(),
            'user_details' => [
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
            ]
        ]);
    }

    return response()->json(['error' => 'User not found'], 404);
});

Route::post('/internal/chat/delivered', function (Request $request) {
    $secret = config('services.socket.secret');
    if (str_starts_with($secret, 'base64:')) {
        $secret = base64_decode(substr($secret, 7));
    }

    $signature = $request->header('X-Hub-Signature-256');
    $payload = $request->getContent();
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

    if (!$signature || $signature !== $expectedSignature) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $messageId = $request->input('message_id');
    $userId = $request->input('user_id');

    $message = \App\Models\Message::find($messageId);
    if (!$message) return response()->json(['error' => 'Message not found'], 404);
    
    $now = now();
    $isAllDelivered = false;

    if ($message->conversation->is_group) {
        \App\Models\MessageReceipt::updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $userId],
            ['delivered_at' => $now]
        );
        $isAllDelivered = $message->updateDeliveryStatusIfAllDelivered();
    } else {
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

    $socketService = app(\App\Services\SocketEmitService::class);
    $socketService->emitToConversation($message->conversation_id, 'chat:delivered', $emitData);
    $socketService->emitToUser($message->sender_id, 'chat:delivered', $emitData);

    return response()->json(['success' => true]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'clearAll']);

    // Push Notification Routes
    Route::prefix('push')->group(function () {
        Route::post('/subscribe', [PushNotificationController::class, 'store']);
        Route::get('/settings', [PushNotificationController::class, 'getSettings']);
        Route::patch('/settings', [PushNotificationController::class, 'updateSettings']);
        Route::delete('/unsubscribe', [PushNotificationController::class, 'destroy']);
        Route::post('/test', [PushNotificationController::class, 'test']);
    });
});

// Public Push Routes
Route::get('/push/vapid-key', [PushNotificationController::class, 'getVapidKey']);

// Username availability check
Route::get('/check-username', [UserController::class, 'checkUsername']);
