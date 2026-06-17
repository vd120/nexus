<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatDeliveredEmitTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_delivery_emits_only_to_conversation_room_not_user_room()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conv = Conversation::createConversation($sender->id, $recipient->id);
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $sender->id,
            'content' => 'hi',
            'type' => 'text',
        ]);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($recipient)
            ->postJson(route('chat.message-delivered'), ['message_id' => $msg->id])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:delivered')
            ->once();
        $spy->shouldNotHaveReceived('emitToUser',
            [\Mockery::any(), 'chat:delivered', \Mockery::any()]);
    }
}
