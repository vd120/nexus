@push('styles')
    @vite('resources/css/communities.css')
@endpush

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
                    <span class="member-count" data-community-members-count="{{ $group->slug }}"><strong>{{ number_format($group->members_count) }}</strong> {{ __('messages.members_label') }}</span>
                </div>
            </div>
            <div class="header-actions">
                @php 
                    $userMember = auth()->check() ? $group->members()->where('user_id', auth()->id())->first() : null;
                @endphp
                <script>
                    window.COMMUNITY_ROLE = "{{ $userMember && $userMember->status === 'approved' ? $userMember->role : '' }}";
                </script>

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


@auth
    {{-- User Settings Modal removed in favor of dedicated page --}}
@endauth
