<?php

namespace App\Policies;

use App\Models\SocialGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SocialGroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SocialGroup $socialGroup): bool
    {
        if ($socialGroup->privacy_level === 'public') {
            return true;
        }

        return $socialGroup->isMember($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SocialGroup $socialGroup): bool
    {
        return $socialGroup->isAdmin($user) || $socialGroup->creator_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SocialGroup $socialGroup): bool
    {
        return $socialGroup->creator_id === $user->id;
    }

    /**
     * Determine whether the user can manage settings.
     */
    public function manage(User $user, SocialGroup $socialGroup): bool
    {
        return $socialGroup->isAdmin($user) || $socialGroup->creator_id === $user->id;
    }

    /**
     * Determine whether the user can post in the group.
     */
    public function post(User $user, SocialGroup $socialGroup): bool
    {
        if ($socialGroup->isPaused()) {
            return false;
        }

        $member = $socialGroup->members()->where('user_id', $user->id)->first();
        
        if (!$member || $member->status !== 'approved') {
            return false;
        }

        return $member->canPost();
    }
}
