<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use App\Models\SocialGroup;
use App\Models\User;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    /**
     * Suggested users to follow, sorted by follower count, excluding
     * already-followed and any IDs the caller already shows.
     *
     * GET /api/sidebar/suggestions?exclude[]=1&exclude[]=2&limit=2
     */
    public function suggestions(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'data' => []], 401);
        }

        $limit = max(1, min(10, (int) $request->get('limit', 2)));
        $exclude = array_map('intval', (array) $request->get('exclude', []));
        $followingIds = $user->following()->pluck('followed_id')->all();
        $excludeIds = array_unique(array_merge($exclude, $followingIds, [$user->id]));

        $users = User::query()
            ->whereNotIn('id', $excludeIds)
            ->withCount('followers')
            ->with('profile')
            ->orderBy('followers_count', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($u) {
                return [
                    'id'              => $u->id,
                    'username'        => $u->username,
                    'name'            => $u->profile?->full_name ?: $u->name,
                    'avatar_url'      => $u->avatar_url,
                    'followers_count' => $u->followers_count ?? 0,
                ];
            });

        return response()->json(['success' => true, 'data' => $users]);
    }

    /**
     * Top communities by member count.
     *
     * GET /api/sidebar/top-communities?limit=3
     */
    public function topCommunities(Request $request)
    {
        $limit = max(1, min(10, (int) $request->get('limit', 3)));

        $groups = SocialGroup::query()
            ->withCount('members')
            ->orderBy('members_count', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($g) {
                return [
                    'id'            => $g->id,
                    'slug'          => $g->slug,
                    'name'          => $g->name,
                    'avatar_url'    => $g->avatar_url,
                    'members_count' => $g->members_count ?? 0,
                ];
            });

        return response()->json(['success' => true, 'data' => $groups]);
    }

    /**
     * Trending hashtags by usage count.
     *
     * GET /api/sidebar/trending-hashtags?limit=4
     */
    public function trendingHashtags(Request $request)
    {
        $limit = max(1, min(10, (int) $request->get('limit', 4)));

        $tags = Hashtag::query()
            ->orderBy('usage_count', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($h) {
                return [
                    'id'          => $h->id,
                    'slug'        => $h->slug,
                    'name'        => $h->name,
                    'usage_count' => $h->usage_count ?? 0,
                ];
            });

        return response()->json(['success' => true, 'data' => $tags]);
    }
}
