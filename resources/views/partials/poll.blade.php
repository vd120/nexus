@php
    $totalVotes = $poll->totalVotes();
    $userVote   = auth()->check() ? $poll->userVote(auth()->id()) : null;
    $hasVoted   = $userVote !== null;
    $isExpired  = $poll->isExpired();
    $canVote    = auth()->check() && !$hasVoted && !$isExpired;
    $showResults = true;
@endphp

<div class="post-poll" data-post-slug="{{ $post->slug }}" data-poll-id="{{ $poll->id }}">
    @if($poll->question)
        <p class="poll-question">{{ $poll->question }}</p>
    @endif

    <div class="poll-options">
        @foreach($poll->options as $option)
            @php
                $pct = $totalVotes > 0 ? round(($option->votes_count / $totalVotes) * 100, 1) : 0;
                $isChosen = $userVote && $userVote->option_id === $option->id;
            @endphp
            <button
                type="button"
                class="poll-option {{ ($hasVoted || $isExpired) ? 'poll-option--result' : '' }} {{ $isChosen ? 'poll-option--chosen' : '' }}"
                data-option-id="{{ $option->id }}"
                data-post-slug="{{ $post->slug }}"
                onclick="{{ $canVote ? 'castPollVote(this)' : 'void(0)' }}"
                style="{{ $canVote ? '' : 'cursor:default;' }}"
            >
                <span class="poll-fill" style="width:{{ $pct }}%;"></span>
                @if(!$hasVoted && !$isExpired)
                    <span class="poll-radio" aria-hidden="true"></span>
                @endif
                <span class="poll-option-text">{{ $option->text }}</span>
                @if($hasVoted || $isExpired)
                    <span class="poll-option-pct">{{ $pct }}%</span>
                    @if($isChosen)
                        <span class="poll-option-check" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    @endif
                @endif
            </button>
        @endforeach
    </div>

    <p class="poll-meta">
        <span class="poll-vote-count">{{ $totalVotes }} {{ trans_choice('posts.poll_vote', $totalVotes) }}</span>
        @if($isExpired)
            &middot; <span>{{ __('posts.poll_final_results') }}</span>
        @elseif($poll->expires_at)
            &middot; <span class="poll-expires-label" data-expires="{{ $poll->expires_at->toISOString() }}">{{ __('posts.poll_ends') }} {{ $poll->expires_at->diffForHumans() }}</span>
        @endif
    </p>
</div>

<style>
.post-poll { margin: .75rem 0; padding: 0 1rem; }
.poll-question { font-weight: 600; margin: 0 0 .75rem; font-size: .9375rem; }
.poll-options { display: flex; flex-direction: column; gap: .5rem; }

/* Base option card */
.poll-option {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: .625rem;
    padding: .75rem 1rem;
    border-radius: 10px;
    border: 1.5px solid var(--border-color, #2a2a3e);
    background: transparent;
    color: inherit;
    font-size: .9375rem;
    text-align: left;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    min-height: 48px;
    width: 100%;
}

/* Hover only when can vote */
.poll-option:not(.poll-option--result):hover {
    border-color: var(--accent, #6366f1);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}

/* Radio dot (before voting) */
.poll-radio {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid var(--border-color, #4a4a6a);
    background: transparent;
    transition: border-color .15s;
}
.poll-option:not(.poll-option--result):hover .poll-radio {
    border-color: var(--accent, #6366f1);
}

/* Fill bar (after voting) */
.poll-fill {
    position: absolute;
    inset-y: 0;
    left: 0;
    border-radius: 8px;
    background: var(--border-color, #2a2a3e);
    opacity: .3;
    transition: width .5s cubic-bezier(.4,0,.2,1);
}

/* Chosen option */
.poll-option--chosen {
    border-color: var(--accent, #6366f1);
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
}
.poll-option--chosen .poll-fill {
    background: var(--accent, #6366f1);
    opacity: .25;
}

.poll-option-text {
    position: relative;
    z-index: 1;
    flex: 1;
    font-weight: 500;
}

/* Percentage (after voting) */
.poll-option-pct {
    position: relative;
    z-index: 1;
    font-size: .8rem;
    font-weight: 600;
    opacity: .75;
    white-space: nowrap;
    min-width: 2.5rem;
    text-align: right;
}

/* Checkmark icon (chosen) */
.poll-option-check {
    position: relative;
    z-index: 1;
    color: var(--accent, #6366f1);
    font-size: 1rem;
    flex-shrink: 0;
    display: flex;
    align-items: center;
}

.poll-meta { font-size: .8rem; opacity: .55; margin: .5rem 0 0; }
</style>
