<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KeyStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class E2ERecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Cache::flush();
        $this->user = User::factory()->create();
    }

    public function test_user_can_retrieve_backup_keys(): void
    {
        $service = app(KeyStorageService::class);
        $service->storeBackupKeys(
            $this->user->id,
            'testciphertext',
            'testsalt12345678',
            'testiv12345678'
        );

        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'ciphertext' => 'testciphertext',
                'salt' => 'testsalt12345678',
                'iv' => 'testiv12345678',
            ]);
    }

    public function test_recovery_rate_limit_is_enforced(): void
    {
        $service = app(KeyStorageService::class);
        $service->storeBackupKeys(
            $this->user->id,
            'testciphertext',
            'testsalt12345678',
            'testiv12345678'
        );

        // Exhaust the 10 attempts
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)
                ->getJson('/api/e2e/keys/backup')
                ->assertOk();
        }

        // The 11th attempt should be rate-limited
        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_recovery_rate_limit_resets_after_decay(): void
    {
        $service = app(KeyStorageService::class);
        $service->storeBackupKeys(
            $this->user->id,
            'testciphertext',
            'testsalt12345678',
            'testiv12345678'
        );

        $key = 'e2e-recovery:' . $this->user->id;

        // Exhaust attempts
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)
                ->getJson('/api/e2e/keys/backup')
                ->assertOk();
        }

        // 11th blocked
        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertStatus(429);

        // Simulate rate limit decay
        RateLimiter::clear($key);

        // After reset, should succeed again
        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertOk();
    }

    public function test_different_users_have_independent_rate_limits(): void
    {
        $service = app(KeyStorageService::class);
        $userB = User::factory()->create();

        $service->storeBackupKeys($this->user->id, 'data', 'salt', 'iv');
        $service->storeBackupKeys($userB->id, 'data', 'salt', 'iv');

        // Exhaust user A's attempts
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)
                ->getJson('/api/e2e/keys/backup')
                ->assertOk();
        }

        // User A should be rate-limited
        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertStatus(429);

        // User B should still be able to access
        $this->actingAs($userB)
            ->getJson('/api/e2e/keys/backup')
            ->assertOk();
    }

    public function test_recovery_returns_404_without_backup(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
