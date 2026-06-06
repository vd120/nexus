@extends('layouts.app')

@section('main_class', 'full-width')
@section('content_class', 'full-width')

@push('styles')
    @vite('resources/css/communities.css')
@endpush

@section('content')

@php
    $userMember = auth()->check() ? $group->members()->where('user_id', auth()->id())->first() : null;
@endphp

<script>
    window.COMMUNITY_ROLE = "{{ $userMember && $userMember->status === 'approved' ? $userMember->role : '' }}";
</script>

<div class="ch-layout">

    {{-- ══════════════════════════════════════════
         LEFT SIDEBAR — community navigation
    ══════════════════════════════════════════ --}}
    <aside class="ch-sidebar-left" id="chSidebarLeft">
        <div class="csl-inner">

            {{-- Collapse toggle --}}
            <button type="button" class="csl-toggle" id="cslToggle"
                    aria-expanded="true" aria-label="{{ __('messages.collapse') }}">
                <i class="fas fa-chevron-left csl-toggle-icon"></i>
            </button>

            {{-- Community identity --}}
            <div class="csl-identity">
                <div class="csl-comm-avatar">
                    <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}">
                </div>
                <div class="csl-comm-info">
                    <div class="csl-comm-name">{{ $group->name }}</div>
                    <div class="csl-comm-meta">
                        <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe' : 'lock' }}" style="font-size:10px;margin-right:3px;"></i>
                        {{ ucfirst($group->privacy_level) }}
                    </div>
                </div>
            </div>

            {{-- Stats grid --}}
            <div class="csl-stats">
                <div class="csl-stat">
                    <strong data-community-members-count="{{ $group->slug }}">{{ number_format($group->members_count) }}</strong>
                    <span>{{ __('messages.members_label') }}</span>
                </div>
                <div class="csl-stat">
                    <strong>{{ number_format($group->posts()->count()) }}</strong>
                    <span>{{ __('messages.posts') }}</span>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="csl-nav">
                <a href="{{ route('communities.show', $group->slug) }}"
                   class="csl-link {{ request()->routeIs('communities.show') ? 'active' : '' }}"
                   data-label="{{ __('messages.discussion') }}">
                    <i class="fas fa-comment-alt"></i>
                    <span>{{ __('messages.discussion') }}</span>
                </a>
                <a href="{{ route('communities.about', $group->slug) }}"
                   class="csl-link {{ request()->routeIs('communities.about') ? 'active' : '' }}"
                   data-label="{{ __('messages.about') }}">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ __('messages.about') }}</span>
                </a>
                @if($group->privacy_level === 'public' || $userMember)
                <a href="{{ route('communities.members', $group->slug) }}"
                   class="csl-link {{ request()->routeIs('communities.members') ? 'active' : '' }}"
                   data-label="{{ __('messages.group_members') }}">
                    <i class="fas fa-users"></i>
                    <span>{{ __('messages.group_members') }}</span>
                </a>
                @endif
                @if($userMember && $userMember->status === 'approved')
                <a href="{{ route('communities.settings', $group->slug) }}"
                   class="csl-link {{ request()->routeIs('communities.settings') ? 'active' : '' }}"
                   data-label="{{ __('messages.settings') }}">
                    <i class="fas fa-sliders-h"></i>
                    <span>{{ __('messages.settings') }}</span>
                </a>
                @endif
                @if($group->isAdmin(auth()->user()))
                <a href="{{ route('communities.admin.index', $group->slug) }}"
                   class="csl-link csl-link--admin"
                   data-label="{{ __('messages.admin_tools') }}">
                    <i class="fas fa-shield-alt"></i>
                    <span>{{ __('messages.admin_tools') }}</span>
                </a>
                @endif
            </nav>

            {{-- Rules --}}
            @if($group->rules->count() > 0)
            <div class="csl-section">
                <div class="csl-section-label">{{ __('messages.rules') }}</div>
                <div class="csl-rules">
                    @foreach($group->rules->take(5) as $i => $rule)
                    <div class="csl-rule">
                        <span class="csl-rule-num">{{ $i + 1 }}</span>
                        <span class="csl-rule-text">{{ $rule->title }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </aside>

    {{-- ══════════════════════════════════════════
         CENTER — hero + feed
    ══════════════════════════════════════════ --}}
    <main class="ch-center">

        {{-- Hero card: banner + avatar + tabs --}}
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

                <a href="javascript:history.back()" class="ch-back-btn" aria-label="{{ __('messages.back') }}">
                    <i class="fas fa-arrow-left"></i>
                </a>

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
                        <a href="{{ route('communities.admin.index', $group->slug) }}" class="ch-btn-admin">
                            <i class="fas fa-shield-alt"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Info bar (avatar overlapping banner) --}}
            <div class="ch-info-bar">
                <div class="ch-avatar-wrap">
                    <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}" class="ch-avatar">
                </div>
                <div class="ch-text">
                    <h1 class="ch-name">{{ $group->name }}</h1>
                    <div class="ch-pills">
                        <span class="ch-pill">
                            <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe' : 'lock' }}"></i>
                            {{ ucfirst($group->privacy_level) }}
                        </span>
                        <span class="ch-pill ch-pill--muted">
                            <i class="fas fa-users"></i>
                            <span data-community-members-count="{{ $group->slug }}">{{ number_format($group->members_count) }}</span>
                        </span>
                        @if($group->created_at)
                        <span class="ch-pill ch-pill--muted">
                            <i class="fas fa-calendar-alt"></i>
                            {{ $group->created_at->format('M Y') }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab bar --}}
            <nav class="ch-tabs">
                <a href="{{ route('communities.show', $group->slug) }}"
                   class="ch-tab {{ request()->routeIs('communities.show') ? 'active' : '' }}">
                    <i class="fas fa-comment-alt"></i><span>{{ __('messages.discussion') }}</span>
                </a>
                <a href="{{ route('communities.about', $group->slug) }}"
                   class="ch-tab {{ request()->routeIs('communities.about') ? 'active' : '' }}">
                    <i class="fas fa-info-circle"></i><span>{{ __('messages.about') }}</span>
                </a>
                @if($group->privacy_level === 'public' || $userMember)
                <a href="{{ route('communities.members', $group->slug) }}"
                   class="ch-tab {{ request()->routeIs('communities.members') ? 'active' : '' }}">
                    <i class="fas fa-users"></i><span>{{ __('messages.group_members') }}</span>
                </a>
                @endif
                @if($userMember && $userMember->status === 'approved')
                <a href="{{ route('communities.settings', $group->slug) }}"
                   class="ch-tab {{ request()->routeIs('communities.settings') ? 'active' : '' }}">
                    <i class="fas fa-sliders-h"></i><span>{{ __('messages.settings') }}</span>
                </a>
                @endif
            </nav>
        </div>{{-- /ch-hero --}}

        {{-- Feed content --}}
        <div class="ch-feed">

            {{-- Post composer or join promo --}}
            @if($group->canPost(auth()->user()))
                @include('communities.partials.post-composer', ['group' => $group])
            @elseif(!$group->isMember(auth()->user()))
                <div class="ch-join-promo">
                    <div class="ch-promo-icon"><i class="fas fa-users"></i></div>
                    <h3>{{ __('messages.join_to_participate') }}</h3>
                    <p>{{ __('messages.join_promo_desc') }}</p>
                    <button class="ch-btn-join" onclick="joinGroup('{{ $group->slug }}')">
                        {{ __('messages.join_community_btn') }}
                    </button>
                </div>
            @endif

            {{-- Posts --}}
            <div id="posts-feed" class="feed-posts-list" data-group-id="{{ $group->id }}">
                @forelse($posts as $post)
                    @include('partials.post', ['post' => $post, 'group' => $group, 'hideGroupContext' => true])
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon-wrap"><i class="fas fa-stream"></i></div>
                        <h3>{{ __('messages.no_posts_title') }}</h3>
                        <p>{{ __('messages.no_posts_desc') }}</p>
                    </div>
                @endforelse
                <div class="pagination-wrap">{{ $posts->links() }}</div>
            </div>

        </div>{{-- /ch-feed --}}
    </main>{{-- /ch-center --}}

    {{-- ══════════════════════════════════════════
         RIGHT SIDEBAR — community info & members
    ══════════════════════════════════════════ --}}
    <aside class="ch-sidebar-right" id="chSidebarRight">

        {{-- Header --}}
        <div class="csr-header">
            <span class="csr-header-title">
                <span class="csr-header-dot"></span>
                {{ __('messages.community_info') }}
            </span>
            <button type="button" class="csr-toggle" id="csrToggle"
                    aria-expanded="true" aria-label="{{ __('messages.collapse') }}">
                <i class="fas fa-chevron-right csr-toggle-icon"></i>
            </button>
        </div>

        {{-- Collapsed icon rail --}}
        <div class="csr-rail" aria-hidden="true">
            <button class="csr-rail-btn" title="{{ __('messages.members_label') }}"><i class="fas fa-users"></i></button>
            <button class="csr-rail-btn" title="{{ __('messages.media') ?? 'Media' }}"><i class="fas fa-images"></i></button>
            <button class="csr-rail-btn" title="{{ __('messages.topics') }}"><i class="fas fa-hashtag"></i></button>
            <button class="csr-rail-btn" title="{{ __('messages.about') }}"><i class="fas fa-info-circle"></i></button>
        </div>

        {{-- Card stack --}}
        <div class="csr-stack">

            {{-- About card --}}
            <div class="csr-card">
                <div class="csr-card-head">
                    <span class="csr-card-title">
                        <span class="csr-icon-pill"><i class="fas fa-info-circle"></i></span>
                        {{ __('messages.about') }}
                    </span>
                </div>
                @if($group->description)
                <p class="csr-about">{{ Str::limit($group->description, 140) }}</p>
                @endif
                <div class="csr-info-rows">
                    <div class="csr-info-row">
                        <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe' : 'lock' }}"></i>
                        <span>{{ ucfirst($group->privacy_level) }}</span>
                    </div>
                    <div class="csr-info-row">
                        <i class="fas fa-users"></i>
                        <span>
                            <strong data-community-members-count="{{ $group->slug }}">{{ number_format($group->members_count) }}</strong>
                            {{ __('messages.members_label') }}
                        </span>
                    </div>
                    <div class="csr-info-row">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ $group->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Members card — hidden from non-members of private communities --}}
            @if($group->privacy_level === 'public' || $userMember)
            <div class="csr-card">
                <div class="csr-card-head">
                    <span class="csr-card-title">
                        <span class="csr-icon-pill"><i class="fas fa-users"></i></span>
                        {{ __('messages.members_label') }}
                    </span>
                    <a href="{{ route('communities.members', $group->slug) }}" class="csr-card-more">
                        {{ __('messages.view_all') }}
                    </a>
                </div>
                <div class="csr-members">
                    @foreach($topMembers as $member)
                        @if($member->user)
                        <a href="{{ route('users.show', $member->user) }}" class="csr-member">
                            <div class="csr-member-av">
                                <img src="{{ $member->user->avatar_url }}" alt="">
                            </div>
                            <div class="csr-member-info">
                                <div class="csr-member-name">
                                    {{ $member->user->profile?->full_name ?: $member->user->name }}
                                </div>
                                <div class="csr-member-username">
                                    {{ '@' . $member->user->username }}
                                </div>
                                <div class="csr-member-role csr-member-role--{{ $member->role }}">
                                    {{ ucfirst($member->role) }}
                                </div>
                            </div>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Media card --}}
            @if($recentMedia->count() > 0)
            <div class="csr-card">
                <div class="csr-card-head">
                    <span class="csr-card-title">
                        <span class="csr-icon-pill"><i class="fas fa-images"></i></span>
                        {{ __('messages.media') ?? 'Media' }}
                    </span>
                </div>
                <div class="csr-media-grid">
                    @foreach($recentMedia as $media)
                    <div class="csr-media-thumb">
                        <img src="{{ asset('storage/' . $media->media_path) }}" alt="" loading="lazy">
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Topics card --}}
            @if($group->topics->count() > 0)
            <div class="csr-card">
                <div class="csr-card-head">
                    <span class="csr-card-title">
                        <span class="csr-icon-pill"><i class="fas fa-hashtag"></i></span>
                        {{ __('messages.topics') }}
                    </span>
                </div>
                <div class="csr-topics">
                    @foreach($group->topics as $topic)
                        <span class="csr-topic">{{ $topic->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- /csr-stack --}}

    </aside>

