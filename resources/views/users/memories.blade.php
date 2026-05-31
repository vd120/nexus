@extends('layouts.app')

@section('title', __('messages.my_memories') . ' - ' . $user->name)

@push('styles')
    @vite(['resources/css/posts-index.css'])
@endpush

@section('content')
<div class="lc-page">
    <div class="lc-page-back">
        <a href="{{ route('users.show', $user->username) }}" class="lc-back-link">
            <i class="fas fa-arrow-left"></i>
            {{ __('messages.back_to_profile') }}
        </a>
    </div>

    <div class="lc-page-header" style="background: linear-gradient(160deg, rgba(255, 236, 210, 0.10), rgba(251, 191, 100, 0.06));">
        <div class="lc-page-emoji">📖</div>
        <div class="lc-page-meta">
            <h1 class="lc-page-title">{{ __('messages.my_memories') }}</h1>
            <p class="lc-page-desc">{{ __('messages.memories_page_desc', ['name' => $user->name]) }}</p>
        </div>
    </div>

    <div class="lc-page-divider"></div>

    @if($memories->isEmpty())
        <div class="lc-empty">
            <div class="lc-empty-emoji">📝</div>
            <h3 class="lc-empty-title">{{ __('messages.no_memories_yet') }}</h3>
            <p class="lc-empty-desc">{{ __('messages.memories_empty_desc') }}</p>
        </div>
    @else
        <div class="my-memories-list">
            @foreach($memories as $memory)
                <div class="my-memory-row">
                    <div class="my-memory-question">{{ $memory->prompt->question }}</div>
                    <div class="my-memory-answer">{{ $memory->content }}</div>
                    <div class="my-memory-meta">
                        <span><i class="far fa-calendar"></i> {{ $memory->created_at->diffForHumans() }}</span>
                        @if($memory->visibility === 'self')
                            <span><i class="fas fa-lock"></i> {{ __('messages.memory_prompt_visibility_self') }}</span>
                        @elseif($memory->visibility === 'followers')
                            <span><i class="fas fa-user-friends"></i> {{ __('messages.memory_prompt_visibility_followers') }}</span>
                        @else
                            <span><i class="fas fa-globe"></i> {{ __('messages.memory_prompt_visibility_public') }}</span>
                        @endif
                        @if($memory->is_anonymous)
                            <span><i class="fas fa-user-secret"></i> {{ __('messages.anonymous') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($memories->hasPages())
            <div style="margin-top: 24px;">
                {{ $memories->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
