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
                    @if($post->topic)
                        <span class="topic-tag">{{ $post->topic->name }}</span>
                    @endif
                </div>

                <div class="panel-body moderation-body">
                    <div class="post-content-full">
                        {!! nl2br(e($post->content)) !!}
                    </div>
                    @if($post->media->count() > 0)
                        <div class="post-media-gallery">
                            @foreach($post->media as $media)
                                <div class="media-preview-item">
                                    <img src="{{ asset('storage/' . $media->media_path) }}" alt="" onclick="window.open(this.src, '_blank')">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="panel-footer moderation-footer">
                    <div class="moderation-controls">
                        <button onclick="approvePost('{{ $post->id }}')" class="mod-btn approve-btn">
                            <div class="btn-icon"><i class="fas fa-check"></i></div>
                            <span>{{ __('community_admin.approve_post') }}</span>
                        </button>
                        <button onclick="rejectPost('{{ $post->id }}')" class="mod-btn reject-btn">
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

<script>
    function approvePost(postId) {
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
            if (el) el.style.display = 'none';
            showToast("{{ __('community_admin.post_approved') }}", 'success');
        }).catch(err => {
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }

    function rejectPost(postId) {
        if (!confirm("{{ __('community_admin.reject_post_confirm') }}")) return;
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
            if (el) el.style.display = 'none';
            showToast("{{ __('community_admin.post_rejected') }}", 'error');
        }).catch(err => {
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }
</script>
        </div>
    </main>
</div>
@endsection
