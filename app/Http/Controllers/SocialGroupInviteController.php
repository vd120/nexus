<?php

namespace App\Http\Controllers;

use App\Models\SocialGroup;
use App\Models\SocialGroupInvite;
use App\Models\SocialGroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialGroupInviteController extends Controller
{
    /**
     * Invite a user to a group.
     */
    public function invite(Request $request, $slug, $userId)
    {
        $group = SocialGroup::where('slug', $slug)->firstOrFail();
        
        // Ensure inviter is a member
        if (!$group->hasMember(Auth::user())) {
            return response()->json(['message' => 'You must be a member to invite others.'], 403);
        }

        if (SocialGroupInvite::where('social_group_id', $group->id)
            ->where('invitee_id', $userId)
            ->where('status', 'pending')
            ->exists()) {
            return response()->json(['message' => 'Invitation already pending.'], 422);
        }

        $invite = SocialGroupInvite::create([
            'social_group_id' => $group->id,
            'inviter_id' => Auth::id(),
            'invitee_id' => $userId,
            'status' => 'pending',
        ]);

        return response()->json($invite, 201);
    }

    /**
     * Accept an invitation.
     */
    public function acceptInvite($inviteId)
    {
        $invite = SocialGroupInvite::where('id', $inviteId)
            ->where('invitee_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $invite->update(['status' => 'accepted']);

        // Add to group
        SocialGroupMember::firstOrCreate([
            'social_group_id' => $invite->social_group_id,
            'user_id' => Auth::id(),
        ], [
            'role' => 'member',
            'status' => 'approved',
        ]);

        return response()->json(['message' => 'Invitation accepted.']);
    }

    /**
     * Decline an invitation.
     */
    public function declineInvite($inviteId)
    {
        $invite = SocialGroupInvite::where('id', $inviteId)
            ->where('invitee_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $invite->update(['status' => 'declined']);

        return response()->json(['message' => 'Invitation declined.']);
    }
}
