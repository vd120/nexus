<?php

namespace App\Services;

use App\Models\User;
use App\Models\Mention;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;

class MentionService
{
    /**
     * Parse mentions from text and return an array of mentioned usernames
     */
    public function parseMentions(string $text): array
    {
        
        preg_match_all('/@([a-zA-Z0-9_-]+)/', $text, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Process mentions for a given model (Post or Comment)
     */
    public function processMentions(Model $mentionable, string $text, int $mentionerId): void
    {
        $mentionedUsernames = $this->parseMentions($text);

        if (empty($mentionedUsernames)) {
            return;
        }

        // Get the mentioner's info
        $mentioner = User::find($mentionerId);
        if (!$mentioner) {
            return;
        }

        $mentionedUsers = User::whereIn('username', $mentionedUsernames)
            ->where('id', '!=', $mentionerId)
            ->whereDoesntHave('blockedBy', function($query) use ($mentionerId) {
                $query->where('blocker_id', $mentionerId);
            })
            ->whereDoesntHave('blockedUsers', function($query) use ($mentionerId) {
                $query->where('blocked_id', $mentionerId);
            })
            ->get();

        foreach ($mentionedUsers as $mentionedUser) {

            Mention::create([
                'mentioner_id' => $mentionerId,
                'mentioned_id' => $mentionedUser->id,
                'mentionable_type' => get_class($mentionable),
                'mentionable_id' => $mentionable->id,
            ]);


            \App\Http\Controllers\NotificationController::createNotification(
                $mentionedUser->id,
                'mention',
                [
                    'mentioner_name' => $mentioner->name,
                    'mentioner_username' => $mentioner->username,
                    'mentioner_id' => $mentioner->id,
                    'mentionable_type' => get_class($mentionable),
                    'post_slug' => ($mentionable instanceof \App\Models\Post) ? $mentionable->slug : ($mentionable->post->slug ?? null),
                    'comment_id' => ($mentionable instanceof \App\Models\Comment) ? $mentionable->id : null,
                ],
                $mentionable
            );
        }
    }

    /**
     * Convert mentions in text to clickable links
     */
    public function convertMentionsToLinks(string $text): string
    {
        return preg_replace_callback(
            '/@([a-zA-Z0-9_-]+)/',
            function ($matches) {
                $username = $matches[1];
                $user = User::where('username', $username)->first();

                if ($user) {
                    // Add dir="ltr" and unicode-bidi to ensure proper display in RTL
                    return '<a href="' . route('users.show', $user) . '" class="mention-link" dir="ltr" style="unicode-bidi: isolate;">@' . $username . '</a>';
                }

                return '@' . $username;
            },
            $text
        );
    }
}
