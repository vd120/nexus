@extends('layouts.app')

@section('title', ($user->name ?: $user->username) . ' (@' . $user->username . ') — ' . config('app.name'))

@push('meta')
@php $ogBio = $user->profile?->bio ? Str::limit($user->profile->bio, 160) : config('app.name') . ' — ' . $user->username; @endphp
<meta property="og:title" content="{{ e($user->name ?: $user->username) }} (@{{ e($user->username) }})">
<meta property="og:description" content="{{ e($ogBio) }}">
<meta property="og:image" content="{{ $user->avatar_url }}">
<meta property="og:url" content="{{ route('users.show', $user) }}">
<meta property="og:type" content="profile">
<meta name="twitter:title" content="{{ e($user->name ?: $user->username) }}">
<meta name="twitter:description" content="{{ e($ogBio) }}">
<meta name="twitter:image" content="{{ $user->avatar_url }}">
@endpush

@section('content')
<style>
/* ─── Profile Layout ─────────────────────────────────── */
.np-page { max-width: 680px; margin: 0 auto; padding-bottom: 80px; }

/* ─── Cover ──────────────────────────────────────────── */
.np-cover {
    position: relative;
    height: 240px;
    overflow: hidden;
    background: linear-gradient(135deg, #312e81 0%, #1e1b4b 40%, #0f0f23 100%);
    cursor: default;
}
.np-cover.has-image { cursor: pointer; }
.np-cover::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 70% 40%, rgba(129,140,248,0.18) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 20% 70%, rgba(78,168,222,0.12) 0%, transparent 60%);
    pointer-events: none;
}
.np-cover-img {
    width: 100%; height: 100%; object-fit: cover;
    position: absolute; inset: 0;
}
.np-cover-monogram {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 110px; font-weight: 900; letter-spacing: -.04em;
    color: rgba(255,255,255,0.04); user-select: none; z-index: 0;
}
/* Top-right overlay actions */
.np-cover-actions {
    position: absolute; top: 14px; right: 14px;
    z-index: 10; display: flex; gap: 8px; align-items: center;
}
.np-cover-btn {
    height: 36px; padding: 0 16px;
    border-radius: var(--radius-full); border: none;
    font-size: 13px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    transition: all .18s; text-decoration: none; white-space: nowrap;
}
.np-cover-btn-primary {
    background: var(--primary); color: #fff;
    box-shadow: 0 4px 16px rgba(129,140,248,0.4);
}
.np-cover-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
.np-cover-btn-ghost {
    background: rgba(0,0,0,0.45); color: #fff;
    border: 1px solid rgba(255,255,255,0.18);
    padding: 0 12px;
}
.np-cover-btn-ghost:hover { background: rgba(0,0,0,0.65); }
.np-cover-btn i { display: flex; align-items: center; justify-content: center; }

/* ─── Avatar ─────────────────────────────────────────── */
.np-avatar-row {
    padding: 0 20px;
    margin-top: -44px;
    position: relative; z-index: 5;
}
.np-avatar-ring {
    width: 92px; height: 92px; border-radius: 50%;
    padding: 3px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: inline-block;
    box-shadow: 0 6px 20px rgba(0,0,0,0.5);
    cursor: pointer;
}
.np-avatar-inner {
    width: 100%; height: 100%; border-radius: 50%;
    overflow: hidden; background: var(--surface-2);
    border: 3px solid var(--bg);
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 800; color: var(--primary);
}
.np-avatar-inner img { width: 100%; height: 100%; object-fit: cover; }