</div>{{-- /ch-layout --}}

@include('communities.partials.join-modal', ['group' => $group])
@include('communities.partials.user-settings-modal', ['group' => $group, 'userMember' => $userMember])

<script>
function joinGroup(slug) {
    fetch(`/communities/${slug}/join`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    }).then(res => res.json()).then(data => {
        if (data.status === 'approved') window.location.reload();
        else if (typeof showToast === 'function') showToast(data.message);
    });
}

// ── Left sidebar collapse ──
(function () {
    const sidebar = document.getElementById('chSidebarLeft');
    const toggle  = document.getElementById('cslToggle');
    if (!sidebar || !toggle) return;
    const KEY = 'nexus_ch_sidebar_left_collapsed';
    const apply = (c) => {
        sidebar.classList.toggle('collapsed', c);
        toggle.setAttribute('aria-expanded', c ? 'false' : 'true');
    };
    apply(localStorage.getItem(KEY) === '1');
    toggle.addEventListener('click', () => {
        const next = !sidebar.classList.contains('collapsed');
        apply(next);
        localStorage.setItem(KEY, next ? '1' : '0');
    });
})();

// ── Right sidebar collapse ──
(function () {
    const sidebar = document.getElementById('chSidebarRight');
    const toggle  = document.getElementById('csrToggle');
    if (!sidebar || !toggle) return;
    const KEY = 'nexus_ch_sidebar_right_collapsed';
    const apply = (c) => {
        sidebar.classList.toggle('collapsed', c);
        toggle.setAttribute('aria-expanded', c ? 'false' : 'true');
    };
    apply(localStorage.getItem(KEY) === '1');
    toggle.addEventListener('click', () => {
        const next = !sidebar.classList.contains('collapsed');
        apply(next);
        localStorage.setItem(KEY, next ? '1' : '0');
    });
    sidebar.querySelectorAll('.csr-rail-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (sidebar.classList.contains('collapsed')) {
                apply(false);
                localStorage.setItem(KEY, '0');
            }
        });
    });
})();
</script>

@endsection
