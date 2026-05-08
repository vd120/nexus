<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\SavedPost;
use App\Models\Story;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\SocialGroup;
use App\Models\SocialGroupMember;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding sample data...');

        // Create sample users
        $users = $this->createSampleUsers();
        $this->command->info('✓ Created ' . count($users) . ' sample users');

        // Create posts
        $posts = $this->createPosts($users);
        $this->command->info('✓ Created ' . count($posts) . ' posts');

        // Create follows
        $this->createFollows($users);
        $this->command->info('✓ Created follow relationships');

        // Create likes
        $this->createLikes($users, $posts);
        $this->command->info('✓ Created likes');

        // Create comments
        $comments = $this->createComments($users, $posts);
        $this->command->info('✓ Created comments');

        // Create comment likes
        $this->createCommentLikes($users, $comments);
        $this->command->info('✓ Created comment likes');

        // Create saved posts
        $this->createSavedPosts($users, $posts);
        $this->command->info('✓ Created saved posts');

        // Create stories
        $this->createStories($users);
        $this->command->info('✓ Created stories');

        // Create groups (Chat Groups)
        $this->createGroups($users);
        $this->command->info('✓ Created chat groups');

        // Create communities (Social Groups)
        $this->createCommunities($users);
        $this->command->info('✓ Created social communities');

        // Create conversations and messages
        $this->createConversations($users);
        $this->command->info('✓ Created conversations and messages');

        $this->command->info('✅ Sample data seeding completed!');
        $this->command->info('');
        $this->command->info('Sample users (all with password: "password"):');
        foreach ($users as $user) {
            $this->command->info("  - {$user->email} ({$user->username})");
        }
    }

    /**
     * Create sample users
     */
    private function createSampleUsers(): array
    {
        $userData = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'username' => 'johndoe',
                'bio' => 'Software developer | Coffee enthusiast | Travel lover',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'username' => 'janesmith',
                'bio' => 'Photographer 📸 | Nature lover | Adventure seeker',
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@example.com',
                'username' => 'mikej',
                'bio' => 'Fitness trainer | Healthy living advocate',
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily@example.com',
                'username' => 'emilyd',
                'bio' => 'Artist | Designer | Creative soul',
            ],
            [
                'name' => 'Chris Wilson',
                'email' => 'chris@example.com',
                'username' => 'chrisw',
                'bio' => 'Musician | Guitar player | Music is life 🎵',
            ],
            [
                'name' => 'Sarah Brown',
                'email' => 'sarah@example.com',
                'username' => 'sarahb',
                'bio' => 'Food blogger | Chef | Exploring flavors worldwide',
            ],
            [
                'name' => 'David Lee',
                'email' => 'david@example.com',
                'username' => 'davidl',
                'bio' => 'Tech enthusiast | Gamer | Startup founder',
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa@example.com',
                'username' => 'lisaa',
                'bio' => 'Yoga instructor | Mindfulness coach | Peace seeker',
            ],
        ];

        $users = [];
        foreach ($userData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'last_active' => now(),
                    'is_online' => fake()->boolean(30),
                ]
            );

            // Create profile
            $user->profile()->firstOrCreate(
                [],
                [
                    'bio' => $data['bio'],
                    'is_private' => false,
                    'avatar' => null,
                ]
            );

            $users[] = $user;
        }

        return $users;
    }

    /**
     * Create posts for users
     */
    private function createPosts(array $users): array
    {
        $posts = [];
        $postContents = [
            'Just had an amazing day exploring the city! 🌆 #adventure #citylife',
            'Working on a new project. Excited to share more soon! 💻 #coding #developer',
            'Beautiful sunset today! Nature never fails to amaze me. 🌅 #sunset #nature',
            'Morning coffee hits different ☕ #morningvibes #coffee',
            'Just finished a great workout session! 💪 #fitness #motivation',
            'Trying out a new recipe today. Wish me luck! 👨‍🍳 #cooking #foodie',
            'Weekend vibes! Time to relax and recharge. 😌 #weekend #selfcare',
            'Just read an amazing book. Highly recommend it! 📚 #reading #bookworm',
            'New music alert! Been listening to this on repeat. 🎵 #music #newmusic',
            'Grateful for all the little things in life. 🙏 #gratitude #blessed',
            'Starting a new journey today! Wish me luck! 🚀 #newbeginnings #goals',
            'Nothing beats a good walk in the park. 🌳 #nature #wellness',
            'Late night coding session. The grind never stops! 🌙 #developer #hustle',
            'Family time is the best time! ❤️ #family #love',
            'Just discovered this amazing cafe! Must visit! ☕ #cafe #foodie',
        ];

        foreach ($users as $user) {
            $numPosts = fake()->numberBetween(3, 8);
            for ($i = 0; $i < $numPosts; $i++) {
                $post = Post::create([
                    'user_id' => $user->id,
                    'content' => fake()->randomElement($postContents),
                    'slug' => Str::random(24),
                    'is_private' => false,
                    'created_at' => now()->subDays(fake()->numberBetween(0, 30)),
                ]);
                $posts[] = $post;
            }
        }

        return $posts;
    }

    /**
     * Create follow relationships
     */
    private function createFollows(array $users): void
    {
        foreach ($users as $follower) {
            $followCount = fake()->numberBetween(2, count($users) - 1);
            $potentialFollows = array_filter($users, fn($u) => $u->id !== $follower->id);
            shuffle($potentialFollows);
            $toFollow = array_slice($potentialFollows, 0, $followCount);

            foreach ($toFollow as $followed) {
                Follow::firstOrCreate([
                    'follower_id' => $follower->id,
                    'followed_id' => $followed->id,
                ]);
            }
        }
    }

    /**
     * Create likes on posts
     */
    private function createLikes(array $users, array $posts): void
    {
        foreach ($posts as $post) {
            $likeCount = fake()->numberBetween(0, min(5, count($users)));
            $potentialLikers = array_filter($users, fn($u) => $u->id !== $post->user_id);
            shuffle($potentialLikers);
            $likers = array_slice($potentialLikers, 0, $likeCount);

            foreach ($likers as $user) {
                Like::firstOrCreate([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                ]);
            }
        }
    }

    /**
     * Create comments on posts
     */
    private function createComments(array $users, array $posts): array
    {
        $comments = [];
        $commentTexts = [
            'Great post! 👏',
            'Love this! ❤️',
            'So inspiring! 🙌',
            'Amazing! Keep it up!',
            'This is exactly what I needed to see today!',
            'Well said! 💯',
            'Beautiful! 😍',
            'Thanks for sharing!',
            'Couldn\'t agree more!',
            'This made my day! 😊',
        ];

        foreach ($posts as $post) {
            $commentCount = fake()->numberBetween(0, 4);
            for ($i = 0; $i < $commentCount; $i++) {
                $comment = Comment::create([
                    'user_id' => fake()->randomElement($users)->id,
                    'post_id' => $post->id,
                    'content' => fake()->randomElement($commentTexts),
                    'created_at' => now()->subDays(fake()->numberBetween(0, 20)),
                ]);
                $comments[] = $comment;
            }
        }

        return $comments;
    }

    /**
     * Create likes on comments
     */
    private function createCommentLikes(array $users, array $comments): void
    {
        foreach ($comments as $comment) {
            if (fake()->boolean(40)) {
                $liker = fake()->randomElement(array_filter($users, fn($u) => $u->id !== $comment->user_id));
                CommentLike::firstOrCreate([
                    'user_id' => $liker->id,
                    'comment_id' => $comment->id,
                ]);
            }
        }
    }

    /**
     * Create saved posts
     */
    private function createSavedPosts(array $users, array $posts): void
    {
        foreach ($users as $user) {
            $saveCount = fake()->numberBetween(0, 3);
            $potentialPosts = array_filter($posts, fn($p) => $p->user_id !== $user->id);
            shuffle($potentialPosts);
            $toSave = array_slice($potentialPosts, 0, $saveCount);

            foreach ($toSave as $post) {
                SavedPost::firstOrCreate([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                ]);
            }
        }
    }

    /**
     * Create stories
     */
    private function createStories(array $users): void
    {
        $storyContents = [
            'Check out this view! 🌄',
            'Having a great day! 😊',
            'New adventure begins! 🚀',
            'Coffee time ☕',
            'Workout done! 💪',
        ];

        shuffle($users);
        $storiesCount = fake()->numberBetween(2, count($users));
        $storyUsers = array_slice($users, 0, $storiesCount);

        foreach ($storyUsers as $user) {
            Story::create([
                'user_id' => $user->id,
                'media_type' => fake()->randomElement(['image', 'video']),
                'media_path' => 'stories/sample_' . Str::random(10) . '.jpg',
                'content' => fake()->randomElement($storyContents),
                'expires_at' => now()->addHours(24),
                'slug' => Str::random(24),
            ]);
        }
    }

    /**
     * Create groups
     */
    private function createGroups(array $users): void
    {
        $groups = [
            ['name' => 'Nexus Devs', 'description' => 'Official developer group for Nexus'],
            ['name' => 'General Chat', 'description' => 'Chat about anything and everything'],
        ];

        foreach ($groups as $groupData) {
            $group = Group::firstOrCreate(
                ['name' => $groupData['name']],
                [
                    'description' => $groupData['description'],
                    'creator_id' => fake()->randomElement($users)->id,
                    'is_private' => false,
                    'slug' => Str::slug($groupData['name']) . '-' . Str::random(5),
                    'invite_link' => Str::random(32),
                ]
            );

            // Add random members
            $memberCount = fake()->numberBetween(3, count($users));
            shuffle($users);
            $members = array_slice($users, 0, $memberCount);

            foreach ($members as $member) {
                GroupMember::firstOrCreate([
                    'group_id' => $group->id,
                    'user_id' => $member->id,
                ], [
                    'role' => $member->id === $group->creator_id ? 'admin' : 'member',
                ]);
            }
        }
    }

    private function createCommunities(array $users): void
    {
        $communities = [
            ['name' => 'Photography Enthusiasts', 'description' => 'Share your best photos and tips! 📸'],
            ['name' => 'Tech Talk', 'description' => 'Discuss the latest in technology 💻'],
            ['name' => 'Fitness Community', 'description' => 'Stay motivated and healthy together 💪'],
            ['name' => 'Food Lovers', 'description' => 'Share recipes and food experiences 🍕'],
        ];

        foreach ($communities as $communityData) {
            $group = SocialGroup::firstOrCreate(
                ['name' => $communityData['name']],
                [
                    'description' => $communityData['description'],
                    'slug' => Str::slug($communityData['name']),
                    'creator_id' => fake()->randomElement($users)->id,
                    'privacy_level' => 'public',
                ]
            );

            // Add random members
            $memberCount = fake()->numberBetween(2, min(5, count($users)));
            shuffle($users);
            $members = array_slice($users, 0, $memberCount);

            foreach ($members as $member) {
                SocialGroupMember::firstOrCreate([
                    'social_group_id' => $group->id,
                    'user_id' => $member->id,
                ], [
                    'role' => $member->id === $group->creator_id ? 'admin' : 'member',
                    'status' => 'approved',
                ]);
            }
        }
    }

    /**
     * Create conversations and messages
     */
    private function createConversations(array $users): void
    {
        // Create a few conversations between random users
        $conversationCount = min(3, count($users) - 1);

        for ($i = 0; $i < $conversationCount; $i++) {
            $user1 = $users[$i];
            $user2 = $users[$i + 1];

            $conversation = Conversation::firstOrCreate([
                'user1_id' => $user1->id,
                'user2_id' => $user2->id,
            ], [
                'slug' => Str::random(24),
            ]);

            // Add some messages
            $messages = [
                ['sender_id' => $user1->id, 'content' => 'Hey! How are you?'],
                ['sender_id' => $user2->id, 'content' => 'I\'m good! How about you?'],
                ['sender_id' => $user1->id, 'content' => 'Doing great, thanks for asking!'],
                ['sender_id' => $user2->id, 'content' => 'That\'s awesome! 😊'],
            ];

            foreach ($messages as $msgData) {
                Message::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $msgData['sender_id'],
                    'content' => $msgData['content'],
                ], [
                    'is_read' => true,
                ]);
            }
        }
    }
}