/* ─── Identity ───────────────────────────────────────── */
.np-identity { padding: 12px 20px 0; }
.np-name-row {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; margin-bottom: 3px;
}
.np-name { font-size: 21px; font-weight: 800; color: var(--text); line-height: 1.2; }
.np-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: var(--radius-full);
    font-size: 11px; font-weight: 700; letter-spacing: .02em; white-space: nowrap;
}
.np-badge-admin { background: var(--primary-soft); color: var(--primary); }
.np-badge-private { background: rgba(244,63,94,0.1); color: #f43f5e; }
.np-badge-suspended { background: rgba(239,68,68,0.12); color: var(--danger); }
.np-badge-blocked { background: rgba(239,68,68,0.12); color: var(--danger); }
.np-username {
    font-size: 14px; color: var(--text-muted);
    margin-bottom: 10px; display: block;
    text-align: start;
}
.np-username bdi { direction: ltr; unicode-bidi: isolate; }
.np-bio {
    font-size: 14px; color: var(--text); line-height: 1.65;
    margin-bottom: 10px; max-width: 520px;
}
.np-meta {
    display: flex; flex-wrap: wrap; gap: 14px;
    font-size: 13px; color: var(--text-muted); margin-bottom: 16px;
}
.np-meta i { margin-right: 4px; font-size: 12px; opacity: .7; }

/* About (longer bio) */
.np-about {
    font-size: 14px; color: var(--text-muted); line-height: 1.7;
    margin-bottom: 14px; max-width: 520px;
    padding: 12px 14px;
    background: var(--surface-2);
    border-radius: var(--radius);
    border-left: 3px solid var(--primary-soft);
    white-space: pre-wrap;
}

/* ─── Stats ──────────────────────────────────────────── */
.np-stats-row {
    display: flex; gap: 28px; padding: 14px 20px;
    border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
}
.np-stat {
    display: flex; flex-direction: column;
    text-decoration: none; gap: 1px; cursor: pointer;
}
.np-stat-num { font-size: 18px; font-weight: 800; color: var(--text); line-height: 1.2; }
.np-stat-lbl { font-size: 12px; color: var(--text-muted); font-weight: 500; }
.np-stat:hover .np-stat-num { color: var(--primary); }

/* ─── Tab bar ────────────────────────────────────────── */
.np-tabs {
    display: flex; border-bottom: 1px solid var(--border);
    overflow-x: auto; scrollbar-width: none;
    position: sticky; top: 76px; z-index: 50;
    background: rgba(10,10,11,0.92);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
}
[data-theme="light"] .np-tabs { background: rgba(246,247,249,0.92); }
.np-tabs::-webkit-scrollbar { display: none; }
.np-tab {
    flex-shrink: 0; padding: 13px 18px;
    font-size: 13px; font-weight: 600;
    color: var(--text-muted); border: none; background: none;
    border-bottom: 2px solid transparent; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: all .18s; white-space: nowrap;
    position: relative; bottom: -1px;
}
.np-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.np-tab:hover:not(.active) { color: var(--text); background: var(--surface-hover); }
.np-tab-count {
    font-size: 11px; padding: 1px 6px; border-radius: var(--radius-full);
    background: var(--surface-2); color: var(--text-dim); font-weight: 700;
}
.np-tab.active .np-tab-count { background: var(--primary-soft); color: var(--primary); }

/* ─── Tab panels ─────────────────────────────────────── */
.np-panel { display: none; padding: 0 0 20px; }
.np-panel.active { display: block; }

/* ─── Overflow dropdown ──────────────────────────────── */
.np-overflow-wrap { position: relative; }
.np-overflow-dropdown {
    position: absolute; top: calc(100% + 8px); right: 0;
    background: var(--surface-2);
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-lg); padding: 6px;
    min-width: 190px;
    box-shadow: 0 8px 28px rgba(0,0,0,0.5);
    z-index: 300; display: none;
}
.np-overflow-wrap.open .np-overflow-dropdown { display: block; }
.np-overflow-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 500; color: var(--text);
    text-decoration: none; cursor: pointer; transition: background .15s;
    background: none; border: none; width: 100%; text-align: left;
}
.np-overflow-item:hover { background: var(--surface-hover); }
.np-overflow-item i { width: 16px; text-align: center; color: var(--text-muted); font-size: 13px; }
.np-overflow-item.danger { color: var(--danger); }
.np-overflow-item.danger i { color: var(--danger); }
.np-overflow-sep { height: 1px; background: var(--border); margin: 4px 0; }

/* ─── Pinned section label ───────────────────────────── */
.np-pinned-label {
    display: flex; align-items: center; gap: 8px;
    padding: 16px 20px 10px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--text-dim);
}
.np-pinned-label i { color: var(--primary); font-size: 10px; transform: rotate(45deg); }

/* ─── Posts feed wrapper ─────────────────────────────── */
.np-posts-feed { display: flex; flex-direction: column; gap: 12px; padding: 16px 20px 0; }
.np-pinned-feed { display: flex; flex-direction: column; gap: 12px; padding: 0 20px; }
.np-pinned-reorder {
    margin-left: auto; padding: 4px 12px;
    font-size: 11px; border-radius: var(--radius-sm);
    background: var(--surface-hover); border: 1px solid var(--border);
    color: var(--text-muted); cursor: pointer;
    display: flex; align-items: center; gap: 6px;
}

/* ─── Empty state ────────────────────────────────────── */
.np-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 60px 20px; color: var(--text-muted); width: 100%; }
.np-empty i { font-size: 40px; margin-bottom: 14px; opacity: .3; }
.np-empty h3 { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.np-empty p { font-size: 14px; }

/* ─── Own-profile action buttons ────────────────────────────── */
.np-owner-actions {
    display: flex; gap: 8px; flex-wrap: nowrap; padding: 14px 20px 0;
}
.np-owner-btn {
    flex: 1; justify-content: center;
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 10px; border-radius: var(--radius); font-size: 13px; font-weight: 600;
    background: var(--surface-2); border: 1px solid var(--border-strong);
    color: var(--text); cursor: pointer; text-decoration: none;
    transition: all .18s; white-space: nowrap; min-width: 0;
}
.np-owner-btn:hover { background: var(--surface-hover); }

/* ─── Blocked-you notice ─────────────────────────────── */
.np-blocked-notice {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px; border-radius: var(--radius);
    background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.18);
    font-size: 13px; color: var(--danger); margin: 12px 20px 0;
}

