@extends('layouts.app')

@section('title', ($post->user->name ?: $post->user->username) . ' on ' . config('app.name'))

@push('meta')
@php
    $ogDescription = $post->content ? Str::limit(strip_tags($post->content), 200) : config('app.name') . ' — Social Platform';
    $ogImage = $post->media->first()?->media_path ? asset('storage/' . $post->media->first()->media_path) : $post->user->avatar_url;
@endphp
<meta property="og:title" content="{{ e(($post->user->name ?: $post->user->username) . ': ' . Str::limit(strip_tags($post->content ?? ''), 100)) }}">
<meta property="og:description" content="{{ e($ogDescription) }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:url" content="{{ route('posts.show', $post) }}">
<meta property="og:type" content="article">
<meta name="twitter:title" content="{{ e(($post->user->name ?: $post->user->username) . ' on ' . config('app.name')) }}">
<meta name="twitter:description" content="{{ e($ogDescription) }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@endpush

@section('content')

@push('styles')
<style>
    @media (max-width: 768px) {
        :is(.app-layout, .main-content) { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
    }
</style>
@endpush

<div class="post-detail-page">
    <div class="page-header">
        <a href="{{ route('home') }}" class="back-link">
            <i class="fas fa-arrow-left"></i>
            {{ __('messages.back') }}
        </a>
    </div>

    <div class="post-detail-wrapper">
        @include('partials.post', ['post' => $post])
    </div>
</div>

<style>
.post-detail-page {
    max-width: 680px;
    margin: 0 auto;
    padding: 24px 16px 100px;
}

.page-header {
    margin-bottom: 12px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 12px;
    background: var(--surface);
    transition: all 0.3s ease;
    margin-bottom: 8px;
}

.back-link:hover {
    background: var(--surface-hover);
    color: var(--text);
    transform: translateX(-4px);
}

/* Mobile Responsive - Perfect Sync with Feed */
@media (max-width: 768px) {
    .post-detail-page {
        max-width: 100%;
        padding: 12px 0 100px;
        margin: 0;
    }

    .page-header {
        padding: 0 16px;
        margin-bottom: 12px;
    }
}
</style>
@endsection
