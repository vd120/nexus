@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="moderation-page">
    <div class="admin-page-header">
        <h1 class="admin-page-title">{{ __('community_admin.join_requests') }}</h1>
        <p class="admin-page-subtitle">{{ __('community_admin.join_requests_subtitle') }}</p>
    </div>

    <div class="member-requests-grid">
        @forelse($members as $request)
            <div class="panel request-card" id="request-{{ $request->user->id }}">
                <div class="panel-body request-card-inner">
                    <div class="request-user-box">
                        <img src="{{ $request->user->avatar_url }}" alt="" class="request-avatar">
                        <div class="request-text">
                            <h3 class="request-name">{{ $request->user->name }}</h3>
                            <span class="request-handle">{{ '@' . $request->user->username }}</span>
                            <div class="request-meta-badge">
                                <i class="far fa-clock"></i> {{ $request->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-actions">
                        <button onclick="approveMember('{{ $request->user->id }}')" class="mod-btn approve-btn">
                            <i class="fas fa-user-check"></i> <span>{{ __('community_admin.approve') }}</span>
                        </button>
                        <button onclick="rejectMember('{{ $request->user->id }}')" class="mod-btn reject-btn">
                            <i class="fas fa-user-times"></i> <span>{{ __('community_admin.reject') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="admin-empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon-wrap">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>{{ __('community_admin.all_caught_up') }}</h3>
                <p>{{ __('community_admin.no_pending_requests') }}</p>
                <a href="{{ route('communities.admin.index', $group->slug) }}" class="btn-link">{{ __('community_admin.back_to_dashboard') }}</a>
            </div>
        @endforelse
    </div>

    @if($members->hasPages())
    <div class="pagination-wrap">
        {{ $members->links() }}
    </div>
    @endif
</div>

<style>
    .moderation-page { max-width: 1100px; margin: 0 auto; }
    
    .member-requests-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    
    .request-card { border-radius: 24px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid var(--border); }
    .request-card:hover { border-color: var(--admin-accent); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    
    .request-card-inner { display: flex; flex-direction: column; gap: 24px; padding: 28px; }
    
    .request-user-box { display: flex; align-items: center; gap: 18px; }
    .request-avatar { width: 64px; height: 64px; border-radius: 18px; object-fit: cover; border: 1px solid var(--border); }
    
    .request-text { display: flex; flex-direction: column; gap: 4px; }
    .request-name { font-size: 17px; font-weight: 800; color: var(--text); margin: 0; }
    .request-handle { font-size: 13px; color: var(--admin-accent); font-weight: 700; }
    .request-meta-badge { font-size: 11px; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 6px; margin-top: 4px; }

    .request-actions { display: flex; gap: 12px; }
    
    .mod-btn { flex: 1; height: 48px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-hover); display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer; transition: 0.2s; font-size: 14px; font-weight: 700; color: var(--text); }
    
    .approve-btn:hover { background: #10b981; color: white; border-color: #10b981; }
    .reject-btn { color: var(--text-muted); }
    .reject-btn:hover { background: #ef4444; color: white; border-color: #ef4444; }

    /* Empty State */
    .admin-empty-state { padding: 80px 40px; text-align: center; background: var(--surface); border-radius: 32px; border: 1px solid var(--border); }
    .empty-icon-wrap { width: 80px; height: 80px; background: var(--admin-accent-glow); color: var(--admin-accent); border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px; }
    .admin-empty-state h3 { font-size: 24px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .admin-empty-state p { color: var(--text-muted); margin-bottom: 24px; }
    .btn-link { color: var(--admin-accent); text-decoration: none; font-weight: 700; font-size: 14px; }

    @media (max-width: 900px) {
        .member-requests-grid { grid-template-columns: 1fr; }
    }
</style>

<script>
    function approveMember(userId) {
        fetch(`/communities/{{ $group->slug }}/admin/members/${userId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => {
            if (!res.ok) throw new Error('Failed to approve member');
            return res.json();
        }).then(data => {
            const el = document.getElementById(`request-${userId}`);
            if (el) el.style.display = 'none';
            showToast("{{ __('community_admin.member_approved') }}", 'success');
        }).catch(err => {
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }

    function rejectMember(userId) {
        if (!confirm("{{ __('community_admin.reject_request_confirm') }}")) return;
        fetch(`/communities/{{ $group->slug }}/admin/members/${userId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => {
            if (!res.ok) throw new Error('Failed to reject request');
            return res.json();
        }).then(data => {
            const el = document.getElementById(`request-${userId}`);
            if (el) el.style.display = 'none';
            showToast("{{ __('community_admin.request_rejected') }}", 'error');
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
