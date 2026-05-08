<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalMessageReaction extends Model
{
    protected $fillable = ['global_message_id', 'user_id', 'reaction'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function message()
    {
        return $this->belongsTo(GlobalMessage::class, 'global_message_id');
    }
}
