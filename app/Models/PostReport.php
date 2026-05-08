<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PostReport extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'reason',
        'content',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'admin_action',
        'slug',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->slug)) {
                $report->slug = static::generateUniqueSlug();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public static function generateUniqueSlug()
    {
        do {
            $slug = 'report-' . Str::random(24);
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public const REASONS = [
        'spam' => 'Spam',
        'inappropriate' => 'Inappropriate Content',
        'harassment' => 'Harassment or Bullying',
        'hate_speech' => 'Hate Speech',
        'violence' => 'Violence or Harmful Activities',
        'misinformation' => 'Misinformation',
        'copyright' => 'Copyright Infringement',
        'other' => 'Other',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class)->withTrashed();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
