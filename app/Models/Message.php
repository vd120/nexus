<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    /**
     * The relationships that should be touched when the model is updated.
     */
    public $touches = ['conversation'];

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'visible_to',
        'content',
        'type',
        'duration',
        'waveform_peaks',
        'media_path',
        'media_thumbnail',
        'original_filename',
        'media_size',
        'read_at',
        'delivered_at',
        'notified_at',
        'deleted_for',
        'deleted_by_sender',
        'link_preview',
    ];

    protected $dates = ['read_at', 'delivered_at', 'notified_at', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'deleted_for'       => 'array',
        'deleted_by_sender' => 'boolean',
        'link_preview'      => 'array',
        'waveform_peaks' => 'array',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // No appends needed - duration and waveform_peaks are DB columns
    // protected $appends = [];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getIsMineAttribute()
    {
        return $this->sender_id === auth()->id();
    }

    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update([
                'read_at' => now(),
            ]);
        }
    }

    public static function markConversationAsRead($conversationId, $userId)
    {
        return static::where('conversation_id', $conversationId)
            ->where('messages.sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    /**
     * Check if the message is read by a specific user
     */
    public function isReadByUser($userId): bool
    {
        if ($this->sender_id == $userId) return true;
        
        if ($this->conversation->is_group) {
            return $this->receipts->where('user_id', $userId)->whereNotNull('read_at')->isNotEmpty();
        }
        
        return $this->read_at !== null;
    }

    public function receipts()
    {
        return $this->hasMany(MessageReceipt::class);
    }

    /**
     * Update the global read_at for the message if all recipients have read it
     */
    public function updateReadStatusIfAllRead()
    {
        $conversation = $this->conversation;
        if (!$conversation) return;

        $recipientIds = $conversation->getRecipients($this->sender_id);
        $recipientsCount = count($recipientIds);
        
        if ($recipientsCount === 0) return;

        $readCount = $this->receipts()->whereNotNull('read_at')->count();

        if ($readCount >= $recipientsCount) {
            $this->update(['read_at' => now()]);
            return true;
        }
        
        return false;
    }

    /**
     * Update the global delivered_at for the message if all recipients have received it
     */
    public function updateDeliveryStatusIfAllDelivered()
    {
        $conversation = $this->conversation;
        if (!$conversation) return;

        $recipientIds = $conversation->getRecipients($this->sender_id);
        $recipientsCount = count($recipientIds);
        
        if ($recipientsCount === 0) return;

        $deliveredCount = $this->receipts()->whereNotNull('delivered_at')->count();

        if ($deliveredCount >= $recipientsCount) {
            $this->update(['delivered_at' => now()]);
            return true;
        }
        
        return false;
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function reactedBy(User $user)
    {
        return $this->reactions()->where('user_id', $user->id)->first();
    }

    public function getGroupedReactions()
    {
        $reactions = $this->reactions;

        if ($reactions->isEmpty()) {
            return collect();
        }

        return $reactions->groupBy('reaction_type')->map(function ($group) {
            return [
                'reaction_type' => $group->first()->reaction_type,
                'count' => $group->count(),
                'users' => $group->map(function($r) {
                    return [
                        'id' => $r->user_id,
                        'username' => $r->user->username ?? 'User',
                        'avatar_url' => $r->user->avatar_url ?? null,
                        'is_verified' => isset($r->user) ? (bool)$r->user->is_verified : false,
                    ];
                })->values()->toArray(),
            ];
        })->values();
    }

    /**
     * Format message for socket broadcasting
     */
    public function toPayload()
    {
        $this->loadMissing('sender.profile');
        if ($this->sender) $this->sender->append('avatar_url');

        return [
            'id' => $this->id,
            'content' => $this->content,
            'type' => $this->type,
            'media_path' => $this->media_path,
            'link_preview' => $this->link_preview,
            'sender_id' => $this->sender_id,
            'sender' => [
                'id' => $this->sender->id ?? null,
                'username' => $this->sender->username ?? null,
                'avatar_url' => $this->sender->avatar_url ?? null,
                'is_verified' => isset($this->sender) ? (bool)$this->sender->is_verified : false,
            ],
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'delivered_at' => $this->delivered_at ? $this->delivered_at->toISOString() : null,
            'read_at' => $this->read_at ? $this->read_at->toISOString() : null,
            'conversation_id' => $this->conversation_id,
            'conversation_slug' => $this->conversation->slug ?? null,
            'duration' => $this->duration ?? null,
            'waveform_peaks' => $this->waveform_peaks ?? null,
        ];
    }
}
