<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\KeyStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class E2EKeyController extends Controller
{
    protected const RECOVERY_LIMIT = 10;
    protected const RECOVERY_DECAY_MINUTES = 60;

    public function __construct(private KeyStorageService $keyStorage) {}

    protected function checkRecoveryRateLimit(Request $request): ?JsonResponse
    {
        $key = 'e2e-recovery:' . $request->user()->id;

        if (RateLimiter::tooManyAttempts($key, self::RECOVERY_LIMIT)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => 'Too many recovery attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
                'retry_after_seconds' => $seconds,
            ], 429);
        }

        return null;
    }

    protected function trackRecoveryAttempt(Request $request): void
    {
        $key = 'e2e-recovery:' . $request->user()->id;
        RateLimiter::hit($key, self::RECOVERY_DECAY_MINUTES * 60);
    }

    public function registerKeys(Request $request): JsonResponse
    {
        $request->validate([
            'ecdh_public_key' => 'required|array',
            'ecdh_public_key.kty' => 'required|string|in:EC',
            'ecdh_public_key.crv' => 'required|string|in:P-256',
            'ecdh_public_key.x' => 'required|string',
            'ecdh_public_key.y' => 'required|string',
            'ecdsa_public_key' => 'required|array',
            'ecdsa_public_key.kty' => 'required|string|in:EC',
            'ecdsa_public_key.crv' => 'required|string|in:P-256',
            'ecdsa_public_key.x' => 'required|string',
            'ecdsa_public_key.y' => 'required|string',
        ]);

        $user = $request->user();
        $saved = $this->keyStorage->storePublicKeys($user->id, [
            'ecdh_public_key' => $request->ecdh_public_key,
            'ecdsa_public_key' => $request->ecdsa_public_key,
        ]);

        if (!$saved) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store public keys',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Public keys registered successfully',
        ]);
    }

    public function getPublicKeys(int $userId): JsonResponse
    {
        $keys = $this->keyStorage->getPublicKeys($userId);

        if (!$keys) {
            return response()->json([
                'success' => false,
                'message' => 'Public keys not found for this user',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user_id' => $keys['user_id'],
            'ecdh_public_key' => $keys['ecdh_public_key'],
            'ecdsa_public_key' => $keys['ecdsa_public_key'],
        ]);
    }

    public function uploadBackup(Request $request): JsonResponse
    {
        $request->validate([
            'ciphertext' => 'required|string',
            'salt' => 'required|string',
            'iv' => 'required|string',
        ]);

        $user = $request->user();
        $saved = $this->keyStorage->storeBackupKeys(
            $user->id,
            $request->ciphertext,
            $request->salt,
            $request->iv
        );

        if (!$saved) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save key backup',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Key backup saved successfully',
        ]);
    }

    public function checkBackupStatus(Request $request): JsonResponse
    {
        $exists = $this->keyStorage->getBackupKeys($request->user()->id) !== null;
        return response()->json(['exists' => $exists]);
    }

    public function getBackup(Request $request): JsonResponse
    {
        $rateLimit = $this->checkRecoveryRateLimit($request);
        if ($rateLimit) {
            return $rateLimit;
        }
        $this->trackRecoveryAttempt($request);

        $user = $request->user();
        $backup = $this->keyStorage->getBackupKeys($user->id);

        if (!$backup) {
            return response()->json([
                'success' => false,
                'message' => 'No key backup found for this user',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'ciphertext' => $backup['ciphertext'],
            'salt' => $backup['salt'],
            'iv' => $backup['iv'],
        ]);
    }

    public function resetKeys(Request $request): JsonResponse
    {
        $user = $request->user();
        $deleted = $this->keyStorage->deleteUserKeys($user->id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete E2E keys and backup',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'E2E keys and backup reset successfully',
        ]);
    }

    public function updateGroupKeys(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|integer',
            'keys' => 'required|array',
            'keys.*.user_id' => 'required|integer',
            'keys.*.key_id' => 'required|string',
            'keys.*.encrypted_key' => 'required|string',
            'keys.*.iv' => 'required|string',
        ]);

        $conversation = Conversation::find($request->conversation_id);
        if (!$conversation || !$conversation->isMember($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $memberIds = $conversation->participants->pluck('id')->toArray();

        $keysPerUser = [];
        foreach ($request->keys as $keyItem) {
            if (!in_array($keyItem['user_id'], $memberIds)) {
                return response()->json(['success' => false, 'message' => 'Target user is not a conversation member'], 403);
            }
            $keysPerUser[] = [
                'user_id' => $keyItem['user_id'],
                'key_data' => [
                    'key_id' => $keyItem['key_id'],
                    'encrypted_key' => $keyItem['encrypted_key'],
                    'iv' => $keyItem['iv'],
                    'sender_id' => $request->user()->id,
                    'created_at' => now()->getTimestampMs(),
                ],
            ];
        }

        $this->keyStorage->storeGroupKeysBatch($request->conversation_id, $keysPerUser);

        return response()->json([
            'success' => true,
            'message' => 'Group keys updated successfully',
        ]);
    }

    public function getGroupKeys(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $keys = $this->keyStorage->getGroupKeys($conversationId, $user->id);

        if (!$keys) {
            return response()->json([
                'success' => true,
                'conversation_id' => $conversationId,
                'encrypted_keys' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $keys['conversation_id'],
            'encrypted_keys' => $keys['encrypted_keys'] ?? [],
        ]);
    }

    public function reportMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message_id' => 'required|integer',
            'plaintext_content' => 'required|string|max:10000',
            'ciphertext' => 'required|string',
            'iv' => 'required|string',
            'signature' => 'required|string',
        ]);

        $message = Message::find($request->message_id);
        if (!$message || !$message->conversation->isMember($request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $reportId = \Illuminate\Support\Str::uuid()->toString();
        $reportData = [
            'id' => $reportId,
            'user_id' => $request->user()->id,
            'message_id' => $request->message_id,
            'reason' => 'e2e_abuse',
            'plaintext_content' => $request->plaintext_content,
            'ciphertext' => $request->ciphertext,
            'iv' => $request->iv,
            'signature' => $request->signature,
            'created_at' => now()->toDateTimeString(),
        ];

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            "reports/messages/{$reportId}.json",
            json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully to administrators',
        ], 201);
    }
}
