@push('styles')
    @vite('resources/css/communities-admin.css')
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
            <a href="{{ route('communities.admin.reports', $group->slug) }}" class="nav-link {{ request()->routeIs('communities.admin.reports') ? 'active' : '' }}">
                <i class="fas fa-flag"></i> {{ __('community_admin.reports') ?? 'Reports' }}
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
