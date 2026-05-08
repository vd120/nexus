<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalMessage extends Model
{
    protected $fillable = ['user_id', 'content'];
    protected $appends = ['reply_data', 'display_content', 'media', 'type'];

    public function getReplyDataAttribute()
    {
        if (str_starts_with($this->content, '{') && str_ends_with($this->content, '}')) {
            $data = json_decode($this->content, true);
            return $data['reply_to'] ?? null;
        }
        return null;
    }

    public function getMediaAttribute()
    {
        if (str_starts_with($this->content, '{') && str_ends_with($this->content, '}')) {
            $data = json_decode($this->content, true);
            return $data['media'] ?? null;
        }
        return null;
    }

    public function getTypeAttribute()
    {
        if (str_starts_with($this->content, '{') && str_ends_with($this->content, '}')) {
            $data = json_decode($this->content, true);
            return $data['type'] ?? 'text';
        }
        return 'text';
    }

    public function getDisplayContentAttribute()
    {
        if (str_starts_with($this->content, '{') && str_ends_with($this->content, '}')) {
            $data = json_decode($this->content, true);
            return $data['text'] ?? '';
        }
        return $this->content;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->hasMany(GlobalMessageReaction::class);
    }
}
