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

<style>
    .admin-dashboard { max-width: 1100px; margin: 0 auto; }
    
    .admin-page-header { margin-bottom: 32px; display: flex; flex-direction: column; align-items: flex-start; }
    .admin-page-title { font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 6px; letter-spacing: -0.5px; }
    .admin-page-subtitle { color: var(--text-muted); font-size: 15px; font-weight: 500; }

    /* Stats Grid Refined */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
    
    .stat-card {
        background: var(--surface);
        padding: 24px;
        border-radius: 24px;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover { border-color: var(--admin-accent); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

    .stat-icon {
        width: 56px; height: 56px;
        background: var(--surface-hover);
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: var(--admin-accent);
        border: 1px solid var(--border);
    }

    .stat-card.highlight { background: var(--admin-accent-glow); border-color: var(--admin-accent-glow); }
    .stat-card.highlight .stat-icon { background: var(--admin-accent); color: white; }

    .stat-label { display: block; font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .stat-value { font-size: 24px; font-weight: 800; margin: 0; color: var(--text); }

    .main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

    .panel { background: var(--surface); border-radius: 24px; border: 1px solid var(--border); overflow: hidden; }
    .panel-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
    .panel-header h3 { margin: 0; font-size: 16px; font-weight: 800; color: var(--text); }

    .queue-item { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; text-decoration: none; border-bottom: 1px solid var(--border); transition: 0.2s; }
    .queue-item:last-child { border-bottom: none; }
    .queue-item:hover { background: var(--surface-hover); }
    .queue-info strong { display: block; font-size: 15px; color: var(--text); margin-bottom: 2px; }
    .queue-info span { font-size: 12px; color: var(--text-muted); }

    .queue-count { min-width: 32px; height: 32px; padding: 0 10px; background: var(--surface-hover); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; color: var(--text-muted); }
    .queue-count.alert { background: var(--admin-accent); color: white; }

    .action-grid { display: grid; grid-template-columns: repeat(2, 1fr); padding: 24px; gap: 16px; }
    .action-tile { 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        gap: 12px; 
        padding: 24px 16px; 
        background: var(--surface-hover); 
        border-radius: 20px; 
        text-decoration: none; 
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
    }
    .action-tile:hover { 
        background: var(--surface); 
        border-color: var(--admin-accent-glow);
        transform: translateY(-4px); 
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }
    .icon-wrap { 
        width: 52px; height: 52px; 
        background: var(--surface); 
        border-radius: 14px; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 20px; color: var(--admin-accent); 
        border: 1px solid var(--border);
        transition: 0.3s;
    }
    .action-tile:hover .icon-wrap {
        background: var(--admin-accent);
        color: white;
        border-color: var(--admin-accent);
    }
    .action-tile span { font-size: 14px; font-weight: 700; color: var(--text); }

    @media (max-width: 800px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .stat-card.highlight { grid-column: 1 / -1; }
        .main-grid { grid-template-columns: 1fr; }
        .admin-page-header { margin-bottom: 24px; text-align: center; align-items: center; }
        .admin-page-title { font-size: 24px; }
    }

    @media (max-width: 500px) {
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        .stat-card { padding: 16px; gap: 12px; flex-direction: column; text-align: center; }
        .stat-card.highlight { grid-column: 1 / -1; flex-direction: row; text-align: left; }
        .stat-icon { width: 44px; height: 44px; font-size: 18px; }
    }

</style>
        </div>
    </main>
</div>
@endsection
