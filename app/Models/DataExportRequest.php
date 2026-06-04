<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExportRequest extends Model
{
    protected $fillable = ['user_id', 'status', 'download_token', 'file_path', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }
}
