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
                            <strong class="mod-username">
                                {{ $post->is_anonymous ? __('community_admin.anonymous_member') : $post->user->username }}
                                @if($post->is_anonymous) <i class="fas fa-user-secret anon-icon"></i> @endif
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

<style>
    .moderation-page { max-width: 900px; margin: 0 auto; }
    .queue-stack { display: flex; flex-direction: column; gap: 32px; }
    
    .moderation-card { border-radius: 28px; overflow: hidden; border: 1px solid var(--border); transition: 0.3s; }
    .moderation-card:hover { border-color: var(--admin-accent); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }

    .moderation-card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .user-cell { display: flex; align-items: center; gap: 14px; }
    .mod-avatar { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border); }
    .mod-meta { display: flex; flex-direction: column; gap: 2px; }
    .mod-username { font-size: 15px; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; }
    .anon-icon { font-size: 13px; color: var(--text-muted); opacity: 0.7; }
    .mod-time { font-size: 12px; color: var(--text-muted); font-weight: 500; display: flex; align-items: center; gap: 4px; }

    .topic-tag { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--admin-accent); background: var(--admin-accent-glow); padding: 5px 12px; border-radius: 8px; border: 1px solid var(--admin-accent-glow); letter-spacing: 0.5px; }

    .moderation-body { padding: 28px 24px; }
    .post-content-full { font-size: 16px; line-height: 1.7; color: var(--text); white-space: pre-wrap; font-weight: 500; word-break: break-word; margin-bottom: 24px; }
    
    .post-media-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-top: 20px; }
    .media-preview-item { aspect-ratio: 4/3; border-radius: 16px; overflow: hidden; border: 1px solid var(--border); cursor: pointer; transition: 0.3s; }
    .media-preview-item:hover { transform: scale(1.02); border-color: var(--admin-accent); }
    .media-preview-item img { width: 100%; height: 100%; object-fit: cover; }

    .moderation-footer { padding: 20px 24px; background: var(--surface-hover); border-top: 1px solid var(--border); }
    .moderation-controls { display: flex; gap: 16px; }
    
    .mod-btn { flex: 1; height: 52px; border-radius: 14px; border: 1px solid var(--border); background: var(--surface); display: flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-size: 15px; font-weight: 700; color: var(--text); }
    .btn-icon { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: 0.2s; }
    
    .approve-btn:hover { border-color: #10b981; color: #10b981; background: rgba(16, 185, 129, 0.05); }
    .approve-btn:hover .btn-icon { background: #10b981; color: white; }
    
    .reject-btn { color: var(--text-muted); }
    .reject-btn:hover { border-color: #ef4444; color: #ef4444; background: rgba(239, 68, 68, 0.05); }
    .reject-btn:hover .btn-icon { background: #ef4444; color: white; }

    /* Empty State */
    .admin-empty-state { padding: 80px 40px; text-align: center; background: var(--surface); border-radius: 32px; border: 1px solid var(--border); }
    .empty-icon-wrap { width: 80px; height: 80px; background: var(--admin-accent-glow); color: var(--admin-accent); border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px; }
    .admin-empty-state h3 { font-size: 24px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .admin-empty-state p { color: var(--text-muted); margin-bottom: 24px; }
    .btn-link { color: var(--admin-accent); text-decoration: none; font-weight: 700; font-size: 14px; }

    @media (max-width: 600px) {
        .moderation-controls { flex-direction: column; }
        .post-media-gallery { grid-template-columns: 1fr; }
        .moderation-body { padding: 20px; }
        .post-content-full { font-size: 15px; }
    }
</style>

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
