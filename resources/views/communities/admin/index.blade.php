@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="admin-dashboard">
    <div class="admin-page-header">
        <h1 class="admin-page-title">{{ __('community_admin.community_overview') }}</h1>
        <p class="admin-page-subtitle">{{ __('community_admin.community_overview_subtitle') }}</p>
    </div>

    {{-- Essential Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <span class="stat-label">{{ __('community_admin.members') }}</span>
                <h2 class="stat-value">{{ number_format($group->members_count) }}</h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-pencil-alt"></i></div>
            <div class="stat-content">
                <span class="stat-label">{{ __('community_admin.total_posts') }}</span>
                <h2 class="stat-value">{{ number_format($group->posts_count) }}</h2>
            </div>
        </div>
        <div class="stat-card highlight">
            <div class="stat-icon"><i class="fas fa-bolt"></i></div>
            <div class="stat-content">
                <span class="stat-label">{{ __('community_admin.actions_required') }}</span>
                <h2 class="stat-value">{{ number_format($pendingMembersCount + $pendingPostsCount) }}</h2>
            </div>
        </div>
    </div>

    <div class="main-grid">
        {{-- Moderation Queue --}}
        <div class="panel">
            <div class="panel-header">
                <h3>{{ __('community_admin.moderation_queue') }}</h3>
            </div>
            <div class="panel-body">
                <a href="{{ route('communities.admin.moderation.posts', $group->slug) }}" class="queue-item">
                    <div class="queue-info">
                        <strong>{{ __('community_admin.pending_posts') }}</strong>
                        <span>{{ __('community_admin.review_submissions') }}</span>
                    </div>
                    <span class="queue-count {{ $pendingPostsCount > 0 ? 'alert' : '' }}">{{ $pendingPostsCount }}</span>
                </a>
                <a href="{{ route('communities.admin.moderation.members', $group->slug) }}" class="queue-item">
                    <div class="queue-info">
                        <strong>{{ __('community_admin.join_requests') }}</strong>
                        <span>{{ __('community_admin.approve_new_members') }}</span>
                    </div>
                    <span class="queue-count {{ $pendingMembersCount > 0 ? 'alert' : '' }}">{{ $pendingMembersCount }}</span>
                </a>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="panel">
            <div class="panel-header">
                <h3>{{ __('community_admin.quick_actions') }}</h3>
            </div>
            <div class="action-grid">
                <a href="{{ route('communities.admin.settings', $group->slug) }}" class="action-tile">
                    <div class="icon-wrap"><i class="fas fa-cog"></i></div>
                    <span>{{ __('community_admin.settings') }}</span>
                </a>
                <a href="{{ route('communities.admin.members', $group->slug) }}" class="action-tile">
                    <div class="icon-wrap"><i class="fas fa-users-cog"></i></div>
                    <span>{{ __('community_admin.members') }}</span>
                </a>
                <a href="{{ route('communities.admin.rules', $group->slug) }}" class="action-tile">
                    <div class="icon-wrap"><i class="fas fa-gavel"></i></div>
                    <span>{{ __('community_admin.rules') }}</span>
                </a>
                <a href="{{ route('communities.admin.topics', $group->slug) }}" class="action-tile">
                    <div class="icon-wrap"><i class="fas fa-tags"></i></div>
                    <span>{{ __('community_admin.topics') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
        </div>
    </main>
</div>
@endsection
