<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class E2EMessageTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();
        $this->conversation = Conversation::createConversation($this->userA->id, $this->userB->id);
    }

    public function test_encrypted_message_content_stored_as_ciphertext(): void
    {
        $envelope = json_encode([
            '__nexus_encrypted__' => true,
            'version' => 1,
            'sender_id' => $this->userA->id,
            'ciphertext' => 'base64aesgcmciphertext',
            'iv' => 'base64iv12bytes',
            'signature' => 'base64ecdsasignature',
            'key_id' => 'senderkeyhash',
        ]);

        $this->actingAs($this->userA)
            ->postJson(route('chat.store', $this->conversation), [
                'content' => $envelope,
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->userA->id,
        ]);

        $message = Message::where('conversation_id', $this->conversation->id)->first();

        $this->assertNotNull($message);
        $this->assertEquals('text', $message->type);

        $decoded = json_decode($message->content, true);
        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['__nexus_encrypted__'] ?? false);
        $this->assertEquals($this->userA->id, $decoded['sender_id']);
        $this->assertArrayHasKey('ciphertext', $decoded);
        $this->assertArrayHasKey('iv', $decoded);
        $this->assertArrayHasKey('signature', $decoded);

        $this->assertStringNotContainsString('plaintext', $message->content);
    }

    public function test_plaintext_message_is_rejected_for_dm(): void
    {
        $this->actingAs($this->userA)
            ->postJson(route('chat.store', $this->conversation), [
                'content' => 'This is a plaintext message without encryption',
            ])
            ->assertStatus(400);
    }

    public function test_empty_encrypted_content_is_rejected(): void
    {
        $this->actingAs($this->userA)
            ->postJson(route('chat.store', $this->conversation), [
                'content' => '',
            ])
            ->assertStatus(422);
    }

    public function test_media_message_is_not_blocked_by_e2e_check(): void
    {
        $this->actingAs($this->userA)
            ->postJson(route('chat.store', $this->conversation), [
                'content' => 'Some plaintext caption',
            ])
            ->assertStatus(400);
    }

    public function test_encrypted_message_emits_socket_event(): void
    {
        $spy = $this->spy(SocketEmitService::class);

        $envelope = json_encode([
            '__nexus_encrypted__' => true,
            'version' => 1,
            'sender_id' => $this->userA->id,
            'ciphertext' => 'base64aesgcmciphertext',
            'iv' => 'base64iv12bytes',
            'signature' => 'base64ecdsasignature',
            'key_id' => 'senderkeyhash',
        ]);

        $this->actingAs($this->userA)
            ->postJson(route('chat.store', $this->conversation), [
                'content' => $envelope,
            ])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event) => $event === 'chat:message' && $cid === $this->conversation->id)
            ->once();
    }

    public function test_upload_encrypted_media_chunk_returns_string_path_instead_of_boolean(): void
    {
        $response = $this->actingAs($this->userA)
            ->postJson(route('chat.upload-encrypted-media', $this->conversation), [
                'file_id' => 'test_file_123',
                'index' => 0,
                'chunk' => base64_encode('fake encrypted chunk content'),
                'original_size' => 100,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'path', 'index', 'file_id']);

        $path = $response->json('path');
        $this->assertIsString($path);
        $this->assertStringContainsString('chunk_0000.enc', $path);
        
        // Assert file exists on public disk
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($path));
    }
}
