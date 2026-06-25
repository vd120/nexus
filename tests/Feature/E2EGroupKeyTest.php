<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\KeyStorageService;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class E2EGroupKeyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member1;
    protected User $member2;
    protected Group $group;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->member1 = User::factory()->create();
        $this->member2 = User::factory()->create();

        $this->group = Group::create([
            'name' => 'Test Group',
            'creator_id' => $this->admin->id,
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
        ]);
        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member1->id,
            'role' => 'member',
        ]);

        $this->conversation = Conversation::createGroupConversation($this->group, $this->admin);
    }

    public function test_can_store_and_retrieve_group_keys(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/e2e/group-keys/update', [
                'conversation_id' => $this->conversation->id,
                'keys' => [
                    [
                        'user_id' => $this->admin->id,
                        'key_id' => 'test-key-id-1',
                        'encrypted_key' => 'base64encryptedkeydata',
                        'iv' => 'base64iv12bytes',
                    ],
                    [
                        'user_id' => $this->member1->id,
                        'key_id' => 'test-key-id-1',
                        'encrypted_key' => 'base64encryptedkeydata',
                        'iv' => 'base64iv12bytes',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_can_retrieve_group_keys_for_authenticated_user(): void
    {
        $service = app(KeyStorageService::class);
        $service->storeGroupKey(
            $this->conversation->id,
            $this->admin->id,
            [
                'key_id' => 'test-key-id-1',
                'encrypted_key' => 'base64encryptedkeydata',
                'iv' => 'base64iv12bytes',
                'created_at' => now()->getTimestampMs(),
            ]
        );

        $this->actingAs($this->admin)
            ->getJson("/api/e2e/group-keys/{$this->conversation->id}")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'conversation_id' => $this->conversation->id,
            ])
            ->assertJsonStructure([
                'encrypted_keys' => [
                    '*' => ['key_id', 'encrypted_key', 'iv', 'created_at'],
                ],
            ]);
    }

    public function test_group_keys_empty_when_none_stored(): void
    {
        $this->actingAs($this->admin)
            ->getJson("/api/e2e/group-keys/{$this->conversation->id}")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'encrypted_keys' => [],
            ]);
    }

    public function test_add_member_triggers_key_rotation_event(): void
    {
        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($this->admin)
            ->postJson(route('groups.members.add', $this->group), [
                'user_id' => $this->member2->id,
            ], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event) => $event === 'chat:e2e:group-key-rotation' && $cid === $this->conversation->id)
            ->once();
    }

    public function test_remove_member_triggers_key_rotation_event(): void
    {
        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($this->admin)
            ->deleteJson(route('groups.members.remove', [$this->group, $this->member1->id]), [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event) => $event === 'chat:e2e:group-key-rotation' && $cid === $this->conversation->id)
            ->once();
    }

    public function test_remove_member_deletes_their_group_keys(): void
    {
        $service = app(KeyStorageService::class);
        $service->storeGroupKey(
            $this->conversation->id,
            $this->member1->id,
            [
                'key_id' => 'old-key',
                'encrypted_key' => 'base64encrypted',
                'iv' => 'base64iv',
                'created_at' => now()->getTimestampMs(),
            ]
        );

        $this->actingAs($this->admin)
            ->deleteJson(route('groups.members.remove', [$this->group, $this->member1->id]), [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertJson(['success' => true]);

        $keys = $service->getGroupKeys($this->conversation->id, $this->member1->id);
        $this->assertNull($keys);
    }
}