/* ──────────────────────────────────────────────────────
   RESPONSIVE
────────────────────────────────────────────────────── */

/* Tablet: header = 64px */
@media (max-width: 900px) and (min-width: 481px) {
    .np-tabs { top: 64px; }
}
/* Mobile: header = 56px */
@media (max-width: 480px) {
    .np-tabs { top: 56px; }
}


@media (max-width: 768px) {
    :is(.app-layout, .main-content, .np-page) {
        padding: 0 !important; margin: 0 !important;
        width: 100% !important; max-width: 100% !important;
    }
}

@media (max-width: 640px) {
    .np-cover { height: 180px; }
    .np-cover-monogram { font-size: 80px; }
    .np-avatar-row { padding: 0 14px; margin-top: -36px; }
    .np-avatar-ring { width: 76px; height: 76px; }
    .np-avatar-inner { font-size: 26px; }
    .np-identity { padding: 10px 14px 0; }
    .np-name { font-size: 18px; }
    .np-username { font-size: 13px; }
    .np-bio { font-size: 13px; }
    .np-meta { gap: 10px; font-size: 12px; }
    .np-stats-row { padding: 12px 14px; gap: 20px; }
    .np-stat-num { font-size: 16px; }
    .np-posts-feed { padding: 12px 0 0; }
    .np-pinned-feed { padding: 0; }
    .np-pinned-label { padding: 14px 0 8px; }
    .np-cover-btn { height: 32px; padding: 0 11px; font-size: 12px; }
    .np-cover-actions { top: 10px; right: 10px; gap: 6px; }
    .np-owner-actions { padding: 12px 14px 0; gap: 6px; }
    .np-owner-btn { padding: 7px 8px; font-size: 12px; }
    .np-owner-btn i { font-size: 13px; }
    .np-tab { padding: 12px 14px; font-size: 12px; gap: 5px; }
    .np-tab-count { display: none; }
    .np-blocked-notice { margin: 10px 0 0; }
    #qr-code-display svg, #qr-code-display img { max-width: 100% !important; height: auto !important; }
}

@media (max-width: 400px) {
    .np-cover-btn span { display: none; }
    .np-cover-btn { padding: 0 10px; }
    .np-owner-btn span { display: none; }
    .np-owner-btn { gap: 0; padding: 8px; }
}
@media (max-width: 380px) {
    .np-owner-btn { font-size: 11px; padding: 7px 6px; }
}

/* ─── Image Modal ─────────────────────────────────────── */
#image-modal {
    z-index: 999999 !important;
    padding: 64px 16px 24px !important;
}
#image-modal-img {
    max-width: 78vw;
    max-height: 68vh;
    object-fit: contain;
    border-radius: 8px;
    display: block;
}
#image-modal-close {
    z-index: 1000000 !important;
}
@media (max-width: 600px) {
    #image-modal {
        padding: 60px 12px 20px !important;
    }
    #image-modal-img {
        max-width: 90vw !important;
        max-height: 55vh !important;
        max-height: 55svh !important;
    }
}
</style>

