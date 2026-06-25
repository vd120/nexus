<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class KeyStorageService
{
    protected string $disk;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->disk = 'local';
        $this->cacheTtl = 86400;
    }

    public function storePublicKeys(int $userId, array $keys): bool
    {
        $path = "keys/users/{$userId}/public_keys.json";
        $data = array_merge($keys, [
            'user_id' => $userId,
            'created_at' => now()->getTimestampMs(),
        ]);

        $stored = Storage::disk($this->disk)->put($path, json_encode($data, JSON_UNESCAPED_SLASHES));
        if ($stored) {
            Cache::put("e2e:public_keys:{$userId}", $data, $this->cacheTtl);
        }
        return (bool) $stored;
    }

    public function getPublicKeys(int $userId): ?array
    {
        $cached = Cache::get("e2e:public_keys:{$userId}");
        if ($cached !== null) {
            return $cached;
        }

        $path = "keys/users/{$userId}/public_keys.json";
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $data = json_decode(Storage::disk($this->disk)->get($path), true);
        if ($data) {
            Cache::put("e2e:public_keys:{$userId}", $data, $this->cacheTtl);
        }
        return $data ?: null;
    }

    public function storeBackupKeys(int $userId, string $ciphertext, string $salt, string $iv): bool
    {
        $path = "keys/users/{$userId}/backup_private_key.json";
        $data = [
            'user_id' => $userId,
            'ciphertext' => $ciphertext,
            'salt' => $salt,
            'iv' => $iv,
            'created_at' => now()->getTimestampMs(),
        ];

        $stored = Storage::disk($this->disk)->put($path, json_encode($data, JSON_UNESCAPED_SLASHES));
        if ($stored) {
            Cache::put("e2e:backup_keys:{$userId}", $data, $this->cacheTtl);
        }
        return (bool) $stored;
    }

    public function getBackupKeys(int $userId): ?array
    {
        $cached = Cache::get("e2e:backup_keys:{$userId}");
        if ($cached !== null) {
            return $cached;
        }

        $path = "keys/users/{$userId}/backup_private_key.json";
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $data = json_decode(Storage::disk($this->disk)->get($path), true);
        if ($data) {
            Cache::put("e2e:backup_keys:{$userId}", $data, $this->cacheTtl);
        }
        return $data ?: null;
    }

    public function storeGroupKey(int $conversationId, int $userId, array $keyData): bool
    {
        $path = "keys/groups/{$conversationId}/members/{$userId}.json";
        $existing = $this->getGroupKeys($conversationId, $userId);
        $keys = $existing ? $existing['encrypted_keys'] : [];
        $keys[] = $keyData;

        $data = [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'encrypted_keys' => $keys,
        ];

        $stored = Storage::disk($this->disk)->put($path, json_encode($data, JSON_UNESCAPED_SLASHES));
        if ($stored) {
            Cache::forget("e2e:group_keys:{$conversationId}:{$userId}");
        }
        return (bool) $stored;
    }

    public function storeGroupKeysBatch(int $conversationId, array $keysPerUser): bool
    {
        foreach ($keysPerUser as $item) {
            $this->storeGroupKey($conversationId, $item['user_id'], $item['key_data']);
        }
        return true;
    }

    public function getGroupKeys(int $conversationId, int $userId): ?array
    {
        $cacheKey = "e2e:group_keys:{$conversationId}:{$userId}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $path = "keys/groups/{$conversationId}/members/{$userId}.json";
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        $data = json_decode(Storage::disk($this->disk)->get($path), true);
        if ($data) {
            Cache::put($cacheKey, $data, $this->cacheTtl);
        }
        return $data ?: null;
    }

    public function deleteUserKeys(int $userId): bool
    {
        $publicPath = "keys/users/{$userId}/public_keys.json";
        $backupPath = "keys/users/{$userId}/backup_private_key.json";

        $deleted = true;
        if (Storage::disk($this->disk)->exists($publicPath)) {
            $deleted = $deleted && Storage::disk($this->disk)->delete($publicPath);
        }
        if (Storage::disk($this->disk)->exists($backupPath)) {
            $deleted = $deleted && Storage::disk($this->disk)->delete($backupPath);
        }

        Cache::forget("e2e:public_keys:{$userId}");
        Cache::forget("e2e:backup_keys:{$userId}");

        return $deleted;
    }

    public function deleteGroupMemberKeys(int $conversationId, int $userId): bool
    {
        $path = "keys/groups/{$conversationId}/members/{$userId}.json";
        $deleted = true;
        if (Storage::disk($this->disk)->exists($path)) {
            $deleted = Storage::disk($this->disk)->delete($path);
        }
        Cache::forget("e2e:group_keys:{$conversationId}:{$userId}");
        return (bool) $deleted;
    }
}
