<?php

namespace App\Helpers;

use App\Models\FeatureFlag;

class Feature
{
    public static function enabled(string $name): bool
    {
        return cache()->remember("feature.{$name}", 60, fn() =>
            FeatureFlag::where('name', $name)->value('enabled') ?? false
        );
    }

    public static function disable(string $name): void
    {
        FeatureFlag::where('name', $name)->update(['enabled' => false]);
        cache()->forget("feature.{$name}");
    }

    public static function enable(string $name): void
    {
        FeatureFlag::updateOrCreate(['name' => $name], ['enabled' => true]);
        cache()->forget("feature.{$name}");
    }
}
