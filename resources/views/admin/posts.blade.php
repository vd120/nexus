@extends('layouts.app')

@section('title', __('admin.manage_posts') . ' - Admin Panel')

@push('styles')
@vite('resources/css/admin-posts.css')
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
                <h1>{{ __('admin.posts') }}</h1>
                <p>{{ __('admin.manage_posts_subtitle') }}</p>
            </div>
        </div>
        <div class="header-stats">
            <span class="total-badge">{{ $posts->total() }} {{ __('admin.total') }}</span>
        </div>
    </div>

    {{-- Search --}}
    <div class="search-section">
        <div class="search-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" value="{{ request('search') }}" placeholder="{{ __('admin.search_posts') }}" autocomplete="off">
            </div>
            @if(request('search'))
            <a href="{{ route('admin.posts') }}" class="clear-btn">
                <i class="fas fa-times"></i> {{ __('admin.clear') }}
            </a>
            @endif
        </div>
    </div>

    {{-- Posts List --}}
    @if($posts->count() > 0)
    <div class="posts-section">
        @foreach($posts as $post)
        <div class="post-card">
            <div class="post-main">
                <div class="post-user">
                    <div class="user-avatar">
                        <img src="{{ $post->user->avatar_url }}" alt="">
                    </div>
                    <div class="user-details">
                        <span class="user-name" style="display:inline-flex;align-items:center;gap:.2em;">{{ $post->user->username }}<x-verified-badge :user="$post->user" size=".8em" /></span>
                        <span class="post-time">{{ $post->created_at->diffForHumans() }}</span>
                    </div>
                    @if($post->is_private)
                    <span class="private-badge"><i class="fas fa-lock"></i></span>
                    @endif
                </div>

                <div class="post-content">
                    @if($post->content)
                        <p>{{ Str::limit($post->content, 250) }}</p>
                    @endif
                </div>

                @if($post->media->count() > 0)
                <div class="post-media">
                    @if($post->media->count() === 1)
                        @php $media = $post->media->first(); @endphp
                        @if($media->media_type === 'image')
                            <img src="{{ asset('storage/' . $media->media_path) }}" alt="{{ __('admin.media_post') }}" class="single-media">
                        @elseif($media->media_type === 'video')
                            <video class="single-media" controls muted>
                                <source src="{{ asset('storage/' . $media->media_path) }}" type="video/mp4">
                            </video>
                        @endif
                    @else
                        <div class="media-grid">
                            @foreach($post->media->take(4) as $media)
                                @if($media->media_type === 'image')
                                    <img src="{{ asset('storage/' . $media->media_path) }}" alt="" class="media-thumb">
                                @endif
                            @endforeach
                            @if($post->media->count() > 4)
                                <div class="media-more">+{{ $post->media->count() - 4 }}</div>
                            @endif
                        </div>
                    @endif
                </div>
                @endif

                <div class="post-stats">
                    <div class="stat-item">
                        <i class="fas fa-heart"></i>
                        <span>{{ $post->likes->count() }}</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-comment"></i>
                        <span>{{ $post->comments->count() }}</span>
                    </div>
                    <a href="/posts/{{ $post->slug }}" target="_blank" class="view-link">
                        <i class="fas fa-external-link-alt"></i> {{ __('admin.view_post') }}
                    </a>
                </div>
            </div>

            <div class="post-actions">
                <form method="POST" action="{{ route('admin.posts.delete', $post) }}" onsubmit="return confirm('{{ __('admin.delete_post_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="delete-btn" title="{{ __('admin.delete') }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $posts->appends(request()->query())->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-images"></i>
        </div>
        <h3>{{ __('admin.no_posts_found') }}</h3>
        <p>{{ __('admin.no_posts_match') }}</p>
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
                    window.location.href = '{{ route("admin.posts") }}';
                }
                return;
            }

            searchTimeout = setTimeout(function() {
                window.location.href = '{{ route("admin.posts") }}?search=' + encodeURIComponent(query);
            }, 500);
        });
    }
});
</script>
@endsection
