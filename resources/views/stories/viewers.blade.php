@extends('layouts.app')

@section('title', __('messages.story_viewers') . ' - ' . $user->username)

@push('styles')
@vite('resources/css/stories-viewer.css')
@endpush

@section('content')
<div class="story-viewers-page">
    <div class="page-header">
        <a href="{{ route('stories.show', [$user, $story]) }}" class="back-link">
            <i class="fas fa-arrow-left"></i>
            {{ __('messages.back_to_story') }}
        </a>
        <h1>{{ __('messages.story_viewers') }}</h1>
    </div>

    <div class="story-preview-section">
        @if($story->media_type === 'image')
            <img src="{{ asset('storage/' . $story->media_path) }}" alt="{{ __('messages.story') }}" class="story-preview">
        @elseif($story->media_type === 'video')
            <video class="story-preview" muted>
                <source src="{{ asset('storage/' . $story->media_path) }}" type="video/mp4">
            </video>
        @endif
        <div class="story-info">
            <span class="story-date">{{ __('messages.posted') }} {{ $story->created_at->diffForHumans() }}</span>
            <span class="view-count"><i class="fas fa-eye"></i> {{ $viewerData->count() }} {{ __('messages.views') }}</span>
        </div>
    </div>

    <div class="viewers-list">
        <h2>{{ __('messages.viewers') }}</h2>
        @if($viewerData->count() > 0)
            <div class="viewers-grid">
                @foreach($viewerData as $viewer)
                <div class="viewer-item">
                    <div class="viewer-avatar">
                        <img src="{{ $viewer['user']->avatar_url }}" alt="Avatar">
                    </div>
                    <div class="viewer-info">
                        <a href="{{ route('users.show', $viewer['user']) }}" class="viewer-name">{{ $viewer['user']->username }}</a>
                        @if($viewer['reaction'])
                            <span class="viewer-reaction">
                                {{ __('messages.reaction') }}: {{ $viewer['reaction'] }}
                            </span>
                        @endif
                        @if($viewer['user']->profile && $viewer['user']->profile->bio)
                            <span class="viewer-bio">{{ Str::limit($viewer['user']->profile->bio, 40) }}</span>
                        @endif
                    </div>
                    <div class="viewer-meta">
                        <span class="viewed-time">{{ $viewer['viewed_at']->diffForHumans() }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-eye-slash"></i>
                <h3>{{ __('messages.no_viewers_yet') }}</h3>
                <p>{{ __('messages.no_viewers_desc') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
