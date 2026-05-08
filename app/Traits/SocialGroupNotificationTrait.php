<?php

namespace App\Traits;

use App\Models\SocialGroupMember;

trait SocialGroupNotificationTrait
{
    /**
     * Format notification data with anti-leak logic for anonymous interactions.
     *
     * @param array $data
     * @param bool $isAnonymous
     * @param int $socialGroupId
     * @param int $actorId
     * @return array
     */
    protected function formatGroupNotificationData(array $data, bool $isAnonymous, int $socialGroupId, int $actorId): array
    {
        if ($isAnonymous) {
            $member = SocialGroupMember::where('social_group_id', $socialGroupId)
                ->where('user_id', $actorId)
                ->first();

            $data['actor_name'] = $member?->anonymous_username ?? 'anonymous#' . rand(1000, 9999);
            $data['actor_id'] = null;
            $data['actor_avatar'] = null; // Use a generic silhouette on the frontend
            $data['is_anonymous'] = true;
        } else {
            $user = \App\Models\User::find($actorId);
            $data['actor_name'] = $user?->username ?? $user?->name ?? 'Unknown';
            $data['actor_id'] = $actorId;
            $data['actor_avatar'] = $user?->avatar_url;
            $data['is_anonymous'] = false;
        }

        return $data;
    }

    /**
     * Check if the notification should be sent based on recipient preferences.
     *
     * @param mixed $notifiable
     * @param int $socialGroupId
     * @param string $type
     * @return bool
     */
    protected function shouldSendToGroupMember($notifiable, int $socialGroupId, string $type = 'all'): bool
    {
        $member = SocialGroupMember::where('social_group_id', $socialGroupId)
            ->where('user_id', $notifiable->id)
            ->first();

        if (!$member) {
            return false;
        }

        if ($member->notification_preference === 'none') {
            return false;
        }

        if ($member->notification_preference === 'highlights' && $type !== 'urgent') {
            return false;
        }

        return true;
    }
}
