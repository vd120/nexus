<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\GlobalMessage;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\SavedPost;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\HashtagService;
use Illuminate\Http\Request;
use App\Notifications\GroupPostPendingNotification;
use Illuminate\Support\Facades\Notification;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function indexPreview(Request $request)
    {
        $response = $this->index($request);
        $data = method_exists($response, 'getData') ? (array) $response->getData() : [];
        return view('posts.index-preview', $data);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 5);
        $page = $request->get('page', 1);
        $followedUsers = collect();
        $topHashtags = collect();
        $globalChatMessages = collect();

        if ($user) {
            $blockedUserIds = $this->getBlockedUserIds($user);
            // Anchor ranking time to page-1 load so it stays stable across paginated requests
            $anchorKey = "feed_anchor_{$user->id}";
            $rankingAnchor = \Illuminate\Support\Facades\Cache::remember($anchorKey, 120, fn() => now()->toDateTimeString());
            $feedCacheKey = "feed_{$user->id}_p{$page}_pp{$perPage}";
            $posts = \Illuminate\Support\Facades\Cache::remember($feedCacheKey, 120, function () use ($user, $blockedUserIds, $perPage, $page, $rankingAnchor) {
                return $this->buildFeedQuery($user, $blockedUserIds, $rankingAnchor)
                    ->paginate($perPage, ['*'], 'page', $page)
                    ->withQueryString();
            });

            // Cache stories for 5 minutes to reduce load
            $cacheKey = 'user_stories_' . $user->id;
            $storiesData = cache()->remember($cacheKey, 60, function() use ($user) {
                return [
                    'followed_users' => \App\Models\User::whereHas('followers', function($query) use ($user) {
                        $query->where('follower_id', $user->id);
                    })->whereHas('activeStories')->with(['activeStories'])->get(),
                    'my_stories' => $user->activeStories
                ];
            });

            $followedUsersWithStories = $storiesData['followed_users'];
            $myStories = $storiesData['my_stories'];

            $followedUsers = \Illuminate\Support\Facades\Cache::remember(
                "following_sidebar_{$user->id}", 120,
                fn() => $user->following()
                    ->select('users.id', 'users.username', 'users.name', 'users.is_online')
                    ->with('profile')
                    ->orderBy('follows.created_at', 'desc')
                    ->limit(8)
                    ->get()
            );

            $topHashtags = \Illuminate\Support\Facades\Cache::remember('top_hashtags_feed', 180, fn() => Hashtag::popular(3)->get());
            $globalChatMessages = GlobalMessage::with('user')
                ->latest()
                ->limit(2)
                ->get()
                ->reverse()
                ->values();
        } else {
            $posts = Post::withCount(['likes', 'reactions', 'comments'])
                ->with([
                    'user', 
                    'media', 
                    'reactions' => function($query) {
                        $query->select('id', 'post_id', 'user_id', 'reaction_type')->with('user:id,name,username');
                    }
                ])
                ->approved()
                ->where('is_private', false)
                ->whereHas('user.profile', function($profileQuery) {
                    $profileQuery->where('is_private', false);
                })
                ->latest()
                ->paginate($perPage)
                ->withQueryString();

            $followedUsersWithStories = collect();
            $myStories = collect();
        }

        return view('posts.index', compact(
            'posts',
            'followedUsersWithStories',
            'myStories',
            'followedUsers',
            'topHashtags',
            'globalChatMessages'
        ));
    }

    /**
     * Load more posts for infinite scroll.
     */
    public function loadMore(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 5);
        $page = $request->get('page', 1);

        if ($user) {
            $blockedUserIds = $this->getBlockedUserIds($user);
            // Reuse the same ranking anchor set by the initial page load
            $rankingAnchor = \Illuminate\Support\Facades\Cache::get("feed_anchor_{$user->id}", now()->toDateTimeString());
            $posts = $this->buildFeedQuery($user, $blockedUserIds, $rankingAnchor)
                ->paginate($perPage, ['*'], 'page', $page)
                ->withQueryString();
        } else {
            $posts = Post::withCount(['likes', 'reactions', 'comments'])
                ->with([
                    'user', 
                    'media', 
                    'reactions' => function($query) {
                        $query->select('id', 'post_id', 'user_id', 'reaction_type')->with('user:id,name,username');
                    }
                ])
                ->approved()
                ->where('is_private', false)
                ->whereHas('user.profile', function($profileQuery) {
                    $profileQuery->where('is_private', false);
                })
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page)
                ->withQueryString();
        }

        // Render the posts into HTML
        $html = view('partials.posts-list', ['posts' => $posts, 'skipEmpty' => true])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'next_page' => $posts->hasMorePages() ? $posts->currentPage() + 1 : null,
            'has_more' => $posts->hasMorePages()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, FileUploadService $fileService)
    {
        // Check if POST data exceeded PHP limits
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.post_too_large')
                ], 422);
            }
            return back()
                ->withInput()
                ->withErrors(['media' => __('messages.post_too_large')]);
        }
        
        $request->validate([
            'content' => 'nullable|string|max:280',
            'media' => 'nullable|array|max:30', // Allow up to 30 files
            'media.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,webm|max:51200', // 50MB max, added webm
            'social_group_id' => 'nullable|exists:social_groups,id',
            'social_group_topic_id' => 'nullable|exists:social_group_topics,id',
            'is_anonymous' => 'nullable|boolean',
            'is_comments_disabled' => 'nullable|boolean',
            'is_sensitive' => 'nullable|boolean',
            'poll_question' => 'nullable|string|max:255',
            'poll_options'  => 'nullable|array|min:2|max:4',
            'poll_options.*'=> 'required_with:poll_options|string|max:255',
            'poll_expires_at' => 'nullable|date|after:now',
        ]);

        // Ensure at least content, media, or poll is provided
        if (!$request->filled('content') && !$request->hasFile('media') && !$request->filled('poll_options')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide either text content or media.'
                ], 422);
            }
            return back()->withErrors(['content' => 'Please provide either text content or media.']);
        }

        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'
        ];

        // SECURITY FIX: Validate all files before processing
        $validatedFiles = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $index => $file) {
                $validation = $fileService->validateFile($file, $allowedMimeTypes);
                
                if (!$validation['valid']) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => implode(', ', $validation['errors'])
                        ], 422);
                    }
                    return back()->withErrors([
                        'media.' . $index => implode(', ', $validation['errors'])
                    ])->withInput();
                }
                
                $validatedFiles[] = $file;
            }
        }

        $postData = $request->only(['content', 'social_group_id', 'social_group_topic_id']);
        $postData['is_private'] = $request->boolean('is_private');
        $postData['is_comments_disabled'] = $request->boolean('is_comments_disabled');
        $postData['is_sensitive'] = $request->boolean('is_sensitive');
        
        // 1. Separate Anonymity: Only allowed in communities that support it
        $isAnonymous = $request->boolean('is_anonymous');
        if (!$request->social_group_id) {
            $isAnonymous = false; // Regular posts cannot be anonymous
        }
        
        // 2. Separate Privacy: Regular posts can be private (followers-only), 
        // while community posts follow community visibility.
        if ($request->social_group_id) {
            $postData['is_private'] = false; // Community posts are not "private" in the followers-sense
        }
        
        $postData['is_anonymous'] = $isAnonymous;

        // Default: Approved if it's a regular post or if user is a platform admin
        $isPlatformAdmin = auth()->user()->is_admin;
        $postData['is_approved'] = true;

        if ($request->social_group_id) {
            $group = \App\Models\SocialGroup::findOrFail($request->social_group_id);
            $member = $group->members()->where('user_id', auth()->id())->first();
            
            if (!$member || $member->status !== 'approved' || !$member->canPost()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to post in this group.'
                    ], 403);
                }
                return back()->withErrors(['social_group_id' => 'You do not have permission to post in this group.']);
            }

            // 3. Separate Anonymity: Ensure the group actually allows anonymous posts
            if ($postData['is_anonymous'] && !$group->allow_anonymous_posts) {
                $postData['is_anonymous'] = false;
            }

            // Auto-approve admins/moderators or if approval is not required
            $isAdminOrModerator = in_array($member->role, ['admin', 'moderator']);
            $postData['is_approved'] = !$group->require_post_approval || $isAdminOrModerator || $isPlatformAdmin;

            if ($group->require_post_approval && !$postData['is_approved']) {
                $admins = $group->members()
                    ->whereIn('role', ['admin', 'moderator'])
                    ->where('status', 'approved')
                    ->with('user')
                    ->get()
                    ->pluck('user');

                // We'll notify later after the post is actually created
            }
        }

        // Remove old single media fields since we're using the new media table
        unset($postData['media_type'], $postData['media_path'], $postData['media_thumbnail']);

        $post = auth()->user()->posts()->create($postData);

        \Illuminate\Support\Facades\Cache::forget("feed_" . auth()->id() . "_p1_pp5");
        \Illuminate\Support\Facades\Cache::forget("feed_anchor_" . auth()->id());

        // Create poll if provided
        if ($request->filled('poll_options')) {
            $options = array_filter(array_map('trim', (array) $request->poll_options));
            if (count($options) >= 2) {
                $poll = $post->poll()->create([
                    'question'   => $request->input('poll_question'),
                    'expires_at' => $request->input('poll_expires_at') ?: null,
                ]);
                foreach ($options as $optionText) {
                    $poll->options()->create(['text' => substr($optionText, 0, 255)]);
                }
            }
        }

        if ($request->social_group_id) {
            $group = \App\Models\SocialGroup::find($request->social_group_id);
            if ($group->require_post_approval && !$post->is_approved) {
                $admins = $group->members()
                    ->whereIn('role', ['admin', 'moderator'])
                    ->where('status', 'approved')
                    ->with('user')
                    ->get()
                    ->pluck('user');

                foreach ($admins as $admin) {
                    \App\Http\Controllers\NotificationController::createNotification(
                        $admin->id,
                        'group_post_pending',
                        [
                            'group_id' => $group->id,
                            'group_name' => $group->name,
                            'group_slug' => $group->slug,
                            'author_id' => auth()->id(),
                            'author_name' => auth()->user()->username,
                            'post_id' => $post->id,
                        ],
                        $post
                    );
                }
            }
        }

        // Process mentions in the post content
        if ($post->content) {
            app(\App\Services\MentionService::class)->processMentions($post, $post->content, auth()->id());
        }

        // Process hashtags in the post content
        if ($post->content) {
            app(HashtagService::class)->syncHashtags($post, $post->content);
        }

        // Handle multiple media uploads with validated files
        if ($validatedFiles) {
            $files = $validatedFiles;
            $sortOrder = 0;

            foreach ($files as $file) {
                $mimeType = $file->getMimeType();
                $originalName = $file->getClientOriginalName();

                if (str_contains($mimeType, 'image/')) {
                    $path = $fileService->compressImage($file, 'posts/images', [
                        'maxWidth' => 1280,
                        'maxHeight' => 1280,
                        'quality' => 80,
                    ]);

                    if (!$path) {
                        continue;
                    }

                    $post->media()->create([
                        'media_type' => 'image',
                        'media_path' => $path,
                        'sort_order' => $sortOrder++
                    ]);
                } elseif (str_contains($mimeType, 'video/')) {
                    // Enhanced video validation
                    $videoMimeTypes = ['video/mp4', 'video/mov', 'video/avi', 'video/webm'];

                    if (!in_array($mimeType, $videoMimeTypes)) {
                        continue; // Skip invalid video types
                    }

                    // Check video size (50MB max)
                    if ($file->getSize() > 52428800) { // 50MB in bytes
                        continue; // Skip oversized videos
                    }

                    // Handle video upload
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = 'posts/videos/' . $filename;
                    
                    // Thumbnail path (always .jpg)
                    $thumbnailPath = 'posts/thumbnails/' . time() . '_' . uniqid() . '.jpg';

                    // Ensure directories exist
                    $fullVideoDir = storage_path('app/public/posts/videos');
                    if (!file_exists($fullVideoDir)) mkdir($fullVideoDir, 0755, true);
                    
                    $fullThumbDir = storage_path('app/public/posts/thumbnails');
                    if (!file_exists($fullThumbDir)) mkdir($fullThumbDir, 0755, true);

                    // Move video to storage
                    $file->move($fullVideoDir, $filename);

                    // Generate thumbnail in background so the HTTP response is not blocked.
                    $fileService->generateVideoThumbnail($path, $thumbnailPath, background: true);

                    $post->media()->create([
                        'media_type' => 'video',
                        'media_path' => $path,
                        'media_thumbnail' => $thumbnailPath, // will exist within ~2s
                        'sort_order' => $sortOrder++
                    ]);
                }
            }
        }

        // Check if it's an AJAX request
        if (request()->expectsJson()) {
            $post->load(['user', 'media', 'socialGroup', 'socialGroupTopic', 'member']);
            if ($post->user) {
                $post->user->append('avatar_url');
            }

            // Owner sees the post on the community page they just posted from — hide "in X" there
            $ownerPostHtml = view('partials.post', [
                'post' => $post,
                'is_broadcast' => false,
                'hideGroupContext' => $request->filled('social_group_id'),
            ])->render();

            // Render broadcast HTML in every supported locale so each recipient
            // gets the version that matches their own language setting.
            $currentLocale = app()->getLocale();
            $broadcastViewData = ['post' => $post, 'is_broadcast' => true, 'hideGroupContext' => false];
            app()->setLocale('en');
            $postHtmlEn = view('partials.post', $broadcastViewData)->render();
            app()->setLocale('ar');
            $postHtmlAr = view('partials.post', $broadcastViewData)->render();
            app()->setLocale($currentLocale);

            $socketPayload = [
                'id'              => $post->id,
                'slug'            => $post->slug,
                'user_id'         => $post->user_id,
                'social_group_id' => $post->social_group_id,
                'html'            => $postHtmlEn,
                'html_ar'         => $postHtmlAr,
            ];

            // Collect broadcast targets before returning, then fire after response
            $followerIds   = auth()->user()->followers()->pluck('follower_id');
            $isPublicPost  = !$post->is_private && !$post->social_group_id && $post->is_approved;
            $groupMemberIds = collect();
            if ($post->social_group_id && $post->is_approved) {
                $groupMemberIds = $post->socialGroup->members()
                    ->where('status', 'approved')
                    ->where('user_id', '!=', auth()->id())
                    ->pluck('user_id');
            }

            app()->terminating(function () use (
                $followerIds, $groupMemberIds, $isPublicPost, $socketPayload
            ) {
                try {
                    $socketService = app(\App\Services\SocketEmitService::class);

                    // Followers — one batch call
                    if ($followerIds->isNotEmpty()) {
                        $socketService->emitToUsers($followerIds, 'post:new', $socketPayload);
                    }

                    // Group members — one batch call
                    if ($groupMemberIds->isNotEmpty()) {
                        $socketService->emitToUsers($groupMemberIds, 'post:new', $socketPayload);
                    }

                    // Global broadcast
                    if ($isPublicPost) {
                        $socketService->emit('global', 'post:new', array_merge($socketPayload, ['social_group_id' => null]));
                    }
                } catch (\Exception $e) {
                    \Log::error('Post broadcast failed: ' . $e->getMessage());
                }
            });

            return response()->json([
                'success' => true,
                'post' => $post,
                'post_html' => $ownerPostHtml,
                'message' => $post->is_approved ? __('messages.post_created') : 'Post submitted for approval.'
            ]);
        }

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        // Check if user can view this post (privacy and blocks)
        $user = auth()->user();

        // Check post-level privacy first
        if ($post->is_private) {
            $isFollowing = $user->isFollowing($post->user);
            $isOwner = $post->user_id === $user->id;

            if (!$isFollowing && !$isOwner) {
                abort(403, __('messages.post_private_followers_only'));
            }
        }

        // If post is from private account and user doesn't follow them (account-level privacy)
        if ($post->user->profile && $post->user->profile->is_private) {
            $isFollowing = $user->isFollowing($post->user);
            $isOwner = $post->user_id === $user->id;

            if (!$isFollowing && !$isOwner) {
                abort(403, __('messages.post_private_account_follow_to_view'));
            }
        }

        // Check if user is blocked by the post author or has blocked the post author
        if ($user->isBlocking($post->user) || $post->user->isBlocking($user)) {
            abort(403, __('messages.cannot_view_blocking_restrictions'));
        }

        $post->load([
            'user', 'media', 'reactions.user:id,name,username', 'comments.replies.user', 'comments.likes',
            'member' => function($query) use ($post) {
                if ($post->social_group_id) {
                    $query->where('social_group_id', $post->social_group_id);
                }
            },
            'comments.member' => function($query) use ($post) {
                if ($post->social_group_id) {
                    $query->where('social_group_id', $post->social_group_id);
                }
            }
        ]);

        // Track view
        $userId  = auth()->id();
        $ipHash  = $userId ? null : hash('sha256', request()->ip());
        $post->recordView($userId, $ipHash);

        return view('posts.show', compact('post'));
    }

    public function analytics(Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            abort(403);
        }

        $viewsByDay = \App\Models\PostView::where('post_id', $post->id)
            ->where('viewed_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');

        $totalViews    = \App\Models\PostView::where('post_id', $post->id)->count();
        $totalLikes    = $post->likes()->count();
        $totalComments = $post->comments()->count();
        $totalSaves    = $post->savedPosts()->count();
        $engagementRate = $totalViews > 0
            ? round((($totalLikes + $totalComments + $totalSaves) / $totalViews) * 100, 1)
            : 0;

        $pollData = null;
        if ($post->poll) {
            $poll = $post->poll->load('options.votes.user');
            $totalVotes = $poll->totalVotes();
            $pollData = [
                'question' => $poll->question,
                'total_votes' => $totalVotes,
                'options' => $poll->options->map(fn($o) => [
                    'text' => $o->text,
                    'votes_count' => $o->votes_count,
                    'percentage' => $totalVotes > 0 ? round(($o->votes_count / $totalVotes) * 100, 1) : 0,
                    'voters' => $o->votes->map(fn($v) => [
                        'name' => $v->user?->name ?? '—',
                        'username' => $v->user?->username ?? '—',
                        'avatar' => $v->user?->avatar_url,
                    ]),
                ]),
            ];
        }

        return view('posts.analytics', compact('post', 'viewsByDay', 'totalViews', 'totalLikes', 'totalComments', 'totalSaves', 'engagementRate', 'pollData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            abort(403);
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

        // Ensure avatar_url is present for the post author in JSON
        $post->load('user');
        if ($post->user) {
            $post->user->append('avatar_url');
        }

        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Post $post)
    {
        $user = auth()->user();
        
        if (!$post->canDelete($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
            abort(403);
        }

        // LOGGING & ACCOUNTABILITY: If admin is deleting someone else's post
        $adminDeletedOwnersPost = $user->is_admin && $post->user_id !== $user->id;
        $adminDeleteReason = null;
        $postOwnerId = $post->user_id;

        if ($adminDeletedOwnersPost) {
            $adminDeleteReason = trim($request->input('reason', '')) ?: 'No reason provided';

            // Log this action for audit
            $actionStr = "admin_delete_post:#{$post->id}_reason:" . mb_substr($adminDeleteReason, 0, 100);
            app(\App\Services\ActivityService::class)->logActivity($actionStr, $user->id);

            \Log::warning("ADMIN ACTION: User #{$user->id} ({$user->username}) deleted post #{$post->id} by user #{$post->user_id}. Reason: {$adminDeleteReason}");
        }

        // Remove hashtags associated with this post
        app(\App\Services\HashtagService::class)->removePostHashtags($post);

        $postId = $post->id;
        $post->delete();

        // Notify the post owner when an admin deletes their post
        if ($adminDeletedOwnersPost) {
            \App\Http\Controllers\NotificationController::createNotification(
                $postOwnerId,
                'admin_post_deleted',
                [
                    'reason'           => $adminDeleteReason,
                    'admin_username'   => $user->username,
                    'post_id'          => $postId,
                ]
            );
        }

        // BROADCAST: Notify all users to remove this post from their feed
        app(\App\Services\SocketEmitService::class)->emit('global', 'post:deleted', [
            'post_id' => $postId
        ]);

        // Check if it's an AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.post_deleted')
            ]);
        }

        return back()->with('success', __('messages.post_deleted'));
    }

    public function like(Post $post)
    {
        $user = auth()->user();
        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
        } else {
            $post->likes()->create(['user_id' => $user->id]);

            // Create notification for post owner (if not liking own post)
            if ($post->user_id !== $user->id) {
                $notification = NotificationController::createNotification(
                    $post->user_id,
                    'like',
                    [
                        'liker_name' => $user->username ?? $user->name ?? 'Someone',
                        'liker_username' => $user->username ?? 'Unknown',
                        'liker_id' => $user->id,
                        'post_content' => mb_substr($post->content ?? 'Image post', 0, 50) . (mb_strlen($post->content ?? 'Image post') > 50 ? '...' : ''),
                        'post_slug' => $post->slug,
                    ],
                    $post
                );

                // Send push notification
                try {
                    $pushService = app(\App\Services\PushNotificationService::class);
                    if ($pushService && $pushService->isConfigured()) {
                        $pushService->sendToUser(
                            $post->user,
                            __('notifications.liked_your_post', ['user' => $user->username]),
                            $user->username . ' liked your post',
                            url('/posts/' . $post->slug),
                            ['type' => 'likes', 'notification_id' => $notification->id]
                        );
                    }
                } catch (\Exception $e) {
                    \Log::debug('Push notification failed: ' . $e->getMessage());
                }
            }
        }

        $likesCount = $post->likes()->count();

        // Broadcast count update to everyone
        app(\App\Services\SocketEmitService::class)->emit('global', 'post:liked', [
            'post_id' => $post->id,
            'count' => $likesCount
        ]);

        // Check if it's an AJAX request
        if (request()->expectsJson()) {
            $recentLikers = $post->likes()->with('user:id,name')->latest()->limit(10)->get()->map(function($like) {
                return [
                    'id' => $like->user->id,
                    'username' => $like->user->username
                ];
            });

            return response()->json([
                'success' => true,
                'liked' => !$like,
                'likes_count' => $likesCount,
                'likers' => $recentLikers
            ]);
        }

        return back();
    }

    public function save(Post $post)
    {
        $saved = SavedPost::where('user_id', auth()->id())->where('post_id', $post->id)->first();
        if ($saved) {
            $saved->delete();
        } else {
            SavedPost::create([
                'user_id' => auth()->id(),
                'post_id' => $post->id
            ]);
        }

        // Check if it's an AJAX request
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'saved' => !$saved
            ]);
        }

        return back();
    }

    public function getLikers(Post $post)
    {
        $user = auth()->user();

        // Check if user can view the post (privacy and blocks)
        if ($post->is_private && !$user->isFollowing($post->user) && $post->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => __('messages.cannot_view_private_post')]);
        }

        if ($user->isBlocking($post->user) || $post->user->isBlocking($user)) {
            return response()->json(['success' => false, 'message' => __('messages.blocking_restriction')]);
        }

        // Get users who liked this post
        $likers = $post->likes()
            ->with('user:id,name,username')
            ->with('user.profile:id,user_id,avatar,bio')
            ->get()
            ->map(function ($like) use ($user) {
                $liker = $like->user;
                $profile = $liker->profile;

                $finalUsername = $liker->username;
                $finalAvatar = $liker->avatar_url;
                $finalBio = $profile ? $profile->bio : null;

                if ($post->social_group_id) {
                    $member = \App\Models\SocialGroupMember::where('social_group_id', $post->social_group_id)
                        ->where('user_id', $liker->id)
                        ->first();
                    if ($member && $member->is_anonymous_default) {
                        $finalUsername = $member->anonymous_username;
                        $finalAvatar = 'https://ui-avatars.com/api/?name=Anon&background=374151&color=9ca3af';
                        $finalBio = null;
                    }
                }

                return [
                    'id' => $liker->id,
                    'username' => $finalUsername,
                    'avatar' => $finalAvatar,
                    'bio' => $finalBio,
                    'can_follow' => $liker->id !== $user->id && !$user->isFollowing($liker) && !$liker->isBlocking($user),
                    'is_following' => $user->isFollowing($liker),
                    'is_verified' => (bool) $liker->is_verified,
                ];
            });

        return response()->json([
            'success' => true,
            'likers' => $likers
        ]);
    }

    public function react(Post $post, Request $request)
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
            'is_anonymous' => 'nullable|boolean',
        ]);

        if (!in_array($validated['emoji'], Post::POST_REACTION_EMOJIS)) {
            return response()->json(['success' => false, 'message' => __('messages.invalid_reaction_type')], 422);
        }

        $userId = auth()->id();
        $user = auth()->user();
        $isInitial = !$post->reactions()->where('user_id', $userId)->exists();

        $isAnonymous = $request->boolean('is_anonymous');
        
        // Respect global anonymity preference for the group
        if ($post->social_group_id) {
            $member = \App\Models\SocialGroupMember::where('social_group_id', $post->social_group_id)
                ->where('user_id', auth()->id())
                ->first();
            if ($member && $member->is_anonymous_default) {
                $isAnonymous = true;
            }
        }

        // Upsert: update existing or create new
        $reaction = $post->reactions()->updateOrCreate(
            ['user_id' => $userId],
            [
                'reaction_type' => $validated['emoji'],
                'is_anonymous' => $isAnonymous,
            ]
        );

        $post->load(['reactions.user:id,name,username,is_verified']);
        $summaries = $post->getGroupedReactions();

        // Broadcast reaction update to all users
        try {
            $reactors = $post->reactions->pluck('user')->filter()->unique('id')->values()->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'avatar' => $u->avatar_url,
                'is_verified' => (bool) $u->is_verified,
            ]);
            app(\App\Services\SocketEmitService::class)->emit('global', 'post:reacted', [
                'post_id' => $post->id,
                'reaction_summaries' => $summaries,
                'reactors' => $reactors,
            ]);
        } catch (\Exception $e) {
            \Log::debug('Socket.io post:reacted broadcast failed: ' . $e->getMessage());
        }

        // Create notification for post owner (if not reacting to own post and it's the first time)
        if ($isInitial && $post->user_id !== $userId) {
            $notificationData = [
                'reactor_username' => $user->username ?? $user->name ?? 'Someone',
                'reaction_type' => $validated['emoji'],
                'post_slug' => $post->slug,
            ];

            $notification = NotificationController::createNotification(
                $post->user_id,
                'post_reaction',
                $notificationData,
                $reaction // Pass the reaction model for anti-leak logic
            );

            // Use potentially anonymized data from the notification for push
            $displayName = $notification->data['reactor_username'] ?? 'Someone';

            // Send push notification
            try {
                $pushService = app(\App\Services\PushNotificationService::class);
                if ($pushService && $pushService->isConfigured()) {
                    $originalLocale = app()->getLocale();
                    if ($post->user->language) {
                        app()->setLocale($post->user->language);
                    }

                    $pushService->sendToUser(
                        $post->user,
                        __('notifications.reacted_to_your_post', [
                            'user' => $displayName,
                            'reaction' => $validated['emoji']
                        ]),
                        $displayName . ' reacted to your post with ' . $validated['emoji'],
                        url('/posts/' . $post->slug),
                        ['type' => 'likes', 'notification_id' => $notification->id]
                    );

                    app()->setLocale($originalLocale);
                }
            } catch (\Exception $e) {
                \Log::debug('Push notification failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'reaction_summaries' => $summaries,
            'reactors' => $reactors,
        ]);
    }

    public function removeReaction(Post $post)
    {
        $post->reactions()->where('user_id', auth()->id())->delete();

        $post->load(['reactions.user:id,name,username,is_verified']);
        $summaries = $post->getGroupedReactions();

        // Broadcast reaction update to all users
        try {
            $reactors = $post->reactions->pluck('user')->filter()->unique('id')->values()->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'avatar' => $u->avatar_url,
                'is_verified' => (bool) $u->is_verified,
            ]);
            app(\App\Services\SocketEmitService::class)->emit('global', 'post:reacted', [
                'post_id' => $post->id,
                'reaction_summaries' => $summaries,
                'reactors' => $reactors,
            ]);
        } catch (\Exception $e) {
            \Log::debug('Socket.io post:reacted broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'reaction_summaries' => $summaries,
            'reactors' => $reactors,
        ]);
    }

    public function getReactions(Post $post)
    {
        $user = auth()->user();

        // Check if user can view the post (privacy and blocks)
        if ($post->is_private && !$user->isFollowing($post->user) && $post->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => __('messages.cannot_view_private_post')], 403);
        }

        if ($user->isBlocking($post->user) || $post->user->isBlocking($user)) {
            return response()->json(['success' => false, 'message' => __('messages.blocking_restriction')], 403);
        }

        $reactions = $post->reactions()
            ->with(['user:id,name,username,is_verified', 'user.profile:id,user_id,avatar'])
            ->latest()
            ->get()
            ->map(function ($reaction) {
                $author = $reaction->author;
                return [
                    'username' => $author->username,
                    'avatar' => $author->avatar_url,
                    'reaction_type' => $reaction->reaction_type,
                    'created_at' => $reaction->created_at->toISOString(),
                    'is_verified' => (bool) $author->is_verified,
                ];
            });

        // Group reactions for summary tabs if needed
        $summary = $post->getGroupedReactions();

        return response()->json([
            'success' => true,
            'data' => $reactions,
            'summary' => $summary
        ]);
    }

    private function getBlockedUserIds(User $user): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "blocked_ids_{$user->id}", 300,
            function () use ($user) {
                $iBlocked  = Block::where('blocker_id', $user->id)->pluck('blocked_id')->toArray();
                $blockedMe = Block::where('blocked_id', $user->id)->pluck('blocker_id')->toArray();
                return array_unique(array_merge($iBlocked, $blockedMe));
            }
        );
    }

    private function buildFeedQuery(User $user, array $blockedUserIds, ?string $rankingAnchor = null)
    {
        // Use a fixed anchor so ranking thresholds don't shift across paginated requests
        $anchor = $rankingAnchor ?? now()->toDateTimeString();

        $query = Post::withCount(['likes', 'reactions', 'comments'])
            ->with([
                'user',
                'media',
                'userReaction',
                'userLike',
                'userSavedPost',
                'reactions' => function($q) {
                    $q->select('id', 'post_id', 'user_id', 'reaction_type')->with('user:id,name,username');
                },
                'comments' => function($q) {
                    $q->withCount('likes')->latest()->limit(5)->with('user');
                }
            ])
            ->approved()
            ->where(function($q) use ($user) {
                $q->where(function($inner) use ($user) {
                    $inner->whereNull('social_group_id')
                        ->where(function($innermost) use ($user) {
                            $innermost->where('user_id', $user->id)
                                ->orWhereHas('user.followers', function($followerQuery) use ($user) {
                                    $followerQuery->where('follower_id', $user->id);
                                })
                                ->orWhere('is_private', false);
                        });
                })
                ->orWhere(function($inner) use ($user) {
                    $inner->whereNotNull('social_group_id')
                        ->where(function($innermost) use ($user) {
                            $innermost->whereHas('socialGroup', function($groupQuery) {
                                $groupQuery->where('privacy_level', 'public');
                            })
                            ->orWhereHas('socialGroup.members', function($memberQuery) use ($user) {
                                $memberQuery->where('user_id', $user->id)->where('status', 'approved');
                            });
                        });
                });
            })
            ->when(!empty($blockedUserIds), function($q) use ($blockedUserIds) {
                $q->whereNotIn('posts.user_id', $blockedUserIds);
            })
            ->orderByRaw("
                (
                    COALESCE(posts.cached_likes_count, 0) * 2 +
                    COALESCE(posts.cached_reactions_count, 0) * 2 +
                    COALESCE(posts.cached_comments_count, 0) * 3 +
                    CASE
                        WHEN posts.created_at >= ? THEN 20
                        WHEN posts.created_at >= ? THEN 10
                        WHEN posts.created_at >= ? THEN 5
                        ELSE 0
                    END
                ) DESC, posts.created_at DESC
            ", [
                \Carbon\Carbon::parse($anchor)->subHours(6)->toDateTimeString(),
                \Carbon\Carbon::parse($anchor)->subHours(24)->toDateTimeString(),
                \Carbon\Carbon::parse($anchor)->subHours(48)->toDateTimeString(),
            ]);

        return $query;
    }
}