<div class="np-page">

    {{-- ── COVER ── --}}
    <div class="np-cover {{ $user->profile && $user->profile->cover_image ? 'has-image' : '' }}"
         @if($user->profile && $user->profile->cover_image)
             onclick="openImageModal('{{ asset('storage/' . $user->profile->cover_image) }}')"
         @endif>
        @if($user->profile && $user->profile->cover_image)
            <img class="np-cover-img" src="{{ asset('storage/' . $user->profile->cover_image) }}" alt="Cover" loading="lazy">
        @else
            <div class="np-cover-monogram">{{ strtoupper(substr($user->name ?: $user->username, 0, 2)) }}</div>
        @endif

        {{-- Overlay action buttons --}}
        <div class="np-cover-actions" onclick="event.stopPropagation()">
            @if(auth()->check() && auth()->id() === $user->id)
                {{-- Own profile: edit button --}}
                <a href="{{ route('profile.edit', $user) }}" class="np-cover-btn np-cover-btn-primary">
                    <i class="fas fa-edit"></i> <span>{{ __('users.edit_profile') }}</span>
                </a>
                <div class="np-overflow-wrap" id="overflowMenu">
                    <button class="np-cover-btn np-cover-btn-ghost" onclick="toggleOverflow()" aria-label="More options">
                        <i class="fas fa-ellipsis"></i>
                    </button>
                    <div class="np-overflow-dropdown">
                        <a href="{{ route('activity.index') }}" class="np-overflow-item">
                            <i class="fas fa-history"></i> {{ __('activity.activity_logs') }}
                        </a>
                        <a href="{{ route('users.memories', $user) }}" class="np-overflow-item">
                            <i class="fas fa-book-open"></i> {{ __('messages.my_memories') }}
                        </a>
                        <button class="np-overflow-item" onclick="showQrCodeModal();closeOverflow()">
                            <i class="fas fa-qrcode"></i> {{ __('users.qr_code') }}
                        </button>
                    </div>
                </div>
            @elseif(auth()->check() && !$isBlockedBy && !$isBlocking)
                {{-- Other user: primary + overflow --}}
                <button class="np-cover-btn np-cover-btn-primary" id="followBtn"
                    onclick="profileToggleFollow(this, '{{ $user->username }}')"
                    data-following="{{ $isFollowing ? 'true' : 'false' }}">
                    <i class="fas fa-user-{{ $isFollowing ? 'check' : 'plus' }}"></i>
                    <span>{{ $isFollowing ? __('users.following') : __('users.follow') }}</span>
                </button>
                <div class="np-overflow-wrap" id="overflowMenu">
                    <button class="np-cover-btn np-cover-btn-ghost" onclick="toggleOverflow()" aria-label="More options">
                        <i class="fas fa-ellipsis"></i>
                    </button>
                    <div class="np-overflow-dropdown">
                        <a href="{{ route('chat.start', $user->id) }}" class="np-overflow-item">
                            <i class="fas fa-envelope"></i> {{ __('users.message') }}
                        </a>
                        <button class="np-overflow-item" onclick="profileConfirmCall({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->avatar_url }}', '{{ addslashes($user->username) }}', {{ $user->is_verified ? 'true' : 'false' }});closeOverflow()">
                            <i class="fas fa-phone"></i> {{ __('messages.voice_call') }}
                        </button>
                        <div class="np-overflow-sep"></div>
                        <button class="np-overflow-item danger" onclick="profileBlockUser('{{ $user->username }}');closeOverflow()">
                            <i class="fas fa-ban"></i> {{ __('users.block') }}
                        </button>
                    </div>
                </div>
            @elseif(auth()->check() && $isBlocking)
                <button class="np-cover-btn np-cover-btn-primary" style="background:var(--danger);"
                    onclick="profileUnblockUser('{{ $user->username }}')">
                    <i class="fas fa-unlock"></i> <span>{{ __('users.unblock') }}</span>
                </button>
            @else
                <a href="{{ route('login') }}" class="np-cover-btn np-cover-btn-primary">
                    <i class="fas fa-sign-in-alt"></i> <span>{{ __('users.sign_in_to_follow') }}</span>
                </a>
            @endif
        </div>
    </div>

    {{-- ── AVATAR ── --}}
    <div class="np-avatar-row">
        <div class="np-avatar-ring" onclick="openImageModal('{{ $user->avatar_url }}')">
            <div class="np-avatar-inner">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" loading="lazy">
            </div>
        </div>
    </div>

    {{-- ── IDENTITY ── --}}
    <div class="np-identity">
        <div class="np-name-row">
            <span class="np-name">{{ $user->name }}</span>
            <x-verified-badge :user="$user" size="1em" />
            @if($user->is_admin)
                <span class="np-badge np-badge-admin"><i class="fas fa-shield-alt"></i> {{ __('users.admin') }}</span>
            @endif
            @if($user->profile && $user->profile->is_private)
                <span class="np-badge np-badge-private"><i class="fas fa-lock"></i> {{ __('users.private') }}</span>
            @endif
            @if($user->is_suspended)
                <span class="np-badge np-badge-suspended"><i class="fas fa-ban"></i> {{ __('users.suspended') }}</span>
            @endif
            @if(auth()->check() && $isBlocking)
                <span class="np-badge np-badge-blocked" data-badge="blocked"><i class="fas fa-ban"></i> {{ __('users.blocked') }}</span>
            @endif
        </div>
        <span class="np-username"><bdi dir="ltr">{{ '@' . $user->username }}</bdi></span>

        @if($user->profile && $user->profile->bio)
            <p class="np-bio">{{ $user->profile->bio }}</p>
        @endif

        <div class="np-meta">
            @if($user->profile && $user->profile->occupation)
                @if($isOwner)
                    <span>
                        <i class="fas fa-briefcase"></i> {{ $user->profile->occupation }}
                        @if(!$user->profile->show_occupation)
                            <span style="font-size:11px;opacity:.5;margin-left:4px;">({{ __('users.only_you') }})</span>
                        @endif
                    </span>
                @elseif($user->profile->show_occupation)
                    <span><i class="fas fa-briefcase"></i> {{ $user->profile->occupation }}</span>
                @endif
            @endif
            @if($user->profile && $user->profile->location)
                @if($isOwner)
                    <span>
                        <i class="fas fa-map-marker-alt"></i> {{ $user->profile->location }}
                        @if(!$user->profile->show_location)
                            <span style="font-size:11px;opacity:.5;margin-left:4px;">({{ __('users.only_you') }})</span>
                        @endif
                    </span>
                @elseif($user->profile->show_location)
                    <span><i class="fas fa-map-marker-alt"></i> {{ $user->profile->location }}</span>
                @endif
            @endif
            @if($user->profile && $user->profile->gender)
                @php
                    $genderKey = match($user->profile->gender) {
                        'male'   => 'users.male',
                        'female' => 'users.female',
                        default  => 'users.other',
                    };
                    $genderIcon = match($user->profile->gender) {
                        'male'   => 'fa-mars',
                        'female' => 'fa-venus',
                        default  => 'fa-genderless',
                    };
                @endphp
                @if($isOwner)
                    <span>
                        <i class="fas {{ $genderIcon }}"></i> {{ __($genderKey) }}
                        @if(!$user->profile->show_gender)
                            <span style="font-size:11px;opacity:.5;margin-left:4px;">({{ __('users.only_you') }})</span>
                        @endif
                    </span>
                @elseif($user->profile->show_gender)
                    <span><i class="fas {{ $genderIcon }}"></i> {{ __($genderKey) }}</span>
                @endif
            @endif
            @if($user->profile && $user->profile->birth_date)
                @if($isOwner)
                    <span>
                        <i class="fas fa-birthday-cake"></i> {{ \Carbon\Carbon::parse($user->profile->birth_date)->format('d M Y') }}
                        @if(!$user->profile->show_birth_date)
                            <span style="font-size:11px;opacity:.5;margin-left:4px;">({{ __('users.only_you') }})</span>
                        @endif
                    </span>
                @elseif($user->profile->show_birth_date)
                    <span><i class="fas fa-birthday-cake"></i> {{ \Carbon\Carbon::parse($user->profile->birth_date)->format('d M Y') }}</span>
                @endif
            @endif
            @if($user->profile && $user->profile->website)
                <span><i class="fas fa-link"></i> <a href="{{ $user->profile->website }}" target="_blank" rel="noopener noreferrer" style="color:var(--primary);text-decoration:none;word-break:break-all;">{{ preg_replace('#^https?://#', '', rtrim($user->profile->website, '/')) }}</a></span>
            @endif
            @if($isOwner && $user->profile && $user->profile->phone)
                <span>
                    <i class="fas fa-phone"></i> {{ $user->profile->phone }}
                    @if(!$user->profile->show_phone)
                        <span style="font-size:11px;opacity:.5;margin-left:4px;">({{ __('users.only_you') ?? 'only you' }})</span>
                    @endif
                </span>
            @elseif(!$isOwner && $user->profile && $user->profile->phone && $user->profile->show_phone)
                <span><i class="fas fa-phone"></i> {{ $user->profile->phone }}</span>
            @endif
            <span><i class="fas fa-calendar-alt"></i> {{ __('messages.joined_on', ['date' => $user->created_at->format('M Y')]) }}</span>
        </div>

        @if($user->profile && $user->profile->about)
            <div class="np-about">{{ $user->profile->about }}</div>
        @endif



        {{-- Blocked-you notice --}}
        @if(auth()->check() && $isBlockedBy)
            <div class="np-blocked-notice"><i class="fas fa-ban"></i> {{ __('users.blocked_you') }}</div>
        @endif
    </div>

    {{-- ── STATS ── --}}
    <div class="np-stats-row">
        <a href="{{ route('users.show', $user) }}" class="np-stat">
            <span class="np-stat-num" id="posts-count">{{ $postsCount }}</span>
            <span class="np-stat-lbl">{{ __('users.posts') }}</span>
        </a>
        <a href="{{ route('users.followers', $user) }}" class="np-stat">
            <span class="np-stat-num" id="follower-count">{{ $followersCount }}</span>
            <span class="np-stat-lbl">{{ __('users.followers') }}</span>
        </a>
        <a href="{{ route('users.following', $user) }}" class="np-stat">
            <span class="np-stat-num" id="following-count">{{ $followingCount }}</span>
            <span class="np-stat-lbl">{{ __('users.following') }}</span>
        </a>
        @if(auth()->check() && auth()->id() === $user->id)
        <a href="{{ route('users.blocked', $user) }}" class="np-stat">
            <span class="np-stat-num">{{ $blockedCount }}</span>
            <span class="np-stat-lbl">{{ __('users.blocked') }}</span>
        </a>
        @endif
    </div>

    {{-- ── TABS ── --}}
    @if(!$isBlockedBy)
    <div class="np-tabs" id="npTabs">
        <button class="np-tab active" data-panel="posts">
            <i class="fas fa-th-large"></i>
            {{ __('users.posts') }}
            <span class="np-tab-count" id="tab-posts-count">{{ $postsCount }}</span>
        </button>
        @if($pinnedPosts->count() > 0)
        <button class="np-tab" data-panel="pinned">
            <i class="fas fa-thumbtack" style="transform:rotate(45deg)"></i>
            {{ __('users.pinned_posts') }}
            <span class="np-tab-count">{{ $pinnedPosts->count() }}</span>
        </button>
        @endif
        <button class="np-tab" data-panel="chapters">
            <i class="fas fa-book-open"></i>
            {{ __('messages.my_chapters') }}
        </button>
    </div>
    @endif

    {{-- ── PANELS (hidden when blocked) ── --}}
    @if(!$isBlockedBy)

    {{-- ── PANEL: Posts ── --}}
    <div class="np-panel active" id="panel-posts">
        @if(!$isOwner && $user->profile && $user->profile->is_private && !$isFollowing)
            <div class="np-empty">
                <i class="fas fa-lock"></i>
                <h3>{{ __('users.private_account') ?? 'Private Account' }}</h3>
                <p>{{ __('users.follow_to_see_posts') ?? 'Follow this account to see their posts.' }}</p>
            </div>
        @elseif($posts->count() === 0 && $pinnedPosts->count() === 0)
            <div class="np-empty">
                <i class="fas fa-newspaper"></i>
                <h3>{{ __('users.no_posts_yet') }}</h3>
                <p>{{ __('users.no_posts_yet_desc') }}</p>
            </div>
        @else
            <div class="np-posts-feed" id="regularPostsFeed">
                @foreach($posts as $post)
                    @include('partials.post', ['post' => $post, 'isPinned' => false])
                @endforeach
            </div>
            <div style="padding: 0 20px;">{{ $posts->links() }}</div>
        @endif
    </div>

    {{-- ── PANEL: Pinned ── --}}
    @if($pinnedPosts->count() > 0)
    <div class="np-panel" id="panel-pinned">
        <div class="np-pinned-label" style="display:flex;align-items:center;">
            <i class="fas fa-thumbtack"></i>
            {{ __('users.pinned_posts') }}
            @if($isOwner && $pinnedCount > 1)
                <button type="button" id="reorderBtn" class="np-pinned-reorder" onclick="toggleReorderMode()">
                    <i class="fas fa-sort"></i> <span>{{ __('users.reorder') }}</span>
                </button>
            @endif
        </div>
        <div class="np-pinned-feed pinned-posts-container" id="pinnedPostsContainer">
            @foreach($pinnedPosts as $post)
                @include('partials.post', ['post' => $post, 'isPinned' => true])
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── PANEL: Chapters ── --}}
    <div class="np-panel" id="panel-chapters">
        <div style="padding: 16px 20px 0;">
            @include('users.partials.chapters-section', ['chaptersUser' => $user, 'chaptersIsOwner' => $isOwner])
        </div>
    </div>

    @endif {{-- !$isBlockedBy --}}

