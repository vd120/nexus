<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:280',
            'post_id' => 'required|exists:posts,id',
            'parent_id' => 'nullable|exists:comments,id',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $currentUser = auth()->user();
        $post = Post::findOrFail($request->post_id);

        // Check if comments are disabled for this post
        if ($post->is_comments_disabled) {
            return back()->withErrors(['post_id' => 'Comments are disabled for this post.']);
        }

        // Check if group is paused
        if ($post->social_group_id && $post->socialGroup->is_paused) {
            return back()->withErrors(['post_id' => 'Group is currently paused.']);
        }
        
        // CRITICAL FIX: Check if commenter is blocked by post owner
        if ($post->user->isBlocking($currentUser)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.post_owner_has_blocked_you')
                ], 403);
            }
            return back()->with('error', __('messages.post_owner_has_blocked_you'));
        }
        
        // Check if commenter has blocked post owner
        if ($currentUser->isBlocking($post->user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.you_have_blocked_this_user')
                ], 403);
            }
            return back()->with('error', __('messages.you_have_blocked_this_user'));
        }
        
        // If replying to a comment, check block status with parent comment author
        if ($request->parent_id) {
            $parentComment = Comment::findOrFail($request->parent_id);
            
            if ($parentComment->user->isBlocking($currentUser)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.comment_author_has_blocked_you')
                    ], 403);
                }
                return back()->with('error', __('messages.comment_author_has_blocked_you'));
            }
            
            if ($currentUser->isBlocking($parentComment->user)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.you_have_blocked_this_user')
                    ], 403);
                }
                return back()->with('error', __('messages.you_have_blocked_this_user'));
            }
        }

        $isAnonymous = $request->boolean('is_anonymous');

        $commentData = [
            'user_id' => auth()->id(),
            'post_id' => $request->post_id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
            'is_anonymous' => $isAnonymous,
        ];

        $comment = Comment::create($commentData);

        // Process mentions in the comment content
        app(\App\Services\MentionService::class)->processMentions($comment, $comment->content, auth()->id());

        // 1. Notify the person being replied to (if it's a reply)
        if ($comment->parent_id) {
            $parentComment = $comment->parent;
            if ($parentComment && $parentComment->user_id !== auth()->id()) {
                NotificationController::createNotification(
                    $parentComment->user_id,
                    'comment_reply',
                    [
                        'commenter_name' => auth()->user()->username ?? auth()->user()->name ?? 'Someone',
                        'commenter_username' => auth()->user()->username ?? 'Unknown',
                        'commenter_id' => auth()->id(),
                        'comment_content' => mb_substr($comment->content, 0, 50) . (mb_strlen($comment->content) > 50 ? '...' : ''),
                        'post_slug' => $comment->post->slug ?? $comment->post->id,
                        'parent_comment_id' => $comment->parent_id
                    ],
                    $comment
                );
            }
        }

        // 2. Notify post owner (if not the commenter and not already notified as parent author)
        $postOwnerId = $comment->post->user_id;
        $parentAuthorId = $comment->parent_id ? ($comment->parent->user_id ?? null) : null;
        
        if ($postOwnerId !== auth()->id() && $postOwnerId !== $parentAuthorId) {
            NotificationController::createNotification(
                $postOwnerId,
                'comment',
                [
                    'commenter_name' => auth()->user()->username ?? auth()->user()->name ?? 'Someone',
                    'commenter_username' => auth()->user()->username ?? 'Unknown',
                    'commenter_id' => auth()->id(),
                    'comment_content' => mb_substr($comment->content, 0, 50) . (mb_strlen($comment->content) > 50 ? '...' : ''),
                    'post_content' => mb_substr($comment->post->content ?? 'Image post', 0, 30) . (mb_strlen($comment->post->content ?? 'Image post') > 30 ? '...' : ''),
                    'post_slug' => $comment->post->slug ?? $comment->post->id
                ],
                $comment
            );
        }

        // Prepare comment data for response and broadcast
        $groupId = $post->social_group_id;
        $commentData = Comment::with(['user.profile', 'post', 'member' => function($query) use ($groupId) {
            if ($groupId) {
                $query->where('social_group_members.social_group_id', $groupId);
            }
        }])->find($comment->id);
        
        // Append virtual attributes
        $commentData->append('author_role');
        
        // Ensure accessor-based attributes like avatar_url are included in JSON
        if ($commentData->user) {
            $commentData->user->append('avatar_url');
        }
        $commentData->content = app(\App\Services\MentionService::class)->convertMentionsToLinks($comment->content);

        // Merge virtual attributes explicitly to ensure they are in the JSON
        $commentArray = $commentData->toArray();
        $commentArray['author_role'] = $commentData->author_role;
        $commentArray['role_badge_html'] = $commentData->role_badge_html;
        if ($commentData->user) {
            $commentArray['user']['avatar_url'] = $commentData->user->avatar_url;
        }

        // BROADCAST: Send updated count and the new comment data for real-time appearance
        $socketPayload = [
            'post_id' => $post->id,
            'count' => $post->comments()->count(),
        ];

        // Prepare anonymized comment data for public broadcast
        $broadcastComment = $commentArray;
        if ($isAnonymous) {
            $broadcastComment['user'] = [
                'id' => null,
                'username' => __('messages.anonymous_participant'),
                'name' => __('messages.anonymous_participant'),
                'avatar_url' => null, // Frontend handles anonymous avatar
            ];
            $broadcastComment['user_id'] = null;
            $broadcastComment['author_role'] = null;
            $broadcastComment['role_badge_html'] = '';
        }
        $socketPayload['comment'] = $broadcastComment;

        app(\App\Services\SocketEmitService::class)->emit('global', 'post:commented', $socketPayload);

        // Check if it's an AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $commentArray,
            ]);
        }

        return back();
    }

    public function update(Request $request, Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'content' => 'required|string|max:280',
        ]);

        $oldContent = $comment->content;
        $comment->update($request->only('content'));

        // Process mentions if content changed
        if ($oldContent !== $comment->content && $comment->content) {
            // Remove old mentions for this comment
            \App\Models\Mention::where('mentionable_type', \App\Models\Comment::class)
                ->where('mentionable_id', $comment->id)
                ->delete();

            // Process new mentions
            app(\App\Services\MentionService::class)->processMentions($comment, $comment->content, auth()->id());
        }

        return response()->json($comment);
    }

    public function destroy(Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        $postId = $comment->post_id;
        $commentId = $comment->id;
        $comment->delete();

        // BROADCAST: Notify all users to remove this comment and update count
        $post = \App\Models\Post::find($postId);
        app(\App\Services\SocketEmitService::class)->emit('global', 'comment:deleted', [
            'comment_id' => $commentId,
            'post_id' => $postId,
            'count' => $post ? $post->comments()->count() : 0
        ]);

        // Check if it's an AJAX request
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true
            ]);
        }

        return back();
    }

    public function like(Comment $comment)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.please_login')
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        try {
            $like = $comment->likes()->where('user_id', $user->id)->first();

            if ($like) {
                $like->delete();
                $comment->refresh();
            } else {
                $isAnonymous = false;
                $post = $comment->post;
                if ($post && $post->social_group_id) {
                    $member = \App\Models\SocialGroupMember::where('social_group_id', $post->social_group_id)
                        ->where('user_id', $user->id)
                        ->first();
                    if ($member && $member->is_anonymous_default) {
                        $isAnonymous = true;
                    }
                }

                $newLike = CommentLike::create([
                    'user_id' => $user->id, 
                    'comment_id' => $comment->id,
                    'is_anonymous' => $isAnonymous
                ]);
                $comment->refresh();

                // Create notification for comment owner (if not liking own comment)
                if ($comment->user_id !== $user->id) {
                    NotificationController::createNotification(
                        $comment->user_id,
                        'comment_like',
                        [
                            'liker_name' => $user->username ?? $user->name ?? 'Someone',
                            'liker_username' => $user->username ?? 'Unknown',
                            'liker_id' => $user->id,
                            'comment_content' => mb_substr($comment->content, 0, 50) . (mb_strlen($comment->content) > 50 ? '...' : ''),
                            'post_slug' => $comment->post->slug ?? '',
                            'comment_id' => $comment->id,
                            'is_reply' => $comment->parent_id !== null
                        ],
                        $newLike // Pass the like model for anti-leak logic
                    );
                }
            }

            // BROADCAST: Update comment likes count in real-time
            app(\App\Services\SocketEmitService::class)->emit('global', 'comment:liked', [
                'comment_id' => $comment->id,
                'likes_count' => $comment->likes()->count()
            ]);

            // Check if it's an AJAX request
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'liked' => !$like,
                    'likes_count' => $comment->likes()->count()
                ]);
            }

            return back();
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.error_occurred')
                ], 500);
            }
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
