<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\MessageReceipt;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Delivery + read receipts for 1-1 and group, including partial vs all-members states. */
class ChatReceiptsTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(User $admin, array $memberIds = []): Group
    {
        $group = Group::create(['name' => 'G', 'creator_id' => $admin->id, 'is_private' => true]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin']);
        foreach ($memberIds as $id) {
            GroupMember::create(['group_id' => $group->id, 'user_id' => $id, 'role' => 'member']);
        }
        Conversation::createGroupConversation($group);
        return $group->fresh('conversation');
    }

    // ── 1-1 ──
    public function test_one_to_one_confirm_delivery_marks_delivered_and_emits_all_delivered()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        $msg = Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'hi', 'type' => 'text']);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($b)
            ->postJson(route('chat.message-delivered'), ['message_id' => $msg->id])
            ->assertJson(['success' => true]);

        $this->assertNotNull($msg->fresh()->delivered_at);
        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) =>
                $event === 'chat:delivered' && ($data['is_all_delivered'] ?? false) === true)
            ->once();
    }

    public function test_one_to_one_mark_read_sets_read_at()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        $msg = Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'hi', 'type' => 'text']);

        $this->actingAs($b)->postJson(route('chat.mark-read', $conv))->assertJson(['success' => true]);

        $this->assertNotNull($msg->fresh()->read_at);
    }

    // ── Group delivery ──
    public function test_group_delivery_partial_until_all_members_confirm()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id, $c->id]);
        $msg = Message::create(['conversation_id' => $group->conversation->id, 'sender_id' => $a->id, 'content' => 'hey', 'type' => 'text']);

        // Only B confirms → not all delivered yet
        $this->actingAs($b)->postJson(route('chat.message-delivered'), ['message_id' => $msg->id])->assertJson(['success' => true]);
        $this->assertDatabaseHas('message_receipts', ['message_id' => $msg->id, 'user_id' => $b->id]);
        $this->assertNull($msg->fresh()->delivered_at, 'message must not be globally delivered until all recipients confirm');

        // C confirms → now all recipients delivered
        $this->actingAs($c)->postJson(route('chat.message-delivered'), ['message_id' => $msg->id])->assertJson(['success' => true]);
        $this->assertNotNull($msg->fresh()->delivered_at, 'message globally delivered once every recipient confirms');
    }

    // ── Group read ──
    public function test_group_read_partial_until_all_members_read()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id, $c->id]);
        $msg = Message::create(['conversation_id' => $group->conversation->id, 'sender_id' => $a->id, 'content' => 'hey', 'type' => 'text']);

        // B reads → receipt, but not all read
        $this->actingAs($b)->postJson(route('chat.mark-read', $group->conversation))->assertJson(['success' => true]);
        $this->assertDatabaseHas('message_receipts', ['message_id' => $msg->id, 'user_id' => $b->id]);
        $this->assertNotNull(MessageReceipt::where(['message_id' => $msg->id, 'user_id' => $b->id])->first()->read_at);
        $this->assertNull($msg->fresh()->read_at, 'not globally read until all recipients read');

        // C reads → all read
        $this->actingAs($c)->postJson(route('chat.mark-read', $group->conversation))->assertJson(['success' => true]);
        $this->assertNotNull($msg->fresh()->read_at, 'globally read once every recipient reads');
    }
}
