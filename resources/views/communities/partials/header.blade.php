@section('content_class', 'wide-content')
{{-- Community Hero Header --}}

<div class="community-hero-card">
    <div class="hero-banner">
        <a href="javascript:history.back()" class="back-btn-overlay">
            <i class="fas fa-arrow-left"></i>
        </a>
        @if($group->cover_photo)
            <img src="{{ asset('storage/' . $group->cover_photo) }}" alt="{{ $group->name }}">
        @else
            <div class="default-hero-banner">
                <div class="banner-pattern"></div>
            </div>
        @endif
        <div class="banner-overlay"></div>
    </div>
    
    <div class="hero-content">
        <div class="header-main-info">
            <div class="avatar-wrap">
                <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}">
            </div>
            <div class="text-info">
                <h1 class="community-title">{{ $group->name }}</h1>
                <div class="meta-row">
                    <span class="meta-pill">
                        <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe-americas' : 'lock' }}"></i>
                        {{ __('messages.' . $group->privacy_level) }}
                    </span>
                    <span class="meta-dot"></span>
                    <span class="member-count"><strong>{{ number_format($group->members_count) }}</strong> {{ __('messages.members_label') }}</span>
                </div>
            </div>
            <div class="header-actions">
                @php 
                    $userMember = auth()->check() ? $group->members()->where('user_id', auth()->id())->first() : null;
                @endphp

                @if(!$userMember)
                    <button class="btn-action-primary" onclick="joinGroup('{{ $group->slug }}')">
                        <i class="fas fa-plus"></i> {{ __('messages.join') }}
                    </button>
                @elseif($userMember->status === 'pending')
                    <button class="btn-action-secondary" disabled>
                        <i class="fas fa-clock"></i> {{ __('messages.pending') }}
                    </button>
                @else
                    <a href="{{ route('communities.settings', $group->slug) }}" class="btn-action-secondary">
                        <i class="fas fa-cog"></i> {{ __('messages.settings') }}
                    </a>
                @endif

                @if($group->isAdmin(auth()->user()))
                    <a href="{{ route('communities.admin.index', $group->slug) }}" class="btn-action-icon" title="{{ __('messages.admin_tools') }}">
                        <i class="fas fa-shield-alt"></i>
                    </a>
                @endif
            </div>
        </div>

        <nav class="community-tab-nav">
            <a href="{{ route('communities.show', $group->slug) }}" class="tab-item {{ request()->routeIs('communities.show') ? 'active' : '' }}">
                <i class="fas fa-comment-alt"></i>
                <span>{{ __('messages.discussion') }}</span>
            </a>
            <a href="{{ route('communities.about', $group->slug) }}" class="tab-item {{ request()->routeIs('communities.about') ? 'active' : '' }}">
                <i class="fas fa-info-circle"></i>
                <span>{{ __('messages.about') }}</span>
            </a>
            <a href="{{ route('communities.members', $group->slug) }}" class="tab-item {{ request()->routeIs('communities.members') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>{{ __('messages.group_members') }}</span>
            </a>
            @if($userMember && $userMember->status === 'approved')
                <a href="{{ route('communities.settings', $group->slug) }}" class="tab-item {{ request()->routeIs('communities.settings') ? 'active' : '' }}">
                    <i class="fas fa-sliders-h"></i>
                    <span>{{ __('messages.settings') }}</span>
                </a>
            @endif
        </nav>
    </div>
</div>

