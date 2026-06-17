<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatGetMessagesPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_messages_returns_before_id_window_with_full_fields()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        for ($i = 0; $i < 60; $i++) {
            Message::create([
                'conversation_id' => $conv->id, 'sender_id' => $a->id,
                'content' => "m{$i}", 'type' => 'text',
            ]);
        }

        // newest 50 by default
        $first = $this->actingAs($a)->getJson(route('chat.messages', $conv))->json('messages');
        $this->assertCount(50, $first);
        $this->assertArrayHasKey('sender_id', $first[0]);
        $this->assertArrayHasKey('delivered_at', $first[0]);
        $this->assertArrayHasKey('reactions', $first[0]);

        // older window before the oldest currently shown
        $oldestShownId = $first[0]['id'];
        $older = $this->actingAs($a)
            ->getJson(route('chat.messages', $conv) . '?before_id=' . $oldestShownId)
            ->json('messages');
        $this->assertNotEmpty($older);
        $this->assertLessThan($oldestShownId, $older[0]['id']);
    }
}
