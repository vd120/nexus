<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostView extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'user_id', 'ip_hash', 'viewed_at'];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
