<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PulseAnswerLike extends Model
{
    protected $fillable = ['user_id', 'answer_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(PulseAnswer::class, 'answer_id');
    }
}
