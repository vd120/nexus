<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Group membership/settings actions each post a live system message (remove/role/leave/join/rename). */
class GroupLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(User $admin, array $memberIds = []): Group
    {
        $group = Group::create(['name' => 'Crew', 'creator_id' => $admin->id, 'is_private' => true]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin']);
        foreach ($memberIds as $id) {
            GroupMember::create(['group_id' => $group->id, 'user_id' => $id, 'role' => 'member']);
        }
        Conversation::createGroupConversation($group);
        return $group->fresh('conversation');
    }

    private function ajax()
    {
        return $this->withHeader('X-Requested-With', 'XMLHttpRequest');
    }

    private function assertSystemMessageEmitted($spy, Group $group): void
    {
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $group->conversation->id, 'type' => 'system',
        ]);
        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:message' && $cid === $group->conversation->id)
            ->atLeast()->once();
    }

    public function test_remove_member_posts_system_message_and_notifies_removed_user()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id, $c->id]);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)->ajax()
            ->deleteJson(route('groups.members.remove', [$group, $c->id]))
            ->assertJson(['success' => true]);

        $this->assertSystemMessageEmitted($spy, $group);

        // Removed user's sidebar is told to drop the conversation
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:deleted' && $uid === $c->id)
            ->once();
    }

    public function test_promote_member_to_admin_posts_system_message()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id]);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)->ajax()
            ->patchJson(route('groups.members.role', [$group, $b->id]), ['role' => 'admin'])
            ->assertJson(['success' => true]);

        $this->assertSystemMessageEmitted($spy, $group);
    }

    public function test_leaving_group_posts_system_message()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id]);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($b)->ajax()
            ->postJson(route('groups.leave', $group))
            ->assertJson(['success' => true]);

        $this->assertSystemMessageEmitted($spy, $group);
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id, 'user_id' => $b->id]);
    }

    public function test_joining_via_invite_posts_system_message()
    {
        $a = User::factory()->create();
        $newcomer = User::factory()->create();
        $group = $this->makeGroup($a);
        $group->update(['invite_link' => 'join-token-123']);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($newcomer)
            ->get(route('groups.join', 'join-token-123'))
            ->assertRedirect();

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $newcomer->id]);
        $this->assertSystemMessageEmitted($spy, $group);
    }

    public function test_renaming_group_posts_system_message()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id]);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)->ajax()
            ->patchJson(route('groups.update', $group), ['name' => 'Renamed Crew'])
            ->assertJson(['success' => true]);

        $this->assertSystemMessageEmitted($spy, $group);
    }
}
