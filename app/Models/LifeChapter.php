<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LifeChapter extends Model
{
    protected $fillable = [
        'title',
        'emoji',
        'starts_on',
        'ends_on',
        'description',
        'sort_order',
        'user_id',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on'   => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'life_chapter_id');
    }

    public function pulseAnswers(): HasMany
    {
        return $this->hasMany(PulseAnswer::class, 'life_chapter_id');
    }

    public function scopeOngoing($q)
    {
        return $q->whereNull('ends_on');
    }

    /**
     * True if the given date falls within this chapter's range.
     * A null bound means "open-ended" on that side.
     */
    public function containsDate($date): bool
    {
        $date = $date instanceof \DateTimeInterface ? $date : \Carbon\Carbon::parse($date);

        $startOk = is_null($this->starts_on) || $this->starts_on->lessThanOrEqualTo($date);
        $endOk   = is_null($this->ends_on)   || $this->ends_on->greaterThanOrEqualTo($date);

        return $startOk && $endOk;
    }
}
