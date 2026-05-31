<?php

namespace App\Http\Controllers;

use App\Models\SocialGroup;
use App\Models\SocialGroupMember;
use App\Models\SocialGroupRule;
use App\Models\SocialGroupTopic;
use App\Models\SocialGroupBadge;
use App\Models\PostReport;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateGroupSettingsRequest;
use Illuminate\Support\Facades\Auth;
use App\Notifications\GroupJoinAcceptedNotification;
use App\Notifications\GroupPostApprovedNotification;
use App\Notifications\GroupWelcomeNotification;
use Illuminate\Support\Facades\Notification;

class SocialGroupAdminController extends Controller
{
    /**
     * Admin Dashboard Overview
     */
    public function index($slug)
    {
        $group = SocialGroup::where('slug', $slug)->withCount(['members', 'posts'])->firstOrFail();
        $this->authorize('manage', $group);

        $pendingPostsCount = Post::where('social_group_id', $group->id)->where('is_approved', false)->count();
        $pendingMembersCount = SocialGroupMember::where('social_group_id', $group->id)->where('status', 'pending')->count();

        return view('communities.admin.index', compact('group', 'pendingPostsCount', 'pendingMembersCount'));
    }

    /**
     * Group Settings Page
     */
    public function settings($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        return view('communities.admin.settings', compact('group'));
    }

