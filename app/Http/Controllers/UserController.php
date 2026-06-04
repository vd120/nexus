<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\LifeChapter;
use App\Models\Post;
use App\Models\User;
use App\Services\QrCodeService;
use App\Services\SocketEmitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected QrCodeService $qrCodeService)
    {
    }

    /**
     * Display user profile
     */
    public function show(User $user)
    {
        $user->load(['profile']);

        $postsCount = $user->posts()->count();
        $followersCount = $user->followers()->count();
        $followingCount = $user->follows()->count();
        $blockedCount = 0;
        $pinnedCount = 0;

        $isFollowing = false;
        $isBlocking = false;
        $isBlockedBy = false;
        $isOwner = false;

        if (auth()->check()) {
            $isFollowing = auth()->user()->isFollowing($user);
            $isBlocking = auth()->user()->isBlocking($user);
            $isBlockedBy = $user->isBlocking(auth()->user());
            $isOwner = auth()->id() === $user->id;

            if ($isOwner) {
                $blockedCount = $user->blockedUsers()->count();
            }
        }

        // Get pinned posts (ordered by pinned_at for custom ordering)
        $pinnedPostsQuery = $user->posts()->pinned();
        if (!$isOwner) {
            $pinnedPostsQuery->where('is_anonymous', false);
        }

        $pinnedPosts = $pinnedPostsQuery
            ->with(['media', 'comments.replies.user', 'comments.likes', 'likes', 'reactions.user:id,name,username', 'user'])
            ->orderBy('pinned_at', 'asc')
            ->get();

        $pinnedCount = $pinnedPosts->count();

        // Get non-pinned posts
        $postsQuery = $user->posts()->notPinned();
        if (!$isOwner) {
            $postsQuery->where('is_anonymous', false);
        }

        $posts = $postsQuery
            ->with(['media', 'comments.replies.user', 'comments.likes', 'likes', 'reactions.user:id,name,username'])
            ->latest()
            ->paginate(10);

        return view('users.show', compact(
            'user',
            'posts',
            'pinnedPosts',
            'postsCount',
            'pinnedCount',
            'followersCount',
            'followingCount',
            'blockedCount',
            'isFollowing',
            'isBlocking',
            'isBlockedBy',
            'isOwner'
        ));
    }

    /**
     * Display a single life chapter timeline page for a user.
     */
    public function chapterPage(User $user, $chapter)
    {
        $chapter = LifeChapter::where('id', $chapter)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $isOwner = auth()->check() && auth()->id() === $user->id;

        $postsQuery = Post::where('user_id', $user->id)
            ->where('life_chapter_id', $chapter->id);

        if (!$isOwner) {
            $postsQuery->where('is_anonymous', false)
                       ->where('is_private', false)
                       ->where('is_approved', true)
                       ->whereNull('social_group_id');
        }

        $posts = $postsQuery
            ->with(['media', 'comments.replies.user', 'comments.likes', 'likes', 'reactions.user:id,name,username', 'user'])
            ->latest()
            ->paginate(10);

        return view('users.chapter', [
            'user'    => $user,
            'chapter' => $chapter,
            'posts'   => $posts,
            'isOwner' => $isOwner,
        ]);
    }

    /**
     * Follow/Unfollow a user
     */
    public function follow(User $user)
    {
        $currentUser = auth()->user();

        if ($currentUser->isFollowing($user)) {
            $currentUser->follows()->where('followed_id', $user->id)->delete();
        } else {
            $follow = $currentUser->follows()->create(['followed_id' => $user->id]);

            NotificationController::createNotification(
                $user->id,
                'follow',
                [
                    'follower_name' => $currentUser->username,
                    'follower_id' => $currentUser->id
                ],
                $follow
            );
        }

        // Check if it's an AJAX request
        if (request()->expectsJson()) {
            // Check if the user has an active story
            $hasStory = false;
            $storySlug = null;
            $latestStory = $user->activeStories()->latest()->first();
            if ($latestStory) {
                $hasStory = true;
                $storySlug = $latestStory->slug;
            }

            $isNowFollowing = $currentUser->isFollowing($user);

            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'is_following' => $isNowFollowing,
                'message' => $isNowFollowing ? __('messages.user_followed') : __('messages.user_unfollowed'),
                'followers_count' => $user->followers()->count(),
                'user' => [
                    'has_story' => $hasStory,
                    'story_slug' => $storySlug,
                    'avatar_url' => $user->avatar_url
                ]
            ]);
        }

        return back();
    }

    /**
     * Block/Unblock a user
     */
    public function block(User $user)
    {
        // Prevent blocking admin users
        if ($user->is_admin) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.cannot_block_admin')
                ], 403);
            }
            return back()->with('error', __('messages.cannot_block_admin'));
        }

        $currentUser = auth()->user();
        
        if ($currentUser->isBlocking($user)) {
            $currentUser->blockedUsers()->where('blocked_id', $user->id)->delete();
        } else {
            $currentUser->blockedUsers()->create(['blocked_id' => $user->id]);
            $currentUser->follows()->where('followed_id', $user->id)->delete();
        }

        // Check if it's an AJAX request
        if (request()->expectsJson()) {
            $isBlocking = $currentUser->isBlocking($user);
            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'blocking' => $isBlocking,
                'followers_count' => $user->followers()->count(),
                'message' => $isBlocking ? __('messages.user_blocked') : __('messages.user_unblocked')
            ]);
        }

        return back();
    }

    /**
     * Show user's memory prompt answers
     */
    public function memories(User $user)
    {
        $isOwner = auth()->check() && auth()->id() === $user->id;

        $memoriesQuery = \App\Models\PulseAnswer::where('user_id', $user->id)
            ->whereHas('prompt', function($q) {
                $q->where('type', 'memory');
            })
            ->with('prompt');

        // Non-owners only see public or followers-only (if following)
        if (!$isOwner) {
            $memoriesQuery->where(function($q) use ($user) {
                $q->where('visibility', 'public');
                if (auth()->check() && auth()->user()->isFollowing($user)) {
                    $q->orWhere('visibility', 'followers');
                }
            });
        }

        $memories = $memoriesQuery->latest()->paginate(20);

        return view('users.memories', compact('user', 'memories'));
    }

    /**
     * Display followers list
     */
    public function followers(User $user)
    {
        $followers = $user->followers()->with('follower.profile')->get();
        $followingIds = auth()->check() ? auth()->user()->follows()->pluck('followed_id')->toArray() : [];
        return view('users.followers', compact('user', 'followers', 'followingIds'));
    }

    /**
     * Display following list
     */
    public function following(User $user)
    {
        $following = $user->follows()->with('followed.profile')->get();
        $followingIds = auth()->check() ? auth()->user()->follows()->pluck('followed_id')->toArray() : [];
        return view('users.following', compact('user', 'following', 'followingIds'));
    }

    /**
     * Display blocked users list
     */
    public function blocked(User $user)
    {
        if ($user->id !== auth()->id()) {
            abort(403, __('messages.view_own_blocked_only'));
        }

        $blocked = $user->blockedUsers()->with('blocked')->get();
        return view('users.blocked', compact('user', 'blocked'));
    }

    /**
     * Display saved posts list
     */
    public function savedPosts()
    {
        $user = auth()->user();
        $savedPosts = \App\Models\SavedPost::where('user_id', $user->id)
            ->whereHas('post')
            ->with(['post.user', 'post.media', 'post.reactions.user:id,name,username', 'post.comments.replies.user', 'post.comments.likes', 'post.userSavedPost', 'post.socialGroup', 'post.userReaction'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('users.saved-posts', compact('user', 'savedPosts'));
    }

    /**
     * Explore users
     */
    public function explore()
    {
        $currentUser = auth()->user();
        if (!$currentUser) return redirect()->route('home');

        $users = User::where('id', '!=', $currentUser->id)
            ->with(['profile'])
            ->withCount(['followers', 'follows'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $blockedByCurrentUser = $currentUser->blockedUsers()->pluck('blocked_id')->toArray();
        $blockedCurrentUser = Block::where('blocked_id', $currentUser->id)->pluck('blocker_id')->toArray();
        $followingIds = $currentUser->follows()->pluck('followed_id')->toArray();

        return view('users.explore', compact('users', 'blockedByCurrentUser', 'blockedCurrentUser', 'followingIds'));
    }

    /**
     * Display search page
     */
    public function searchPage()
    {
        if (!auth()->check()) return redirect()->route('home');
        return view('users.search');
    }

    /**
     * Display edit profile page
     */
    public function editProfile(User $user)
    {
        if ($user->id !== auth()->id()) abort(403);
        $user->load('profile');
        $user->profile()->firstOrCreate([]);
        return view('users.edit-profile', compact('user'));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_-]+$/|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'bio' => 'nullable|string|max:500',
            'about' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'location' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'is_private'            => 'nullable|boolean',
            'show_online_status'    => 'nullable|boolean',
            'show_read_receipts'    => 'nullable|boolean',
            'birth_date'            => 'nullable|date|before:today',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $usernameChanged = $user->username !== $request->username;
        $user->update($request->only(['name', 'username', 'email']));

        $data = $request->only(['bio', 'about', 'website', 'location', 'occupation', 'phone', 'gender', 'birth_date']);
        $data['is_private']          = $request->has('is_private');
        $data['show_online_status']  = $request->has('show_online_status');
        $data['show_read_receipts']  = $request->has('show_read_receipts');

        $fileService = app(\App\Services\FileUploadService::class);

        // Avatar upload
        if ($request->hasFile('avatar')) {
            $path = $fileService->compressImage($request->file('avatar'), 'avatars', [
                'cover' => [200, 200],
                'quality' => 85,
                'prefix' => 'avatar',
            ]);
            if ($path) {
                $data['avatar'] = $path;
            }
        }

        // Cover image upload
        if ($request->hasFile('cover_image')) {
            $path = $fileService->compressImage($request->file('cover_image'), 'covers', [
                'maxWidth' => 1280,
                'quality' => 80,
                'prefix' => 'cover',
            ]);
            if ($path) {
                $data['cover_image'] = $path;
            }
        }

        $user->profile()->updateOrCreate(['user_id' => $user->id], $data);
        if ($usernameChanged) {
            app(\App\Services\ActivityService::class)->logUsernameChange($user->id);
        } else {
            app(\App\Services\ActivityService::class)->logProfileUpdate($user->id);
        }

        return redirect()->route('users.show', $user)->with('success', __('messages.profile_updated'));
    }

    /**
     * Delete profile avatar
     */
    public function deleteAvatar()
    {
        $user = auth()->user();
        if ($user->profile && $user->profile->avatar) {
            if (file_exists(storage_path('app/public/' . $user->profile->avatar))) {
                unlink(storage_path('app/public/' . $user->profile->avatar));
            }
            $user->profile->update(['avatar' => null]);
            return response()->json(['success' => true, 'message' => __('messages.avatar_deleted')]);
        }
        return response()->json(['success' => false, 'message' => __('messages.no_avatar_to_delete')], 400);
    }

    /**
     * Delete profile cover image
     */
    public function deleteCoverImage()
    {
        $user = auth()->user();
        if ($user->profile && $user->profile->cover_image) {
            if (file_exists(storage_path('app/public/' . $user->profile->cover_image))) {
                unlink(storage_path('app/public/' . $user->profile->cover_image));
            }
            $user->profile->update(['cover_image' => null]);
            return response()->json(['success' => true, 'message' => __('messages.cover_deleted')]);
        }
        return response()->json(['success' => false, 'message' => __('messages.no_cover_to_delete')], 400);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $user = auth()->user();
        $request->validate(['password' => ['required', 'current_password']]);

        \DB::beginTransaction();
        try {
            $this->deleteUserMediaFiles($user);
            $user->storyViews()->delete();
            $user->storyReactions()->delete();
            foreach ($user->stories as $story) {
                if ($story->media_path && file_exists(storage_path('app/public/' . $story->media_path))) {
                    unlink(storage_path('app/public/' . $story->media_path));
                }
            }
            $user->stories()->delete();
            $user->commentLikes()->delete();
            $user->comments()->delete();
            $user->likes()->delete();
            $user->savedPosts()->delete();
            $user->blockedUsers()->delete();
            \App\Models\Block::where('blocked_id', $user->id)->delete();
            $user->follows()->delete();
            \App\Models\Follow::where('followed_id', $user->id)->delete();
            foreach ($user->posts as $post) {
                foreach ($post->media as $media) {
                    if ($media->media_path && file_exists(storage_path('app/public/' . $media->media_path))) {
                        unlink(storage_path('app/public/' . $media->media_path));
                    }
                }
                $post->media()->delete();
            }
            $user->posts()->delete();
            if ($user->profile) $user->profile->delete();
            
            $user->remember_token = null;
            $user->save();
            $user->delete();
            
            \DB::commit();
            \DB::purge();
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->hasCookie('remember_web_user')) \Cookie::queue(\Cookie::forget('remember_web_user'));

            return response()->json(['success' => true, 'message' => __('messages.account_deleted')]);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['success' => false, 'message' => __('messages.account_delete_failed')], 500);
        }
    }

    /**
     * Bulk online status for multiple user IDs — reduces N HTTP requests to 1
     */
    public function getBulkOnlineStatus(\Illuminate\Http\Request $request)
    {
        $ids = array_slice(array_filter(array_map('intval', (array) $request->input('ids', []))), 0, 100);
        if (empty($ids)) return response()->json(['success' => true, 'statuses' => []]);

        $users = User::whereIn('id', $ids)->get(['id', 'is_online', 'last_active']);
        $now = now();
        $statuses = [];
        foreach ($users as $user) {
            $isOnline = (bool) $user->is_online;
            $statuses[(string) $user->id] = $isOnline;
        }

        return response()->json(['success' => true, 'statuses' => $statuses]);
    }

    /**
     * Get user's online status (REST endpoint)
     */
    public function getOnlineStatus($userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['success' => false, 'message' => __('messages.user_not_found')], 404);

        $isOnline = (bool) $user->is_online;

        return response()->json([
            'success' => true,
            'user_id' => $userId,
            'is_online' => $isOnline,
            'last_active' => $user->last_active ? $user->last_active->toISOString() : null,
            'last_active_human' => $user->last_active ? __('chat.last_active') . ' ' . $user->last_active->diffForHumans() : null
        ]);
    }

    /**
     * Get username from user ID
     */
    public function getUsername($userId)
    {
        try {
            $user = User::findOrFail($userId);
            return response()->json(['success' => true, 'username' => $user->username]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('messages.user_not_found')], 404);
        }
    }

    /**
     * Delete all media files associated with a user
     */
    private function deleteUserMediaFiles(User $user)
    {
        if ($user->profile && $user->profile->avatar) {
            $path = storage_path('app/public/' . $user->profile->avatar);
            if (file_exists($path)) unlink($path);
        }
        if ($user->profile && $user->profile->cover_image) {
            $path = storage_path('app/public/' . $user->profile->cover_image);
            if (file_exists($path)) unlink($path);
        }
        foreach ($user->posts as $post) {
            foreach ($post->media as $media) {
                if ($media->media_path && file_exists(storage_path('app/public/' . $media->media_path))) {
                    unlink(storage_path('app/public/' . $media->media_path));
                }
            }
        }
        foreach ($user->stories as $story) {
            if ($story->media_path && file_exists(storage_path('app/public/' . $story->media_path))) {
                unlink(storage_path('app/public/' . $story->media_path));
            }
        }
    }

    /**
     * Generate QR code for user profile
     */
    public function generateQrCode(User $user)
    {
        $qrCodeSvg = $this->qrCodeService->generateProfileQrCode($user);
        $profileUrl = $this->qrCodeService->getProfileUrl($user);
        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'qr_code' => $qrCodeSvg, 'profile_url' => $profileUrl, 'username' => $user->username]);
        }
        return view('users.qr-code', compact('user', 'qrCodeSvg', 'profileUrl'));
    }

    /**
     * Download QR code
     */
    public function downloadQrCode(User $user)
    {
        $svgData = $this->qrCodeService->generateProfileQrCode($user, 400);
        return response($svgData, 200)->header('Content-Type', 'image/svg+xml')->header('Content-Disposition', 'attachment; filename="profile-qr-' . $user->username . '.svg"');
    }

    /**
     * Pin a post (max 3)
     */
    public function pinPost(Request $request, User $user, $postId)
    {
        $post = Post::findOrFail($postId);
        if ($post->user_id !== $user->id || $user->id !== auth()->id()) return response()->json(['success' => false, 'message' => __('messages.unauthorized')], 403);
        
        $limit = $user->is_admin ? 10 : 5;
        if ($user->posts()->pinned()->count() >= $limit && !$post->isPinned()) {
            return response()->json(['success' => false, 'message' => __('posts.max_pinned_reached', ['max' => $limit])], 422);
        }
        
        $post->pin();

        return response()->json(['success' => true, 'message' => __('posts.post_pinned'), 'post' => ['id' => $post->id, 'pinned_at' => $post->pinned_at->toISOString()]]);
    }

    /**
     * Unpin a post
     */
    public function unpinPost(Request $request, User $user, $postId)
    {
        $post = Post::findOrFail($postId);
        if ($post->user_id !== $user->id || $user->id !== auth()->id()) return response()->json(['success' => false, 'message' => __('messages.unauthorized')], 403);
        $post->unpin();

        return response()->json(['success' => true, 'message' => __('posts.post_unpinned'), 'post' => ['id' => $post->id, 'pinned_at' => null]]);
    }

    /**
     * Reorder pinned posts
     */
    public function reorderPinnedPosts(Request $request, User $user)
    {
        if ($user->id !== auth()->id()) return response()->json(['success' => false, 'message' => __('messages.unauthorized')], 403);
        $request->validate(['post_ids' => 'required|array', 'post_ids.*' => 'exists:posts,id']);
        $postIds = $request->input('post_ids');
        $ownedPosts = $user->posts()->whereIn('id', $postIds)->pinned()->pluck('id');
        if ($ownedPosts->count() !== count($postIds)) return response()->json(['success' => false, 'message' => __('messages.unauthorized')], 403);
        $baseTime = now()->subHours(count($postIds));
        foreach ($postIds as $index => $postId) {
            Post::where('id', $postId)->update(['pinned_at' => (clone $baseTime)->addMinutes($index)]);
        }

        return response()->json(['success' => true, 'message' => __('posts.posts_reordered')]);
    }

    /**
     * Search users for new chat (API)
     */
    public function apiSearch(Request $request)
    {
        $query = $request->query('q');
        if (empty($query) || strlen($query) < 1) {
            return response()->json(['success' => true, 'users' => []]);
        }

        $users = User::where(function($q) use ($query) {
                $q->where('username', 'LIKE', "%{$query}%")
                  ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->where('id', '!=', auth()->id())
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                    'is_online' => (bool) $user->is_online,
                    'is_verified' => (bool) $user->is_verified,
                ];
            });

        return response()->json(['success' => true, 'users' => $users]);
    }

    /**
     * Get following suggestions for @mention autocomplete
     */
    public function followingSuggestions(Request $request)
    {
        $search = $request->get('search', '');
        $user = auth()->user();
        
        $suggestions = $user->follows()
            ->whereHas('followed', function($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%');
            })
            ->with('followed')
            ->limit(8)
            ->get()
            ->map(function($follow) {
                return [
                    'id' => $follow->followed->id,
                    'username' => $follow->followed->username,
                    'name' => $follow->followed->name,
                    'avatar_url' => $follow->followed->avatar_url,
                    'is_verified' => (bool) $follow->followed->is_verified,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }

    /**
     * Check if a username is available
     */
    public function checkUsername(Request $request)
    {
        $username = $request->query('username');
        
        if (empty($username)) {
            return response()->json(['available' => false]);
        }

        // Basic validation matching the profile update regex
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            return response()->json(['available' => false, 'message' => 'Invalid characters']);
        }

        $exists = User::where('username', $username)
            ->where('id', '!=', auth()->id())
            ->exists();

        return response()->json(['available' => !$exists]);
    }
}
