<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 10); // Default 10 posts per page
        $page = $request->get('page', 1);

        // Get paginated posts, but filter based on privacy settings
        $posts = Post::with(['user', 'media', 'comments.replies.user', 'comments.likes'])
            ->approved()
            ->where(function($query) use ($user) {
                // Always show public posts from non-private accounts
                $query->where('is_private', false)
                      ->whereHas('user.profile', function($profileQuery) {
                          $profileQuery->where('is_private', false);
                      })
                // Or show private posts from non-private accounts that the user follows
                ->orWhere(function($subQuery) use ($user) {
                    $subQuery->where('is_private', true)
                             ->whereHas('user.profile', function($profileQuery) {
                                 $profileQuery->where('is_private', false);
                             })
                             ->whereHas('user.followers', function($followerQuery) use ($user) {
                                 $followerQuery->where('follower_id', $user->id);
                             });
                })
                // Or show posts from private accounts that the user follows (both account and post level)
                ->orWhere(function($subQuery) use ($user) {
                    $subQuery->whereHas('user.profile', function($profileQuery) {
                        $profileQuery->where('is_private', true);
                    })
                    ->whereHas('user.followers', function($followerQuery) use ($user) {
                        $followerQuery->where('follower_id', $user->id);
                    });
                })
                // Or show the user's own posts
                ->orWhere('user_id', $user->id);
            })
            // Exclude posts from users that blocked the current user or that the current user blocked
            ->whereDoesntHave('user.blockedBy', function($blockedByQuery) use ($user) {
                $blockedByQuery->where('blocker_id', $user->id);
            })
            ->whereDoesntHave('user.blockedUsers', function($blockedUsersQuery) use ($user) {
                $blockedUsersQuery->where('blocked_id', $user->id);
            })
            ->latest()
            ->paginate($perPage);

        // Process mentions in posts and comments
        $posts->getCollection()->transform(function ($post) {
            // Convert mentions in post content
            if ($post->content) {
                $post->content = app(\App\Services\MentionService::class)->convertMentionsToLinks($post->content);
            }

            if ($post->comments) {
                $post->comments->transform(function ($comment) {
                    $comment->content = app(\App\Services\MentionService::class)->convertMentionsToLinks($comment->content);
                    if ($comment->replies) {
                        $comment->replies->transform(function ($reply) {
                            $reply->content = app(\App\Services\MentionService::class)->convertMentionsToLinks($reply->content);
                            return $reply;
                        });
                    }
                    return $comment;
                });
            }
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string|max:280',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:51200',
            'is_private' => 'nullable|boolean',
        ]);

        // Ensure at least content or media is provided
        if (!$request->filled('content') && !$request->hasFile('media')) {
            return response()->json(['message' => __('messages.provide_content_or_media')], 422);
        }

        $postData = $request->only('content');
        $postData['is_private'] = $request->has('is_private') ? true : false;
        $postData['is_approved'] = true; // Auto-approve regular feed posts

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mimeType = $file->getMimeType();

            if (str_contains($mimeType, 'image/')) {
                $mediaPath = $file->store('posts/images', 'public');
                $postData['media_type'] = 'image';
                $postData['media_path'] = $mediaPath;
            } elseif (str_contains($mimeType, 'video/')) {
                $mediaPath = $file->store('posts/videos', 'public');
                $postData['media_type'] = 'video';
                $postData['media_path'] = $mediaPath;
            }
        }

        $post = auth()->user()->posts()->create($postData);
        return response()->json($post->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post->load(['user', 'comments.replies.user', 'comments.likes']);

        // Convert mentions in post content
        if ($post->content) {
            $post->content = app(\App\Services\MentionService::class)->convertMentionsToLinks($post->content);
        }

        // Process mentions in comments
        if ($post->comments) {
            $post->comments->transform(function ($comment) {
                $comment->content = app(\App\Services\MentionService::class)->convertMentionsToLinks($comment->content);
                if ($comment->replies) {
                    $comment->replies->transform(function ($reply) {
                        $reply->content = app(\App\Services\MentionService::class)->convertMentionsToLinks($reply->content);
                        return $reply;
                    });
                }
                return $comment;
            });
        }

        return response()->json($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $request->validate([
            'content' => 'required|string|max:280',
        ]);

        $oldContent = $post->content;
        $post->update($request->only('content'));

        // Process mentions if content changed
        if ($oldContent !== $post->content && $post->content) {
            // Remove old mentions for this post
            \App\Models\Mention::where('mentionable_type', \App\Models\Post::class)
                ->where('mentionable_id', $post->id)
                ->delete();

            // Process new mentions
            app(\App\Services\MentionService::class)->processMentions($post, $post->content, auth()->id());
        }

        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $post->delete();
        return response()->json(['success' => true]);
    }

    public function like(Post $post)
    {
        $like = $post->likes()->where('user_id', auth()->id())->first();
        if ($like) {
            $like->delete();
            return response()->json(['liked' => false, 'likes_count' => $post->likes()->count()]);
        } else {
            $post->likes()->create(['user_id' => auth()->id()]);
            return response()->json(['liked' => true, 'likes_count' => $post->likes()->count()]);
        }
    }
}
