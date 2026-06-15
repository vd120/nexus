<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SocialGroup;
use App\Models\SocialGroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityModerationTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunity(User $admin, array $attrs = []): SocialGroup
    {
        $group = SocialGroup::create(array_merge([
            'name'                 => 'Test Community',
            'slug'                 => 'test-community-' . uniqid(),
            'creator_id'           => $admin->id,
            'privacy_level'        => 'public',
            'require_post_approval' => true,
        ], $attrs));

        SocialGroupMember::create([
            'social_group_id' => $group->id,
            'user_id'         => $admin->id,
            'role'            => 'admin',
            'status'          => 'approved',
        ]);

        return $group;
    }

    private function joinGroup(SocialGroup $group, User $user, string $role = 'member', string $status = 'approved'): void
    {
        SocialGroupMember::create([
            'social_group_id' => $group->id,
            'user_id'         => $user->id,
            'role'            => $role,
            'status'          => $status,
        ]);
    }

    private function makeGroupPost(User $author, SocialGroup $group, bool $approved = false): Post
    {
        return Post::create([
            'user_id'         => $author->id,
            'social_group_id' => $group->id,
            'content'         => 'Post pending approval',
            'is_approved'     => $approved,
            'is_private'      => false,
        ]);
    }

    public function test_admin_can_approve_pending_post(): void
    {
        $admin  = User::factory()->create();
        $member = User::factory()->create();
        $group  = $this->createCommunity($admin);
        $this->joinGroup($group, $member);
        $post = $this->makeGroupPost($member, $group, approved: false);

        $response = $this->actingAs($admin)
            ->post(route('communities.admin.posts.approve', [
                'slug'   => $group->slug,
                'postId' => $post->id,
            ]));

        $response->assertOk();
        $this->assertTrue((bool) $post->fresh()->is_approved);
    }

    public function test_non_admin_cannot_approve_post(): void
    {
        $admin      = User::factory()->create();
        $member     = User::factory()->create();
        $outsider   = User::factory()->create();
        $group      = $this->createCommunity($admin);
        $this->joinGroup($group, $member);
        $post = $this->makeGroupPost($member, $group, approved: false);

        $response = $this->actingAs($outsider)
            ->post(route('communities.admin.posts.approve', [
                'slug'   => $group->slug,
                'postId' => $post->id,
            ]));

        $response->assertForbidden();
        $this->assertFalse((bool) $post->fresh()->is_approved);
    }

    public function test_admin_can_reject_pending_post(): void
    {
        $admin  = User::factory()->create();
        $member = User::factory()->create();
        $group  = $this->createCommunity($admin);
        $this->joinGroup($group, $member);
        $post = $this->makeGroupPost($member, $group);

        $response = $this->actingAs($admin)
            ->post(route('communities.admin.posts.reject', [
                'slug'   => $group->slug,
                'postId' => $post->id,
            ]));

        $response->assertOk();
        $this->assertDatabaseMissing('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    public function test_pending_member_is_not_shown_as_approved(): void
    {
        $admin  = User::factory()->create();
        $group  = $this->createCommunity($admin);
        $joiner = User::factory()->create();
        $this->joinGroup($group, $joiner, status: 'pending');

        $membership = SocialGroupMember::where('social_group_id', $group->id)
            ->where('user_id', $joiner->id)
            ->first();

        $this->assertEquals('pending', $membership->status);
    }

    public function test_admin_can_approve_member(): void
    {
        $admin  = User::factory()->create();
        $group  = $this->createCommunity($admin);
        $joiner = User::factory()->create();
        $this->joinGroup($group, $joiner, status: 'pending');

        $response = $this->actingAs($admin)
            ->post(route('communities.admin.members.approve', [
                'slug'   => $group->slug,
                'userId' => $joiner->id,
            ]));

        $response->assertOk();
        $this->assertEquals('approved', SocialGroupMember::where([
            'social_group_id' => $group->id,
            'user_id'         => $joiner->id,
        ])->value('status'));
    }
}
