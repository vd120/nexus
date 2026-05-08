<?php

namespace App\Notifications;

use App\Models\SocialGroup;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupJoinRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;
    protected $requester;

    /**
     * Create a new notification instance.
     */
    public function __construct(SocialGroup $group, User $requester)
    {
        $this->group = $group;
        $this->requester = $requester;
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
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'message' => "{$this->requester->name} requested to join {$this->group->name}.",
            'type' => 'group_join_request',
        ];
    }
}
