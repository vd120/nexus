<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(User $admin, array $memberIds = []): Group
    {
        $group = Group::create([
            'name' => 'Test Group', 'creator_id' => $admin->id, 'is_private' => true,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin']);
        foreach ($memberIds as $id) {
            GroupMember::create(['group_id' => $group->id, 'user_id' => $id, 'role' => 'member']);
        }
        Conversation::createGroupConversation($group);
        return $group->fresh('conversation');
    }

    public function test_adding_member_creates_system_message_and_emits()
    {
        $admin = User::factory()->create();
        $existing = User::factory()->create();
        $newcomer = User::factory()->create();
        $group = $this->makeGroup($admin, [$existing->id]);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('groups.members.add', $group), ['user_id' => $newcomer->id])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $group->conversation->id,
            'type' => 'system',
        ]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:message')
            ->atLeast()->once();
    }

    public function test_deleting_group_emits_conversation_deleted()
    {
        $admin = User::factory()->create();
        $group = $this->makeGroup($admin);
        $convId = $group->conversation->id;

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->deleteJson(route('groups.destroy', $group))
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:conversation:deleted' && $cid === $convId)
            ->once();
    }
}
