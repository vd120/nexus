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

<div class="ch-layout">

    {{-- Left sidebar --}}
    @include('communities.partials.sidebar-left', ['group' => $group, 'userMember' => $userMember])

    {{-- Center --}}
    <main class="ch-center">

        {{-- Hero --}}
        <div class="ch-hero">
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
                    </div>
                </div>
            </div>
            <nav class="ch-tabs">
                <a href="{{ route('communities.show', $group->slug) }}" class="ch-tab">
                    <i class="fas fa-comment-alt"></i><span>{{ __('messages.discussion') }}</span>
                </a>
                <a href="{{ route('communities.about', $group->slug) }}" class="ch-tab active">
                    <i class="fas fa-info-circle"></i><span>{{ __('messages.about') }}</span>
                </a>
                <a href="{{ route('communities.members', $group->slug) }}" class="ch-tab">
                    <i class="fas fa-users"></i><span>{{ __('messages.group_members') }}</span>
                </a>
                @if($userMember && $userMember->status === 'approved')
                <a href="{{ route('communities.settings', $group->slug) }}" class="ch-tab">
                    <i class="fas fa-sliders-h"></i><span>{{ __('messages.settings') }}</span>
                </a>
                @endif
            </nav>
        </div>

        {{-- About content --}}
        <div class="ch-feed">
            <div class="community-about-grid">
                <div class="about-main-col">
                    <div class="panel about-card">
                        <div class="panel-header">
                            <h3>{{ __('messages.about_this_community') }}</h3>
                        </div>
                        <div class="panel-body">
                            <div class="description-content">
                                {{ $group->description ?? __('messages.no_description') }}
                            </div>
                        </div>
                    </div>

                    <div class="panel about-card">
                        <div class="panel-header">
                            <h3>{{ __('messages.community_rules') }}</h3>
                        </div>
                        <div class="panel-body">
                            <div class="rules-stack">
                                @forelse($group->rules as $rule)
                                    <div class="rule-box">
                                        <div class="rule-head">
                                            <span class="rule-index">{{ $loop->iteration }}</span>
                                            <h4 class="rule-title">{{ $rule->title }}</h4>
                                        </div>
                                        <div class="rule-text">{{ $rule->description }}</div>
                                    </div>
                                @empty
                                    <div class="empty-state-mini">
                                        <i class="fas fa-gavel"></i>
                                        <p>{{ __('messages.no_rules_set') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="about-side-col">
                    <div class="panel info-card">
                        <div class="panel-header">
                            <h3>{{ __('messages.details') }}</h3>
                        </div>
                        <div class="panel-body">
                            <div class="details-list">
                                <div class="detail-row">
                                    <div class="icon-circle"><i class="fas fa-history"></i></div>
                                    <div class="info">
                                        <label>{{ __('messages.created_at_label') }}</label>
                                        <strong>{{ $group->created_at->format('M d, Y') }}</strong>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="icon-circle"><i class="fas fa-shield-alt"></i></div>
                                    <div class="info">
                                        <label>{{ __('messages.privacy_label') }}</label>
                                        <strong>{{ __('messages.' . $group->privacy_level) }}</strong>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="icon-circle"><i class="fas fa-eye"></i></div>
                                    <div class="info">
                                        <label>{{ __('messages.visibility_label') }}</label>
                                        <strong>{{ $group->is_discoverable ? __('messages.publicly_visible') : __('messages.hidden') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($group->topics->count() > 0)
                    <div class="panel info-card">
                        <div class="panel-header">
                            <h3>{{ __('messages.popular_topics') }}</h3>
                        </div>
                        <div class="panel-body">
                            <div class="topics-cloud">
                                @foreach($group->topics as $topic)
                                    <span class="topic-pill">#{{ $topic->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </main>

    {{-- Right sidebar: about details --}}
    <aside class="ch-sidebar-right" id="chSidebarRight">
        <div class="csr-header">
            <span class="csr-header-title">
                <span class="csr-header-dot"></span>
                {{ __('messages.community_info') }}
            </span>
            <button type="button" class="csr-toggle" id="csrToggle" aria-expanded="true">
                <i class="fas fa-chevron-right csr-toggle-icon"></i>
            </button>
        </div>
        <div class="csr-rail" aria-hidden="true">
            <button class="csr-rail-btn" title="{{ __('messages.about') }}"><i class="fas fa-info-circle"></i></button>
            <button class="csr-rail-btn" title="{{ __('messages.topics') }}"><i class="fas fa-hashtag"></i></button>
        </div>
        <div class="csr-stack">
            <div class="csr-card">
                <div class="csr-card-head">
                    <span class="csr-card-title">
                        <span class="csr-icon-pill"><i class="fas fa-info-circle"></i></span>
                        {{ __('messages.about') }}
                    </span>
                </div>
                <div class="csr-info-rows">
                    <div class="csr-info-row">
                        <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe' : 'lock' }}"></i>
                        <span>{{ ucfirst($group->privacy_level) }}</span>
                    </div>
                    <div class="csr-info-row">
                        <i class="fas fa-users"></i>
                        <span><strong>{{ number_format($group->members_count) }}</strong> {{ __('messages.members_label') }}</span>
                    </div>
                    <div class="csr-info-row">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ $group->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
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
        </div>
    </aside>

</div>

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
(function () {
    const sidebar = document.getElementById('chSidebarRight');
    const toggle  = document.getElementById('csrToggle');
    if (!sidebar || !toggle) return;
    const KEY = 'nexus_ch_sidebar_right_collapsed';
    const apply = (c) => { sidebar.classList.toggle('collapsed', c); toggle.setAttribute('aria-expanded', c ? 'false' : 'true'); };
    apply(localStorage.getItem(KEY) === '1');
    toggle.addEventListener('click', () => { const next = !sidebar.classList.contains('collapsed'); apply(next); localStorage.setItem(KEY, next ? '1' : '0'); });
    sidebar.querySelectorAll('.csr-rail-btn').forEach(btn => { btn.addEventListener('click', () => { if (sidebar.classList.contains('collapsed')) { apply(false); localStorage.setItem(KEY, '0'); } }); });
})();
</script>

@endsection
