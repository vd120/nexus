<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Story;
use App\Models\Block;
use App\Models\Follow;
use App\Models\PostReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Get comprehensive statistics
        $stats = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_comments' => Comment::count(),
            'total_stories' => Story::count(),
            'total_follows' => Follow::count(),
            'total_blocks' => Block::count(),
            'total_reports' => PostReport::count(),
            'pending_reports' => PostReport::where('status', PostReport::STATUS_PENDING)->count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'private_profiles' => User::whereHas('profile', function($q) {
                $q->where('is_private', true);
            })->count(),
            'recent_users' => User::with('profile')->latest()->take(5)->get(),
            'recent_posts' => Post::with(['user.profile', 'reactions'])->latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function users(Request $request)
    {
        $query = User::with('profile');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'LIKE', '%' . $search . '%')
                  ->orWhere('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('email', 'LIKE', '%' . $search . '%');
            });
        }

        // Filter by admin status
        if ($request->has('admin_filter')) {
            if ($request->admin_filter === 'admin') {
                $query->where('is_admin', true);
            } elseif ($request->admin_filter === 'user') {
                $query->where('is_admin', false);
            }
        }

        $users = $query->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function showUser(User $user)
    {
        $user->load(['profile', 'posts' => function($q) {
            $q->with(['media', 'comments.user', 'likes', 'reactions'])->latest();
        }, 'followers', 'follows', 'stories' => function($q) {
            $q->with(['storyViews', 'reactions'])->latest();
        }]);

        return view('admin.user-detail', compact('user'));
    }

    public function editUser(User $user)
    {
        $user->load('profile');
        return view('admin.user-edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|min:1|max:255',
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                'unique:users,username,' . $user->id,
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'password' => 'nullable|string|min:8',
            'is_admin' => 'boolean',
            'bio' => 'nullable|string|max:500',
            'about' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'location' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'is_private' => 'boolean',
            'is_suspended' => 'boolean',
            'birth_date' => 'nullable|date|before:today',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Update user basic information
        $updateData = [
            'username' => $request->username,
            'name' => $request->name ?? $user->name,
            'email' => $request->email,
            'is_admin' => $request->has('is_admin'),
            'is_suspended' => $request->has('is_suspended'),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Prepare profile data
        $profileData = $request->only([
            'bio', 'about', 'website', 'location', 'occupation',
            'phone', 'gender', 'is_private', 'birth_date'
        ]);

        // Ensure is_private is properly set
        $profileData['is_private'] = $request->has('is_private');

        // Debug logging for image removal
        \Log::info('Admin image removal debug', [
            'remove_avatar' => $request->input('remove_avatar'),
            'remove_cover' => $request->input('remove_cover'),
            'has_avatar_file' => $request->hasFile('avatar'),
            'has_cover_file' => $request->hasFile('cover_image'),
        ]);

        // Handle avatar removal first (takes precedence over upload)
        if ($request->has('remove_avatar') && $request->input('remove_avatar') == '1') {
            // Load profile if not already loaded
            $user->load('profile');

            if ($user->profile && $user->profile->avatar) {
                // Delete the file from storage
                $avatarPath = storage_path('app/public/' . $user->profile->avatar);
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                    \Log::info('Admin avatar removed: ' . $avatarPath);
                }
            }
            $profileData['avatar'] = null;
            \Log::info('Admin avatar set to null');
        }
        // Handle avatar upload with compression (only if not being removed)
        elseif ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');

            // Compress and resize avatar (square, 200x200 max)
            $manager = new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
            $avatarImage = $manager->read($avatarFile);

            $filename = time() . '_avatar_' . uniqid() . '.jpg';
            $avatarPath = 'avatars/' . $filename;
            
            // Ensure directory exists
            $fullPath = storage_path('app/public/avatars');
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            $avatarImage->cover(200, 200)->toJpeg(90)->save(storage_path('app/public/' . $avatarPath));

            \Log::info('Admin avatar compressed and stored at: ' . $avatarPath);
            $profileData['avatar'] = $avatarPath;
        }

        // Handle cover image removal first (takes precedence over upload)
        if ($request->has('remove_cover') && $request->input('remove_cover') == '1') {
            // Load profile if not already loaded
            $user->load('profile');

            if ($user->profile && $user->profile->cover_image) {
                // Delete the file from storage
                $coverPath = storage_path('app/public/' . $user->profile->cover_image);
                if (file_exists($coverPath)) {
                    unlink($coverPath);
                    \Log::info('Admin cover image removed: ' . $coverPath);
                }
            }
            $profileData['cover_image'] = null;
            \Log::info('Admin cover image set to null');
        }
        // Handle cover image upload with compression (only if not being removed)
        elseif ($request->hasFile('cover_image')) {
            $coverFile = $request->file('cover_image');

            // Compress and resize cover image (1200px width max, maintain aspect ratio)
            $coverManager = new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
            $coverImage = $coverManager->read($coverFile);

            $filename = time() . '_cover_' . uniqid() . '.jpg';
            $coverPath = 'covers/' . $filename;

            // Ensure directory exists
            $fullPath = storage_path('app/public/covers');
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Resize to max width while maintaining aspect ratio
            if ($coverImage->width() > 1200) {
                $coverImage->scale(width: 1200);
            }

            $coverImage->toJpeg(85)->save(storage_path('app/public/' . $coverPath));

            \Log::info('Admin cover image compressed and stored at: ' . $coverPath);
            $profileData['cover_image'] = $coverPath;
        }

        // Update or create profile
        $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);

        // If suspended, notify the user immediately via socket
        if ($request->has('is_suspended') && !$user->getOriginal('is_suspended')) {
            app(\App\Services\SocketEmitService::class)->emitToUser($user->id, 'account:status', [
                'status' => 'suspended',
                'message' => __('messages.account_suspended_contact_admin'),
                'redirect_url' => route('login.view')
            ]);
        }

        return redirect()->route('admin.users.show', $user)->with('success', __('messages.user_updated'));
    }

    public function deleteUser(User $user)
    {
        // Prevent deleting admin accounts (except self)
        if ($user->is_admin && $user->id !== auth()->id()) {
            return redirect()->back()->with('error', __('messages.cannot_delete_admin'));
        }

        // Notify the user via socket before deletion
        $originalLocale = app()->getLocale();
        if ($user->language) {
            app()->setLocale($user->language);
        }

        $message = __('messages.account_deleted_contact_admin');

        app()->setLocale($originalLocale);

        app(\App\Services\SocketEmitService::class)->emitToUser($user->id, 'account:status', [
            'status' => 'deleted',
            'message' => $message,
            'redirect_url' => route('login.view')
        ]);

        // Delete user and all related data
        $user->delete();

        return redirect()->route('admin.users')->with('success', __('messages.user_deleted'));
    }

    public function posts(Request $request)
    {
        $query = Post::with(['user', 'media', 'comments', 'likes', 'reactions']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('content', 'LIKE', '%' . $search . '%');
        }

        $posts = $query->latest()->paginate(20);

        return view('admin.posts', compact('posts'));
    }

    public function deletePost(Post $post)
    {
        // Delete associated media files
        foreach ($post->media as $media) {
            if ($media->media_path && Storage::disk('public')->exists($media->media_path)) {
                Storage::disk('public')->delete($media->media_path);
            }
        }

        $post->delete();

        return redirect()->back()->with('success', __('messages.post_deleted'));
    }

    public function comments(Request $request)
    {
        $query = Comment::with(['user', 'post.user', 'likes']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('content', 'LIKE', '%' . $search . '%');
        }

        $comments = $query->latest()->paginate(20);

        return view('admin.comments', compact('comments'));
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();

        return redirect()->back()->with('success', __('messages.comment_deleted'));
    }

    public function stories(Request $request)
    {
        $query = Story::with(['user', 'storyViews', 'reactions']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('username', 'LIKE', '%' . $search . '%')
                  ->orWhere('name', 'LIKE', '%' . $search . '%');
            });
        }

        $stories = $query->latest()->paginate(20);

        return view('admin.stories', compact('stories'));
    }

    public function deleteStory(Story $story)
    {
        // Delete media file
        if ($story->media_path && Storage::disk('public')->exists($story->media_path)) {
            Storage::disk('public')->delete($story->media_path);
        }

        $story->delete();

        return redirect()->back()->with('success', __('messages.story_deleted'));
    }



    public function createAdminAccount(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:1|max:255',
            'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_-]+$/|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Administrator Account',
                'is_private' => false,
            ]
        );

        return redirect()->back()->with('success', __('messages.admin_account_created'));
    }
}