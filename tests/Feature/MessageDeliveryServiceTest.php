<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageDeliveryService;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Shared delivery-confirmation service used by the HTTP route AND the push job (app-closed path). */
class MessageDeliveryServiceTest extends TestCase
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

    public function test_one_to_one_confirm_sets_delivered_and_emits_all_delivered()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        $msg = Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'hi', 'type' => 'text']);

        $spy = $this->spy(SocketEmitService::class);

        $all = app(MessageDeliveryService::class)->confirm($msg, $b->id);

        $this->assertTrue($all);
        $this->assertNotNull($msg->fresh()->delivered_at);
        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) =>
                $event === 'chat:delivered' && ($data['is_all_delivered'] ?? false) === true)
            ->once();
    }

    public function test_group_confirm_partial_then_all()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id, $c->id]);
        $msg = Message::create(['conversation_id' => $group->conversation->id, 'sender_id' => $a->id, 'content' => 'hey', 'type' => 'text']);

        $svc = app(MessageDeliveryService::class);

        $allAfterB = $svc->confirm($msg, $b->id);
        $this->assertFalse($allAfterB, 'not all delivered after only B');
        $this->assertNull($msg->fresh()->delivered_at);

        $allAfterC = $svc->confirm($msg, $c->id);
        $this->assertTrue($allAfterC, 'all delivered after B and C');
        $this->assertNotNull($msg->fresh()->delivered_at);
    }
}
