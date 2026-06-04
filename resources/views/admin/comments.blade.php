@extends('layouts.app')

@section('title', __('admin.manage_comments') . ' - Admin Panel')

@push('styles')
@vite('resources/css/admin-comments.css')
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
                <h1>{{ __('admin.comments') }}</h1>
                <p>{{ __('admin.manage_comments_subtitle') }}</p>
            </div>
        </div>
        <div class="header-stats">
            <span class="total-badge">{{ $comments->total() }} {{ __('admin.total') }}</span>
        </div>
    </div>

    {{-- Search --}}
    <div class="search-section">
        <div class="search-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" value="{{ request('search') }}" placeholder="{{ __('admin.search_comments') }}" autocomplete="off">
            </div>
            @if(request('search'))
            <a href="{{ route('admin.comments') }}" class="clear-btn">
                <i class="fas fa-times"></i> {{ __('admin.clear') }}
            </a>
            @endif
        </div>
        <div id="search-results" class="search-results"></div>
    </div>

    {{-- Comments List --}}
    @if($comments->count() > 0)
    <div class="comments-section">
        @foreach($comments as $comment)
        <div class="comment-card">
            <div class="comment-main">
                <div class="comment-user">
                    <div class="user-avatar">
                        <img src="{{ $comment->user->avatar_url }}" alt="">
                    </div>
                    <div class="user-details">
                        <span class="user-name" style="display:inline-flex;align-items:center;gap:.2em;">{{ $comment->user->username }}<x-verified-badge :user="$comment->user" size=".8em" /></span>
                        <span class="comment-time">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                <div class="comment-body">
                    <p>{!! app(\App\Services\MentionService::class)->convertMentionsToLinks($comment->content) !!}</p>
                </div>

                <div class="comment-meta">
                    <div class="meta-item">
                        <i class="fas fa-heart"></i>
                        <span>{{ $comment->likes->count() }} {{ __('admin.likes') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>{{ __('admin.post_by') }} {{ $comment->post->user->username }}</span>
                    </div>
                    <a href="/posts/{{ $comment->post->slug }}" target="_blank" class="view-post-link">
                        <i class="fas fa-external-link-alt"></i> {{ __('admin.view_post_link') }}
                    </a>
                </div>
            </div>

            <div class="comment-actions">
                <form method="POST" action="{{ route('admin.comments.delete', $comment) }}" onsubmit="return confirm('{{ __('admin.delete_comment_confirm') }}')">
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
        {{ $comments->appends(request()->query())->links() }}
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-comments"></i>
        </div>
        <h3>{{ __('admin.no_comments_found') }}</h3>
        <p>{{ __('admin.no_comments_match') }}</p>
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
                    window.location.href = '{{ route("admin.comments") }}';
                }
                return;
            }

            searchTimeout = setTimeout(function() {
                window.location.href = '{{ route("admin.comments") }}?search=' + encodeURIComponent(query);
            }, 500);
        });
    }
});
</script>
@endsection
