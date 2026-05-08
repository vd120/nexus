<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sender_can_delete_message_for_everyone_and_record_is_soft_deleted()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conv = Conversation::createConversation($sender->id, $recipient->id);
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $sender->id,
            'content' => 'secret',
            'type' => 'text',
        ]);

        $this->assertDatabaseHas('messages', ['id' => $msg->id, 'deleted_by_sender' => false]);

        $response = $this->actingAs($sender)
            ->delete(route('chat.destroy', $msg), ['type' => 'everyone']);

        $response->assertJson([
            'success' => true,
            'deleted_message_id' => $msg->id,
            'delete_type' => 'everyone',
            'deleted_by_sender' => true,
        ]);

        $this->assertSoftDeleted('messages', ['id' => $msg->id]);
        $this->assertDatabaseHas('messages', ['id' => $msg->id, 'deleted_by_sender' => true]);
    }

    public function test_recipient_can_delete_message_for_me_only_and_message_hidden_for_them()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conv = Conversation::createConversation($sender->id, $recipient->id);
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $sender->id,
            'content' => 'hi there',
            'type' => 'text',
        ]);

        $this->assertNull($msg->deleted_for);

        $response = $this->actingAs($recipient)
            ->delete(route('chat.destroy', $msg), ['type' => 'me']);

        $response->assertJson([
            'success' => true,
            'deleted_message_id' => $msg->id,
            'delete_type' => 'me',
        ]);

        $fresh = Message::find($msg->id);
        $this->assertContains($recipient->id, $fresh->deleted_for);
        // message should still exist for other user and not be soft-deleted yet
        $this->assertFalse($fresh->deleted_by_sender);
        $this->assertNull($fresh->deleted_at);

        // sender deletes their own message for themselves - should not
        // affect the other user's view
        $senderMsg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $sender->id,
            'content' => 'own message',
            'type' => 'text',
        ]);

        $this->actingAs($sender)
            ->delete(route('chat.destroy', $senderMsg), ['type' => 'me'])
            ->assertJson(['success' => true, 'delete_type' => 'me']);

        $senderMsgFresh = Message::find($senderMsg->id);
        $this->assertFalse($senderMsgFresh->trashed());
        $this->assertNull($senderMsgFresh->deleted_at);
        $this->assertEquals([$sender->id], $senderMsgFresh->deleted_for);
    }
}