    /**
     * Update group settings.
     */
    public function updateSettings(UpdateGroupSettingsRequest $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('group_avatars', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            $data['cover_photo'] = $request->file('cover_photo')->store('group_covers', 'public');
        }

        $group->update($data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($group);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Pending Posts Moderation
     */
    public function pendingPosts($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $posts = Post::where('social_group_id', $group->id)
            ->where('is_approved', false)
            ->with(['user', 'media'])
            ->latest()
            ->paginate(15);

        return view('communities.admin.pending-posts', compact('group', 'posts'));
    }

    /**
     * General Members Management
     */
    public function membersList($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $members = SocialGroupMember::where('social_group_id', $group->id)
            ->where('status', 'approved')
            ->with(['user', 'badges'])
            ->latest()
            ->paginate(30);

        return view('communities.admin.members', compact('group', 'members'));
    }

    /**
     * Pending Members Moderation
     */
    public function pendingMembers($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $members = SocialGroupMember::where('social_group_id', $group->id)
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->paginate(15);

        return view('communities.admin.pending-members', compact('group', 'members'));
    }

    /**
     * Approve a pending member.
     */
    public function approveMember($slug, $userId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $member = $group->members()->where('user_id', $userId)->where('status', 'pending')->firstOrFail();
        $member->update(['status' => 'approved']);

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

        NotificationController::createNotification(
            $member->user_id,
            'group_join_accepted',
            [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'group_slug' => $group->slug,
            ]
        );

        if ($group->welcome_message) {
            NotificationController::createNotification(
                $member->user_id,
                'message',
                [
                    'sender_name' => $group->name,
                    'message_preview' => $group->welcome_message,
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'group_slug' => $group->slug,
                ]
            );
        }

        return response()->json(['message' => 'Member approved.']);
    }

    /**
     * Reject a pending member.
     */
    public function rejectMember($slug, $userId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $member = $group->members()->where('user_id', $userId)->where('status', 'pending')->firstOrFail();
        $member->delete();

        return response()->json(['message' => 'Member rejected.']);
    }

    /**
     * Approve a pending post.
     */
    public function approvePost($slug, $postId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $post = Post::where('id', $postId)->where('social_group_id', $group->id)->where('is_approved', false)->firstOrFail();
        $post->update([
            'is_approved' => true,
            'created_at' => now(), // Refresh timestamp on approval so it appears fresh in the feed
        ]);

        NotificationController::createNotification(
            $post->user_id,
            'group_post_approved',
            [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'group_slug' => $group->slug,
                'post_id' => $post->id,
                'post_slug' => $post->slug,
            ]
        );

        return response()->json(['message' => 'Post approved.']);
    }

    /**
     * Reject a pending post.
     */
    public function rejectPost($slug, $postId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $post = Post::where('id', $postId)->where('social_group_id', $group->id)->where('is_approved', false)->firstOrFail();
        $post->delete();

        return response()->json(['message' => 'Post rejected and deleted.']);
    }

    /**
     * Mute a member.
     */
    public function muteMember(Request $request, $slug, $userId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $request->validate(['muted_until' => 'required|date|after:now']);

        $member = $group->members()->where('user_id', $userId)->firstOrFail();
        $member->update(['muted_until' => $request->muted_until]);

        return response()->json(['message' => 'Member muted successfully.']);
    }

    public function removeMember($slug, $userId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        if ($group->creator_id == $userId) {
            return response()->json(['message' => 'Cannot remove the creator.'], 422);
        }

        $group->members()->where('user_id', $userId)->delete();

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

        return response()->json(['message' => 'Member removed successfully.']);
    }

    public function updateMemberRole(Request $request, $slug, $userId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $request->validate(['role' => 'required|in:member,moderator,admin']);

        $member = $group->members()->where('user_id', $userId)->firstOrFail();
        $member->update(['role' => $request->role]);

        return response()->json(['message' => 'Role updated to ' . $request->role]);
    }

    /**
     * Rules Management Page
     */
    public function rules($slug)
    {
        $group = SocialGroup::where('slug', $slug)->with('rules')->firstOrFail();
        $this->authorize('manage', $group);

        return view('communities.admin.rules', compact('group'));
    }

    public function addRule(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->rules()->create($validated);

        return back()->with('success', 'Rule added.');
    }

    public function deleteRule($slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $group->rules()->where('id', $id)->delete();

        return back()->with('success', 'Rule deleted.');
    }

    /**
     * Topics Management Page
     */
    public function topics($slug)
    {
        $group = SocialGroup::where('slug', $slug)->with('topics')->firstOrFail();
        $this->authorize('manage', $group);

        return view('communities.admin.topics', compact('group'));
    }

    public function addTopic(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']) . '-' . rand(100, 999);

        $group->topics()->create($validated);

        return back()->with('success', 'Topic added.');
    }

    public function deleteTopic($slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $group->topics()->where('id', $id)->delete();

        return back()->with('success', 'Topic deleted.');
    }

    /**
     * Badges Management Page
     */
    public function badges($slug)
    {
        $group = SocialGroup::where('slug', $slug)->with('badges')->firstOrFail();
        $this->authorize('manage', $group);

        return view('communities.admin.badges', compact('group'));
    }

    public function addBadge(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $group->badges()->create($validated);

        return back()->with('success', 'Badge added.');
    }

    public function deleteBadge($slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $group->badges()->where('id', $id)->delete();

        return back()->with('success', 'Badge deleted.');
    }

    /**
     * Toggle a badge for a member.
     */
    public function toggleBadge(Request $request, $slug, $userId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $request->validate(['badge_id' => 'required|exists:social_group_badges,id']);
        $badgeId = $request->badge_id;

        // Verify badge belongs to this group
        $badge = $group->badges()->where('id', $badgeId)->firstOrFail();

        $member = $group->members()->where('user_id', $userId)->firstOrFail();

        $exists = \DB::table('social_group_member_badges')
            ->where('social_group_id', $group->id)
            ->where('user_id', $userId)
            ->where('badge_id', $badgeId)
            ->exists();

        if ($exists) {
            \DB::table('social_group_member_badges')
                ->where('social_group_id', $group->id)
                ->where('user_id', $userId)
                ->where('badge_id', $badgeId)
                ->delete();
            return response()->json(['message' => 'Badge removed.', 'action' => 'removed']);
        } else {
            \DB::table('social_group_member_badges')->insert([
                'social_group_id' => $group->id,
                'user_id' => $userId,
                'badge_id' => $badgeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['message' => 'Badge granted.', 'action' => 'granted']);
        }
    }

    /**
     * Delete the community.
     */
    public function destroy($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        
        // Only the creator or a platform admin can delete the community
        if ($group->creator_id !== auth()->id() && !auth()->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized. Only the creator can delete this community.'], 403);
        }

        // Delete all related records
        // Posts, Members, Rules, Topics, Badges etc. are usually handled by database cascading 
        // or manual cleanup if SoftDeletes are involved.
        
        $group->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'redirect' => route('communities.index')]);
        }

        return redirect()->route('communities.index')->with('success', 'Community deleted successfully.');
    }

    // ==========================================================================
    // API: Rules CRUD
    // ==========================================================================

