<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class StoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $followedUsersWithStories = User::whereHas('followers', function($query) use ($user) {
            $query->where('follower_id', $user->id);
        })->whereHas('activeStories')->with(['activeStories'])->get();

        $myStories = $user->activeStories;

        return view('stories.index', compact('followedUsersWithStories', 'myStories'));
    }

    public function create()
    {
        return view('stories.create');
    }

    public function store(Request $request)
    {
        // Check if this is a text-only story
        if ($request->has('text_only') && $request->text_only) {
            $request->validate([
                'content' => 'required|string|max:500',
            ]);

            // Create text-only story with background color
            $story = Story::create([
                'user_id' => auth()->id(),
                'media_type' => 'text',
                'media_path' => null,
                'content' => $request->content,
                'expires_at' => now()->addHours(24),
                'metadata' => [
                    'bg_color' => $request->input('bg_color', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)')
                ],
            ]);

            // Clear stories cache for the current user to ensure it updates on feed
            cache()->forget('user_stories_' . auth()->id());

            // Broadcast to followers in real time
            try {
                $socketService = app(\App\Services\SocketEmitService::class);
                $user = auth()->user();
                foreach ($user->followers as $follower) {
                    $socketService->emitToUser($follower->follower_id, 'story:new', [
                        'username' => $user->username,
                        'storySlug' => $story->slug,
                        'avatarUrl' => $user->avatar_url,
                        'mediaType' => $story->media_type,
                        'mediaPath' => $story->media_path ? asset('storage/' . $story->media_path) : null,
                        'content' => $story->content,
                        'bgColor' => $story->metadata['bg_color'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                        'timeAgo' => $story->created_at->diffForHumans(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Real-time story broadcast failed: ' . $e->getMessage());
            }

            return redirect()->route('stories.index')->with('success', __('messages.story_posted'));
        }

        // Media story (existing logic)
        $request->validate([
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,webm|max:51200', // 50MB max
            'content' => 'nullable|string|max:280',
        ]);

        $file = $request->file('media');
        $mimeType = $file->getMimeType();

        if (str_contains($mimeType, 'image/')) {
            // Handle image upload with compression
            $manager = new ImageManager(new Driver());
            $compressedImage = $manager->read($file);

            // Compress image
            $maxWidth = 1080;
            $maxHeight = 1920;
            $quality = 85;

            // Resize if too large
            if ($compressedImage->width() > $maxWidth || $compressedImage->height() > $maxHeight) {
                $compressedImage->scale(width: $maxWidth, height: $maxHeight);
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.jpg';
            $path = 'stories/images/' . $filename;

            // Ensure directory exists
            $directory = dirname(storage_path('app/public/' . $path));
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save compressed image
            $compressedImage->toJpeg($quality)->save(storage_path('app/public/' . $path));

            $mediaType = 'image';
        } elseif (str_contains($mimeType, 'video/')) {
            // Handle video upload
            $videoMimeTypes = ['video/mp4', 'video/mov', 'video/avi', 'video/webm'];

            if (!in_array($mimeType, $videoMimeTypes)) {
                return back()->withErrors(['media' => __('messages.invalid_video_format')]);
            }

            if ($file->getSize() > 52428800) { // 50MB in bytes
                return back()->withErrors(['media' => __('messages.video_too_large')]);
            }

            // Get trim values if provided (max 60 seconds)
            $trimStart = $request->input('trim_start', 0);
            $trimEnd = $request->input('trim_end', 60);
            
            // Ensure trim values are valid
            $trimStart = max(0, floatval($trimStart));
            $trimEnd = min(60, max($trimStart + 1, floatval($trimEnd)));
            
            // Determine if trimming is needed
            $needsTrimming = ($trimEnd - $trimStart) < 60 || $trimStart > 0 || $trimEnd < 60;
            
            $filename = time() . '_' . uniqid() . '.mp4';
            $path = 'stories/videos/' . $filename;

            // Ensure directory exists
            $directory = storage_path('app/public/stories/videos');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            if ($needsTrimming && $trimEnd - $trimStart > 0) {
                // Use ffmpeg to trim the video using Symfony Process for security
                $tempPath = $file->storeAs('temp', 'temp_' . time() . '.' . $file->getClientOriginalExtension());
                $tempFullPath = storage_path('app/' . $tempPath);
                $outputPath = storage_path('app/public/' . $path);

                // Build ffmpeg command to trim video using Symfony Process
                $duration = $trimEnd - $trimStart;
                
                $process = new \Symfony\Component\Process\Process([
                    'ffmpeg',
                    '-i', $tempFullPath,
                    '-ss', (string) $trimStart,
                    '-t', (string) $duration,
                    '-c:v', 'libx264',
                    '-c:a', 'aac',
                    '-strict', 'experimental',
                    '-movflags', '+faststart',
                    $outputPath
                ]);
                
                $process->setTimeout(60); // 60 second timeout
                $process->run();

                // Clean up temp file
                if (file_exists($tempFullPath)) {
                    unlink($tempFullPath);
                }

                if (!$process->isSuccessful() || !file_exists($outputPath)) {
                    // If ffmpeg fails, fall back to original file
                    $file->move($directory, $filename);
                }
            } else {
                // No trimming needed, just move the file
                $file->move($directory, $filename);
            }

            $mediaType = 'video';
        } else {
            return back()->withErrors(['media' => __('messages.invalid_file_type')]);
        }

        // Create story with 24-hour expiration
        $story = Story::create([
            'user_id' => auth()->id(),
            'media_type' => $mediaType,
            'media_path' => $path,
            'content' => $request->content,
            'expires_at' => now()->addHours(24),
        ]);

        // Clear stories cache for the current user to ensure it updates on feed
        cache()->forget('user_stories_' . auth()->id());

        // Broadcast to followers in real time
        try {
            $socketService = app(\App\Services\SocketEmitService::class);
            $user = auth()->user();
            foreach ($user->followers as $follower) {
                $socketService->emitToUser($follower->follower_id, 'story:new', [
                    'username' => $user->username,
                    'storySlug' => $story->slug,
                    'avatarUrl' => $user->avatar_url,
                    'mediaType' => $story->media_type,
                    'mediaPath' => $story->media_path ? asset('storage/' . $story->media_path) : null,
                    'content' => $story->content,
                    'bgColor' => $story->metadata['bg_color'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'timeAgo' => $story->created_at->diffForHumans(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Real-time story broadcast failed: ' . $e->getMessage());
        }

        return redirect()->route('stories.index')->with('success', __('messages.story_posted'));
    }

    public function show(User $user, Story $story, Request $request)
    {
        // Check if current user can view this user's stories
        $currentUser = auth()->user();

        if ($user->id !== $currentUser->id && !$currentUser->isFollowing($user)) {
            abort(403, __('messages.view_stories_followed_only'));
        }

        // Get all active stories from this user with their views
        $stories = $user->activeStories()->with('storyViews')->get();

        // If a specific story is requested via route parameter, prioritize it but show all stories
        if ($story && $story->user_id === $user->id) {
            // Mark story as viewed
            if ($story->user_id !== $currentUser->id) {
                // Check if this user has already viewed this story
                $existingView = \App\Models\StoryView::where('user_id', $currentUser->id)
                    ->where('story_id', $story->id)
                    ->exists();

                if (!$existingView) {
                    // Record the view and increment the count
                    \App\Models\StoryView::create([
                        'user_id' => $currentUser->id,
                        'story_id' => $story->id
                    ]);
                    $story->increment('views');
                }
            }

            // Sort stories to show the requested one first
            $stories = $stories->sortBy(function ($s) use ($story) {
                return $s->id == $story->id ? 0 : 1;
            })->values();
        }

        return view('stories.show', compact('user', 'stories'));
    }

    public function viewers(User $user, Story $story)
    {
        // Check if current user is the story author
        if ($story->user_id !== auth()->id()) {
            abort(403, __('messages.view_viewers_own_only'));
        }

        // Get all users who viewed this story with their view timestamps and reactions
        // Eager load reactions to prevent N+1 queries
        $viewers = \App\Models\StoryView::where('story_id', $story->id)
            ->with(['user.profile'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all reactions for this story in a single query
        $reactions = \App\Models\StoryReaction::where('story_id', $story->id)
            ->get()
            ->keyBy('user_id');

        // Transform the data to include reaction info
        $viewerData = $viewers->map(function($viewer) use ($reactions) {
            return [
                'user' => $viewer->user,
                'viewed_at' => $viewer->created_at,
                'reaction' => $reactions->has($viewer->user_id) ? $reactions->get($viewer->user_id)->reaction_type : null
            ];
        });

        return view('stories.viewers', compact('user', 'story', 'viewerData'));
    }

    public function react(string $user, Request $request, Story $story)
    {
        $authUser = auth()->user();

        // Check if user can view this story
        if ($story->user_id !== $authUser->id && !$authUser->isFollowing($story->user)) {
            abort(403, __('messages.react_stories_followed_only'));
        }

        // Check if user already reacted to this story
        $existingReaction = \App\Models\StoryReaction::where('user_id', $authUser->id)
            ->where('story_id', $story->id)
            ->first();

        $isNewReaction = false;
        if ($existingReaction) {
            // Update existing reaction
            $existingReaction->update(['reaction_type' => $request->reaction_type]);
        } else {
            // Create new reaction
            $newReaction = \App\Models\StoryReaction::create([
                'user_id' => $authUser->id,
                'story_id' => $story->id,
                'reaction_type' => $request->reaction_type
            ]);
            $isNewReaction = true;
        }

        // Create notification for story owner (only for new reactions)
        if ($isNewReaction && $story->user_id !== $authUser->id) {
            NotificationController::createNotification(
                $story->user_id,
                'story_reaction',
                [
                    'reactor_name' => $authUser->username,
                    'reactor_username' => $authUser->username,
                    'reaction_type' => $request->reaction_type,
                    'story_id' => $story->id,
                    'story_slug' => $story->slug,
                    'story_owner_username' => $story->user->username,
                ],
                $story
            );
        }

        return response()->json(['success' => true]);
    }

    public function removeReaction(string $user, Story $story)
    {
        $authUser = auth()->user();

        // Delete user's reaction from this story
        \App\Models\StoryReaction::where('user_id', $authUser->id)
            ->where('story_id', $story->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function getReactions(string $user, Story $story)
    {
        // Get all reactions for this story
        $reactions = \App\Models\StoryReaction::where('story_id', $story->id)
            ->with(['user.profile'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($reaction) {
                return [
                    'user_id' => $reaction->user_id,
                    'user_username' => $reaction->user->username,
                    'user_avatar' => $reaction->user->avatar_url ?? null,
                    'reaction_type' => $reaction->reaction_type
                ];
            });

        return response()->json([
            'success' => true,
            'reactions' => $reactions
        ]);
    }

    public function checkReaction(string $user, Story $story)
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'has_reaction' => false
            ], 401);
        }

        // Check if user has already reacted to this story
        $hasReaction = \App\Models\StoryReaction::where('user_id', $authUser->id)
            ->where('story_id', $story->id)
            ->exists();

        return response()->json([
            'success' => true,
            'has_reaction' => $hasReaction
        ]);
    }

    public function unreact(Story $story)
    {
        $user = auth()->user();

        // Check if user can view this story
        if ($story->user_id !== $user->id && !$user->isFollowing($story->user)) {
            abort(403, __('messages.unreact_stories_followed_only'));
        }

        // Remove user's reaction from this story
        \App\Models\StoryReaction::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function destroy(string $user, Story $story)
    {
        if ($story->user_id !== auth()->id()) {
            abort(403);
        }

        $storyId = $story->id;
        $userId = $story->user_id;
        $userName = $story->user->username;

        // Delete the media file only if it exists (not for text-only stories)
        if ($story->media_path) {
            Storage::disk('public')->delete($story->media_path);
        }

        $story->delete();

        // Clear stories cache for the current user
        cache()->forget('user_stories_' . auth()->id());

        // Broadcast story deletion or update to followers in real time
        try {
            $userModel = auth()->user();
            $remainingStories = $userModel->activeStories()->orderBy('created_at', 'desc')->get();
            $socketService = app(\App\Services\SocketEmitService::class);
            
            if ($remainingStories->count() > 0) {
                // If there are other active stories, update the click handler to target the next latest story
                $nextStory = $remainingStories->first();
                foreach ($userModel->followers as $follower) {
                    $socketService->emitToUser($follower->follower_id, 'story:new', [
                        'username' => $userModel->username,
                        'storySlug' => $nextStory->slug,
                        'avatarUrl' => $userModel->avatar_url,
                    ]);
                }
            } else {
                // If no active stories are left, completely remove the story avatar from followers' feeds
                foreach ($userModel->followers as $follower) {
                    $socketService->emitToUser($follower->follower_id, 'story:deleted', [
                        'username' => $userModel->username,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Real-time story delete broadcast failed: ' . $e->getMessage());
        }

        // Log for debugging
        \Log::info('Story deleted', [
            'story_id' => $storyId,
            'user_id' => $userId,
            'user_name' => $userName
        ]);

        // Check if it's an AJAX request
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.story_deleted')
            ]);
        }

        return back();
    }
}
