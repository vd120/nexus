@extends('layouts.app')

@section('title', __('users.saved_posts'))

@section('content')
<style>
.saved-posts-container { max-width: 680px; margin: 0 auto; padding: 0 12px; }
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 20px; font-weight: 800; color: var(--text); display: flex; align-items: center; gap: 8px; }
.page-header p { color: var(--text-muted); font-size: 13px; }

.posts-feed { display: flex; flex-direction: column; gap: 20px; }

.empty-state { text-align: center; padding: 60px 20px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); position: relative; }
.empty-state i { font-size: 64px; color: var(--text-muted); margin-bottom: 20px; opacity: 0.5; }
body.light-theme .empty-state { background: #ffffff; }

/* Mobile Responsive */
@media (max-width: 480px) {
    .saved-posts-container { padding: 0 8px; }
    .page-header h1 { font-size: 18px; }
    .page-header p { font-size: 12px; }
}
</style>

<div class="saved-posts-container">
    <div class="page-header" style="display: flex; align-items: flex-start; gap: 16px;">
        <a href="javascript:history.back()" class="btn" style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--surface); border: 1px solid var(--border); color: var(--text); padding: 0; min-width: 38px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex: 1;">
            <h1 style="margin: 0;"><i class="fas fa-bookmark" style="color: var(--primary);"></i> {{ __('users.saved_posts') }}</h1>
            <p style="margin: 4px 0 0 0;">{{ trans_choice('users.saved_posts_count', $savedPosts->count(), ['count' => $savedPosts->count()]) }}</p>
        </div>
    </div>

    <div class="posts-feed">
        @forelse($savedPosts as $saved)
            @if($saved->post)
                @include('partials.post', ['post' => $saved->post])
            @endif
        @empty
        <div class="empty-state">
            <i class="fas fa-bookmark"></i>
            <h3>{{ __('users.no_saved_posts') }}</h3>
            <p style="color: var(--text-muted);">{{ __('users.no_saved_posts_desc') }}</p>
            <a href="{{ route('home') }}" class="btn btn-primary" style="margin-top: 16px; position: relative; z-index: 1;">{{ __('users.browse_posts') }}</a>
        </div>
        @endforelse
    </div>

    @if($savedPosts->hasPages())
    <div style="margin-top: 24px;">
        {{ $savedPosts->links() }}
    </div>
    @endif
</div>
@endsection
