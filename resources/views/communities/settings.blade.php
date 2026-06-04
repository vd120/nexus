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
                <a href="{{ route('communities.about', $group->slug) }}" class="ch-tab">
                    <i class="fas fa-info-circle"></i><span>{{ __('messages.about') }}</span>
                </a>
                <a href="{{ route('communities.members', $group->slug) }}" class="ch-tab">
                    <i class="fas fa-users"></i><span>{{ __('messages.group_members') }}</span>
                </a>
                <a href="{{ route('communities.settings', $group->slug) }}" class="ch-tab active">
                    <i class="fas fa-sliders-h"></i><span>{{ __('messages.settings') }}</span>
                </a>
            </nav>
        </div>

        {{-- Settings content --}}
        <div class="ch-feed">
            <div class="settings-container">
                <div class="settings-page-header">
                    <h2 class="settings-title">{{ __('messages.my_preferences') }}</h2>
                    <p class="settings-subtitle">{{ __('messages.manage_interaction_with', ['name' => $group->name]) }}</p>
                </div>

                <form id="preferences-form" class="settings-form">
                    @csrf
                    @method('PATCH')

                    {{-- Notification Preferences --}}
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="header-icon"><i class="fas fa-bell"></i></div>
                            <div class="header-text">
                                <h3>{{ __('messages.notifications') }}</h3>
                                <p>{{ __('messages.choose_updates_desc') }}</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>{{ __('messages.notification_level') }}</label>
                                <div class="custom-select-wrap">
                                    <select name="notification_preference" class="form-input">
                                        <option value="all" {{ $member->notification_preference === 'all' ? 'selected' : '' }}>{{ __('messages.all_activity_posts_comments') }}</option>
                                        <option value="highlights" {{ $member->notification_preference === 'highlights' ? 'selected' : '' }}>{{ __('messages.highlights_only') }}</option>
                                        <option value="none" {{ $member->notification_preference === 'none' ? 'selected' : '' }}>{{ __('messages.muted_no_notifications') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Identity & Privacy --}}
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="header-icon"><i class="fas fa-user-secret"></i></div>
                            <div class="header-text">
                                <h3>{{ __('messages.identity_privacy') }}</h3>
                                <p>{{ __('messages.identity_privacy_desc') }}</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="toggles-list">
                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <strong>{{ __('messages.global_anonymity') }}</strong>
                                        <p>{{ __('messages.global_anonymity_desc') }}</p>
                                    </div>
                                    <label class="nexus-switch">
                                        <input type="checkbox" name="is_anonymous_default" {{ $member->is_anonymous_default ? 'checked' : '' }}>
                                        <span class="switch-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button type="button" onclick="savePreferences()" class="action-btn save-btn">
                            <i class="fas fa-save"></i>
                            <span>{{ __('messages.save_preferences') }}</span>
                        </button>
                    </div>
                </form>

                {{-- Danger Zone --}}
                <div class="danger-section">
                    <div class="danger-header">
                        <h3>{{ __('messages.danger_zone') }}</h3>
                        <p>{{ __('messages.danger_zone_actions_desc') }}</p>
                    </div>
                    <div class="danger-card">
                        <div class="danger-info">
                            <strong>{{ __('messages.leave_community') }}</strong>
                            <p>{{ __('messages.leave_community_desc') }}</p>
                        </div>
                        <button type="button" onclick="confirmLeaveGroup()" class="action-btn leave-btn">
                            {{ __('messages.leave_group') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    {{-- Right sidebar --}}
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
        </div>
        <div class="csr-stack">
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
                        <span><strong>{{ number_format($group->members_count) }}</strong> {{ __('messages.members_label') }}</span>
                    </div>
                    <div class="csr-info-row">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ $group->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

</div>

<script>
    function savePreferences() {
        const form = document.getElementById('preferences-form');
        const formData = new FormData(form);
        const slug = '{{ $group->slug }}';

        const payload = {
            notification_preference: formData.get('notification_preference'),
            is_anonymous_default: form.querySelector('[name="is_anonymous_default"]').checked ? 1 : 0
        };

        const btn = document.querySelector('.save-btn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

        fetch(`/communities/${slug}/preferences`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => { showToast('{{ __("messages.preferences_updated_success") }}', 'success'); })
        .catch(err => { showToast('{{ __("messages.failed_save_preferences") }}', 'error'); })
        .finally(() => { btn.innerHTML = originalHtml; btn.disabled = false; });
    }

    function confirmLeaveGroup() {
        if (confirm('{{ __("messages.leave_community_confirm") }}')) {
            fetch(`/communities/{{ $group->slug }}/leave`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(() => { window.location.href = '{{ route("communities.index") }}'; });
        }
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