</div>

{{-- ── QR Code Modal ── --}}
<div id="qr-code-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9998;align-items:center;justify-content:center;" onclick="closeQrCodeModal(event)">
    <div style="background:var(--surface);border-radius:var(--radius-lg);padding:32px;max-width:400px;width:90%;text-align:center;position:relative;" onclick="event.stopPropagation()">
        <button style="position:absolute;top:12px;right:12px;background:none;border:none;color:inherit;font-size:24px;cursor:pointer;padding:4px;opacity:.5;" onclick="closeQrCodeModal()">×</button>
        <h3 style="font-size:20px;font-weight:700;color:var(--text);margin-bottom:8px;"><i class="fas fa-qrcode" style="color:var(--primary);"></i> {{ __('users.profile_qr_code') }}</h3>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px;">{{ __('users.scan_to_visit_profile') }}</p>
        <div id="qr-code-loading" style="display:flex;align-items:center;justify-content:center;padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--primary);"></i>
        </div>
        <div id="qr-code-content" style="display:none;">
            <div id="qr-code-display" style="background:white;padding:16px;border-radius:var(--radius-md);display:inline-block;margin-bottom:20px;max-width:100%;box-sizing:border-box;"></div>
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;word-break:break-all;" id="qr-profile-url"></p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button class="btn btn-primary" onclick="downloadQrCode()"><i class="fas fa-download"></i> {{ __('users.download_qr') }}</button>
                <button class="btn" onclick="closeQrCodeModal()"><i class="fas fa-times"></i> {{ __('users.close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('body-modals')
{{-- ── Image Modal — rendered as a direct <body> child to escape .main-content stacking context ── --}}
<div id="image-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.92);z-index:999999;align-items:center;justify-content:center;box-sizing:border-box;" onclick="closeImageModal(event)">
    <button id="image-modal-close" type="button" aria-label="{{ __('users.close') }}"
        style="position:fixed;top:calc(env(safe-area-inset-top) + 12px);right:12px;z-index:1000000;width:40px;height:40px;min-width:40px;min-height:40px;border-radius:50%;background:rgba(30,30,30,0.85);border:1.5px solid rgba(255,255,255,0.35);color:#fff;font-size:22px;line-height:1;display:flex;align-items:center;justify-content:center;cursor:pointer;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);box-shadow:0 2px 12px rgba(0,0,0,0.5);flex-shrink:0;"
        onclick="closeImageModal()">&times;</button>
    <img id="image-modal-img" src="" alt="Image" style="display:block;" onclick="event.stopPropagation()">
</div>
@endpush

<script>
/* ── Translations ──────────────────────────────────── */
const unblockConfirmText    = {!! json_encode(__('users.unblock_user_confirm')) !!};
const blockConfirmText      = {!! json_encode(__('users.block_user_confirm')) !!};
const callConfirmText       = {!! json_encode(__('users.call_user_confirm')) !!};
const errorUnblockingText   = {!! json_encode(__('users.error_unblocking')) !!};
const errorBlockingText     = {!! json_encode(__('users.error_blocking')) !!};
const followingText         = {!! json_encode(__('users.following')) !!};
const followText            = {!! json_encode(__('users.follow')) !!};
const blockText             = {!! json_encode(__('users.block')) !!};
const unblockText           = {!! json_encode(__('users.unblock')) !!};
const messageText           = {!! json_encode(__('users.message')) !!};
const voiceCallText         = {!! json_encode(__('messages.voice_call')) !!};
const blockedBadgeText      = {!! json_encode(__('users.blocked')) !!};
const loadingQRCodeText     = {!! json_encode(__('users.loading_qr_code')) !!};
const errorLoadingQRText    = {!! json_encode(__('users.error_loading_qr_code')) !!};
const pinPostText           = {!! json_encode(__('users.post_pinned')) !!};
const unpinPostText         = {!! json_encode(__('users.post_unpinned')) !!};
const maxPinnedReachedText  = {!! json_encode(__('posts.max_pinned_reached', ['max' => $user->is_admin ? 10 : 5])) !!};
const confirmPinText        = {!! json_encode(__('users.confirm_pin_post')) !!};
@if(auth()->check() && auth()->id() !== $user->id)
const profileChatUrl        = {!! json_encode(route('chat.start', $user->id)) !!};
const profileCallId         = {{ $user->id }};
const profileCallName       = {!! json_encode($user->name) !!};
const profileCallAvatar     = {!! json_encode($user->avatar_url) !!};
const profileCallUsername   = {!! json_encode($user->username) !!};
const profileCallVerified   = {{ $user->is_verified ? 'true' : 'false' }};
@endif

/* ── Tab switching ─────────────────────────────────── */
document.getElementById('npTabs')?.addEventListener('click', function(e) {
    const tab = e.target.closest('.np-tab');
    if (!tab) return;
    const panel = tab.dataset.panel;
    this.querySelectorAll('.np-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('.np-panel').forEach(p => p.classList.remove('active'));
    const target = document.getElementById('panel-' + panel);
    if (target) target.classList.add('active');
});

/* ── Overflow menu ─────────────────────────────────── */
function toggleOverflow() {
    document.getElementById('overflowMenu')?.classList.toggle('open');
}
function closeOverflow() {
    document.getElementById('overflowMenu')?.classList.remove('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#overflowMenu')) closeOverflow();
});

/* ── Image modal ───────────────────────────────────── */
function openImageModal(src) {
    document.getElementById('image-modal-img').src = src;
    document.getElementById('image-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeImageModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('image-modal').style.display = 'none';
    document.body.style.overflow = '';
}

/* ── Follow toggle ─────────────────────────────────── */
let _savedActionsHtml = null;
function _esc(s) { return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }

function profileToggleFollow(btn, userName) {
    const isFollowing = btn.getAttribute('data-following') === 'true';
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch(`/users/${encodeURIComponent(userName)}/follow`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const nowFollowing = data.is_following;
            btn.setAttribute('data-following', nowFollowing ? 'true' : 'false');
            btn.innerHTML = `<i class="fas fa-user-${nowFollowing ? 'check' : 'plus'}"></i><span>${nowFollowing ? followingText : followText}</span>`;
            btn.disabled = false;
            const stat = document.getElementById('follower-count');
            if (stat && data.followers_count !== undefined) stat.textContent = data.followers_count;
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Error', 'error');
            btn.innerHTML = `<i class="fas fa-user-${isFollowing ? 'check' : 'plus'}"></i><span>${isFollowing ? followingText : followText}</span>`;
            btn.disabled = false;
        }
    })
    .catch(() => {
        btn.innerHTML = `<i class="fas fa-user-${isFollowing ? 'check' : 'plus'}"></i><span>${isFollowing ? followingText : followText}</span>`;
        btn.disabled = false;
    });
}

/* ── Block / Unblock ───────────────────────────────── */
function profileUnblockUser(userName) {
    if (!confirm(unblockConfirmText.replace(':username', userName))) return;
    fetch(`/users/${encodeURIComponent(userName)}/block`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            const coverActions = document.querySelector('.np-cover-actions');
            if (coverActions) {
                if (_savedActionsHtml) {
                    coverActions.innerHTML = _savedActionsHtml;
                    _savedActionsHtml = null;
                } else {
                    coverActions.innerHTML = `
                        <button class="np-cover-btn np-cover-btn-primary" id="followBtn"
                            onclick="profileToggleFollow(this,'${_esc(userName)}')" data-following="false">
                            <i class="fas fa-user-plus"></i><span>${followText}</span>
                        </button>
                        <div class="np-overflow-wrap" id="overflowMenu">
                            <button class="np-cover-btn np-cover-btn-ghost" onclick="toggleOverflow()" aria-label="More options">
                                <i class="fas fa-ellipsis"></i>
                            </button>
                            <div class="np-overflow-dropdown">
                                <a href="${profileChatUrl}" class="np-overflow-item"><i class="fas fa-envelope"></i> ${messageText}</a>
                                <button class="np-overflow-item" onclick="profileConfirmCall(${profileCallId},'${_esc(profileCallName)}','${_esc(profileCallAvatar)}','${_esc(profileCallUsername)}',${profileCallVerified});closeOverflow()">
                                    <i class="fas fa-phone"></i> ${voiceCallText}
                                </button>
                                <div class="np-overflow-sep"></div>
                                <button class="np-overflow-item danger" onclick="profileBlockUser('${_esc(userName)}');closeOverflow()">
                                    <i class="fas fa-ban"></i> ${blockText}
                                </button>
                            </div>
                        </div>`;
                }
            }
            document.querySelector('[data-badge="blocked"]')?.remove();
        }
    })
    .catch(() => showToast(errorUnblockingText, 'error'));
}

