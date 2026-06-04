<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\Post;
use App\Services\HashtagService;
use Illuminate\Http\Request;

class HashtagController extends Controller
{
    protected HashtagService $hashtagService;

    public function __construct(HashtagService $hashtagService)
    {
        $this->hashtagService = $hashtagService;
    }

    /**
     * Display hashtag page with all posts
     */
    public function show(string $slug)
    {
        $hashtag = Hashtag::where('slug', $slug)->firstOrFail();
        
        $user = auth()->user();
        
        $query = Post::with(['user.profile', 'media', 'likes', 'reactions.user:id,name,username', 'comments'])
            ->whereHas('hashtags', function ($q) use ($hashtag) {
                $q->where('hashtag_id', $hashtag->id);
            });
        
        // Apply same visibility rules as home page
        if ($user) {
            $query->where(function($q) use ($user) {
                $q->where(function($subQ) use ($user) {
                        $subQ->where('user_id', $user->id);
                    })
                    ->orWhere(function($subQ) use ($user) {
                        $subQ->whereHas('user.followers', function($followerQuery) use ($user) {
                            $followerQuery->where('follower_id', $user->id);
                        });
                    })
                    ->orWhere(function($subQ) {
                        $subQ->where('is_private', false);
                    });
            })
            ->whereDoesntHave('user.blockedBy', function($q) use ($user) {
                $q->where('blocker_id', $user->id);
            })
            ->whereDoesntHave('user.blockedUsers', function($q) use ($user) {
                $q->where('blocked_id', $user->id);
            });
        } else {
            $query->where('is_private', false)
                ->whereHas('user.profile', function($q) {
                    $q->where('is_private', false);
                });
        }
        
        $posts = $query->latest()->paginate(20);

        $relatedHashtags = Hashtag::popular(5)
            ->where('id', '!=', $hashtag->id)
            ->get();

        return view('hashtags.show', compact('hashtag', 'posts', 'relatedHashtags'));
    }

    /**
     * Display popular hashtags (top 5)
     */
    public function index()
    {
        $topHashtags = Hashtag::popular(5)->get();
        $allHashtags = Hashtag::orderBy('usage_count', 'desc')->paginate(50);
        return view('hashtags.index', compact('topHashtags', 'allHashtags'));
    }

    /**
     * Get hashtag suggestions for autocomplete
     */
    public function suggestions(Request $request)
    {
        $search = $request->get('search', '');
        
        $hashtags = Hashtag::where('name', 'like', '%' . $search . '%')
            ->orderBy('usage_count', 'desc')
            ->limit(8)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => [
                'top' => [],
                'matching' => $hashtags
            ]
        ]);
    }
}
