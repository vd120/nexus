<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Clear chat, delete conversation, and reactions (1-1). */
class ChatLifecycleScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_chat_emits_cleared_event_and_creates_system_message()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'old', 'type' => 'text']);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->deleteJson(route('chat.clear', $conv))
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:cleared' && $cid === $conv->id)
            ->once();

        // A system_cleared message now represents the conversation
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id, 'type' => 'system', 'content' => 'system_cleared',
        ]);
    }

    public function test_delete_one_to_one_conversation_emits_deleted_then_removes_it()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        $convId = $conv->id;

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->deleteJson(route('chat.delete-conversation', $conv))
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:conversation:deleted' && $cid === $convId)
            ->once();

        $this->assertDatabaseMissing('conversations', ['id' => $convId]);
    }

    public function test_reaction_emits_chat_reaction_and_updates_sidebar_preview()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        $msg = Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'nice', 'type' => 'text']);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($b)
            ->postJson(route('chat.message.react', $msg), ['reaction_type' => '👍'])
            ->assertJson(['success' => true]);

        // Reaction broadcast to the room
        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:reaction' && $cid === $conv->id)
            ->once();

        // Sidebar preview refresh with no_reorder (reactions don't bump to top)
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && ($data['no_reorder'] ?? false) === true)
            ->atLeast()->once();
    }

    public function test_reaction_notifies_the_message_owner()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        $msg = Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'nice', 'type' => 'text']);

        $this->actingAs($b)
            ->postJson(route('chat.message.react', $msg), ['reaction_type' => '❤️'])
            ->assertJson(['success' => true]);

        // Owner A gets a chat_reaction notification; reactor B does not
        $this->assertGreaterThanOrEqual(1, Notification::where('user_id', $a->id)->where('type', 'chat_reaction')->count());
        $this->assertSame(0, Notification::where('user_id', $b->id)->where('type', 'chat_reaction')->count());
    }

    public function test_reacting_to_own_message_creates_no_notification()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        $msg = Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'self', 'type' => 'text']);

        $this->actingAs($a)
            ->postJson(route('chat.message.react', $msg), ['reaction_type' => '😂'])
            ->assertJson(['success' => true]);

        $this->assertSame(0, Notification::where('type', 'chat_reaction')->count());
    }
}
