<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use App\Models\SocialGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SidebarController extends Controller
{
    /**
     * Suggested users to follow.
     * Mutual-follow candidates (people followed by your followees) appear first,
     * then falls back to most-followed accounts. Excludes already-followed users.
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

        // Mutual-follow candidates: users that people-you-follow also follow
        $mutualIds = $followingIds
            ? User::whereHas('followers', fn ($q) => $q->whereIn('follower_id', $followingIds))
                ->whereNotIn('id', $excludeIds)
                ->limit($limit)
                ->pluck('id')
                ->all()
            : [];

        if (count($mutualIds) >= $limit) {
            $ids = array_slice($mutualIds, 0, $limit);
        } else {
            // Fill remaining slots with most-followed accounts
            $popularIds = User::whereNotIn('id', array_merge($excludeIds, $mutualIds))
                ->withCount('followers')
                ->orderBy('followers_count', 'desc')
                ->limit($limit - count($mutualIds))
                ->pluck('id')
                ->all();
            $ids = array_merge($mutualIds, $popularIds);
        }

        $users = User::whereIn('id', $ids)
            ->withCount('followers')
            ->with('profile')
            ->get()
            ->sortByDesc(fn ($u) => [
                in_array($u->id, $mutualIds) ? 1 : 0,
                $u->followers_count,
            ])
            ->values()
            ->map(fn ($u) => [
                'id'              => $u->id,
                'username'        => $u->username,
                'name'            => $u->profile?->full_name ?: $u->name,
                'avatar_url'      => $u->avatar_url,
                'followers_count' => $u->followers_count ?? 0,
                'mutual'          => in_array($u->id, $mutualIds),
            ]);

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
     * Trending hashtags ranked by recent activity (last 24 h) with 15-min cache.
     *
     * GET /api/sidebar/trending-hashtags?limit=4
     */
    public function trendingHashtags(Request $request)
    {
        $limit = max(1, min(10, (int) $request->get('limit', 4)));

        $tags = Cache::remember(
            'sidebar_trending_hashtags_' . $limit,
            900, // 15 minutes
            function () use ($limit) {
                $cutoff = now()->subHours(24)->toDateTimeString();

                // Score = posts in last 24 h (weighted ×3) + all-time usage_count
                return Hashtag::query()
                    ->selectRaw('hashtags.*, (
                        (SELECT COUNT(*) FROM hashtag_post hp
                         JOIN posts p ON p.id = hp.post_id
                         WHERE hp.hashtag_id = hashtags.id
                           AND p.created_at >= ?
                           AND p.is_approved = 1) * 3
                        + COALESCE(hashtags.usage_count, 0)
                    ) AS trending_score', [$cutoff])
                    ->having('trending_score', '>', 0)
                    ->orderByDesc('trending_score')
                    ->orderByDesc('hashtags.id')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($h) => [
                        'id'          => $h->id,
                        'slug'        => $h->slug,
                        'name'        => $h->name,
                        'usage_count' => $h->usage_count ?? 0,
                    ]);
            }
        );

        return response()->json(['success' => true, 'data' => $tags]);
    }
}
