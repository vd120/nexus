<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = ['name', 'enabled', 'rollout_percentage', 'notes'];

    protected $casts = [
        'enabled' => 'boolean',
        'rollout_percentage' => 'integer',
    ];
}
