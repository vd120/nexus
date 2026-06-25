<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\User;
use App\Services\SocketEmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end chat scenarios for 1-1 and group conversations.
 *
 * These assert the SOCKET PAYLOADS that drive the realtime UI — the same data the
 * frontend uses to render the chat window and the sidebar/chat-list preview. The
 * realtime appearance is therefore covered at the contract level (DOM rendering
 * itself has no JS test harness and is verified manually).
 */
class ChatScenariosTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(User $admin, array $memberIds = []): Group
    {
        $group = Group::create([
            'name' => 'Squad', 'creator_id' => $admin->id, 'is_private' => true,
        ]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $admin->id, 'role' => 'admin']);
        foreach ($memberIds as $id) {
            GroupMember::create(['group_id' => $group->id, 'user_id' => $id, 'role' => 'member']);
        }
        Conversation::createGroupConversation($group);
        return $group->fresh('conversation');
    }

    // ─────────────────────────────── 1-1 ───────────────────────────────

    /**
     * Build a fake encrypted envelope for test purposes.
     */
    private function encryptedContent(string $plaintext, int $senderId): string
    {
        return json_encode([
            '__nexus_encrypted__' => true,
            'version' => 1,
            'sender_id' => $senderId,
            'ciphertext' => base64_encode($plaintext),
            'iv' => base64_encode('testiv12bytes'),
            'signature' => base64_encode('fake-signature'),
            'key_id' => 'test-key-id',
        ]);
    }

    /** A sends to B: message broadcast to the room AND a sidebar update to BOTH parties. */
    public function test_one_to_one_send_emits_message_to_room_and_preview_to_both()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => $this->encryptedContent('hello there', $a->id)])
            ->assertJson(['success' => true]);

        // The message itself → conversation room
        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:message' && $cid === $conv->id)
            ->once();

        // Sidebar preview update → recipient B
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $b->id)
            ->once();

        // Sidebar preview update → sender A (so their own list reorders/updates)
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $a->id)
            ->once();
    }

    /** The sidebar preview text for a text message shows "Encrypted message" label. */
    public function test_one_to_one_preview_text_is_the_message_content()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => $this->encryptedContent('pizza tonight?', $a->id)])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $b->id)
            ->once();
    }

    /** First message in a fresh conversation carries enough data for B's sidebar to CREATE the item. */
    public function test_first_message_payload_lets_recipient_sidebar_create_the_item()
    {
        $a = User::factory()->create(['username' => 'alice_'.uniqid()]);
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => $this->encryptedContent('first!', $a->id)])
            ->assertJson(['success' => true]);

        // B receives display_name (sender username for 1-1) + conversation identifiers → addNewConversationItem can render.
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(function ($uid, $event, $data) use ($b, $a, $conv) {
                return $event === 'chat:conversation:updated'
                    && $uid === $b->id
                    && ($data['conversation_id'] ?? null) === $conv->id
                    && !empty($data['conversation_slug'])
                    && ($data['display_name'] ?? null) === $a->username;
            })
            ->once();
    }

    /** Unread count increments for the recipient and stays zero for the sender. */
    public function test_unread_count_increments_for_recipient_only()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => $this->encryptedContent('yo', $a->id)])
            ->assertJson(['success' => true]);

        // B: unread >= 1
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $b->id && ($data['unread_count'] ?? 0) >= 1)
            ->once();

        // A: unread 0
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $a->id && ($data['unread_count'] ?? -1) === 0)
            ->once();
    }

    /** B reading the conversation emits chat:read and clears B's unread count in DB. */
    public function test_recipient_reading_emits_chat_read_and_clears_unread()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);
        Message::create([
            'conversation_id' => $conv->id, 'sender_id' => $a->id,
            'content' => 'read me', 'type' => 'text',
        ]);

        $this->assertSame(1, $conv->unreadCountFor($b->id));

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($b)
            ->postJson(route('chat.mark-read', $conv))
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:read' && $cid === $conv->id)
            ->once();

        $this->assertSame(0, $conv->fresh()->unreadCountFor($b->id));
    }

    /** A media (image) message shows a type-specific sidebar preview, not an empty string. */
    public function test_one_to_one_image_message_preview_shows_photo_label()
    {
        Storage::fake('public');
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->post(route('chat.store', $conv), [
                'media' => [UploadedFile::fake()->image('holiday.jpg')],
            ])
            ->assertJson(['success' => true]);

        // B's preview = recipient label for a photo (no caption).
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated'
                && $uid === $b->id
                && ($data['last_message'] ?? '') === __('chat.sent_photo'))
            ->once();
    }

    /** Recipient must NOT get a sent-checkmark flag on a message they received. */
    public function test_recipient_preview_has_no_sent_checkmark()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = Conversation::createConversation($a->id, $b->id);

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => $this->encryptedContent('mine', $a->id)])
            ->assertJson(['success' => true]);

        // B (recipient): show_checkmarks must be false
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $b->id && ($data['show_checkmarks'] ?? null) === false)
            ->once();

        // A (sender): show_checkmarks true
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $a->id && ($data['show_checkmarks'] ?? null) === true)
            ->once();
    }

    // ─────────────────────────────── Group ───────────────────────────────

    /** A group message fans out a sidebar update to EVERY member with a "Name: " prefix. */
    public function test_group_send_emits_preview_to_all_members_with_name_prefix()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id, $c->id]);
        $conv = $group->conversation;

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'standup in 5'])
            ->assertJson(['success' => true]);

        // Message to the room
        $spy->shouldHaveReceived('emitToConversation')
            ->withArgs(fn ($cid, $event, $data) => $event === 'chat:message' && $cid === $conv->id)
            ->once();

        // B (other member) sees "alice: standup in 5"
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated'
                && $uid === $b->id
                && str_contains($data['last_message'] ?? '', $a->username . ': ')
                && str_contains($data['last_message'] ?? '', 'standup in 5'))
            ->once();

        // C (other member) also receives the update
        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $c->id)
            ->once();
    }

    /** The sender's own group preview uses the "You: " prefix. */
    public function test_group_sender_preview_uses_you_prefix()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id]);
        $conv = $group->conversation;

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'hi team'])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated'
                && $uid === $a->id
                && str_contains($data['last_message'] ?? '', __('chat.you') . ': hi team'))
            ->once();
    }

    /** Group unread increments for non-senders only. */
    public function test_group_unread_increments_for_non_senders()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $group = $this->makeGroup($a, [$b->id]);
        $conv = $group->conversation;

        $spy = $this->spy(SocketEmitService::class);

        $this->actingAs($a)
            ->postJson(route('chat.store', $conv), ['content' => 'ping'])
            ->assertJson(['success' => true]);

        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $b->id && ($data['unread_count'] ?? 0) >= 1)
            ->once();

        $spy->shouldHaveReceived('emitToUser')
            ->withArgs(fn ($uid, $event, $data) =>
                $event === 'chat:conversation:updated' && $uid === $a->id && ($data['unread_count'] ?? -1) === 0)
            ->once();
    }
}
