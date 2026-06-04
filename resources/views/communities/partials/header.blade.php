@push('styles')
    @vite('resources/css/communities.css')
@endpush

@section('content_class', 'wide-content')
@section('main_class', 'full-width')

@php
    $userMember = auth()->check() ? $group->members()->where('user_id', auth()->id())->first() : null;
@endphp

<script>
    window.COMMUNITY_ROLE = "{{ $userMember && $userMember->status === 'approved' ? $userMember->role : '' }}";
</script>

<div class="ch-hero">
    {{-- Banner --}}
    <div class="ch-banner">
        @if($group->cover_photo)
            <img src="{{ asset('storage/' . $group->cover_photo) }}" alt="" class="ch-banner-img">
        @else
            <div class="ch-banner-default">
                <div class="ch-banner-glow"></div>
                <div class="ch-banner-grid"></div>
            </div>
        @endif
        <div class="ch-banner-fade"></div>

        {{-- Back btn --}}
        <a href="javascript:history.back()" class="ch-back-btn" aria-label="Back">
            <i class="fas fa-arrow-left"></i>
        </a>

        {{-- Actions pinned top-right --}}
        <div class="ch-header-actions">
            @if(!$userMember)
                <button class="ch-btn-join" onclick="joinGroup('{{ $group->slug }}')">
                    <i class="fas fa-plus"></i> {{ __('messages.join') }}
                </button>
            @elseif($userMember->status === 'pending')
                <button class="ch-btn-pending" disabled>
                    <i class="fas fa-clock"></i> {{ __('messages.pending') }}
                </button>
            @else
                <a href="{{ route('communities.settings', $group->slug) }}" class="ch-btn-settings">
                    <i class="fas fa-cog"></i>
                </a>
            @endif
            @if($group->isAdmin(auth()->user()))
                <a href="{{ route('communities.admin.index', $group->slug) }}" class="ch-btn-admin" title="{{ __('messages.admin_tools') }}">
                    <i class="fas fa-shield-alt"></i>
                </a>
            @endif
        </div>
    </div>

    {{-- Info row --}}
    <div class="ch-info-wrap">
        <div class="ch-info-inner">
            <div class="ch-info-row">
                <div class="ch-avatar-wrap">
                    <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}" class="ch-avatar">
                </div>
                <div class="ch-text">
                    <h1 class="ch-name">{{ $group->name }}</h1>
                    <div class="ch-pills">
                        <span class="ch-pill">
                            <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe' : 'lock' }}"></i>
                            {{ __('messages.' . $group->privacy_level) }}
                        </span>
                        @if($group->category)
                            <span class="ch-pill ch-pill--muted">{{ $group->category }}</span>
                        @endif
                    </div>
                </div>
                {{-- Desktop join/settings (hidden on mobile — shown in banner) --}}
                <div class="ch-desktop-actions">
                    @if(!$userMember)
                        <button class="ch-btn-join" onclick="joinGroup('{{ $group->slug }}')">
                            <i class="fas fa-plus"></i> {{ __('messages.join') }}
                        </button>
                    @elseif($userMember->status === 'pending')
                        <button class="ch-btn-pending" disabled>
                            <i class="fas fa-clock"></i> {{ __('messages.pending') }}
                        </button>
                    @else
                        <a href="{{ route('communities.settings', $group->slug) }}" class="ch-btn-settings-full">
                            <i class="fas fa-cog"></i> {{ __('messages.settings') }}
                        </a>
                    @endif
                    @if($group->isAdmin(auth()->user()))
                        <a href="{{ route('communities.admin.index', $group->slug) }}" class="ch-btn-admin-full">
                            <i class="fas fa-shield-alt"></i> {{ __('messages.admin_tools') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Stat pills --}}
            <div class="ch-stats">
                <div class="ch-stat">
                    <i class="fas fa-users"></i>
                    <strong data-community-members-count="{{ $group->slug }}">{{ number_format($group->members_count) }}</strong>
                    <span>{{ __('messages.members_label') }}</span>
                </div>
                <div class="ch-stat">
                    <i class="fas fa-file-alt"></i>
                    <strong>{{ number_format($group->posts()->count()) }}</strong>
                    <span>{{ __('messages.posts') }}</span>
                </div>
                <div class="ch-stat">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>{{ $group->created_at->format('M Y') }}</strong>
                    <span>{{ __('messages.created') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab nav --}}
    <div class="ch-tabs-wrap">
        <div class="ch-tabs-inner">
            <nav class="ch-tabs">
                <a href="{{ route('communities.show', $group->slug) }}" class="ch-tab {{ request()->routeIs('communities.show') ? 'active' : '' }}">
                    <i class="fas fa-comment-alt"></i>
                    <span>{{ __('messages.discussion') }}</span>
                </a>
                <a href="{{ route('communities.about', $group->slug) }}" class="ch-tab {{ request()->routeIs('communities.about') ? 'active' : '' }}">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ __('messages.about') }}</span>
                </a>
                <a href="{{ route('communities.members', $group->slug) }}" class="ch-tab {{ request()->routeIs('communities.members') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>{{ __('messages.group_members') }}</span>
                </a>
                @if($userMember && $userMember->status === 'approved')
                    <a href="{{ route('communities.settings', $group->slug) }}" class="ch-tab {{ request()->routeIs('communities.settings') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h"></i>
                        <span>{{ __('messages.settings') }}</span>
                    </a>
                @endif
            </nav>
        </div>
    </div>
</div>
