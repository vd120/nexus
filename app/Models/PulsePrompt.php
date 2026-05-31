<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PulsePrompt extends Model
{
    protected $fillable = [
        'question_en',
        'question_ar',
        'type',
        'starts_at',
        'ends_at',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at'    => 'datetime',
        'ends_at'      => 'datetime',
        'last_used_at' => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(PulseAnswer::class, 'prompt_id');
    }

    /** Question text in the caller's locale, falling back to English. */
    public function getQuestionAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && !empty($this->question_ar)
            ? $this->question_ar
            : $this->question_en;
    }

    /**
     * Shared logic for "find the active prompt of a given type right now".
     * Centralised so daily and memory variants stay in lock-step.
     */
    protected static function currentByType(string $type): ?self
    {
        $now = now();
        return static::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->latest('starts_at')
            ->first();
    }

    /** The one active daily prompt right now (or null if rotation hasn't run yet). */
    public static function currentDaily(): ?self
    {
        return static::currentByType('daily');
    }

    /** The one active memory prompt right now (or null if rotation hasn't run yet). */
    public static function currentMemory(): ?self
    {
        return static::currentByType('memory');
    }

    /**
     * Backwards-compatible alias for currentDaily(). All pre-existing callers
     * (sidebar widget, original PulseController@index) used current() before
     * the memory variant was introduced — keep their behaviour unchanged.
     */
    public static function current(): ?self
    {
        return static::currentDaily();
    }
}
