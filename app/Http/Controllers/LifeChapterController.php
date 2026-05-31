<?php

namespace App\Http\Controllers;

use App\Models\LifeChapter;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LifeChapterController extends Controller
{
    /**
     * Public list of a user's life chapters (for the profile timeline).
     */
    public function index(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        $chapters = LifeChapter::where('user_id', $user->id)
            ->orderByDesc('sort_order')
            ->orderByDesc('starts_on')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $chapters->map(fn ($c) => [
                'id'          => $c->id,
                'title'       => $c->title,
                'emoji'       => $c->emoji,
                'starts_on'   => $c->starts_on?->toDateString(),
                'ends_on'     => $c->ends_on?->toDateString(),
                'description' => $c->description,
                'sort_order'  => $c->sort_order,
                'ongoing'     => is_null($c->ends_on),
                'created_at'  => $c->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Create a new chapter for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        abort_unless($userId, 401);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:120'],
            'emoji'       => ['nullable', 'string', 'max:16'],
            'starts_on'   => ['nullable', 'date'],
            'ends_on'     => ['nullable', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order'  => ['nullable', 'integer'],
        ]);

        $data['user_id']    = $userId;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $chapter = LifeChapter::create($data);

        return response()->json([
            'success' => true,
            'data'    => $chapter,
        ], 201);
    }

    /**
     * Update an owned chapter.
     */
    public function update(Request $request, LifeChapter $chapter): JsonResponse
    {
        $userId = $request->user()?->id;
        abort_unless($userId, 401);
        abort_unless($chapter->user_id === $userId, 403);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:120'],
            'emoji'       => ['nullable', 'string', 'max:16'],
            'starts_on'   => ['nullable', 'date'],
            'ends_on'     => ['nullable', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order'  => ['nullable', 'integer'],
        ]);

        $chapter->update($data);

        return response()->json([
            'success' => true,
            'data'    => $chapter->fresh(),
        ]);
    }

    /**
     * Delete a chapter. We do NOT cascade — posts/answers stay,
     * but their life_chapter_id is set back to null so they don't dangle.
     */
    public function destroy(Request $request, LifeChapter $chapter): JsonResponse
    {
        $userId = $request->user()?->id;
        abort_unless($userId, 401);
        abort_unless($chapter->user_id === $userId, 403);

        DB::transaction(function () use ($chapter) {
            DB::table('posts')
                ->where('life_chapter_id', $chapter->id)
                ->update(['life_chapter_id' => null]);

            DB::table('pulse_answers')
                ->where('life_chapter_id', $chapter->id)
                ->update(['life_chapter_id' => null]);

            $chapter->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * Paginated posts that belong to this chapter, honoring post visibility.
     * - The chapter owner sees everything.
     * - Other viewers see only non-private, approved, non-deleted posts.
     */
    public function posts(Request $request, LifeChapter $chapter): JsonResponse
    {
        $viewerId = $request->user()?->id;
        $isOwner  = $viewerId === $chapter->user_id;

        $query = Post::where('life_chapter_id', $chapter->id)
            ->with(['user.profile', 'media'])
            ->latest();

        if (!$isOwner) {
            $query->where('is_private', false)
                  ->where('is_approved', true)
                  ->whereNull('social_group_id');
        }

        $posts = $query->paginate(15);

        return response()->json([
            'success' => true,
            'chapter' => [
                'id'    => $chapter->id,
                'title' => $chapter->title,
                'emoji' => $chapter->emoji,
            ],
            'data'    => $posts->items(),
            'meta'    => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }
}
