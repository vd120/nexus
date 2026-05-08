<?php

namespace App\Notifications;

use App\Models\SocialGroup;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupPostApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        return [
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'group_slug' => $this->group->slug,
            'post_id' => $this->post->id,
            'post_slug' => $this->post->slug,
            'message' => "Your post in {$this->group->name} has been approved and is now live.",
            'type' => 'group_post_approved',
        ];
    }
}
