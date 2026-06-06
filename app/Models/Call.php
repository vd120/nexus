<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    protected $fillable = [
        'conversation_id',
        'caller_id',
        'callee_id',
        'status',
        'started_at',
        'ended_at',
        'duration',
    ];

    protected $casts = [
        'status'     => 'string',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function callee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callee_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['initiated', 'accepted']);
    }
}
