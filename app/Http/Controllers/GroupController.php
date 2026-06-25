<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Message;
use App\Services\KeyStorageService;
use App\Services\SocketEmitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    /**
     * Persist a group system message and broadcast it live to all members.
     * Content is built in the acting user's locale (a single string for the room),
     * matching how regular message content is not re-localized per viewer.
     */
    private function emitGroupSystemMessage(Group $group, string $content): void
    {
        $conversation = $group->conversation;
        if (!$conversation) {
            return;
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => Auth::id(),
            'content'         => $content,
            'type'            => 'system',
        ]);

        $conversation->update(['last_message_at' => now()]);

        $socket = app(\App\Services\SocketEmitService::class);

        // System bubble to everyone currently in the conversation room
        $socket->emitToConversation($conversation->id, 'chat:message', $message->toPayload());

        // Per-member sidebar preview (also lets a brand-new member's sidebar create the item)
        $preview = mb_substr($content, 0, 30) . (mb_strlen($content) > 30 ? '...' : '');
        $avatarUrl = $group->avatar ? asset('storage/' . $group->avatar) : null;

        foreach ($group->members()->pluck('user_id') as $memberId) {
            $socket->emitToUser($memberId, 'chat:conversation:updated', [
                'conversation_id'   => $conversation->id,
                'conversation_slug' => $conversation->slug,
                'is_group'          => true,
                'display_name'      => $conversation->display_name,
                'avatar_url'        => $avatarUrl,
                'last_message'      => $preview,
                'last_message_id'   => $message->id,
                'show_checkmarks'   => false,
                'last_message_time' => $message->created_at->toISOString(),
                'unread_count'      => $conversation->unreadCountFor($memberId),
            ]);
        }
    }

    /**
     * Display a listing of groups.
     */
    public function index()
    {
        $groups = Auth::user()->groups()->withCount('members')->get();
        return view('groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        $following = Auth::user()->following()->get();
        $followers = Auth::user()->followersList()->get();
        $users = $following->merge($followers)->unique('id');
        
        return view('groups.create', compact('users'));
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'creator_id' => Auth::id(),
            'is_private' => true, // Chat groups are private by default
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('groups/avatars', 'public');
            $group->update(['avatar' => $path]);
        }

        // Add creator as admin
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'role' => 'admin',
        ]);

        // Add selected members
        foreach ($request->members as $userId) {
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $userId,
                'role' => 'member',
            ]);
        }

        // Create a conversation for this group
        $conversation = Conversation::createGroupConversation($group);

        $this->emitGroupSystemMessage(
            $group->fresh('conversation'),
            __('chat.system_group_created', ['actor' => Auth::user()->username])
        );

        return redirect()->route('chat.show', $conversation->slug)->with('success', 'Group created successfully.');
    }

    /**
     * Display the specified group.
     */
    public function show($slug)
    {
        // Try finding by group slug first
        $group = Group::where('slug', $slug)->with(['members.user', 'creator', 'conversation'])->first();
        
        // If not found, try finding via conversation slug
        if (!$group) {
            $conversation = \App\Models\Conversation::where('slug', $slug)->where('is_group', true)->first();
            if ($conversation && $conversation->group_id) {
                $group = Group::where('id', $conversation->group_id)->with(['members.user', 'creator', 'conversation'])->first();
            }
        }

        if (!$group) {
            abort(404);
        }
        
        if (!$group->isMember(Auth::user()) && $group->is_private) {
            abort(403);
        }

        return view('groups.show', compact('group'));
    }

    /**
     * Update the group settings.
     */
    public function update(Request $request, Group $group)
    {
        if (!$group->isAdmin(Auth::user())) abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:2048',
            'is_private' => 'boolean',
        ]);

        $data = $request->only(['name', 'description', 'is_private']);
        
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('groups/avatars', 'public');
        }

        $group->update($data);

        $actor = Auth::user()->username;
        if (array_key_exists('name', $data) && $group->wasChanged('name')) {
            $this->emitGroupSystemMessage($group, __('chat.system_group_renamed', ['actor' => $actor, 'name' => $group->name]));
        }
        if (array_key_exists('avatar', $data) && $group->wasChanged('avatar')) {
            $this->emitGroupSystemMessage($group, __('chat.system_group_avatar', ['actor' => $actor]));
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('chat.group_updated_successfully'),
                'group' => $group
            ]);
        }

        return redirect()->back()->with('success', __('chat.group_updated_successfully'));
    }

    /**
     * Add a member to the group.
     */
    public function addMember(Request $request, Group $group)
    {
        if (!$group->isAdmin(Auth::user())) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            abort(403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // Check if already a member
        if ($group->members()->where('user_id', $request->user_id)->exists()) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => __('chat.user_already_member')]);
            return redirect()->back()->with('error', __('chat.user_already_member'));
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $request->user_id,
            'role' => 'member'
        ]);

        $added = User::find($request->user_id);
        $this->emitGroupSystemMessage($group, __('chat.system_member_added', [
            'actor' => Auth::user()->username,
            'user'  => $added->username ?? 'user',
        ]));

        // Trigger E2E group key rotation: delete existing keys so the new member
        // cannot read old messages, and notify remaining members to rotate.
        if ($group->conversation) {
            app(KeyStorageService::class)->deleteGroupMemberKeys($group->conversation->id, $request->user_id);
            app(SocketEmitService::class)->emitToConversation(
                $group->conversation->id,
                'chat:e2e:group-key-rotation',
                ['conversation_id' => $group->conversation->id, 'action' => 'member_added']
            );
        }

        if ($request->ajax()) {
            $user = User::find($request->user_id);
            $member = $group->members()->where('user_id', $request->user_id)->first();
            $html = view('groups.partials.member-item', [
                'member' => $member,
                'group' => $group
            ])->render();

            return response()->json([
                'success' => true,
                'message' => __('chat.member_added_successfully'),
                'html' => $html,
                'member_count' => $group->members()->count()
            ]);
        }

        return redirect()->back()->with('success', __('chat.member_added_successfully'));
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(Request $request, Group $group, $userId)
    {
        if (!$group->isAdmin(Auth::user()) && Auth::id() != $userId) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            abort(403);
        }
        
        if ($userId == $group->creator_id && Auth::id() != $userId) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => 'Cannot remove creator'], 403);
            abort(403);
        }

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->delete();

        $removed = User::find($userId);

        // Remove E2E group keys for the removed member
        if ($group->conversation) {
            app(KeyStorageService::class)->deleteGroupMemberKeys($group->conversation->id, $userId);
        }

        if (Auth::id() == $userId) {
            $this->emitGroupSystemMessage($group, __('chat.system_member_left', [
                'user' => $removed->username ?? 'user',
            ]));
        } else {
            $this->emitGroupSystemMessage($group, __('chat.system_member_removed', [
                'actor' => Auth::user()->username,
                'user'  => $removed->username ?? 'user',
            ]));
            // Drop the conversation from the removed user's sidebar
            if ($group->conversation) {
                app(SocketEmitService::class)->emitToUser($userId, 'chat:conversation:deleted', [
                    'conversation_id' => $group->conversation->id,
                    'deleted_by'      => Auth::id(),
                ]);
            }
        }

        // Notify remaining members to rotate group keys
        if ($group->conversation && Auth::id() != $userId) {
            app(SocketEmitService::class)->emitToConversation(
                $group->conversation->id,
                'chat:e2e:group-key-rotation',
                ['conversation_id' => $group->conversation->id, 'action' => 'member_removed']
            );
        }

        if (Auth::id() == $userId) {
            if ($request->ajax()) return response()->json(['success' => true, 'redirect' => route('chat.index'), 'message' => __('chat.left_group_successfully')]);
            return redirect()->route('chat.index')->with('success', __('chat.left_group_successfully'));
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('chat.member_removed_successfully'),
                'user_id' => $userId,
                'member_count' => $group->members()->count()
            ]);
        }

        return redirect()->back()->with('success', __('chat.member_removed_successfully'));
    }

    /**
     * Change a member's role.
     */
    public function changeRole(Request $request, Group $group, $userId)
    {
        if (!$group->isAdmin(Auth::user())) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            abort(403);
        }
        
        if ($userId == $group->creator_id) {
            if ($request->ajax()) return response()->json(['success' => false, 'message' => 'Cannot demote creator'], 403);
            abort(403);
        }

        $request->validate(['role' => 'required|in:admin,member']);

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->update(['role' => $request->role]);

        $target = User::find($userId);
        $roleKey = $request->role === 'admin' ? 'chat.system_promoted_admin' : 'chat.system_demoted_member';
        $this->emitGroupSystemMessage($group, __($roleKey, [
            'actor' => Auth::user()->username,
            'user'  => $target->username ?? 'user',
        ]));

        if ($request->ajax()) {
            $member = GroupMember::where('group_id', $group->id)
                ->where('user_id', $userId)
                ->first();
            
            $html = view('groups.partials.member-item', [
                'member' => $member,
                'group' => $group
            ])->render();

            return response()->json([
                'success' => true,
                'message' => __('chat.role_updated_successfully'),
                'html' => $html,
                'user_id' => $userId
            ]);
        }

        return redirect()->back()->with('success', __('chat.role_updated_successfully'));
    }

    /**
     * Join a group via invite link.
     */
    public function join($inviteLink)
    {
        $group = Group::where('invite_link', $inviteLink)->firstOrFail();
        
        if ($group->isMember(Auth::user())) {
            return redirect()->route('chat.show', $group->conversation->slug);
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'role' => 'member',
        ]);

        $this->emitGroupSystemMessage($group->fresh('conversation'), __('chat.system_member_joined', [
            'user' => Auth::user()->username,
        ]));

        // Trigger E2E group key rotation so the new member gets a fresh shared key
        if ($group->conversation) {
            app(KeyStorageService::class)->deleteGroupMemberKeys($group->conversation->id, Auth::id());
            app(SocketEmitService::class)->emitToConversation(
                $group->conversation->id,
                'chat:e2e:group-key-rotation',
                ['conversation_id' => $group->conversation->id, 'action' => 'member_joined']
            );
        }

        return redirect()->route('chat.show', $group->conversation->slug)->with('success', "Joined {$group->name} successfully.");
    }

    /**
     * Leave the group.
     */
    public function leave(Group $group)
    {
        $leaverName = Auth::user()->username;

        GroupMember::where('group_id', $group->id)
            ->where('user_id', Auth::id())
            ->delete();

        $this->emitGroupSystemMessage($group, __('chat.system_member_left', ['user' => $leaverName]));

        // Trigger E2E group key rotation so remaining members generate a new shared key
        if ($group->conversation) {
            app(KeyStorageService::class)->deleteGroupMemberKeys($group->conversation->id, Auth::id());
            app(SocketEmitService::class)->emitToConversation(
                $group->conversation->id,
                'chat:e2e:group-key-rotation',
                ['conversation_id' => $group->conversation->id, 'action' => 'member_left']
            );
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('chat.left_group_successfully'),
                'redirect' => route('chat.index')
            ]);
        }

        return redirect()->route('chat.index')->with('success', __('chat.left_group_successfully'));
    }

    /**
     * Delete the group.
     */
    public function destroy(Group $group)
    {
        if ($group->creator_id != Auth::id()) {
            abort(403);
        }

        // Delete conversation and members will be deleted via cascade or manual if needed
        if ($group->conversation) {
            app(\App\Services\SocketEmitService::class)->emitToConversation($group->conversation->id, 'chat:conversation:deleted', [
                'conversation_id' => $group->conversation->id,
                'deleted_by'      => Auth::id(),
            ]);
            $group->conversation->delete();
        }
        
        $group->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('chat.group_deleted_successfully'),
                'redirect' => route('chat.index')
            ]);
        }

        return redirect()->route('chat.index')->with('success', __('chat.group_deleted_successfully'));
    }
}
