@extends('layouts.app')

@section('title', $chapter->title . ' - ' . $user->username)

@section('content')
@php
    $startYear = $chapter->starts_on?->format('Y');
    $endYear = $chapter->ends_on?->format('Y');
    $rangeText = $startYear
        ? ($endYear ? $startYear.' – '.$endYear : $startYear.' – '.__('messages.ongoing'))
        : ($endYear ? '… – '.$endYear : __('messages.ongoing'));
@endphp

<div class="lc-page">
    <div class="lc-page-back">
        <a href="{{ url('/users/'.$user->username) }}" class="lc-back-link">
            <i class="fas {{ app()->getLocale() === 'ar' ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i>
            <span>{{ '@'.$user->username }}</span>
        </a>
    </div>

    <header class="lc-page-header">
        <div class="lc-page-emoji" aria-hidden="true">{{ $chapter->emoji ?: '📖' }}</div>
        <div class="lc-page-meta">
            <h1 class="lc-page-title">{{ $chapter->title }}</h1>
            <div class="lc-page-range">
                <i class="fas fa-calendar-alt"></i>
                <span>{{ $rangeText }}</span>
            </div>
            @if($chapter->description)
                <p class="lc-page-desc">{{ $chapter->description }}</p>
            @endif
        </div>
    </header>

    <div class="lc-page-divider"></div>

    <section class="lc-page-posts">
        <h2 class="lc-page-posts-title">
            <i class="fas fa-stream"></i>
            <span>{{ __('users.posts') }}</span>
            <span class="lc-page-count">{{ $posts->total() }}</span>
        </h2>

        <div class="regular-posts-section" style="display: flex; flex-direction: column; gap: 20px;">
            @forelse($posts as $post)
                @include('partials.post', ['post' => $post, 'isPinned' => false])
            @empty
                <div class="empty-state" style="text-align:center; padding: 60px 20px;">
                    <i class="fas fa-feather-alt" style="font-size:36px;color:var(--text-muted);"></i>
                    <h3 style="margin-top:14px;">{{ __('users.no_posts_yet') }}</h3>
                    <p style="color:var(--text-muted);">{{ __('messages.chapters_empty_desc') }}</p>
                </div>
            @endforelse
            {{ $posts->links() }}
        </div>
    </section>
</div>
@endsection
