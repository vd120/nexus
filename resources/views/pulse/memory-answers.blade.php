@extends('layouts.app')

@section('title', __('messages.community_memories') ?? 'Community Memories')

@push('styles')
    @vite(['resources/css/posts-index.css', 'resources/css/pulse.css'])
@endpush

@section('content')
<div class="lc-page">
    <div class="lc-page-back">
        <a href="{{ route('home') }}" class="lc-back-link">
            <i class="fas fa-arrow-left"></i>
            {{ __('messages.back_to_feed') ?? 'Back to feed' }}
        </a>
    </div>

    @if(!$prompt)
        <div class="lc-empty">
            <div class="lc-empty-emoji">📖</div>
            <h3 class="lc-empty-title">{{ __('messages.no_active_memory_prompt') ?? 'No Active Memory Prompt' }}</h3>
            <p class="lc-empty-desc">{{ __('messages.no_active_memory_prompt_desc') ?? 'There is no active memory prompt at the moment. Check back later!' }}</p>
        </div>
    @else
        <div class="lc-page-header" style="background: linear-gradient(160deg, rgba(255, 236, 210, 0.10), rgba(251, 191, 100, 0.06));">
            <div class="lc-page-emoji">📖</div>
            <div class="lc-page-meta">
                <h1 class="lc-page-title">{{ __('messages.community_memories') ?? 'Community Memories' }}</h1>
                <p class="lc-page-desc" style="font-style: italic; font-weight: 500; margin-top: 8px;">{{ $prompt->question }}</p>
            </div>
        </div>

        <div class="lc-page-divider"></div>

        @if($answers->isEmpty())
            <div class="lc-empty">
                <div class="lc-empty-emoji">✍️</div>
                <h3 class="lc-empty-title">{{ __('messages.no_memories_shared_yet') ?? 'No Memories Shared Yet' }}</h3>
                <p class="lc-empty-desc">{{ __('messages.be_first_to_share_memory') ?? 'Be the first to share your memory!' }}</p>
            </div>
        @else
            <div class="memory-answers-list">
                @foreach($answers as $answer)
                    <article class="memory-answer-card">
                        <div class="memory-answer-header">
                            @if($answer->is_anonymous)
                                <div class="memory-answer-author">
                                    <div class="memory-answer-avatar">
                                        <i class="fas fa-user-secret"></i>
                                    </div>
                                    <div class="memory-answer-author-info">
                                        <span class="memory-answer-author-name">{{ __('messages.anonymous_participant') ?? 'Anonymous Participant' }}</span>
                                        <span class="memory-answer-meta">
                                            <i class="far fa-calendar"></i> {{ $answer->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('users.show', $answer->user->username) }}" class="memory-answer-author">
                                    <img src="{{ $answer->user->avatar_url }}" alt="{{ $answer->user->name }}" class="memory-answer-avatar">
                                    <div class="memory-answer-author-info">
                                        <span class="memory-answer-author-name">{{ $answer->user->profile->full_name ?? $answer->user->name }}</span>
                                        <span class="memory-answer-meta">
                                            <span class="memory-answer-username">@{{ $answer->user->username }}</span>
                                            <span>•</span>
                                            <i class="far fa-calendar"></i> {{ $answer->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </a>
                            @endif

                            <div class="memory-answer-badges">
                                @if($answer->visibility === 'followers')
                                    <span class="memory-badge memory-badge-followers">
                                        <i class="fas fa-user-friends"></i>
                                        {{ __('messages.followers_only') ?? 'Followers' }}
                                    </span>
                                @elseif($answer->visibility === 'self')
                                    <span class="memory-badge memory-badge-private">
                                        <i class="fas fa-lock"></i>
                                        {{ __('messages.private') ?? 'Private' }}
                                    </span>
                                @else
                                    <span class="memory-badge memory-badge-public">
                                        <i class="fas fa-globe"></i>
                                        {{ __('messages.public') ?? 'Public' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="memory-answer-content">
                            {{ $answer->content }}
                        </div>
                    </article>
                @endforeach
            </div>

            @if($answers->hasPages())
                <div style="margin-top: 24px;">
                    {{ $answers->links() }}
                </div>
            @endif
        @endif
    @endif
</div>

<style>
.memory-answers-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.memory-answer-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 16px 18px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.memory-answer-card:hover {
    border-color: rgba(251, 191, 100, 0.35);
    box-shadow: 0 4px 16px rgba(251, 191, 100, 0.08);
}

.memory-answer-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.memory-answer-author {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    flex: 1;
    min-width: 0;
}

.memory-answer-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--border);
}

.memory-answer-author-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.memory-answer-author-name {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.memory-answer-meta {
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

.memory-answer-username {
    direction: ltr;
    unicode-bidi: embed;
}

.memory-answer-badges {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

.memory-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid;
}

.memory-badge-public {
    background: rgba(34, 197, 94, 0.08);
    border-color: rgba(34, 197, 94, 0.25);
    color: #16a34a;
}

.memory-badge-followers {
    background: rgba(99, 102, 241, 0.08);
    border-color: rgba(99, 102, 241, 0.25);
    color: var(--primary);
}

.memory-badge-private {
    background: rgba(156, 163, 175, 0.08);
    border-color: rgba(156, 163, 175, 0.25);
    color: var(--text-muted);
}

.memory-badge i {
    font-size: 10px;
}

.memory-answer-content {
    font-size: 14.5px;
    line-height: 1.6;
    color: var(--text);
    white-space: pre-wrap;
    word-break: break-word;
}

/* Anonymous avatar styling */
.memory-answer-author .memory-answer-avatar:not(img) {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(99, 102, 241, 0.15));
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

@media (max-width: 640px) {
    .memory-answer-card {
        padding: 14px 16px;
    }

    .memory-answer-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .memory-answer-badges {
        align-self: flex-start;
    }

    .memory-answer-avatar {
        width: 38px;
        height: 38px;
    }

    .memory-answer-author-name {
        font-size: 14px;
    }

    .memory-answer-content {
        font-size: 14px;
    }
}
</style>
@endsection
