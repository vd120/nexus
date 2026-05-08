<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMessageInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_returns_user_objects_for_group_conversations()
    {
        $creator = User::factory()->create();
        $group = Group::create([
            'name' => 'Test Group',
            'creator_id' => $creator->id,
            'slug' => 'test-group',
        ]);
        
        $user = User::factory()->create();
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $conversation = Conversation::create([
            'is_group' => true,
            'group_id' => $group->id,
            'slug' => 'group-chat',
            'user1_id' => $creator->id,
        ]);

        $participants = $conversation->participants;

        $this->assertCount(1, $participants);
        $this->assertInstanceOf(User::class, $participants->first());
        $this->assertEquals($user->id, $participants->first()->id);
    }

    public function test_get_message_info_returns_success_for_group_message_author()
    {
        $author = User::factory()->create();
        $group = Group::create([
            'name' => 'Test Group 2',
            'creator_id' => $author->id,
            'slug' => 'test-group-2',
        ]);
        
        $member = User::factory()->create();

        GroupMember::create(['group_id' => $group->id, 'user_id' => $author->id, 'role' => 'admin', 'joined_at' => now()]);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

        $conversation = Conversation::create([
            'is_group' => true,
            'group_id' => $group->id,
            'slug' => 'group-chat-2',
            'user1_id' => $author->id,
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $author->id,
            'content' => 'Hello group',
            'type' => 'text',
        ]);

        $response = $this->actingAs($author)
            ->get(route('chat.message.info', $message));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
