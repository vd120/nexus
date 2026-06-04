<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PollVote;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function vote(Request $request, Post $post)
    {
        $request->validate(['option_id' => 'required|integer']);

        $poll = $post->poll()->with('options')->first();
        if (!$poll) {
            return response()->json(['message' => __('posts.poll_not_found')], 404);
        }

        if ($poll->isExpired()) {
            return response()->json(['message' => __('posts.poll_ended')], 422);
        }

        $option = $poll->options->find($request->option_id);
        if (!$option) {
            return response()->json(['message' => __('posts.poll_invalid_option')], 422);
        }

        $userId = auth()->id();

        if (PollVote::where('poll_id', $poll->id)->where('user_id', $userId)->exists()) {
            return response()->json(['message' => __('posts.poll_already_voted')], 422);
        }

        PollVote::create([
            'poll_id'   => $poll->id,
            'option_id' => $option->id,
            'user_id'   => $userId,
        ]);

        $option->increment('votes_count');
        $poll->refresh()->load('options');

        $totalVotes = $poll->totalVotes();
        $results = $poll->options->map(fn($o) => [
            'id'          => $o->id,
            'text'        => $o->text,
            'votes_count' => $o->votes_count,
            'percentage'  => $o->percentage($totalVotes),
        ]);

        // Broadcast updated results via socket to all users
        app(\App\Services\SocketEmitService::class)->emit('global', 'poll:vote', [
            'post_id'     => $post->id,
            'post_slug'   => $post->slug,
            'poll_id'     => $poll->id,
            'total_votes' => $totalVotes,
            'results'     => $results,
        ]);

        return response()->json([
            'voted_option_id' => $option->id,
            'total_votes'     => $totalVotes,
            'results'         => $results,
        ]);
    }
}
