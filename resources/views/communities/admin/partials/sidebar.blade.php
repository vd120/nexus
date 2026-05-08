@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
@endpush

@section('main_class', 'full-width')
@section('content_class', 'full-width')

{{-- Sidebar Overlay --}}


<div class="admin-sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

{{-- Sidebar --}}
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="{{ route('communities.show', $group->slug) }}" class="back-to-site">
            <i class="fas fa-chevron-left"></i>
            <span>{{ __('community_admin.back_to_community') }}</span>
        </a>
        <div class="sidebar-community-card">
            <img src="{{ $group->avatar_url }}" alt="">
            <div class="info">
                <span class="label">{{ __('community_admin.admin_panel') }}</span>
                <h3 class="name">{{ $group->name }}</h3>
            </div>
        </div>
        {{-- Mobile Close Button Inside Sidebar --}}
        <button class="mobile-close-sidebar" onclick="toggleSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <a href="{{ route('communities.admin.index', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.index') ? 'active' : '' }}">
                <i class="fas fa-home"></i> {{ __('community_admin.overview') }}
            </a>
        </div>

        <div class="nav-group">
            <span class="nav-title">{{ __('community_admin.moderation') }}</span>
            <a href="{{ route('communities.admin.members', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.members') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i> {{ __('community_admin.manage_members_nav') }}
            </a>
            <a href="{{ route('communities.admin.moderation.posts', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.moderation.posts') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> {{ __('community_admin.pending_posts') }}
                @if(isset($pendingPostsCount) && $pendingPostsCount > 0)
                    <span class="badge">{{ $pendingPostsCount }}</span>
                @endif
            </a>
            <a href="{{ route('communities.admin.moderation.members', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.moderation.members') ? 'active' : '' }}">
                <i class="fas fa-users"></i> {{ __('community_admin.join_requests') }}
                @if(isset($pendingMembersCount) && $pendingMembersCount > 0)
                    <span class="badge">{{ $pendingMembersCount }}</span>
                @endif
            </a>
        </div>

        <div class="nav-group">
            <span class="nav-title">{{ __('community_admin.community') }}</span>
            <a href="{{ route('communities.admin.settings', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.settings') ? 'active' : '' }}">
                <i class="fas fa-sliders-h"></i> {{ __('community_admin.settings') }}
            </a>
            <a href="{{ route('communities.admin.rules', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.rules') ? 'active' : '' }}">
                <i class="fas fa-gavel"></i> {{ __('community_admin.rules') }}
            </a>
            <a href="{{ route('communities.admin.topics', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.topics') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> {{ __('community_admin.topics') }}
            </a>
            <a href="{{ route('communities.admin.badges', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.badges') ? 'active' : '' }}">
                <i class="fas fa-award"></i> {{ __('community_admin.badges') }}
            </a>
        </div>
    </nav>
</aside>

<header class="admin-mobile-header">
    <div class="mobile-header-content">
        <div class="header-left">
            <span class="mobile-panel-label">{{ __('community_admin.admin_management') }}</span>
        </div>
        <button class="mobile-hamburger-btn" onclick="toggleSidebar()">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>
</header>

<style>
    /* CORE DESIGN TOKENS */
    :root {
        --admin-accent: #6366f1;
        --admin-accent-glow: rgba(99, 102, 241, 0.1);
        --admin-danger: #ef4444;
        --admin-success: #10b981;
    }

    .admin-wrapper { display: flex; min-height: 100vh; background: var(--bg); position: relative; }

    /* SIDEBAR */
    .admin-sidebar {
        width: 280px; background: var(--surface); border-right: 1px solid var(--border);
        position: sticky; top: 76px; height: calc(100vh - 76px); padding: 32px 16px;
        display: flex; flex-direction: column; z-index: 5000;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto; 
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }


    @media (max-width: 900px) {
        .admin-sidebar { top: 72px; height: calc(100vh - 72px); }
    }

    @media (max-width: 480px) {
        .admin-sidebar { top: 48px; height: calc(100vh - 48px); padding-bottom: 100px; }
    }


    .admin-sidebar::-webkit-scrollbar { display: none; }

    .sidebar-header { margin-bottom: 32px; flex-shrink: 0; position: relative; }
    .back-to-site { display: flex; align-items: center; gap: 10px; color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 700; margin-bottom: 24px; transition: 0.2s; padding: 4px 8px; border-radius: 8px; }
    .back-to-site:hover { background: var(--surface-hover); color: var(--text); }
    .sidebar-community-card { display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--surface-hover); border-radius: 16px; border: 1px solid var(--border); }
    .sidebar-community-card img { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; }
    .sidebar-community-card .label { font-size: 11px; text-transform: uppercase; font-weight: 800; color: var(--admin-accent); display: block; margin-bottom: 2px; }
    .sidebar-community-card .name { font-size: 15px; font-weight: 800; color: var(--text); margin: 0; }

    .mobile-close-sidebar {
        display: none; position: absolute; top: 10px; right: 10px;
        width: 32px; height: 32px; border-radius: 50%; background: var(--surface-hover);
        border: 1px solid var(--border); color: var(--text); cursor: pointer;
        align-items: center; justify-content: center; font-size: 12px;
    }

    .sidebar-nav { display: flex; flex-direction: column; gap: 32px; padding-bottom: 40px; }
    .nav-group { display: flex; flex-direction: column; gap: 4px; }
    .nav-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; margin-bottom: 8px; padding-left: 12px; }
    .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 14px; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); position: relative; }
    .nav-link i { font-size: 18px; color: var(--text-muted); width: 24px; text-align: center; transition: 0.2s; }
    .nav-link:hover { background: var(--surface-hover); color: var(--text); }
    .nav-link:hover i { color: var(--admin-accent); }
    .nav-link.active { background: var(--admin-accent-glow); color: var(--admin-accent); }
    .nav-link.active i { color: var(--admin-accent); }
    .nav-link.active::after {
        content: ''; position: absolute; left: -4px; top: 12px; bottom: 12px; width: 4px; background: var(--admin-accent); border-radius: 0 4px 4px 0;
    }

    .admin-mobile-header {
        display: none; position: sticky; top: 76px; left: 0; right: 0;
        background: var(--surface); border-bottom: 1px solid var(--border);
        padding: 12px 20px; z-index: 4000; backdrop-filter: blur(10px);
    }

    @media (max-width: 900px) {
        .admin-mobile-header { top: 72px; }
    }

    @media (max-width: 480px) {
        .admin-mobile-header { top: 48px; padding: 10px 16px; }
    }

    .mobile-header-content { display: flex; justify-content: space-between; align-items: center; }
    .mobile-panel-label { font-size: 14px; font-weight: 800; color: var(--admin-accent); text-transform: uppercase; letter-spacing: 0.5px; }

    .mobile-hamburger-btn {
        width: 40px; height: 40px; border-radius: 10px; background: var(--surface-hover);
        border: 1px solid var(--border); cursor: pointer; display: flex; flex-direction: column;
        justify-content: center; align-items: center; gap: 4px;
    }
    .hamburger-line { display: block; width: 18px; height: 2px; background: var(--text); border-radius: 2px; }

    /* CONTENT */
    .admin-main { flex: 1; position: relative; }
    .admin-content-inner { padding: 40px; }

    .admin-sidebar-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4);
        backdrop-filter: blur(6px); z-index: 4500; opacity: 0; transition: all 0.3s;
    }
    .admin-sidebar-overlay.open { display: block; opacity: 1; }

    body.admin-sidebar-open { overflow: hidden !important; }

    @media (max-width: 1024px) {
        .admin-wrapper { display: block; }
        .admin-sidebar { position: fixed; left: 0; transform: translateX(-100%); top: 0; height: 100dvh; width: 280px; }

        .admin-sidebar.open { transform: translateX(0); }
        .admin-mobile-header { display: block; width: 100%; }
        .admin-main { width: 100%; }
        .admin-content-inner { padding: 24px 20px; }
        .mobile-close-sidebar { display: flex; }
    }

</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        const isOpen = sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
        
        if (isOpen) {
            document.body.classList.add('admin-sidebar-open');
        } else {
            document.body.classList.remove('admin-sidebar-open');
        }
    }
</script>
