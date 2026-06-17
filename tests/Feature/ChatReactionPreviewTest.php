<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatReactionPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function preview(Conversation $conv, int $userId): array
    {
        $controller = app(\App\Http\Controllers\ChatController::class);
        $ref = new \ReflectionMethod($controller, 'getConversationPreviewForUser');
        $ref->setAccessible(true);
        return $ref->invoke($controller, $conv->fresh(), $userId);
    }

    public function test_self_reaction_does_not_become_the_preview()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $msg = Message::create([
            'conversation_id' => $conv->id, 'sender_id' => $a->id,
            'content' => 'hello world', 'type' => 'text',
        ]);
        $msg->forceFill(['created_at' => now()->subMinute()])->save();

        // A reacts to A's OWN message — reload suppresses this; realtime must too.
        MessageReaction::create([
            'user_id' => $a->id, 'message_id' => $msg->id, 'reaction_type' => '👍',
        ]);

        $preview = $this->preview($conv, $a->id);
        $this->assertStringContainsString('hello world', $preview['text']);
        $this->assertStringNotContainsString(__('chat.reacted_on_message', ['emoji' => '👍', 'content' => '']), $preview['text']);
    }

    public function test_other_party_reaction_in_one_to_one_has_no_name_prefix()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $msg = Message::create([
            'conversation_id' => $conv->id, 'sender_id' => $a->id,
            'content' => 'hi', 'type' => 'text',
        ]);
        $msg->forceFill(['created_at' => now()->subMinute()])->save();

        // B (the other party) reacts to A's message
        MessageReaction::create([
            'user_id' => $b->id, 'message_id' => $msg->id, 'reaction_type' => '❤️',
        ]);

        // From A's perspective, the 1-1 preview must NOT start with B's username.
        $preview = $this->preview($conv, $a->id);
        $this->assertStringNotContainsString($b->username . ' ', $preview['text']);
        $this->assertStringContainsString('❤️', $preview['text']);
    }
}
