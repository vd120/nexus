@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="moderation-page">
    <div class="admin-page-header">
        <h1 class="admin-page-title">{{ __('community_admin.pending_posts') }}</h1>
        <p class="admin-page-subtitle">{{ __('community_admin.pending_posts_subtitle') }}</p>
    </div>

    <div class="queue-stack">
        @forelse($posts as $post)
            <div class="panel moderation-card" id="post-{{ $post->id }}">
                <div class="panel-header moderation-card-header">
                    <div class="user-cell">
                        <img src="{{ $post->is_anonymous ? 'https://ui-avatars.com/api/?name=Anon&background=374151&color=9ca3af' : $post->user->avatar_url }}" alt="" class="mod-avatar">
                        <div class="mod-meta">
                            <strong class="mod-username" style="display:inline-flex;align-items:center;gap:.2em;">
                                {{ $post->is_anonymous ? __('community_admin.anonymous_member') : $post->user->username }}
                                @if($post->is_anonymous) <i class="fas fa-user-secret anon-icon"></i>
                                @elseif($post->user) <x-verified-badge :user="$post->user" size=".8em" />
                                @endif
                            </strong>
                            <span class="mod-time"><i class="far fa-clock"></i> {{ $post->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    @if($post->socialGroupTopic)
                        <span class="topic-tag">{{ $post->socialGroupTopic->name }}</span>
                    @endif
                </div>

                <div class="panel-body moderation-body">

                    {{-- Text content — same rendering as feed --}}
                    @if($post->content)
                        @php
                            $stripped  = strip_tags($post->content);
                            $content   = $post->content_html;
                            $isArabic  = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $stripped);
                        @endphp
                        <div class="post-content-full"
                             style="{{ $isArabic ? 'direction:rtl;text-align:right;' : '' }}">
                            {!! $content !!}
                        </div>
                    @endif

                    {{-- Poll (if any) --}}
                    @if($post->poll)
                        @include('partials.poll', ['poll' => $post->poll->load('options'), 'post' => $post])
                    @endif

                    {{-- Media — same fb-grid layout as feed --}}
                    @if($post->media && $post->media->count() > 0)
                        @php
                            $mediaCount    = $post->media->count();
                            $remainingCount = $mediaCount - 4;
                            $mediaData = $post->media->map(fn($m, $i) => [
                                'index' => $i,
                                'type'  => $m->media_type,
                                'src'   => asset('storage/' . $m->media_path),
                            ]);
                        @endphp
                        <div class="post-media fb-grid fb-grid-{{ $mediaCount > 4 ? 4 : $mediaCount }}"
                             data-post-id="{{ $post->id }}"
                             data-media-count="{{ $mediaCount }}"
                             data-media-list="{{ json_encode($mediaData) }}"
                             style="margin-top: 16px;">
                            @foreach($post->media as $index => $media)
                                @if($index < 4)
                                    @if($media->media_type === 'image')
                                        <div class="media-item {{ $index === 3 && $remainingCount > 0 ? 'has-more' : '' }}">
                                            <img src="{{ asset('storage/' . $media->media_path) }}" alt="{{ __('messages.post_image') }}" loading="lazy">
                                            @if($index === 3 && $remainingCount > 0)
                                                <div class="more-overlay"><span class="more-count">+{{ $remainingCount }}</span></div>
                                            @endif
                                            <button type="button" class="media-click-catcher" onclick="openMediaModal('{{ $post->id }}', '{{ $index }}')" aria-label="{{ __('messages.view_media') ?? '' }}"></button>
                                        </div>
                                    @elseif($media->media_type === 'video')
                                        <div class="media-item video-indicator {{ $index === 3 && $remainingCount > 0 ? 'has-more' : '' }}">
                                            <video preload="metadata"
                                                   poster="{{ $media->media_thumbnail ? asset('storage/' . $media->media_thumbnail) : '' }}"
                                                   playsinline muted>
                                                <source src="{{ asset('storage/' . $media->media_path) }}" type="video/mp4">
                                            </video>
                                            <div class="video-play-button" aria-hidden="true"><i class="fas fa-play"></i></div>
                                            @if($index === 3 && $remainingCount > 0)
                                                <div class="more-overlay"><span class="more-count">+{{ $remainingCount }}</span></div>
                                            @endif
                                            <button type="button" class="media-click-catcher" onclick="openMediaModal('{{ $post->id }}', '{{ $index }}')" aria-label="{{ __('messages.view_media') ?? '' }}"></button>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif

                </div>

                <div class="panel-footer moderation-footer">
                    <div class="moderation-controls">
                        <button onclick="approvePost.call(this, '{{ $post->id }}')" class="mod-btn approve-btn">
                            <div class="btn-icon"><i class="fas fa-check"></i></div>
                            <span>{{ __('community_admin.approve_post') }}</span>
                        </button>
                        <button onclick="rejectPost.call(this, '{{ $post->id }}')" class="mod-btn reject-btn">
                            <div class="btn-icon"><i class="fas fa-times"></i></div>
                            <span>{{ __('community_admin.reject_submission') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="admin-empty-state">
                <div class="empty-icon-wrap">
                    <i class="fas fa-check-double"></i>
                </div>
                <h3>{{ __('community_admin.all_clear') }}</h3>
                <p>{{ __('community_admin.no_pending_posts') }}</p>
                <a href="{{ route('communities.admin.index', $group->slug) }}" class="btn-link">{{ __('community_admin.back_to_dashboard') }}</a>
            </div>
        @endforelse
    </div>

    @if($posts->hasPages())
    <div class="pagination">
        {{ $posts->links() }}
    </div>
    @endif
</div>

<style>
    .moderation-card.removing {
        transition: opacity 0.3s ease, transform 0.3s ease;
        opacity: 0;
        transform: scale(0.97);
        pointer-events: none;
    }
</style>
<script>
    function fadeAndRemove(el) {
        el.classList.add('removing');
        setTimeout(() => {
            el.remove();
            checkQueueEmpty();
        }, 300);
    }

    function checkQueueEmpty() {
        const stack = document.querySelector('.queue-stack');
        if (!stack) return;
        const remaining = stack.querySelectorAll('.moderation-card:not(.removing)');
        if (remaining.length === 0) {
            stack.innerHTML = `
                <div class="admin-empty-state">
                    <div class="empty-icon-wrap"><i class="fas fa-check-double"></i></div>
                    <h3>{{ __('community_admin.all_clear') }}</h3>
                    <p>{{ __('community_admin.no_pending_posts') }}</p>
                    <a href="{{ route('communities.admin.index', $group->slug) }}" class="btn-link">{{ __('community_admin.back_to_dashboard') }}</a>
                </div>`;
            // Update sidebar badge to 0
            const badge = document.querySelector('.nav-link[href*="moderation/posts"] .badge');
            if (badge) badge.remove();
        }
    }

    function approvePost(postId) {
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`/communities/{{ $group->slug }}/admin/posts/${postId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => {
            if (!res.ok) throw new Error('Failed to approve post');
            return res.json();
        }).then(data => {
            const el = document.getElementById(`post-${postId}`);
            if (el) fadeAndRemove(el);
            showToast("{{ __('community_admin.post_approved') }}", 'success');
        }).catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }

    function rejectPost(postId) {
        if (!confirm("{{ __('community_admin.reject_post_confirm') }}")) return;

        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch(`/communities/{{ $group->slug }}/admin/posts/${postId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => {
            if (!res.ok) throw new Error('Failed to reject post');
            return res.json();
        }).then(data => {
            const el = document.getElementById(`post-${postId}`);
            if (el) fadeAndRemove(el);
            showToast("{{ __('community_admin.post_rejected') }}", 'error');
        }).catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }
</script>
        </div>
    </main>
</div>
@endsection