function profileBlockUser(userName) {
    if (!confirm(blockConfirmText.replace(':username', userName))) return;
    fetch(`/users/${encodeURIComponent(userName)}/block`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            const coverActions = document.querySelector('.np-cover-actions');
            if (coverActions) {
                _savedActionsHtml = coverActions.innerHTML;
                coverActions.innerHTML = `
                    <button class="np-cover-btn np-cover-btn-primary" style="background:var(--danger);"
                        onclick="profileUnblockUser('${_esc(userName)}')">
                        <i class="fas fa-unlock"></i><span>${unblockText}</span>
                    </button>`;
            }
            const badgesRow = document.querySelector('.np-name-row');
            if (badgesRow && !badgesRow.querySelector('[data-badge="blocked"]')) {
                badgesRow.insertAdjacentHTML('beforeend',
                    `<span class="np-badge np-badge-blocked" data-badge="blocked"><i class="fas fa-ban"></i> ${blockedBadgeText}</span>`);
            }
        } else {
            showToast(data.message || errorBlockingText, 'error');
        }
    })
    .catch(() => showToast(errorBlockingText, 'error'));
}

/* ── QR Code ───────────────────────────────────────── */
function showQrCodeModal() {
    const modal   = document.getElementById('qr-code-modal');
    const loading = document.getElementById('qr-code-loading');
    const content = document.getElementById('qr-code-content');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    loading.style.display = 'flex';
    content.style.display = 'none';

    fetch(`{{ route('users.qr-code', $user) }}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('qr-code-display').innerHTML = data.qr_code;
            document.getElementById('qr-profile-url').textContent = data.profile_url;
            loading.style.display = 'none';
            content.style.display = 'block';
        } else throw new Error();
    })
    .catch(() => {
        loading.innerHTML = `<i class="fas fa-exclamation-circle" style="font-size:32px;color:#ef4444;"></i><p style="color:var(--text-muted);margin-top:12px;">${errorLoadingQRText}</p>`;
    });
}

function closeQrCodeModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('qr-code-modal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('qr-code-loading').innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--primary);"></i>';
}

function downloadQrCode() {
    const url = `{{ route('users.qr-code.download', $user) }}`;
    fetch(url).then(r => r.blob()).then(blob => {
        const a = document.createElement('a');
        a.href = window.URL.createObjectURL(blob);
        a.download = 'profile-qr-{{ $user->username }}.svg';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        window.URL.revokeObjectURL(a.href);
    }).catch(() => window.open(url, '_blank'));
}

/* ── Calls ─────────────────────────────────────────── */
function profileConfirmCall(id, name, avatar, username, verified) {
    if (!confirm(callConfirmText.replace(':username', username || name))) return;
    initiateCallFromProfile(id, name, avatar, username, verified);
}

function initiateCallFromProfile(calleeId, calleeName, calleeAvatar, calleeUsername, calleeVerified) {
    if (window.CallManager) window.CallManager.unlockAudioNow();
    fetch('/call/initiate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ callee_id: calleeId })
    })
    .then(r => r.json())
    .then(data => {
        const t = window.CallTranslations || {};
        if (data.error === 'busy')    showToast(t.user_busy || 'User is already in a call.', 'error');
        else if (data.error === 'offline') showToast((t.user_offline || ':username is currently offline.').replace(':username', calleeUsername || calleeName), 'error');
        else if (data.callId && window.CallManager)
            window.CallManager.startCall(data.callId, calleeId, data.conversationId, calleeName, calleeAvatar, calleeUsername, calleeVerified);
    })
    .catch(err => console.error('Call initiation failed', err));
}

/* ── Keyboard ──────────────────────────────────────── */
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    if (document.getElementById('qr-code-modal').style.display === 'flex') closeQrCodeModal();
    if (document.getElementById('image-modal').style.display === 'flex') closeImageModal();
});

/* ── Session flash ─────────────────────────────────── */
@if(session('success'))
document.addEventListener('DOMContentLoaded', () => showToast({!! json_encode(session('success')) !!}, 'success'));
@endif
</script>
@endsection
