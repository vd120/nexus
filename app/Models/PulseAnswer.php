<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PulseAnswer extends Model
{
    protected $fillable = [
        'prompt_id',
        'user_id',
        'life_chapter_id',
        'content',
        'is_anonymous',
        'visibility',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(PulsePrompt::class, 'prompt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lifeChapter(): BelongsTo
    {
        return $this->belongsTo(LifeChapter::class, 'life_chapter_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PulseAnswerLike::class, 'answer_id');
    }

    /**
     * Display author — anonymized when is_anonymous is true. Mirrors the same
     * pattern used elsewhere in the app (see PostReaction::getAuthorAttribute).
     */
    public function getAuthorAttribute()
    {
        if ($this->is_anonymous) {
            $fake = new User([
                'name'     => 'Anonymous Participant',
                'username' => 'Anonymous Participant',
            ]);
            $fake->id = null;
            return $fake;
        }
        return $this->user;
    }
}