    public function apiIndexRules($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        return response()->json(['success' => true, 'data' => $group->rules()->orderBy('id')->get()]);
    }

    public function apiStoreRule(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);
        $rule = $group->rules()->create($validated);
        return response()->json(['success' => true, 'data' => $rule], 201);
    }

    public function apiUpdateRule(Request $request, $slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $rule = $group->rules()->where('id', $id)->firstOrFail();
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);
        $rule->update($validated);
        return response()->json(['success' => true, 'data' => $rule]);
    }

    public function apiDestroyRule($slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $deleted = $group->rules()->where('id', $id)->delete();
        return response()->json(['success' => (bool) $deleted]);
    }

    // ==========================================================================
    // API: Topics CRUD
    // ==========================================================================

    public function apiIndexTopics($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        return response()->json(['success' => true, 'data' => $group->topics()->orderBy('id')->get()]);
    }

    public function apiStoreTopic(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']) . '-' . rand(100, 999);
        $topic = $group->topics()->create($validated);
        return response()->json(['success' => true, 'data' => $topic], 201);
    }

    public function apiUpdateTopic(Request $request, $slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $topic = $group->topics()->where('id', $id)->firstOrFail();
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);
        if (isset($validated['name'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']) . '-' . rand(100, 999);
        }
        $topic->update($validated);
        return response()->json(['success' => true, 'data' => $topic]);
    }

    public function apiDestroyTopic($slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $deleted = $group->topics()->where('id', $id)->delete();
        return response()->json(['success' => (bool) $deleted]);
    }

    // ==========================================================================
    // API: Badges CRUD
    // ==========================================================================

    public function apiIndexBadges($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        return response()->json(['success' => true, 'data' => $group->badges()->orderBy('id')->get()]);
    }

    public function apiStoreBadge(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:32',
        ]);
        $badge = $group->badges()->create($validated);
        return response()->json(['success' => true, 'data' => $badge], 201);
    }

    public function apiUpdateBadge(Request $request, $slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $badge = $group->badges()->where('id', $id)->firstOrFail();
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:32',
        ]);
        $badge->update($validated);
        return response()->json(['success' => true, 'data' => $badge]);
    }

    public function apiDestroyBadge($slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        $deleted = $group->badges()->where('id', $id)->delete();
        return response()->json(['success' => (bool) $deleted]);
    }

    // ==========================================================================
    // API: Reports — list reports on posts inside this community
    // ==========================================================================

    public function reports(Request $request, $slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $status = $request->query('status'); // optional: pending|accepted|rejected
        $perPage = max(1, min(50, (int) $request->query('per_page', 20)));

        $query = PostReport::query()
            ->whereHas('post', function ($q) use ($group) {
                $q->where('social_group_id', $group->id);
            })
            ->with(['post:id,slug,user_id,content,social_group_id', 'reporter:id,username,name,avatar_url'])
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'accepted', 'rejected'], true)) {
            $query->{$status}();
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    /**
     * Server-rendered reports management page (shell). JS loads data via reports.data.
     */
    public function reportsPage($slug)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);
        return view('communities.admin.reports', compact('group'));
    }

    /**
     * Resolve a report: accept (delete the post) or reject (dismiss).
     */
    public function resolveReport(Request $request, $slug, $id)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        $this->authorize('manage', $group);

        $validated = $request->validate([
            'action' => 'required|in:accept,reject',
            'note'   => 'nullable|string|max:1000',
        ]);

        $report = PostReport::with('post')
            ->whereHas('post', function ($q) use ($group) {
                $q->where('social_group_id', $group->id);
            })
            ->findOrFail($id);

        if ($validated['action'] === 'accept') {
            // Delete the offending post
            if ($report->post) {
                $report->post->delete();
            }
            $report->update([
                'status'       => 'accepted',
                'reviewed_by'  => auth()->id(),
                'reviewed_at'  => now(),
                'admin_note'   => $validated['note'] ?? null,
                'admin_action' => 'delete_post',
            ]);
        } else {
            $report->update([
                'status'       => 'rejected',
                'reviewed_by'  => auth()->id(),
                'reviewed_at'  => now(),
                'admin_note'   => $validated['note'] ?? null,
                'admin_action' => 'dismiss',
            ]);
        }

        return response()->json(['success' => true, 'data' => $report->fresh()]);
    }
}
