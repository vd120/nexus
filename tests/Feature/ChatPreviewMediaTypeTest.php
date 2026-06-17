<?php

namespace Tests\Feature;

use App\Http\Controllers\ChatController;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatPreviewMediaTypeTest extends TestCase
{
    use RefreshDatabase;

    private function previewForType(string $type, string $mediaPath): array
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conv = Conversation::createConversation($sender->id, $recipient->id);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $sender->id,
            'content' => '',
            'type' => $type,
            'media_path' => $mediaPath,
        ]);

        $controller = app(ChatController::class);
        $ref = new \ReflectionMethod($controller, 'getConversationPreviewForUser');
        $ref->setAccessible(true);

        $this->actingAs($sender);

        return $ref->invoke($controller, $conv->fresh(), $sender->id);
    }

    public function test_document_preview_uses_document_label()
    {
        $preview = $this->previewForType('document', 'chat/files/x.pdf');

        $this->assertStringContainsString(trim(__('chat.sent_document'), '.'), $preview['text']);
        $this->assertNotEquals(__('chat.sent_a_message'), $preview['text']);
    }

    public function test_gif_preview_uses_gif_label()
    {
        $preview = $this->previewForType('gif', 'chat/files/x.gif');

        $this->assertStringContainsString(trim(__('chat.sent_gif'), '.'), $preview['text']);
        $this->assertNotEquals(__('chat.sent_a_message'), $preview['text']);
    }
}
