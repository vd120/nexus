<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Read-receipt privacy and reply-message preview formatting. */
class ChatPrivacyAndReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_receipts_disabled_suppresses_broadcast_to_others_but_still_updates_reader()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        // B disables read receipts
        Profile::updateOrCreate(['user_id' => $b->id], ['show_read_receipts' => false]);

        $conv = Conversation::createConversation($a->id, $b->id);
        Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'seen?', 'type' => 'text']);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($b)->postJson(route('chat.mark-read', $conv))->assertJson(['success' => true]);

        // No chat:read to the conversation room (privacy) ...
        $spy->shouldNotHaveReceived('emitToConversation', [\Mockery::any(), 'chat:read', \Mockery::any()]);
        // ... but the reader (B) still gets their own chat:read so their UI clears unread.
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) => $event === 'chat:read' && $uid === $b->id)
            ->once();
    }

    public function test_read_receipts_enabled_broadcasts_to_conversation()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        // Both default to receipts ON (no profile = default true)
        $conv = Conversation::createConversation($a->id, $b->id);
        Message::create(['conversation_id' => $conv->id, 'sender_id' => $a->id, 'content' => 'seen?', 'type' => 'text']);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($b)->postJson(route('chat.mark-read', $conv))->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:read' && $cid === $conv->id)
            ->once();
    }

    public function test_reply_message_preview_shows_reply_indicator()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $parent = Message::create(['conversation_id' => $conv->id, 'sender_id' => $b->id, 'content' => 'original', 'type' => 'text']);

        $spy = $this->spy(SocketEmitService::class);

        // A replies to B's message
        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'my reply', 'reply_to_id' => $parent->id])
            ->assertJson(['success' => true]);

        // B's sidebar preview shows the reply indicator + the reply content
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated'
                && $uid === $b->id
                && str_contains($data['last_message'] ?? '', '↩')
                && str_contains($data['last_message'] ?? '', 'my reply'))
            ->once();
    }
}
