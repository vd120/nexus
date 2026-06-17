<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Conversation;
use App\Models\ConversationMute;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Blocking and muting behavior in 1-1 chats. */
class ChatModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sender_blocking_recipient_cannot_send()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        Block::create(['blocker_id' => $a->id, 'blocked_id' => $b->id]);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'hi'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('messages', ['conversation_id' => $conv->id, 'content' => 'hi']);
    }

    public function test_recipient_blocking_sender_cannot_send()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        // B has blocked A; A tries to message B
        Block::create(['blocker_id' => $b->id, 'blocked_id' => $a->id]);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'hi'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_muted_conversation_creates_no_notification_for_recipient()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        // B mutes the conversation
        ConversationMute::create(['user_id' => $b->id, 'conversation_id' => $conv->id]);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'hi'])
            ->assertJson(['success' => true]);

        // Message still delivered, but NO notification row for the muted recipient
        $this->assertDatabaseHas('messages', ['conversation_id' => $conv->id, 'content' => 'hi']);
        $this->assertSame(0, Notification::where('user_id', $b->id)->where('type', 'message')->count());
    }

    public function test_unmuted_conversation_creates_a_notification()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'hi'])
            ->assertJson(['success' => true]);

        $this->assertGreaterThanOrEqual(1, Notification::where('user_id', $b->id)->where('type', 'message')->count());
    }
}
