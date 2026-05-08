@extends('layouts.app')

@section('title', $user->username . ' - ' . __('users.following'))

@section('content')
<style>
.users-list-container { max-width: 680px; margin: 0 auto; padding: 0 12px; }
.page-header { margin-bottom: 24px; display: flex; flex-direction: column; gap: 8px; }
.page-header-top { display: flex; align-items: center; gap: 12px; }
.back-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 50%; color: var(--text);
    text-decoration: none; flex-shrink: 0;
}
.page-header h1 { font-size: 20px; font-weight: 800; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px; }
.page-header p { color: var(--text-muted); font-size: 13px; margin: 0; }

.btn-follow {
    padding: 8px 14px; border-radius: 16px; font-size: 12px; font-weight: 600;
    cursor: pointer; border: none; min-width: 70px; white-space: nowrap;
}
.btn-follow.primary { background: var(--primary); color: white; }
.btn-follow.secondary { background: transparent; border: 1px solid var(--border); color: var(--text); }

.users-grid { display: flex; flex-direction: column; gap: 12px; }
.user-card {
    display: grid; grid-template-columns: auto 1fr auto; align-items: center;
    gap: 12px; padding: 14px; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius-lg);
}
.user-avatar {
    width: 42px; height: 42px; border-radius: 50%; overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--secondary)); flex-shrink: 0;
}
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }
.user-avatar .placeholder {
    width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: white;
}
.user-info { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.user-info a { text-decoration: none; }
.user-name { font-size: 14px; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.user-name:hover { color: var(--primary); }
.user-meta { font-size: 12px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.user-meta span { direction: ltr; }
.user-actions { display: flex; gap: 8px; flex-shrink: 0; }

.empty-state { text-align: center; padding: 60px 20px; }
.empty-state i { font-size: 64px; color: var(--text-muted); margin-bottom: 20px; opacity: 0.5; }
</style>

<div class="users-list-container">
    <div class="page-header">
        <div class="page-header-top">
            <a href="{{ route('users.show', $user) }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1><i class="fas fa-user-friends"></i> {{ __('users.following') }}</h1>
        </div>
        <p>{{ trans_choice('users.following_count', $following->count(), ['count' => $following->count()]) }}</p>
    </div>

    <div class="users-grid">
        @forelse($following as $follow)
        <div class="user-card">
            <a href="{{ route('users.show', $follow->followed) }}" class="user-avatar">
                <img src="{{ $follow->followed->avatar_url }}" alt="{{ $follow->followed->username }}">
            </a>
            <div class="user-info">
                <a href="{{ route('users.show', $follow->followed) }}">
                    <div class="user-name">{{ $follow->followed->name }}</div>
                </a>
                <div class="user-meta"><span dir="ltr" style="display: inline-block;">@ {{ $follow->followed->username }}</span></div>
            </div>
            <div class="user-actions">
                @if(auth()->check() && auth()->id() === $user->id)
                    <button class="btn btn-ghost" onclick="followingPageUnfollow(this, '{{ $follow->followed->username }}')">
                        <i class="fas fa-user-minus"></i> {{ __('users.unfollow') }}
                    </button>
                @elseif(auth()->check() && auth()->id() !== $follow->followed->id)
                    @php $isFollowing = in_array($follow->followed->id, $followingIds); @endphp
                    <button class="btn btn-sm {{ $isFollowing ? '' : 'btn-primary' }}" onclick="followingPageToggleFollow(this, '{{ $follow->followed->username }}')" data-following="{{ $isFollowing ? 'true' : 'false' }}">
                        {{ $isFollowing ? __('users.following') : __('users.follow') }}
                    </button>
                @endif
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="fas fa-user-friends"></i>
            <h3>{{ __('users.no_following_yet') }}</h3>
            <p style="color: var(--text-muted);">{{ __('users.no_following_yet_desc', ['username' => $user->username]) }}</p>
        </div>
        @endforelse
    </div>
</div>

<script>
function followingPageUnfollow(btn, username) {
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    fetch(`/users/${username}/follow`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 
            'Accept': 'application/json' 
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && !data.is_following) {
            // Successfully unfollowed, remove the card
            const card = btn.closest('.user-card');
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateX(20px)';
                setTimeout(() => card.remove(), 300);
            }
            showToast(data.message, 'success');
        } else {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

function followingPageToggleFollow(btn, username) {
    const originalHtml = btn.innerHTML;
    const isFollowing = btn.getAttribute('data-following') === 'true';
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    fetch(`/users/${username}/follow`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 
            'Accept': 'application/json' 
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const isNowFollowing = data.is_following;
            btn.setAttribute('data-following', isNowFollowing ? 'true' : 'false');
            btn.classList.toggle('btn-primary', !isNowFollowing);
            btn.innerHTML = isNowFollowing ? '{{ __('users.following') }}' : '{{ __('users.follow') }}';
            btn.disabled = false;
            showToast(data.message, 'success');
        } else {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

// Show success message toast if exists
@if(session('success'))
document.addEventListener('DOMContentLoaded', function() {
    showToast({!! json_encode(session('success')) !!}, 'success');
});
@endif
</script>
@endsection
