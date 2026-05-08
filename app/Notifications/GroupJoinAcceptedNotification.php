<?php

namespace App\Notifications;

use App\Models\SocialGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupJoinAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;

    /**
     * Create a new notification instance.
     */
    public function __construct(SocialGroup $group)
    {
        $this->group = $group;
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
            'message' => "Your request to join {$this->group->name} has been approved.",
            'type' => 'group_join_accepted',
        ];
    }
}
