<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketEmitService
{
    protected string $url;
    protected string $secret;

    public function __construct()
    {
        // Use config() instead of env() for better performance and reliability in Octane/FrankenPHP
        $this->url = config('services.socket.internal_url', 'http://127.0.0.1:3001');
        $this->secret = config('services.socket.secret', '');

        if (str_starts_with($this->secret, 'base64:')) {
            $this->secret = base64_decode(substr($this->secret, 7));
        }
    }

    /**
     * Emit a generic event to a room
     */
    public function emit(string $room, string $event, array $data = []): bool
    {
        if (empty($this->secret) || empty($this->url)) {
            Log::error('SocketEmitService: SOCKET_INTERNAL_SECRET or SOCKET_IO_URL not configured');
            return false;
        }

        $payload = [
            'room' => $room,
            'event' => $event,
            'data' => $data,
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $this->secret);

        try {
            $response = Http::withHeaders([
                'X-Hub-Signature-256' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->url . '/internal/emit', $payload);

            if ($response->failed()) {
                Log::error('SocketEmitService: Failed to emit event', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'room' => $room,
                    'event' => $event,
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('SocketEmitService: Exception while emitting event', [
                'error' => $e->getMessage(),
                'room' => $room,
                'event' => $event,
            ]);
            return false;
        }
    }

    /**
     * Emit an event to a specific user
     */
    public function emitToUser(int $userId, string $event, array $data = []): bool
    {
        return $this->emit("user:{$userId}", $event, $data);
    }

    /**
     * Emit the same event to many users in a single HTTP request (batch).
     * Replaces N individual emitToUser() calls with one round-trip.
     *
     * @param int[]|iterable $userIds
     */
    public function emitToUsers(iterable $userIds, string $event, array $data = []): bool
    {
        $rooms = [];
        foreach ($userIds as $id) {
            $rooms[] = "user:{$id}";
        }

        if (empty($rooms)) {
            return true;
        }

        if (empty($this->secret) || empty($this->url)) {
            return false;
        }

        $payload = ['rooms' => $rooms, 'event' => $event, 'data' => $data];
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $this->secret);

        try {
            $response = Http::withHeaders([
                'X-Hub-Signature-256' => $signature,
                'Content-Type'        => 'application/json',
                'Accept'              => 'application/json',
            ])->post($this->url . '/internal/emit-batch', $payload);

            return !$response->failed();
        } catch (\Exception $e) {
            \Log::error('SocketEmitService: emitToUsers failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Emit an event to a specific conversation
     */
    public function emitToConversation(int $conversationId, string $event, array $data = []): bool
    {
        return $this->emit("conversation:{$conversationId}", $event, $data);
    }

    /**
     * Emit a call-related event to a specific user
     */
    public function emitCallEvent(int $userId, string $event, array $data = []): bool
    {
        return $this->emitToUser($userId, $event, $data);
    }

    /**
     * Tell the socket server whether a user should appear in users:online bulk syncs
     */
    public function setUserVisibility(int $userId, bool $visible): bool
    {
        if (empty($this->secret) || empty($this->url)) return false;

        $payload = ['userId' => $userId, 'visible' => $visible];
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $this->secret);

        try {
            Http::withHeaders([
                'X-Hub-Signature-256' => $signature,
                'Content-Type' => 'application/json',
            ])->post($this->url . '/internal/set-visibility', $payload);
            return true;
        } catch (\Exception $e) {
            Log::error('SocketEmitService: setUserVisibility failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
