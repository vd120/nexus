<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatConversationUpdatedCheckmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_includes_checkmark_class_in_conversation_updated_payload()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conv = Conversation::createConversation($sender->id, $recipient->id);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($sender)
            ->postJson(route('chat.store', $conv), ['content' => 'hello'])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToUser')->withArgs(function ($uid, $event, $data) {
            return $event === 'chat:conversation:updated'
                && array_key_exists('checkmark_class', $data);
        })->atLeast()->once();
    }
}
