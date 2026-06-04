<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    public function index()
    {
        $flags = FeatureFlag::orderBy('name')->get();
        return view('admin.feature-flags', compact('flags'));
    }

    public function toggle(string $name)
    {
        $flag = FeatureFlag::firstOrCreate(
            ['name' => $name],
            ['enabled' => false, 'rollout_percentage' => 100]
        );

        $flag->update(['enabled' => !$flag->enabled]);
        cache()->forget("feature.{$name}");

        return response()->json([
            'name'    => $flag->name,
            'enabled' => $flag->enabled,
        ]);
    }
}
