@extends('layouts.app')

@section('content')
<div class="community-container">
    {{-- Community Hero Header --}}
    <div class="community-hero-wrapper">
        <div class="community-cover-image">
            <img src="{{ $group->cover_photo ? asset('storage/' . $group->cover_photo) : 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070' }}" alt="{{ $group->name }}">
            <div class="cover-overlay"></div>
        </div>
        
        <div class="community-header-main">
            <div class="community-info-section">
                <div class="community-avatar-lg">
                    <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}">
                </div>
                <div class="community-details">
                    <h1 class="community-name">{{ $group->name }}</h1>
                    <div class="community-stats-meta">
                        <span class="meta-item">
                            <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe-americas' : 'lock' }}"></i>
                            {{ ucfirst($group->privacy_level) }} Group
                        </span>
                        <span class="meta-separator">•</span>
                        <span class="meta-item">
                            <strong>{{ number_format($group->members_count) }}</strong> members
                        </span>
                    </div>
                </div>
                <div class="community-actions">
                    @php 
                        $userMember = $group->members()->where('user_id', auth()->id())->first();
                    @endphp

                    @if(!$userMember)
                        <button class="btn-nexus-join" onclick="joinGroup('{{ $group->slug }}')">
                            <i class="fas fa-user-plus"></i> Join Community
                        </button>
                    @elseif($userMember->status === 'pending')
                        <button class="btn-nexus-pending" disabled>
                            <i class="fas fa-clock"></i> Request Pending
                        </button>
                    @else
                        <div class="joined-dropdown">
                            <button class="btn-nexus-joined">
                                <i class="fas fa-check"></i> Joined
                            </button>
                            {{-- Dropdown for leave/preferences can be added here --}}
                        </div>
                    @endif

                    @if($group->isAdmin(auth()->user()))
                        <a href="{{ route('communities.admin.index', $group->slug) }}" class="btn-nexus-admin">
                            <i class="fas fa-cog"></i> Admin Tools
                        </a>
                    @endif
                </div>
            </div>

            {{-- Community Navigation --}}
            <div class="community-nav-bar">
                <a href="{{ route('communities.show', $group->slug) }}" class="nav-item {{ request()->routeIs('communities.show') ? 'active' : '' }}">
                    Discussion
                </a>
                <a href="{{ route('communities.about', $group->slug) }}" class="nav-item {{ request()->routeIs('communities.about') ? 'active' : '' }}">
                    About
                </a>
                <a href="{{ route('communities.members', $group->slug) }}" class="nav-item {{ request()->routeIs('communities.members') ? 'active' : '' }}">
                    Members
                </a>
                @if($group->topics->count() > 0)
                <a href="#" class="nav-item">
                    Topics
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Page Content --}}
    <div class="community-content-body">
        @yield('group-content')
    </div>
</div>

<style>
    .community-container {
        max-width: 1100px;
        margin: 0 auto;
        padding-bottom: 50px;
    }

    .community-hero-wrapper {
        background: var(--surface);
        border-radius: 0 0 24px 24px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        border: 1px solid var(--border);
        border-top: none;
    }

    .community-cover-image {
        height: 350px;
        width: 100%;
        position: relative;
        overflow: hidden;
    }

    .community-cover-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cover-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 150px;
        background: linear-gradient(to top, rgba(0,0,0,0.6), transparent);
    }

    .community-header-main {
        padding: 0 32px;
        position: relative;
    }

    .community-info-section {
        display: flex;
        align-items: flex-end;
        gap: 24px;
        margin-top: -60px;
        padding-bottom: 24px;
        border-bottom: 1px solid var(--border);
        position: relative;
        z-index: 10;
    }

    .community-avatar-lg {
        width: 168px;
        height: 168px;
        border-radius: 24px;
        background: var(--surface);
        padding: 4px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .community-avatar-lg img {
        width: 100%;
        height: 100%;
        border-radius: 20px;
        object-fit: cover;
    }

    .community-details {
        flex: 1;
        padding-bottom: 10px;
    }

    .community-name {
        font-size: 32px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }

    .community-stats-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        font-size: 15px;
        font-weight: 600;
    }

    .meta-separator {
        opacity: 0.5;
    }

    .community-actions {
        display: flex;
        gap: 12px;
        padding-bottom: 15px;
    }

    .btn-nexus-join {
        background: var(--primary);
        color: white;
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-nexus-join:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.4);
    }

    .btn-nexus-joined {
        background: var(--surface-hover);
        color: var(--text);
        padding: 10px 24px;
        border-radius: 12px;
        font-weight: 700;
        border: 1px solid var(--border);
        cursor: pointer;
    }

    .btn-nexus-admin {
        background: var(--surface-hover);
        color: var(--text);
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 700;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .community-nav-bar {
        display: flex;
        gap: 8px;
        padding: 12px 0;
    }

    .nav-item {
        padding: 14px 20px;
        font-weight: 700;
        color: var(--text-muted);
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.2s;
        position: relative;
    }

    .nav-item:hover {
        background: var(--surface-hover);
        color: var(--text);
    }

    .nav-item.active {
        color: var(--primary);
    }

    .nav-item.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 20px;
        right: 20px;
        height: 4px;
        background: var(--primary);
        border-radius: 10px 10px 0 0;
    }

    @media (max-width: 768px) {
        .community-hero-wrapper {
            border-radius: 0;
        }
        .community-cover-image {
            height: 180px;
        }
        .community-info-section {
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-top: -70px;
            gap: 16px;
            padding: 0 16px 20px;
        }
        .community-avatar-lg {
            width: 120px;
            height: 120px;
            border-radius: 20px;
        }
        .community-name {
            font-size: 24px;
        }
        .community-stats-meta {
            justify-content: center;
            font-size: 13px;
        }
        .community-actions {
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }
        .community-nav-bar {
            overflow-x: auto;
            padding: 8px 16px;
            gap: 4px;
            -webkit-overflow-scrolling: touch;
        }
        .nav-item {
            padding: 10px 16px;
            font-size: 14px;
            white-space: nowrap;
        }
        .nav-item.active::after {
            left: 10px;
            right: 10px;
        }
    }
</style>
@endsection
