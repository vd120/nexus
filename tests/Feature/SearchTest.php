<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Post;
use App\Models\SocialGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    // ── helpers ──────────────────────────────────────────────────────────────

    private function search(string $q, string $type = 'all', ?User $as = null): \Illuminate\Testing\TestResponse
    {
        $actor = $as ?? User::factory()->create();
        return $this->actingAs($actor)->getJson("/api/search?q={$q}&type={$type}");
    }

    // ── query length guard ────────────────────────────────────────────────────

    public function test_short_query_returns_empty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->getJson('/api/search?q=a')
            ->assertOk()
            ->assertJson(['success' => true, 'users' => [], 'posts' => [], 'communities' => []]);
    }

    // ── user search ───────────────────────────────────────────────────────────

    public function test_search_finds_user_by_name(): void
    {
        User::factory()->create(['name' => 'Unique Nexus Person', 'username' => 'uniquenexusperson']);

        $response = $this->search('Unique Nexus');
        $response->assertOk()->assertJsonPath('success', true);

        $usernames = collect($response->json('users'))->pluck('username');
        $this->assertTrue($usernames->contains('uniquenexusperson'));
    }

    public function test_search_finds_user_by_username(): void
    {
        User::factory()->create(['name' => 'John Doe', 'username' => 'findmeplease']);

        $response = $this->search('findmeplease');
        $usernames = collect($response->json('users'))->pluck('username');
        $this->assertTrue($usernames->contains('findmeplease'));
    }

    public function test_search_excludes_self(): void
    {
        $actor = User::factory()->create(['name' => 'Selfie User', 'username' => 'selfieuser']);

        $response = $this->actingAs($actor)->getJson('/api/search?q=selfieuser');
        $usernames = collect($response->json('users'))->pluck('username');
        $this->assertFalse($usernames->contains('selfieuser'));
    }

    public function test_search_excludes_blocked_users(): void
    {
        $actor   = User::factory()->create();
        $blocked = User::factory()->create(['name' => 'Blocked Person', 'username' => 'blockedperson99']);

        Block::create(['blocker_id' => $actor->id, 'blocked_id' => $blocked->id]);

        $response = $this->actingAs($actor)->getJson('/api/search?q=blockedperson99');
        $usernames = collect($response->json('users'))->pluck('username');
        $this->assertFalse($usernames->contains('blockedperson99'));
    }

    // ── post search ───────────────────────────────────────────────────────────

    public function test_search_finds_public_post_by_content(): void
    {
        $author = User::factory()->create();
        Post::create([
            'user_id'      => $author->id,
            'content'      => 'This is a uniquepostcontent test message',
            'slug'         => 'uniquepostcontent-slug',
            'is_private'   => false,
            'is_approved'  => true,
            'is_anonymous' => false,
        ]);

        $response = $this->search('uniquepostcontent');
        $response->assertOk();
        $this->assertNotEmpty($response->json('posts'));
    }

    public function test_search_excludes_unapproved_posts(): void
    {
        $author = User::factory()->create();
        Post::create([
            'user_id'      => $author->id,
            'content'      => 'unapprovedspecialterm content here',
            'slug'         => 'unapprovedspecialterm-slug',
            'is_private'   => false,
            'is_approved'  => false,
            'is_anonymous' => false,
        ]);

        $response = $this->search('unapprovedspecialterm');
        $this->assertEmpty($response->json('posts'));
    }

    public function test_search_excludes_anonymous_posts(): void
    {
        $author = User::factory()->create();
        Post::create([
            'user_id'      => $author->id,
            'content'      => 'anonspecialterm hidden content',
            'slug'         => 'anonspecialterm-slug',
            'is_private'   => false,
            'is_approved'  => true,
            'is_anonymous' => true,
        ]);

        $response = $this->search('anonspecialterm');
        $this->assertEmpty($response->json('posts'));
    }

    // ── community search ──────────────────────────────────────────────────────

    public function test_search_finds_public_community_by_name(): void
    {
        $creator = User::factory()->create();
        SocialGroup::create([
            'name'          => 'Unique Laravel Developers',
            'slug'          => 'unique-laravel-developers',
            'description'   => 'A group for laravel devs',
            'creator_id'    => $creator->id,
            'privacy_level' => 'public',
            'is_discoverable' => true,
        ]);

        $response = $this->search('Unique Laravel');
        $names = collect($response->json('communities'))->pluck('name');
        $this->assertTrue($names->contains('Unique Laravel Developers'));
    }

    public function test_search_excludes_private_undiscoverable_community(): void
    {
        $creator = User::factory()->create();
        SocialGroup::create([
            'name'            => 'SecretGroupXYZ',
            'slug'            => 'secret-group-xyz',
            'description'     => 'hidden group',
            'creator_id'      => $creator->id,
            'privacy_level'   => 'private',
            'is_discoverable' => false,
        ]);

        $response = $this->search('SecretGroupXYZ');
        $names = collect($response->json('communities'))->pluck('name');
        $this->assertFalse($names->contains('SecretGroupXYZ'));
    }

    // ── type filter ───────────────────────────────────────────────────────────

    public function test_type_users_returns_only_users_key(): void
    {
        User::factory()->create(['name' => 'TypeFilter User', 'username' => 'typefilteruser']);

        $response = $this->search('typefilteruser', 'users');
        $response->assertOk()->assertJsonStructure(['success', 'users']);
        // posts and communities are not in the response when type=users
        $this->assertEmpty($response->json('posts'));
        $this->assertEmpty($response->json('communities'));
    }
}
