<?php

namespace App\Http\Controllers;

use App\Models\PulseAnswer;
use App\Models\PulseAnswerLike;
use App\Models\PulsePrompt;
use Illuminate\Http\Request;

class PulseController extends Controller
{
    public function index()
    {
        $prompt = PulsePrompt::currentDaily();

        $answers = collect();
        $userAnswer = null;
        if ($prompt) {
            $answers = $prompt->answers()
                ->with(['user.profile', 'likes'])
                ->withCount('likes')
                ->orderByDesc('likes_count')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            if (auth()->check()) {
                $userAnswer = $answers->firstWhere('user_id', auth()->id());
            }
        }

        $userLikedIds = auth()->check()
            ? PulseAnswerLike::where('user_id', auth()->id())
                ->whereIn('answer_id', $answers->pluck('id'))
                ->pluck('answer_id')->flip()->all()
            : [];

        return view('pulse.index', compact('prompt', 'answers', 'userAnswer', 'userLikedIds'));
    }

    public function answer(Request $request)
    {
        $data = $request->validate([
            'content'      => ['required', 'string', 'max:600'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $prompt = PulsePrompt::currentDaily();
        if (!$prompt) {
            return response()->json([
                'success' => false,
                'message' => __('messages.pulse_no_active_prompt'),
            ], 422);
        }

        // updateOrCreate enforces our (prompt_id, user_id) unique constraint —
        // each user has exactly one answer per prompt, editable until rotation.
        $isUpdate = PulseAnswer::where('prompt_id', $prompt->id)->where('user_id', auth()->id())->exists();
        $answer = PulseAnswer::updateOrCreate(
            ['prompt_id' => $prompt->id, 'user_id' => auth()->id()],
            [
                'content'      => trim($data['content']),
                'is_anonymous' => $request->boolean('is_anonymous'),
            ]
        );

        $answer->load('user.profile');

        $answersCount = $prompt->answers()->count();
        $likesCount = PulseAnswerLike::where('answer_id', $answer->id)->count();

        // Broadcast to all users viewing the pulse page
        app(\App\Services\SocketEmitService::class)->emit('pulse', 'pulse:answer', [
            'id'           => $answer->id,
            'content'      => $answer->content,
            'is_anonymous' => $answer->is_anonymous,
            'created_at'   => $answer->created_at->toIso8601String(),
            'answers_count' => $answersCount,
            'likes_count'  => $likesCount,
            'is_update'    => $isUpdate,
            'author_id'    => $answer->is_anonymous ? null : $answer->user_id,
            'author' => [
                'id'          => $answer->is_anonymous ? null : $answer->user_id,
                'username'    => $answer->is_anonymous ? null : $answer->user->username,
                'name'        => $answer->is_anonymous ? __('messages.anonymous_participant') : ($answer->user->profile?->full_name ?: $answer->user->name),
                'avatar_url'  => $answer->is_anonymous ? null : $answer->user->avatar_url,
                'is_verified' => $answer->is_anonymous ? false : (bool)$answer->user->is_verified,
            ],
        ]);

        return response()->json([
            'success' => true,
            'answer'  => [
                'id'           => $answer->id,
                'content'      => $answer->content,
                'is_anonymous' => $answer->is_anonymous,
                'created_at'   => $answer->created_at->toIso8601String(),
                'likes_count'  => $likesCount,
                'author'       => [
                    'id'          => $answer->is_anonymous ? null : $answer->user_id,
                    'username'    => $answer->is_anonymous ? null : $answer->user->username,
                    'name'        => $answer->is_anonymous ? __('messages.anonymous_participant') : ($answer->user->profile?->full_name ?: $answer->user->name),
                    'avatar_url'  => $answer->is_anonymous ? null : $answer->user->avatar_url,
                    'is_verified' => $answer->is_anonymous ? false : (bool)$answer->user->is_verified,
                ],
            ],
            'answers_count' => $answersCount,
        ]);
    }

    /**
     * Lightweight JSON used by the right-sidebar widget on every page load.
     * Kept narrow so it stays cheap to call on every feed render.
     */
    public function deleteAnswer()
    {
        $prompt = PulsePrompt::currentDaily();
        if (!$prompt) return response()->json(['success' => false], 422);

        $answer = PulseAnswer::where('prompt_id', $prompt->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$answer) return response()->json(['success' => false], 404);

        $answerId = $answer->id;
        $answer->delete();
        $answersCount = $prompt->answers()->count();

        app(\App\Services\SocketEmitService::class)->emit('pulse', 'pulse:answer:deleted', [
            'id'            => $answerId,
            'answers_count' => $answersCount,
        ]);

        return response()->json(['success' => true, 'answers_count' => $answersCount]);
    }

    public function today()
    {
        return $this->promptJson(PulsePrompt::currentDaily());
    }

    public function answers()
    {
        $prompt = PulsePrompt::currentDaily();
        if (!$prompt) return response()->json(['count' => 0, 'answers' => []]);

        $answers = $prompt->answers()
            ->with(['user.profile'])
            ->withCount('likes')
            ->orderByDesc('likes_count')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn($a) => [
                'id'           => $a->id,
                'content'      => $a->content,
                'is_anonymous' => $a->is_anonymous,
                'created_at'   => $a->created_at->toIso8601String(),
                'author' => [
                    'username'   => $a->is_anonymous ? null : $a->user->username,
                    'name'       => $a->is_anonymous ? __('messages.anonymous_participant') : ($a->user->profile?->full_name ?: $a->user->name),
                    'avatar_url' => $a->is_anonymous ? null : $a->user->avatar_url,
                ],
            ]);

        return response()->json(['count' => $answers->count(), 'answers' => $answers]);
    }

    /**
     * Sibling of today() for the weekly Memory Prompt. Same JSON shape so the
     * frontend widget can render either prompt with one renderer.
     */
    public function memory()
    {
        return $this->promptJson(PulsePrompt::currentMemory());
    }

    /**
     * POST handler for memory answers. Longer max length (5000 chars) than the
     * daily variant because memory prompts invite paragraph-length stories, and
     * adds a visibility scope (self|followers|public) so users can write the
     * intimate stuff without broadcasting it.
     */
    public function answerMemory(Request $request)
    {
        $data = $request->validate([
            'content'      => ['required', 'string', 'max:5000'],
            'is_anonymous' => ['nullable', 'boolean'],
            'visibility'   => ['nullable', 'in:self,followers,public'],
        ]);

        $prompt = PulsePrompt::currentMemory();
        if (!$prompt) {
            return response()->json([
                'success' => false,
                'message' => __('messages.pulse_no_active_prompt'),
            ], 422);
        }

        $isUpdate = PulseAnswer::where('prompt_id', $prompt->id)->where('user_id', auth()->id())->exists();
        $answer = PulseAnswer::updateOrCreate(
            ['prompt_id' => $prompt->id, 'user_id' => auth()->id()],
            [
                'content'      => trim($data['content']),
                'is_anonymous' => $request->boolean('is_anonymous'),
                'visibility'   => $data['visibility'] ?? 'public',
            ]
        );

        $answer->load('user.profile');

        $answersCount = $prompt->answers()->count();
        $likesCount = PulseAnswerLike::where('answer_id', $answer->id)->count();

        app(\App\Services\SocketEmitService::class)->emit('memory', 'memory:answer', [
            'id'           => $answer->id,
            'content'      => $answer->content,
            'is_anonymous' => $answer->is_anonymous,
            'visibility'   => $answer->visibility,
            'created_at'   => $answer->created_at->toIso8601String(),
            'answers_count' => $answersCount,
            'likes_count'  => $likesCount,
            'is_update'    => $isUpdate,
            'author_id'    => $answer->is_anonymous ? null : $answer->user_id,
            'author'       => [
                'id'          => $answer->is_anonymous ? null : $answer->user_id,
                'username'    => $answer->is_anonymous ? null : $answer->user->username,
                'name'        => $answer->is_anonymous ? __('messages.anonymous_participant') : ($answer->user->profile?->full_name ?: $answer->user->name),
                'avatar_url'  => $answer->is_anonymous ? null : $answer->user->avatar_url,
                'is_verified' => $answer->is_anonymous ? false : (bool)$answer->user->is_verified,
            ],
        ]);

        return response()->json([
            'success' => true,
            'answer'  => [
                'id'           => $answer->id,
                'content'      => $answer->content,
                'is_anonymous' => $answer->is_anonymous,
                'visibility'   => $answer->visibility,
                'created_at'   => $answer->created_at->toIso8601String(),
                'likes_count'  => $likesCount,
                'author'       => [
                    'id'          => $answer->is_anonymous ? null : $answer->user_id,
                    'username'    => $answer->is_anonymous ? null : $answer->user->username,
                    'name'        => $answer->is_anonymous ? __('messages.anonymous_participant') : ($answer->user->profile?->full_name ?: $answer->user->name),
                    'avatar_url'  => $answer->is_anonymous ? null : $answer->user->avatar_url,
                    'is_verified' => $answer->is_anonymous ? false : (bool)$answer->user->is_verified,
                ],
            ],
            'answers_count' => $answersCount,
        ]);
    }

    /**
     * Delete the authenticated user's memory answer for the current memory prompt.
     */
    public function deleteMemoryAnswer()
    {
        $prompt = PulsePrompt::currentMemory();
        if (!$prompt) return response()->json(['success' => false], 422);

        $answer = PulseAnswer::where('prompt_id', $prompt->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$answer) return response()->json(['success' => false], 404);

        $answerId = $answer->id;
        $answer->delete();
        $answersCount = $prompt->answers()->count();

        app(\App\Services\SocketEmitService::class)->emit('memory', 'memory:answer:deleted', [
            'id'            => $answerId,
            'answers_count' => $answersCount,
        ]);

        return response()->json(['success' => true, 'answers_count' => $answersCount]);
    }

    /**
     * Dedicated memories page — mirrors the pulse index page structure.
     * Shows hero, compose section (pre-filled if user already answered), and paginated answers.
     */
    public function memoriesIndex()
    {
        $prompt = PulsePrompt::currentMemory();

        $userAnswer = null;
        $answers = collect();

        if ($prompt) {
            if (auth()->check()) {
                $userAnswer = $prompt->answers()
                    ->where('user_id', auth()->id())
                    ->first();
            }

            $answersQuery = $prompt->answers()
                ->with(['user.profile', 'likes'])
                ->where(function ($q) {
                    $q->where('visibility', 'public');
                    if (auth()->check()) {
                        $q->orWhere(function ($subQ) {
                            $subQ->where('visibility', 'followers')
                                ->whereIn('user_id', function ($followQuery) {
                                    $followQuery->select('followed_id')
                                        ->from('follows')
                                        ->where('follower_id', auth()->id());
                                });
                        });
                        $q->orWhere(function ($subQ) {
                            $subQ->where('visibility', 'self')
                                ->where('user_id', auth()->id());
                        });
                    }
                })
                ->withCount('likes')
                ->orderByDesc('likes_count')
                ->orderByDesc('created_at');

            $answers = $answersQuery->paginate(20);
        }

        $userLikedIds = auth()->check() && $answers->count()
            ? PulseAnswerLike::where('user_id', auth()->id())
                ->whereIn('answer_id', $answers->pluck('id'))
                ->pluck('answer_id')->flip()->all()
            : [];

        return view('memories.index', compact('prompt', 'userAnswer', 'answers', 'userLikedIds'));
    }

    /**
     * Browse all public memory answers from the community
     */
    public function memoryAnswers()
    {
        $prompt = PulsePrompt::currentMemory();

        if (!$prompt) {
            return view('pulse.memory-answers', [
                'prompt' => null,
                'answers' => collect(),
            ]);
        }

        // Build query for visible answers
        $answersQuery = $prompt->answers()
            ->with(['user.profile'])
            ->where(function($q) {
                $q->where('visibility', 'public');

                // If authenticated, also show followers-only from people they follow
                if (auth()->check()) {
                    $q->orWhere(function($subQ) {
                        $subQ->where('visibility', 'followers')
                            ->whereIn('user_id', function($followQuery) {
                                $followQuery->select('followed_id')
                                    ->from('follows')
                                    ->where('follower_id', auth()->id());
                            });
                    });
                }
            })
            ->withCount('likes')
            ->orderByDesc('likes_count')
            ->orderByDesc('created_at');

        $answers = $answersQuery->paginate(20);

        return view('pulse.memory-answers', compact('prompt', 'answers'));
    }

    /**
     * Toggle a love reaction on a pulse/memory answer.
     */
    public function toggleLike(Request $request)
    {
        $request->validate(['answer_id' => ['required', 'integer', 'exists:pulse_answers,id']]);

        $answer = PulseAnswer::with('prompt')->findOrFail($request->answer_id);
        $userId = auth()->id();

        $existing = PulseAnswerLike::where('answer_id', $answer->id)->where('user_id', $userId)->first();
        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            PulseAnswerLike::create(['answer_id' => $answer->id, 'user_id' => $userId]);
            $liked = true;
        }

        $likesCount = PulseAnswerLike::where('answer_id', $answer->id)->count();
        $room  = $answer->prompt->type === 'memory' ? 'memory' : 'pulse';
        $event = $answer->prompt->type === 'memory' ? 'memory:answer:liked' : 'pulse:answer:liked';

        app(\App\Services\SocketEmitService::class)->emit($room, $event, [
            'answer_id'   => $answer->id,
            'likes_count' => $likesCount,
            'liked_by'    => $userId,
            'liked'       => $liked,
        ]);

        return response()->json(['success' => true, 'liked' => $liked, 'likes_count' => $likesCount]);
    }

    /**
     * Shared JSON shape for the lightweight prompt endpoints. Daily and memory
     * widgets both render from this structure so one renderer covers both.
     */
    protected function promptJson(?PulsePrompt $prompt)
    {
        if (!$prompt) {
            return response()->json(['success' => true, 'data' => null]);
        }

        $userAnswered = false;
        if (auth()->check()) {
            $userAnswered = $prompt->answers()
                ->where('user_id', auth()->id())
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $prompt->id,
                'type'           => $prompt->type,
                'question'       => $prompt->question,
                'ends_at'        => $prompt->ends_at?->toIso8601String(),
                'answers_count'  => $prompt->answers()->count(),
                'user_answered'  => $userAnswered,
            ],
        ]);
    }
}
