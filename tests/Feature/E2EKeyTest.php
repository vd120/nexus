<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KeyStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class E2EKeyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    public function test_user_can_register_public_keys(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/e2e/keys/register', [
                'ecdh_public_key' => [
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'x' => 'MKBCTGlcRqwY7aC7VtB4xKQxWqGqY3gzj0RfqFj6JzI',
                    'y' => '4Etl6SRW2YiLUrn5mGCm3G7Jz2i8jOfgGJX9YwZ7lQM',
                ],
                'ecdsa_public_key' => [
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'x' => 'hGo5K1S40Xx8G0kP0E1wN9yQWzF6vB2mR7dL4tY8cJk',
                    'y' => 'cJu2K69R3Dq8pAz5sW7xE4bN6mY0vC2fH9gL1tR5sX3w',
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_register_keys_fails_with_invalid_ecdsa_key(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/e2e/keys/register', [
                'ecdh_public_key' => [
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'x' => 'MKBCTGlcRqwY7aC7VtB4xKQxWqGqY3gzj0RfqFj6JzI',
                    'y' => '4Etl6SRW2YiLUrn5mGCm3G7Jz2i8jOfgGJX9YwZ7lQM',
                ],
                'ecdsa_public_key' => [
                    'kty' => 'RSA',
                    'crv' => 'P-256',
                    'x' => 'hGo5K1S40Xx8G0kP0E1wN9yQWzF6vB2mR7dL4tY8cJk',
                    'y' => 'cJu2K69R3Dq8pAz5sW7xE4bN6mY0vC2fH9gL1tR5sX3w',
                ],
            ])
            ->assertStatus(422);
    }

    public function test_can_fetch_public_keys_for_registered_user(): void
    {
        $service = app(KeyStorageService::class);
        $service->storePublicKeys($this->user->id, [
            'ecdh_public_key' => [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => 'MKBCTGlcRqwY7aC7VtB4xKQxWqGqY3gzj0RfqFj6JzI',
                'y' => '4Etl6SRW2YiLUrn5mGCm3G7Jz2i8jOfgGJX9YwZ7lQM',
            ],
            'ecdsa_public_key' => [
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => 'hGo5K1S40Xx8G0kP0E1wN9yQWzF6vB2mR7dL4tY8cJk',
                'y' => 'cJu2K69R3Dq8pAz5sW7xE4bN6mY0vC2fH9gL1tR5sX3w',
            ],
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/e2e/keys/{$this->user->id}")
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'user_id',
                'ecdh_public_key' => ['kty', 'crv', 'x', 'y'],
                'ecdsa_public_key' => ['kty', 'crv', 'x', 'y'],
            ]);
    }

    public function test_fetch_keys_returns_404_for_unregistered_user(): void
    {
        $otherUser = User::factory()->create();

        $this->actingAs($this->user)
            ->getJson("/api/e2e/keys/{$otherUser->id}")
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_upload_and_retrieve_backup_keys(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/e2e/keys/backup', [
                'ciphertext' => 'base64encryptedprivatekeydata',
                'salt' => 'base64salt12345678',
                'iv' => 'base64iv12345678',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'ciphertext' => 'base64encryptedprivatekeydata',
                'salt' => 'base64salt12345678',
                'iv' => 'base64iv12345678',
            ]);
    }

    public function test_get_backup_returns_404_when_no_backup_exists(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/e2e/keys/backup')
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_backup_validation_fails_with_missing_fields(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/e2e/keys/backup', [
                'ciphertext' => 'somedata',
            ])
            ->assertStatus(422);
    }

    public function test_register_keys_stores_files_in_keys_directory(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/e2e/keys/register', [
                'ecdh_public_key' => [
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'x' => 'MKBCTGlcRqwY7aC7VtB4xKQxWqGqY3gzj0RfqFj6JzI',
                    'y' => '4Etl6SRW2YiLUrn5mGCm3G7Jz2i8jOfgGJX9YwZ7lQM',
                ],
                'ecdsa_public_key' => [
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'x' => 'hGo5K1S40Xx8G0kP0E1wN9yQWzF6vB2mR7dL4tY8cJk',
                    'y' => 'cJu2K69R3Dq8pAz5sW7xE4bN6mY0vC2fH9gL1tR5sX3w',
                ],
            ])
            ->assertOk();

        Storage::disk('local')->assertExists("keys/users/{$this->user->id}/public_keys.json");
    }

    public function test_backup_stores_file_in_keys_directory(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/e2e/keys/backup', [
                'ciphertext' => 'base64encrypteddata',
                'salt' => 'base64salt12345678',
                'iv' => 'base64iv12345678',
            ])
            ->assertOk();

        Storage::disk('local')->assertExists("keys/users/{$this->user->id}/backup_private_key.json");
    }
}
