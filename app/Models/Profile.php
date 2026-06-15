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
        'show_phone',
        'gender',
        'show_gender',
        'show_birth_date',
        'show_location',
        'show_occupation',
        'is_private',
        'social_links',
        'show_online_status',
        'show_read_receipts',
        'show_sensitive_content',
    ];

    protected $casts = [
        'is_private'             => 'boolean',
        'show_phone'             => 'boolean',
        'show_gender'            => 'boolean',
        'show_birth_date'        => 'boolean',
        'show_location'          => 'boolean',
        'show_occupation'        => 'boolean',
        'show_online_status'     => 'boolean',
        'show_read_receipts'     => 'boolean',
        'show_sensitive_content' => 'boolean',
        'social_links'           => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
