@extends('layouts.app')

@section('title', __('admin.manage_stories') . ' - Admin Panel')

@push('styles')
@vite('resources/css/admin-stories.css')
@endpush

@section('content')
<div class="admin-page">
    {{-- Header --}}
    <div class="admin-header">
        <div class="header-left">
            <a href="{{ route('admin.dashboard') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ __('admin.stories') }}</h1>
                <p>{{ __('admin.manage_stories_subtitle') }}</p>
            </div>
        </div>
        <div class="header-stats">
            <span class="total-badge">{{ $stories->total() }} {{ __('admin.total') }}</span>
        </div>
    </div>

    {{-- Search --}}
    <div class="search-section">
        <div class="search-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" value="{{ request('search') }}" placeholder="{{ __('admin.search_stories') }}" autocomplete="off">
            </div>
            @if(request('search'))
            <a href="{{ route('admin.stories') }}" class="clear-btn">
                <i class="fas fa-times"></i> {{ __('admin.clear') }}
            </a>
            @endif
        </div>
    </div>

    {{-- Stories Grid --}}
    @if($stories->count() > 0)
    <div class="stories-grid">
        @foreach($stories as $story)
        <div class="story-card">
            <div class="story-media">
                @if($story->media_type === 'image')
                    <img src="{{ asset('storage/' . $story->media_path) }}" alt="{{ __('admin.stories') }}" class="story-img">
                @elseif($story->media_type === 'video')
                    <video class="story-video" muted>
                        <source src="{{ asset('storage/' . $story->media_path) }}" type="video/mp4">
                    </video>
                @endif
                <div class="story-overlay">
                    <form method="POST" action="{{ route('admin.stories.delete', $story) }}" onsubmit="return confirm('{{ __('admin.delete_story_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="delete-btn" title="{{ __('admin.delete') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="story-info">
                <div class="story-user">
                    <div class="user-avatar">
                        <img src="{{ $story->user->avatar_url }}" alt="">
                    </div>
                    <div class="user-details">
                        <span class="user-name">{{ $story->user->username }}</span>
                        <span class="story-time">{{ $story->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                @if($story->content)
                <div class="story-content">
                    <p>{{ Str::limit($story->content, 80) }}</p>
                </div>
                @endif

                <div class="story-stats">
                    <div class="stat-item">
                        <i class="fas fa-eye"></i>
                        <span>{{ $story->storyViews->count() }}</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-heart"></i>
                        <span>{{ $story->reactions->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $stories->appends(request()->query())->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-circle-notch"></i>
        </div>
        <h3>{{ __('admin.no_stories_found') }}</h3>
        <p>{{ __('admin.no_stories_match') }}</p>
    </div>
    @endif
</div>

<script>
window.runOnPageLoad( function() {
    const searchInput = document.getElementById('search-input');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                if (query.length === 0) {
                    window.location.href = '{{ route("admin.stories") }}';
                }
                return;
            }

            searchTimeout = setTimeout(function() {
                window.location.href = '{{ route("admin.stories") }}?search=' + encodeURIComponent(query);
            }, 500);
        });
    }
});
</script>
@endsection
