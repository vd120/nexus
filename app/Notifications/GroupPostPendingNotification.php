<?php

namespace App\Notifications;

use App\Models\SocialGroup;
use App\Models\Post;
use App\Traits\SocialGroupNotificationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupPostPendingNotification extends Notification implements ShouldQueue
{
    use Queueable, SocialGroupNotificationTrait;

    protected $group;
    protected $post;

    /**
     * Create a new notification instance.
     */
    public function __construct(SocialGroup $group, Post $post)
    {
        $this->group = $group;
        $this->post = $post;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'group_slug' => $this->group->slug,
            'post_id' => $this->post->id,
            'post_slug' => $this->post->slug,
            'type' => 'group_post_pending',
        ];

        $formattedData = $this->formatGroupNotificationData(
            $data,
            (bool) $this->post->is_anonymous,
            $this->group->id,
            $this->post->user_id
        );

        $formattedData['message'] = "{$formattedData['actor_name']} submitted a post for approval in {$this->group->name}.";

        return $formattedData;
    }
}
