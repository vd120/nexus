@extends('layouts.app')

@section('title', $hashtag->display_name)

@push('styles')
@vite('resources/css/hashtag.css')
@endpush

@section('content')
<div class="hashtag-page">
    {{-- Header --}}
    <div class="hashtag-header">
        <a href="{{ route('home') }}" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="hashtag-info">
            <h1>{{ $hashtag->display_name }}</h1>
            <p>{{ __('hashtags.posts_count', ['count' => $hashtag->usage_count]) }}</p>
        </div>
    </div>

    {{-- Stats Card --}}
    <div class="hashtag-stats">
        <div class="stat-item">
            <i class="fas fa-hashtag"></i>
            <div>
                <strong>{{ $hashtag->usage_count }}</strong>
                <span>{{ __('hashtags.uses') }}</span>
            </div>
        </div>
        @if($hashtag->created_at)
        <div class="stat-item">
            <i class="fas fa-calendar"></i>
            <div>
                <strong>{{ __('hashtags.created') }}</strong>
                <span>{{ $hashtag->created_at->diffForHumans() }}</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Posts Grid --}}
    @if($posts->count() > 0)
    <div class="hashtag-posts">
        @foreach($posts as $post)
            @include('partials.post', ['post' => $post])
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pagination-section">
        {{ $posts->links() }}
    </div>
    @else
    <div class="empty-state">
        <i class="fas fa-hashtag"></i>
        <h3>{{ __('hashtags.no_posts') }}</h3>
        <p>{{ __('hashtags.no_posts_message') }}</p>
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="fas fa-home"></i> {{ __('hashtags.browse_posts') }}
        </a>
    </div>
    @endif

    {{-- Related Hashtags --}}
    @if($relatedHashtags->count() > 0)
    <div class="related-hashtags">
        <h3><i class="fas fa-fire"></i> {{ __('hashtags.related_hashtags') }}</h3>
        <div class="hashtags-cloud">
            @foreach($relatedHashtags as $tag)
                <a href="{{ route('hashtags.show', $tag->slug) }}" class="hashtag-tag">
                    <span class="tag-name">{{ $tag->name }}</span>
                    <span class="count">{{ $tag->usage_count }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif
</div>

@endsection
