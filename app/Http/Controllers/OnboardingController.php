<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function welcome()
    {
        $user = auth()->user();

        // If already completed onboarding, redirect to feed
        if ($user->onboarding_completed_at) {
            return redirect('/');
        }

        // Suggest popular users (most followers), excluding self and already-followed
        $followingIds = $user->follows()->pluck('followed_id')->toArray();
        $suggestions = User::withCount('followers')
            ->where('id', '!=', $user->id)
            ->whereNotIn('id', $followingIds)
            ->orderByDesc('followers_count')
            ->limit(12)
            ->get();

        return view('onboarding.welcome', compact('suggestions'));
    }

    public function complete(Request $request)
    {
        $user = auth()->user();
        $user->onboarding_completed_at = now();
        $user->save();

        return response()->json(['redirect' => '/']);
    }
}
