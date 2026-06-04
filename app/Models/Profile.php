<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'avatar',
        'cover_image',
        'bio',
        'website',
        'location',
        'birth_date',
        'occupation',
        'about',
        'phone',
        'gender',
        'is_private',
        'social_links',
        'show_online_status',
        'show_read_receipts',
        'show_sensitive_content',
    ];

    protected $casts = [
        'is_private'           => 'boolean',
        'show_online_status'   => 'boolean',
        'show_read_receipts'   => 'boolean',
        'show_sensitive_content' => 'boolean',
        'social_links'         => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
