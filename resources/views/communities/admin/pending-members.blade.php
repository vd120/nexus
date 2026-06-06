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
                        <a href="{{ route('users.show', $request->user) }}" style="display:flex;flex-shrink:0;"><img src="{{ $request->user->avatar_url }}" alt="" class="request-avatar" style="pointer-events:none;"></a>
                        <div class="request-text">
                            <a href="{{ route('users.show', $request->user) }}" style="text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:.2em;"><h3 class="request-name" style="margin:0;">{{ $request->user->name }}</h3><x-verified-badge :user="$request->user" size=".85em" /></a>
                            <a href="{{ route('users.show', $request->user) }}" class="request-handle" style="text-decoration:none;">{{ '@' . $request->user->username }}</a>
                            <div class="request-meta-badge">
                                <i class="far fa-clock"></i> {{ $request->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-actions">
                        <button onclick="approveMember.call(this, '{{ $request->user->id }}')" class="mod-btn approve-btn">
                            <i class="fas fa-user-check"></i> <span>{{ __('community_admin.approve') }}</span>
                        </button>
                        <button onclick="rejectMember.call(this, '{{ $request->user->id }}')" class="mod-btn reject-btn">
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
    .request-card.removing {
        transition: opacity 0.3s ease, transform 0.3s ease;
        opacity: 0;
        transform: scale(0.97);
        pointer-events: none;
    }
</style>
<script>
    function fadeAndRemoveMember(el) {
        el.classList.add('removing');
        setTimeout(() => {
            el.remove();
            checkRequestsEmpty();
        }, 300);
    }

    function checkRequestsEmpty() {
        const grid = document.querySelector('.member-requests-grid');
        if (!grid) return;
        const remaining = grid.querySelectorAll('.request-card:not(.removing)');
        if (remaining.length === 0) {
            grid.innerHTML = `
                <div class="admin-empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-icon-wrap"><i class="fas fa-user-shield"></i></div>
                    <h3>{{ __('community_admin.all_caught_up') }}</h3>
                    <p>{{ __('community_admin.no_pending_requests') }}</p>
                    <a href="{{ route('communities.admin.index', $group->slug) }}" class="btn-link">{{ __('community_admin.back_to_dashboard') }}</a>
                </div>`;
            // Update sidebar badge to 0
            const badge = document.querySelector('.nav-link[href*="moderation/members"] .badge');
            if (badge) badge.remove();
        }
    }

    function approveMember(userId) {
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
            if (el) fadeAndRemoveMember(el);
            showToast("{{ __('community_admin.member_approved') }}", 'success');
        }).catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }

    function rejectMember(userId) {
        if (!confirm("{{ __('community_admin.reject_request_confirm') }}")) return;

        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
            if (el) fadeAndRemoveMember(el);
            showToast("{{ __('community_admin.request_rejected') }}", 'error');
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
