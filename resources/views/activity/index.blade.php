@extends('layouts.app')

@section('title', __('activity.activity_logs'))

@section('content')
<div class="activity-container">
    <div class="activity-header">
        <div class="activity-header-title">
            <a href="{{ route('profile') }}" class="back-link" title="{{ __('messages.back') }}">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1><i class="fas fa-history"></i> {{ __('activity.activity_logs') }}</h1>
        </div>
        <p class="activity-description">{{ __('activity.recent_activity') }}</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="activity-stats">
        <div class="stat-card">
            <div class="stat-icon text-primary">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $totalLogins }}</div>
                <div class="stat-label">{{ __('activity.total_logins') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon text-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $failedLogins }}</div>
                <div class="stat-label">{{ __('activity.failed_login') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon text-success">
                <i class="fas fa-laptop"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $activeSessions }}</div>
                <div class="stat-label">{{ __('activity.active_sessions') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon text-info">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $days }}</div>
                <div class="stat-label">{{ __('activity.days') }}</div>
            </div>
        </div>
    </div>

    {{-- Active Sessions Section --}}
    @if($activeSessionsList->count() > 0)
    <div class="active-sessions-section">
        <div class="active-sessions-header">
            <h3><i class="fas fa-laptop"></i> {{ __('activity.active_sessions') }}</h3>
            <a href="{{ route('activity.index') }}" class="btn btn-sm btn-secondary" title="{{ __('activity.refresh') }}">
                <i class="fas fa-sync-alt"></i> {{ __('activity.refresh') }}
            </a>
        </div>
        
        <div class="sessions-grid">
            @foreach($activeSessionsList as $session)
            <div class="session-card {{ $session['is_current'] ? 'current-session' : '' }}">
                <div class="session-header">
                    <div class="session-device">
                        <i class="fas {{ $session['device_type'] === 'mobile' ? 'fa-mobile-alt' : ($session['device_type'] === 'tablet' ? 'fa-tablet-alt' : 'fa-desktop') }}"></i>
                        <span>{{ ucfirst($session['device_type']) }}</span>
                    </div>
                    @if($session['is_current'])
                    <span class="current-badge">
                        <i class="fas fa-check-circle"></i> {{ __('activity.current_session') }}
                    </span>
                    @endif
                </div>
                
                <div class="session-details">
                    <div class="session-detail">
                        <i class="fas fa-globe"></i>
                        <span>{{ $session['browser'] }}</span>
                    </div>
                    <div class="session-detail">
                        <i class="fas fa-desktop"></i>
                        <span>{{ $session['os'] }}</span>
                    </div>
                    <div class="session-detail">
                        <i class="fas fa-network-wired"></i>
                        <span>{{ $session['ip_address'] }}</span>
                    </div>
                    @if($session['city'] || $session['country'])
                    <div class="session-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>{{ $session['city'] }}{{ $session['city'] && $session['country'] ? ', ' : '' }}{{ $session['country'] }}</span>
                    </div>
                    @endif
                </div>
                
                <div class="session-footer">
                    <div class="session-footer-content">
                        <span class="session-time">
                            <i class="fas fa-clock"></i>
                            {{ __('activity.last_active') }}: {{ $session['last_active'] }}
                        </span>
                        @if(!$session['is_current'])
                        <form method="POST" action="{{ route('activity.terminate-session', $session['id']) }}?t={{ time() }}" class="terminate-session-form" onsubmit="return confirm('{{ __('activity.terminate_session_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-terminate" title="{{ __('activity.terminate_session') }}">
                                <i class="fas fa-sign-out-alt"></i> {{ __('activity.terminate') }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="activity-filters">
        <div class="filters-left">
            <form method="GET" action="{{ route('activity.index') }}" class="filter-form">
                <div class="filter-group">
                    <label for="action">{{ __('activity.filter_by') }}</label>
                    <select name="action" id="action" onchange="this.form.submit()">
                        @foreach($actions as $value => $label)
                            <option value="{{ $value }}" {{ $action === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="days">{{ __('activity.period') }}</label>
                    <select name="days" id="days" onchange="this.form.submit()">
                        <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 {{ __('activity.days') }}</option>
                        <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 {{ __('activity.days') }}</option>
                        <option value="90" {{ $days === 90 ? 'selected' : '' }}>90 {{ __('activity.days') }}</option>
                        <option value="180" {{ $days === 180 ? 'selected' : '' }}>180 {{ __('activity.days') }}</option>
                        <option value="365" {{ $days === 365 ? 'selected' : '' }}>365 {{ __('activity.days') }}</option>
                    </select>
                </div>
            </form>

            <div class="view-toggle">
                <button type="button" class="btn btn-secondary" onclick="toggleView('list')" id="list-view-btn">
                    <i class="fas fa-list"></i> {{ __('activity.list_view') }}
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleView('timeline')" id="timeline-view-btn">
                    <i class="fas fa-stream"></i> {{ __('activity.timeline_view') }}
                </button>
            </div>
        </div>

        <div class="filters-right">
            <a href="{{ route('activity.export', ['days' => $days, 'action' => $action]) }}" class="btn btn-success">
                <i class="fas fa-download"></i> {{ __('activity.export_logs') }}
            </a>

            <form method="POST" action="{{ route('activity.terminate-all-sessions') }}" class="terminate-form" onsubmit="return confirm('{{ __('activity.terminate_all_confirm') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-sign-out-alt"></i> {{ __('activity.terminate_all_sessions') }}
                </button>
            </form>

            <form method="POST" action="{{ route('activity.clear') }}" class="clear-form" onsubmit="return confirm('{{ __('activity.clear_logs_confirm') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> {{ __('activity.clear_old_logs') }}
            </button>
        </form>
    </div>

    {{-- Activity List --}}
    <div class="activity-list">
        @forelse($activities as $activity)
            <div class="activity-item {{ $activity->action_color }}">
                <div class="activity-icon">
                    <i class="{{ $activity->action_icon }}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-header-row">
                        <span class="activity-action">{{ $activity->action_name }}</span>
                        <span class="activity-time" title="{{ $activity->logged_at->format('Y-m-d H:i:s') }}">
                            <i class="fas fa-clock"></i> {{ $activity->logged_at->timezone(config('app.timezone'))->format('M d, Y h:i a') }}
                        </span>
                    </div>
                    <div class="activity-details">
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.device_type') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">{{ $activity->device_type ?? __('activity.unknown_device') }}</span>
                            <i class="{{ $activity->device_icon }}"></i>
                        </span>
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.browser') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">{{ $activity->browser }}</span>
                            <i class="fas fa-globe"></i>
                        </span>
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.operating_system') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">{{ $activity->os }}</span>
                            <i class="fas fa-desktop"></i>
                        </span>
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.ip_address') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">{{ $activity->ip_address }}</span>
                            <i class="fas fa-network-wired"></i>
                        </span>
                        @if($activity->isp)
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.isp') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">{{ $activity->isp }}</span>
                            <i class="fas fa-wifi"></i>
                        </span>
                        @endif
                        @if($activity->city || $activity->region || $activity->country)
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.location') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">
                                {{ $activity->city ?? '' }}{{ $activity->city && $activity->region ? ', ' : '' }}{{ $activity->region ?? '' }}{{ ($activity->city || $activity->region) && $activity->country ? ', ' : '' }}{{ $activity->country ?? '' }}
                            </span>
                            <i class="fas fa-map-marker-alt"></i>
                        </span>
                        @endif
                        @if($activity->latitude && $activity->longitude)
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.coordinates') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">
                                <a href="https://www.google.com/maps?q={{ $activity->latitude }},{{ $activity->longitude }}" target="_blank" rel="noopener noreferrer" style="color: var(--primary);">
                                    {{ number_format($activity->latitude, 4) }}, {{ number_format($activity->longitude, 4) }}
                                </a>
                            </span>
                            <i class="fas fa-map"></i>
                        </span>
                        @endif
                        @if($activity->timezone)
                        <span class="activity-detail">
                            <span class="detail-label">{{ __('activity.timezone') }}</span>
                            <span class="detail-separator">:</span>
                            <span class="detail-value">
                                {{ $activity->timezone }}
                                @if($activity->logged_at)
                                    (<span style="color: var(--primary);">{{ $activity->logged_at->timezone(str_replace('_', '/', $activity->timezone))->format('h:i a') }}</span>)
                                @endif
                            </span>
                            <i class="fas fa-clock"></i>
                        </span>
                        @endif
                    </div>
                </div>
                <div class="activity-badge">
                    @if($activity->action === 'login')
                        <span class="badge badge-success">{{ __('activity.login') }}</span>
                        @if($activity->is_suspicious)
                            <span class="badge badge-suspicious" title="{{ __('activity.suspicious_login_info') }}">
                                <i class="fas fa-exclamation-circle"></i> {{ __('activity.suspicious_login') }}
                            </span>
                        @endif
                    @elseif($activity->action === 'logout')
                        <span class="badge badge-secondary">{{ __('activity.logout') }}</span>
                    @elseif($activity->action === 'failed_login')
                        <span class="badge badge-danger">{{ __('activity.failed_login') }}</span>
                    @endif
                </div>
                <div class="activity-actions">
                    <form method="POST" action="{{ route('activity.log.delete', $activity->id) }}" class="delete-log-form" onsubmit="return confirm('{{ __('activity.delete_log_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-delete-log" title="{{ __('activity.delete_log_confirm') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h3>{{ __('activity.no_activities') }}</h3>
                <p>{{ __('activity.no_activities_message') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Timeline View (hidden by default) --}}
    <div class="timeline-view" style="display: none;">
        <div class="timeline-container">
            @forelse($activities as $activity)
                <div class="timeline-item {{ $activity->action_color }}">
                    <div class="timeline-dot">
                        <i class="{{ $activity->action_icon }}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <span class="timeline-action">{{ $activity->action_name }}</span>
                            <span class="timeline-time">
                                <i class="fas fa-clock"></i> {{ $activity->logged_at->timezone(config('app.timezone'))->format('M d, Y h:i a') }}
                            </span>
                        </div>
                        <div class="timeline-details">
                            <span class="timeline-detail">
                                <i class="{{ $activity->device_icon }}"></i> {{ $activity->device_type }} • {{ $activity->browser }}
                            </span>
                            <span class="timeline-detail">
                                <i class="fas fa-network-wired"></i> {{ $activity->ip_address }}
                            </span>
                            @if($activity->city || $activity->country)
                                <span class="timeline-detail">
                                    <i class="fas fa-map-marker-alt"></i> {{ $activity->city }}{{ $activity->city && $activity->country ? ', ' : '' }}{{ $activity->country }}
                                </span>
                            @endif
                        </div>
                        @if($activity->is_suspicious)
                            <div class="timeline-suspicious">
                                <i class="fas fa-exclamation-triangle"></i> {{ __('activity.suspicious_login') }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>{{ __('activity.no_activities') }}</h3>
                    <p>{{ __('activity.no_activities_message') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($activities->hasPages())
        <div class="activity-pagination">
            {{ $activities->links() }}
        </div>
    @endif

    {{-- Security Tips --}}
    <div class="security-tips">
        <h3><i class="fas fa-shield-alt"></i> {{ __('activity.security_tips') }}</h3>
        <ul>
            <li><i class="fas fa-check"></i> {{ __('activity.security_tip_1') }}</li>
            <li><i class="fas fa-check"></i> {{ __('activity.security_tip_2') }}</li>
            <li><i class="fas fa-check"></i> {{ __('activity.security_tip_3') }}</li>
            <li><i class="fas fa-check"></i> {{ __('activity.security_tip_4') }}</li>
            <li><i class="fas fa-check"></i> {{ __('activity.security_tip_5') }}</li>
        </ul>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/activity.css') }}">
@endpush

<script>
function toggleView(view) {
    const listView = document.querySelector('.activity-list');
    const timelineView = document.querySelector('.timeline-view');
    const listBtn = document.getElementById('list-view-btn');
    const timelineBtn = document.getElementById('timeline-view-btn');

    if (view === 'timeline') {
        listView.style.display = 'none';
        timelineView.style.display = 'block';
        timelineBtn.classList.add('btn-primary');
        timelineBtn.classList.remove('btn-secondary');
        listBtn.classList.remove('btn-primary');
        listBtn.classList.add('btn-secondary');
    } else {
        listView.style.display = 'flex';
        timelineView.style.display = 'none';
        listBtn.classList.add('btn-primary');
        listBtn.classList.remove('btn-secondary');
        timelineBtn.classList.remove('btn-primary');
        timelineBtn.classList.add('btn-secondary');
    }

    // Save preference
    localStorage.setItem('activityView', view);
}

// Load saved preference on page load
window.runOnPageLoad( function() {
    const savedView = localStorage.getItem('activityView') || 'list';
    toggleView(savedView);
});
</script>
@endsection