<style>
    :root {
        --community-accent: #6366f1;
        --community-accent-soft: rgba(99, 102, 241, 0.08);
        --glass-border: rgba(255, 255, 255, 0.05);
    }

    .community-hero-card {
        background: var(--surface);
        border-radius: 0 0 24px 24px;
        border: 1px solid var(--border);
        border-top: none;
        overflow: hidden;
        margin-bottom: 16px;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
        position: relative;
    }

    .hero-banner {
        height: 240px;
        position: relative;
        overflow: hidden;
    }

    .hero-banner img { width: 100%; height: 100%; object-fit: cover; }
    
    .default-hero-banner {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        position: relative;
        overflow: hidden;
    }

    .banner-pattern {
        position: absolute;
        inset: 0;
        background-image: radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.05) 1px, transparent 0);
        background-size: 24px 24px;
        opacity: 0.5;
    }

    .banner-overlay { 
        position: absolute; 
        inset: 0; 
        background: linear-gradient(to bottom, transparent 40%, rgba(0,0,0,0.8) 100%); 
    }

    .hero-content { padding: 0 20px; }

    .header-main-info {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: -36px;
        padding-bottom: 16px;
        position: relative;
        z-index: 5;
    }

    .avatar-wrap {
        width: 100px;
        height: 100px;
        border-radius: 22px;
        background: var(--surface);
        padding: 3px;
        box-shadow: 0 12px 32px rgba(0,0,0,0.25);
        flex-shrink: 0;
        border: 1px solid var(--glass-border);
    }

    .avatar-wrap img { width: 100%; height: 100%; border-radius: 19px; object-fit: cover; }

    .text-info { flex: 1; padding-top: 24px; }
    .community-title { font-size: 24px; font-weight: 800; color: var(--text); margin: 0 0 4px; letter-spacing: -0.5px; }
    
    .meta-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .meta-pill { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--community-accent); background: var(--community-accent-soft); padding: 3px 8px; border-radius: 6px; display: flex; align-items: center; gap: 4px; border: 1px solid rgba(99, 102, 241, 0.15); }
    .meta-dot { width: 3px; height: 3px; border-radius: 50%; background: var(--border); }
    .member-count { font-size: 13px; color: var(--text-muted); font-weight: 600; opacity: 0.8; }

    .header-actions { display: flex; align-items: center; gap: 8px; padding-top: 24px; }

    .btn-action-primary { background: var(--community-accent); color: white; border: none; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px; }
    .btn-action-secondary { 
        background: var(--surface-hover); 
        color: var(--text); 
        border: 1px solid var(--border); 
        padding: 6px 12px; 
        border-radius: 8px; 
        font-weight: 700; 
        font-size: 12px; 
        cursor: pointer; 
        text-decoration: none; 
        display: flex; 
        align-items: center; 
        gap: 6px;
        transition: 0.2s;
    }
    .btn-action-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--surface-hover); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text); text-decoration: none; transition: 0.2s; font-size: 12px; }
    
    .btn-action-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); }
    .btn-action-secondary:hover { background: var(--border); transform: translateY(-1px); }
    .btn-action-icon:hover { background: var(--border); transform: translateY(-1px); }

    .community-tab-nav { display: flex; gap: 4px; border-top: 1px solid var(--border); padding: 4px 0; }
    .tab-item { display: flex; align-items: center; gap: 6px; padding: 10px 16px; font-size: 13px; font-weight: 700; color: var(--text-muted); text-decoration: none; border-radius: 8px; transition: 0.2s; }
    .tab-item i { font-size: 14px; opacity: 0.7; }
    .tab-item:hover { background: var(--surface-hover); color: var(--text); }
    .tab-item.active { color: var(--community-accent); background: var(--community-accent-soft); }
    .tab-item.active i { opacity: 1; }

    @media (max-width: 768px) {
        .community-hero-card { border-radius: 0; margin-bottom: 12px; border-left: none; border-right: none; }
        .hero-banner { height: 160px; }
        
        .back-btn-overlay {
            position: absolute; top: 16px; left: 16px; z-index: 100;
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff; display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: 0.2s;
        }
        .back-btn-overlay:active { transform: scale(0.9); }

        .header-main-info { 
            flex-direction: row !important; 
            align-items: flex-end !important; 
            text-align: left !important; 
            margin-top: -45px !important; 
            padding: 0 16px 16px !important; 
            gap: 12px !important;
        }

        .avatar-wrap { 
            width: 86px !important; 
            height: 86px !important; 
            border-radius: 18px !important;
            margin: 0 !important;
        }

        .text-info { padding: 0 !important; flex: 1; }
        .community-title { font-size: 20px !important; margin-bottom: 2px !important; }
        
        .meta-row { justify-content: flex-start !important; gap: 4px !important; }
        .meta-pill { font-size: 9px !important; padding: 2px 6px !important; }
        .member-count { font-size: 12px !important; }

        .header-actions { 
            position: absolute !important;
            top: 16px !important;
            right: 16px !important;
            padding: 0 !important;
            margin: 0 !important;
            flex-direction: row !important;
            z-index: 100 !important;
            gap: 6px !important;
        }
        
        .header-actions .btn-action-primary,
        .header-actions .btn-action-secondary,
        .header-actions .btn-action-icon { 
            width: 40px !important;
            height: 40px !important;
            padding: 0 !important;
            border-radius: 12px !important;
            justify-content: center !important;
            background: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            font-size: 0 !important;
        }

        .header-actions .btn-action-primary i,
        .header-actions .btn-action-secondary i,
        .header-actions .btn-action-icon i {
            font-size: 18px !important;
            margin: 0 !important;
        }

        .community-tab-nav { 
            overflow: hidden;
            border-top: 1px solid var(--border);
            padding: 0;
            display: flex;
            background: var(--surface);
        }

        .community-tab-nav::-webkit-scrollbar { display: none; }
        
        .tab-item { 
            flex: 1;
            padding: 16px 0; 
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            border-bottom: 3px solid transparent;
            color: var(--text-muted);
            transition: 0.2s;
        }
        .tab-item i { font-size: 18px !important; }
        .tab-item.active { 
            background: none !important; 
            color: var(--community-accent) !important; 
            border-bottom-color: var(--community-accent); 
        }
        .tab-item span { display: none !important; }
    }
</style>

@auth
    {{-- User Settings Modal removed in favor of dedicated page --}}
@endauth
