@extends('layouts.app')

@section('title', __('admin.dashboard') . ' - Nexus')

@section('content')
<div class="admin-dashboard">
    {{-- Header --}}
    <div class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <i class="fas fa-shield-alt"></i>
                <h1>{{ __('admin.dashboard') }}</h1>
            </div>
            <p class="admin-subtitle">{{ __('admin.dashboard_subtitle') }}</p>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="stats-section">
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-icon-wrap users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['total_users']) }}</span>
                    <span class="stat-label">{{ __('admin.total_users') }}</span>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon-wrap posts">
                    <i class="fas fa-pen-square"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['total_posts']) }}</span>
                    <span class="stat-label">{{ __('admin.total_posts') }}</span>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon-wrap comments">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['total_comments']) }}</span>
                    <span class="stat-label">{{ __('admin.comments') }}</span>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon-wrap stories">
                    <i class="fas fa-circle-notch"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['total_stories']) }}</span>
                    <span class="stat-label">{{ __('admin.stories') }}</span>
                </div>
            </div>
        </div>

        <div class="stats-row secondary">
            <div class="stat-box small">
                <div class="stat-icon-wrap follows">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['total_follows']) }}</span>
                    <span class="stat-label">{{ __('admin.follows') }}</span>
                </div>
            </div>

            <div class="stat-box small">
                <div class="stat-icon-wrap blocks">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['total_blocks']) }}</span>
                    <span class="stat-label">{{ __('admin.blocks') }}</span>
                </div>
            </div>

            <div class="stat-box small">
                <div class="stat-icon-wrap admin">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['admin_users']) }}</span>
                    <span class="stat-label">{{ __('admin.admins') }}</span>
                </div>
            </div>

            <div class="stat-box small">
                <div class="stat-icon-wrap private">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['private_profiles']) }}</span>
                    <span class="stat-label">{{ __('admin.private_profiles') }}</span>
                </div>
            </div>

            <div class="stat-box small">
                <div class="stat-icon-wrap reports" style="background: linear-gradient(135deg, #EF4444, #F97316);">
                    <i class="fas fa-flag"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($stats['total_reports']) }}</span>
                    <span class="stat-label">{{ __('admin.reports') }}</span>
                    @if($stats['pending_reports'] > 0)
                    <span class="stat-sub">{{ number_format($stats['pending_reports']) }} {{ __('admin.pending') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-bolt"></i>
            {{ __('admin.quick_actions') }}
        </h2>
        <div class="actions-row">
            <a href="{{ route('admin.users') }}" class="action-btn">
                <div class="action-icon"><i class="fas fa-users-cog"></i></div>
                <b style="color: #ffffff !important; display: block; margin-top: 8px;">{{ __('admin.users') }}</b>
            </a>
            <a href="{{ route('admin.posts') }}" class="action-btn">
                <div class="action-icon"><i class="fas fa-images"></i></div>
                <b style="color: #ffffff !important; display: block; margin-top: 8px;">{{ __('admin.posts') }}</b>
            </a>
            <a href="{{ route('admin.comments') }}" class="action-btn">
                <div class="action-icon"><i class="fas fa-comments"></i></div>
                <b style="color: #ffffff !important; display: block; margin-top: 8px;">{{ __('admin.comments') }}</b>
            </a>
            <a href="{{ route('admin.stories') }}" class="action-btn">
                <div class="action-icon"><i class="fas fa-camera"></i></div>
                <b style="color: #ffffff !important; display: block; margin-top: 8px;">{{ __('admin.stories') }}</b>
            </a>
            @if($stats['pending_reports'] > 0)
            <a href="{{ route('admin.reports') }}" class="action-btn highlight" style="position: relative;">
                <div class="action-icon"><i class="fas fa-flag"></i></div>
                <b style="color: #ffffff !important; display: block; margin-top: 8px;">{{ __('admin.reports') }}</b>
                <span class="notification-badge">{{ $stats['pending_reports'] }}</span>
            </a>
            @else
            <a href="{{ route('admin.reports') }}" class="action-btn">
                <div class="action-icon"><i class="fas fa-flag"></i></div>
                <b style="color: #ffffff !important; display: block; margin-top: 8px;">{{ __('admin.reports') }}</b>
            </a>
            @endif
            <a href="#" onclick="showCreateAdminModal()" class="action-btn highlight">
                <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                <b style="color: #ffffff !important; display: block; margin-top: 8px;">{{ __('admin.new_admin') }}</b>
            </a>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-clock"></i>
            {{ __('admin.recent_activity') }}
        </h2>
        <div class="activity-grid">
            <div class="activity-card">
                <div class="activity-header">
                    <h3>{{ __('admin.new_users') }}</h3>
                    <span class="badge">{{ $stats['recent_users']->count() }}</span>
                </div>
                <div class="activity-list">
                    @forelse($stats['recent_users']->take(5) as $user)
                    <div class="activity-item">
                        <div class="activity-avatar">
                            <img src="{{ $user->avatar_url }}" alt="">
                        </div>
                        <div class="activity-details">
                            <span class="activity-name" style="color: #ffffff !important; font-weight: bold;">{{ $user->username }}</span>
                            <span class="activity-time" style="color: #86868b !important;">{{ $user->created_at->diffForHumans() }}</span>
                        </div>
                        <a href="{{ route('admin.users.show', $user) }}" class="activity-link">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    @empty
                    <div class="empty-activity" style="color: #86868b !important;">{{ __('admin.no_users_yet') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="activity-card">
                <div class="activity-header">
                    <h3>{{ __('admin.latest_posts') }}</h3>
                    <span class="badge">{{ $stats['recent_posts']->count() }}</span>
                </div>
                <div class="activity-list">
                    @forelse($stats['recent_posts']->take(5) as $post)
                    <div class="activity-item">
                        <div class="activity-avatar">
                            <img src="{{ $post->user->avatar_url }}" alt="">
                        </div>
                        <div class="activity-details">
                            <span class="activity-name" style="color: #ffffff !important; font-weight: bold;">{{ $post->user->username }}</span>
                            <span class="activity-time" style="color: #86868b !important;">{{ Str::limit($post->content ?? __('admin.media_post'), 30) }}</span>
                        </div>
                        <a href="{{ route('admin.posts') }}" class="activity-link">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    @empty
                    <div class="empty-activity" style="color: #86868b !important;">{{ __('admin.no_posts_yet') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">

{{-- Create Admin Modal --}}
<div id="create-admin-modal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <div class="modal-top">
            <h3><i class="fas fa-user-shield"></i> {{ __('admin.create_admin') }}</h3>
            <button class="modal-close-btn" onclick="hideCreateAdminModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.create-admin') }}">
            @csrf
            <div class="form-row">
                <label>{{ __('admin.full_name') }}</label>
                <input type="text" name="name" required minlength="1" maxlength="255" placeholder="{{ __('admin.full_name') }}">
            </div>
            <div class="form-row">
                <label>{{ __('admin.username') }}</label>
                <div style="position: relative;">
                    <input type="text" name="username" id="admin-username" required minlength="3" maxlength="50" autocomplete="username" placeholder="{{ __('admin.username') }}" pattern="[a-zA-Z0-9_\-]+" style="width: 100%;">
                    <div id="admin-username-status" style="font-size: 12px; margin-top: 4px; display: none;"></div>
                </div>
            </div>
            <div class="form-row">
                <label>{{ __('admin.email') }}</label>
                <input type="email" name="email" required placeholder="{{ __('admin.email') }}" autocomplete="email">
            </div>
            <div class="form-row">
                <label>{{ __('admin.password') }}</label>
                <input type="password" name="password" required minlength="8" autocomplete="current-password" placeholder="{{ __('admin.min_8_characters') }}">
            </div>
            <div class="form-buttons">
                <button type="button" class="btn-cancel" onclick="hideCreateAdminModal()">{{ __('admin.cancel') }}</button>
                <button type="submit" class="btn-submit">{{ __('admin.create') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateAdminModal() {
    document.getElementById('create-admin-modal').style.display = 'flex';
}

function hideCreateAdminModal() {
    document.getElementById('create-admin-modal').style.display = 'none';
}

document.getElementById('create-admin-modal').addEventListener('click', function(e) {
    if (e.target === this) hideCreateAdminModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') hideCreateAdminModal();
});

// Admin Username Check
function initAdminUsernameCheck() {
    const usernameInput = document.getElementById('admin-username');
    const statusDiv = document.getElementById('admin-username-status');
    const submitBtn = document.querySelector('#create-admin-modal .btn-submit');
    
    if (!usernameInput || !statusDiv) return;
    
    let debounceTimer;
    
    usernameInput.addEventListener('input', function() {
        const username = this.value.trim();
        
        clearTimeout(debounceTimer);
        statusDiv.style.display = 'none';
        submitBtn.disabled = false;
        
        if (username.length < 3) return;
        
        statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('messages.username_checking') }}';
        statusDiv.style.color = 'var(--text-muted)';
        statusDiv.style.display = 'block';
        
        debounceTimer = setTimeout(() => {
            fetch('/api/check-username?username=' + encodeURIComponent(username))
                .then(r => r.json())
                .then(data => {
                    if (data.available) {
                        statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> {{ __('messages.username_available') }}';
                        statusDiv.style.color = '#22c55e';
                        submitBtn.disabled = false;
                    } else {
                        statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> {{ __('messages.username_taken') }}';
                        statusDiv.style.color = '#ef4444';
                        submitBtn.disabled = true;
                    }
                })
                .catch(() => {
                    statusDiv.style.display = 'none';
                });
        }, 500);
    });
}

window.runOnPageLoad( initAdminUsernameCheck);
</script>
@endsection
