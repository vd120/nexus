<?php

namespace App\Http\Controllers;

use App\Models\SocialGroup;
use App\Models\SocialGroupMember;
use App\Models\SocialGroupAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Requests\CreateSocialGroupRequest;
use App\Notifications\GroupJoinRequestedNotification;
use App\Notifications\GroupWelcomeNotification;
use Illuminate\Support\Facades\Notification;

class SocialGroupController extends Controller
{
    /**
     * List discoverable groups.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Groups the user is already a member of
        $myGroups = $user->socialGroups()->withCount('members')->get();

        // Discoverable groups (including joined ones)
        $groups = SocialGroup::where('is_discoverable', true)
            ->withCount('members')
            ->paginate(20);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'my_groups' => $myGroups,
                'discover_groups' => $groups
            ]);
        }

        $joinedIds = $myGroups->pluck('id')->toArray();
        return view('communities.index', compact('groups', 'myGroups', 'joinedIds'));
    }

    /**
     * Create a new group.
     */
    public function store(CreateSocialGroupRequest $request)
    {
        $validated = $request->validated();
        
        $group = SocialGroup::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
            'description' => $validated['description'] ?? null,
            'creator_id' => Auth::id(),
            'privacy_level' => $validated['privacy_level'] ?? 'public',
        ]);

        // Creator becomes the first admin
        SocialGroupMember::create([
            'social_group_id' => $group->id,
            'user_id' => Auth::id(),
            'role' => 'admin',
            'status' => 'approved',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($group, 201);
        }

        return redirect()->route('communities.show', $group->slug);
    }

    /**
     * Display group feed and metadata.
     */
    public function show(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)
            ->with(['rules', 'topics', 'badges'])
            ->withCount('members')
            ->firstOrFail();

        $this->authorize('view', $group);

        $posts = $group->posts()
            ->where(function($query) {
                $query->where('is_approved', true)
                      ->orWhere('user_id', auth()->id());
            })
            ->with(['user', 'media', 'socialGroupTopic', 'member' => function($query) use ($group) {
                $query->where('social_group_members.social_group_id', $group->id)->with('badges');
            }, 'comments.member' => function($query) use ($group) {
                $query->where('social_group_members.social_group_id', $group->id);
            }])
            ->orderByRaw('pinned_at DESC, created_at DESC')
            ->paginate(15);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['group' => $group, 'posts' => $posts]);
        }

        $topMembers = $group->members()
            ->where('status', 'approved')
            ->with('user.profile')
            ->orderByRaw("FIELD(role, 'admin', 'moderator', 'member')")
            ->limit(6)
            ->get();

        $recentMedia = $group->posts()
            ->where('is_approved', true)
            ->with('media')
            ->has('media')
            ->latest()
            ->limit(12)
            ->get()
            ->pluck('media')
            ->flatten()
            ->where('media_type', 'image')
            ->take(6)
            ->values();

        return view('communities.show', compact('group', 'posts', 'topMembers', 'recentMedia'));
    }

    /**
     * Join a group.
     */
    public function join(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        if ($group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already a member or request pending.'], 422);
        }

        // Validate questions if they exist
        if ($group->questions()->exists()) {
            $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|exists:social_group_questions,id',
                'answers.*.answer' => 'required|string',
            ]);

            foreach ($request->answers as $answer) {
                SocialGroupAnswer::create([
                    'social_group_id' => $group->id,
                    'user_id' => $user->id,
                    'question_id' => $answer['question_id'],
                    'answer' => $answer['answer'],
                ]);
            }
        }

        $status = $group->privacy_level === 'public' ? 'approved' : 'pending';

        $member = SocialGroupMember::create([
            'social_group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => $status,
        ]);

        if ($status === 'approved') {
            try {
                $memberCount = $group->members()->where('status', 'approved')->count();
                app(\App\Services\SocketEmitService::class)->emit('global', 'community:member_count', [
                    'group_id' => $group->id,
                    'slug' => $group->slug,
                    'members_count' => $memberCount,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to emit community:member_count: ' . $e->getMessage());
            }
        }

        if ($status === 'pending') {
            $admins = $group->members()
                ->whereIn('role', ['admin', 'moderator'])
                ->where('status', 'approved')
                ->get();

            foreach ($admins as $adminMember) {
                \App\Http\Controllers\NotificationController::createNotification(
                    $adminMember->user_id,
                    'group_join_requested',
                    [
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'group_slug' => $group->slug,
                        'requester_id' => $user->id,
                        'requester_name' => $user->username,
                    ]
                );
            }
        }

        if ($status === 'approved' && $group->welcome_message) {
            \App\Http\Controllers\NotificationController::createNotification(
                $user->id,
                'group_join_accepted',
                [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'group_slug' => $group->slug,
                ]
            );
        }

        return response()->json([
            'message' => $status === 'approved' ? 'Joined successfully.' : 'Join request submitted.',
            'status' => $status,
        ], $status === 'approved' ? 200 : 202);
    }

    /**
     * Leave a group.
     */
    public function leave($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        if ($group->creator_id === $user->id) {
            return response()->json(['message' => 'Creators cannot leave their own group. Delete it instead.'], 422);
        }

        $group->members()->where('user_id', $user->id)->delete();

        try {
            $memberCount = $group->members()->where('status', 'approved')->count();
            app(\App\Services\SocketEmitService::class)->emit('global', 'community:member_count', [
                'group_id' => $group->id,
                'slug' => $group->slug,
                'members_count' => $memberCount,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to emit community:member_count: ' . $e->getMessage());
        }

        return response()->json(null, 204);
    }

    /**
     * Display group about page.
     */
    public function about($slug)
    {
        $group = SocialGroup::where('slug', $slug)
            ->with(['rules', 'topics', 'badges'])
            ->firstOrFail();

        $this->authorize('view', $group);

        return view('communities.about', compact('group'));
    }

    /**
     * Display group members page.
     */
    public function members(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('view', $group);

        $members = $group->members()
            ->where('status', 'approved')
            ->with('user')
            ->paginate(30);

        return view('communities.members', compact('group', 'members'));
    }

    /**
     * Update user preferences.
     */
    public function updateUserPreferences(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        $member = $group->members()->where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'notification_preference' => 'in:all,highlights,none',
            'is_anonymous_default' => 'boolean',
            'anonymous_username' => 'nullable|string|max:255',
        ]);

        $member->update($validated);

        return response()->json($member);
    }

    /**
     * Display member settings page.
     */
    public function settings($slug)
    {
        $group = SocialGroup::where('slug', $slug)->withCount('members')->firstOrFail();
        $this->authorize('view', $group);

        $member = $group->members()->where('user_id', auth()->id())->where('status', 'approved')->firstOrFail();

        return view('communities.settings', compact('group', 'member'));
    }
}
