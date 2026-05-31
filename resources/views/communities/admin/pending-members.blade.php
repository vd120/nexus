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
