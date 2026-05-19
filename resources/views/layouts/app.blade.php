<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    
    {{-- Speed & Performance Optimization --}}
    <link rel="preconnect" href="https://stickit-fearlessly-braiden.ngrok-free.dev">
    <link rel="dns-prefetch" href="https://stickit-fearlessly-braiden.ngrok-free.dev">

    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        window.runOnPageLoad = function(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
            } else {
                setTimeout(callback, 0);
            }
        };

    </script>

    <style>
        /* Immediate Theme Background to prevent Flash */
        html[data-theme="dark"] { background: #0d0d0d; color: #f5f5f7; }
        html[data-theme="light"] { background: #ffffff; color: #111111; }
        body { background: inherit; color: inherit; }
    </style>

    <meta name="theme-color" content="#111111">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/images/nexus-logo-white.png">
    
    <script>
        // Register Service Worker for PWA support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Nexus Service Worker registered'))
                    .catch(err => console.log('Nexus Service Worker failed', err));
            });
        }
    </script>
    @auth
        <script>
            window.SOCKET_CONFIG = {
                url: '{{ config('app.socket_io_url') }}',
                userId: {{ auth()->id() }},
                sessionId: '{{ session()->getId() }}',
                isAdmin: {{ auth()->user()->is_admin ? 'true' : 'false' }},
                username: '{{ auth()->user()->username }}',
                token: '{{ auth()->user()->createSocketToken() }}',
                following: @json(auth()->user()->following()->pluck('followed_id'))
            };

            // Synchronize with Android Native Bridge if available
        </script>
    @endauth
    <title>@yield('title', 'Nexus')</title>
    
    {{-- Performance: Local & System Font Stacks --}}
    <style>
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: 200 1000;
            font-display: swap;
            src: url('{{ asset("fonts/cairo/cairo-arabic.woff2") }}') format('woff2');
            unicode-range: U+0600-06FF, U+0750-077F, U+0870-088E, U+0890-0891, U+0897-08E1, U+08E3-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FE74, U+FE76-FEFC, U+102E0-102FB, U+10E60-10E7E, U+10EC2-10EC4, U+10EFC-10EFF, U+1EE00-1EE03, U+1EE05-1EE1F, U+1EE21-1EE22, U+1EE24, U+1EE27, U+1EE29-1EE32, U+1EE34-1EE37, U+1EE39, U+1EE3B, U+1EE42, U+1EE47, U+1EE49, U+1EE4B, U+1EE4D-1EE4F, U+1EE51-1EE52, U+1EE54, U+1EE57, U+1EE59, U+1EE5B, U+1EE5D, U+1EE5F, U+1EE61-1EE62, U+1EE64, U+1EE67-1EE6A, U+1EE6C-1EE72, U+1EE74-1EE77, U+1EE79-1EE7C, U+1EE7E, U+1EE80-1EE89, U+1EE8B-1EE9B, U+1EEA1-1EEA3, U+1EEA5-1EEA9, U+1EEAB-1EEBB, U+1EEF0-1EEF1;
        }
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: 200 1000;
            font-display: swap;
            src: url('{{ asset("fonts/cairo/cairo-latin-ext.woff2") }}') format('woff2');
            unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face {
            font-family: 'Cairo';
            font-style: normal;
            font-weight: 200 1000;
            font-display: swap;
            src: url('{{ asset("fonts/cairo/cairo-latin.woff2") }}') format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }

        :root {
            --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            --font-arabic: "Cairo", "Segoe UI", Tahoma, sans-serif;
        }
        body { font-family: var(--font-sans); }
        [lang="ar"] body { font-family: var(--font-arabic); }
    </style>
    
    {{-- Performance: Critical CSS Preloading --}}
    <link rel="preload" href="{{ asset('css/app-layout.css') }}" as="style">
    <link rel="preload" href="{{ asset('css/mobile-header.css') }}" as="style">
    
    {{-- Critical CSS Fallback: Ensures branded background/text even if external CSS has a delay --}}
    <style>
        :root {
            --bg: #0d0d0d;
            --text: #f5f5f7;
        }
        html, body {
            background-color: #0d0d0d !important;
            color: #f5f5f7 !important;
            margin: 0;
            padding: 0;
        }
        [data-theme="light"], [data-theme="light"] body {
            background-color: #ffffff !important;
            color: #111111 !important;
        }
    </style>

    {{-- Icons --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    
    {{-- Main Styles --}}
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/comments.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partial-posts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modals.css') }}">

    {{-- Page-specific styles --}}
    @stack('styles')

    <style>
    /* Mobile message badge */
    .mobile-msg-badge {
        position: absolute;
        top: -6px;
        left: 50%;
        margin-left: 10px;
        background: linear-gradient(135deg, #ff4b2b 0%, #ef4444 100%);
        color: white;
        font-size: 11px;
        font-weight: 800;
        font-family: var(--font-sans);
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.3px;
        min-width: 20px;
        height: 20px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 4.5px;
        box-shadow: 0 3px 10px rgba(239, 68, 68, 0.4);
        border: 2px solid var(--bg);
        z-index: 10;
        line-height: 1;
        text-align: center;
    }
    .mobile-nav-inner a {
        position: relative;
    }
    /* Desktop message badge */
    .desktop-msg-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: linear-gradient(135deg, #ff4b2b 0%, #ef4444 100%);
        color: white;
        font-size: 11px;
        font-weight: 800;
        font-family: var(--font-sans);
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.3px;
        min-width: 18px;
        height: 18px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 4.5px;
        box-shadow: 0 3px 10px rgba(239, 68, 68, 0.4);
        border: 2px solid var(--bg);
        z-index: 10;
        line-height: 1;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .desktop-msg-badge.pulse,
    .mobile-msg-badge.pulse {
        animation: badgePulse 0.5s ease-in-out;
    }
    
    html[dir="rtl"] .desktop-msg-badge {
        right: auto;
        left: -6px;
    }

    /* Light Theme Badges */
    [data-theme="light"] .desktop-msg-badge {
        border: 2px solid var(--bg);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    .notif-reaction {
        background: rgba(244, 63, 94, 0.1) !important;
        color: #f43f5e !important;
    }
    .notif-reaction i {
        color: #f43f5e !important;
    }
    .notif-item .notif-icon img {
        border-radius: 4px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    .toast-reaction-badge img {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    /* Ultra-Simple Fade-In Entrance */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .main-content {
        animation: fadeIn 0.3s ease-out forwards;
    }
    </style>
</head>
<body id="app-body">

    <header class="header">
        <div class="header-inner">
            <a href="{{ route('home') }}" class="logo">
                <img src="{{ asset('images/nexus-logo-black.png') }}" alt="Nexus" class="logo-black">
                <img src="{{ asset('images/nexus-logo-white.png') }}" alt="Nexus" class="logo-white">
            </a>

            @auth
            @php 
                $unreadMessages = \App\Models\Message::where('sender_id', '!=', auth()->id())
                    ->whereNull('read_at')
                    ->whereHas('conversation', function($q) {
                        $q->where('user1_id', auth()->id())
                          ->orWhere('user2_id', auth()->id())
                          ->orWhereHas('group.members', function($q2) {
                              $q2->where('user_id', auth()->id());
                          });
                    })
                    ->count();
            @endphp
            <nav class="nav-links">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><i class="fas fa-home"></i> {{ __('navigation.home') }}</a>
                <a href="{{ route('stories.index') }}" class="{{ request()->routeIs('stories.*') ? 'active' : '' }}"><i class="fas fa-circle-play"></i> {{ __('navigation.stories') }}</a>
                <a href="{{ route('communities.index') }}" class="{{ request()->routeIs('communities.*') ? 'active' : '' }}"><i class="fas fa-users"></i> {{ __('navigation.groups') }}</a>
                <a href="{{ route('chat.index') }}" class="{{ request()->routeIs('chat.*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fas fa-message"></i> 
                    {{ __('navigation.messages') }}
                    <span class="desktop-msg-badge" id="desktopMsgBadge" style="{{ $unreadMessages > 0 ? 'display: flex !important;' : 'display: none !important;' }}">
                        {{ $unreadMessages > 0 ? ($unreadMessages > 99 ? '99+' : $unreadMessages) : '' }}
                    </span>
                </a>
                <a href="{{ route('global-chat.index') }}" class="{{ request()->routeIs('global-chat.index') ? 'active' : '' }}"><i class="fas fa-globe-americas"></i> {{ __('navigation.global_chat') }}</a>
                <a href="{{ route('ai.index') }}" class="{{ request()->routeIs('ai.*') ? 'active' : '' }}"><i class="fas fa-robot"></i> {{ __('navigation.ai_assistant') }}</a>
            </nav>
            @endauth

            <div class="user-actions">
                @guest
                <div class="guest-nav-actions" style="display: flex; align-items: center; gap: 12px;">
                    @include('partials.language-switcher')
                    <div id="themeToggleGlobal" class="theme-switcher-pill" onclick="toggleTheme()" title="{{ __('home.toggle_theme') }}">
                        <div class="theme-slide-bg"></div>
                        <div class="theme-option-btn btn-sun" data-theme-btn="light">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2" stroke-linecap="round"/><path d="M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-linecap="round"/></svg>
                        </div>
                        <div class="theme-option-btn btn-moon" data-theme-btn="dark">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        </div>
                    </div>
                </div>
                @endguest

                @auth
                <div class="status-indicator">
                    <span id="connection-status-dot" class="status-dot pending" title="{{ __('notifications.connecting') }}"></span>
                </div>

                <div style="position: relative;">
                    @php $unreadCount = auth()->user()->notifications()->unread()->count(); @endphp
                    <button class="nav-action-btn" id="notifBtn" onclick="toggleNotifications(event)">
                    <i class="fas fa-bell" id="notif-bell-icon"></i>
                        <span class="badge" id="notif-badge" {!! $unreadCount > 0 ? '' : 'style="display: none;"' !!}>
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    </button>
                </div>

                <div style="position: relative;">
                    <button class="nav-user-btn" id="userBtn" onclick="toggleUserMenu(event)">
                        <div class="user-avatar">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}">
                        </div>
                        <span>{{ auth()->user()->username }}</span>
                        <i class="fas fa-chevron-down" style="font-size: 10px; color: var(--text-muted);"></i>
                    </button>
                </div>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                @else
                <a href="{{ route('login') }}" class="nav-action-btn">{{ __('auth.sign_in') }}</a>
                <a href="{{ route('register') }}" class="nav-action-btn primary">{{ __('auth.sign_up') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <div class="dropdown-overlay" id="dropdownOverlay" onclick="closeAllDropdowns()"></div>

    @auth
    {{-- Real-time Feed Update Banner --}}
    <div id="new-posts-banner" class="new-posts-banner"></div>
    
    <!-- Notification Dropdown - Modern Design -->
    <div class="dropdown-menu notif-panel" id="notifMenu">
        <div class="notif-header">
            <h3>{{ __('navigation.notifications') }}</h3>
            <div class="notif-header-actions">
                <button class="notif-action-btn" onclick="markAllRead(); return false;" title="{{ __('notifications.mark_all_read') }}">
                    <i class="fas fa-check"></i>
                    <span>{{ __('notifications.mark_all_read') }}</span>
                </button>
                <button class="notif-action-btn danger" onclick="clearAllNotifications(); return false;" title="{{ __('notifications.clear_all') }}">
                    <i class="fas fa-trash"></i>
                    <span>{{ __('notifications.clear_all') }}</span>
                </button>
                <button class="notif-action-btn" id="dndToggleBtn" onclick="toggleDND(); return false;" title="{{ __('notifications.dnd') }}">
                    <i class="fas fa-moon"></i>
                    <span id="dndText">{{ __('notifications.dnd') }}</span>
                </button>
            </div>
        </div>
        <div class="notif-list" id="notif-list">
            <div class="notif-empty">
                <i class="fas fa-bell-slash"></i>
                <p>{{ __('notifications.no_notifications') }}</p>
            </div>
        </div>
        <div class="notif-footer" style="padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="{{ route('notifications.index') }}" style="display: block; text-align: center; color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">
                <i class="fas fa-th-list"></i> {{ __('notifications.view_all') }}
            </a>
        </div>
    </div>

    <!-- User Menu Dropdown - outside header for proper z-index -->
    <div class="dropdown-menu" id="userMenu">
        <a href="{{ route('users.show', auth()->user()) }}"><i class="fas fa-user"></i> {{ __('navigation.profile') }}</a>
        <a href="{{ route('communities.index') }}"><i class="fas fa-users"></i> {{ __('navigation.groups') }}</a>
        <a href="{{ route('users.saved-posts') }}"><i class="fas fa-bookmark"></i> {{ __('navigation.saved_posts') }}</a>
        <a href="{{ route('explore') }}"><i class="fas fa-compass"></i> {{ __('navigation.explore') }}</a>
        <a href="{{ route('hashtags.index') }}"><i class="fas fa-hashtag"></i> {{ __('hashtags.hashtags') }}</a>
        <a href="{{ route('global-chat.index') }}"><i class="fas fa-globe-americas"></i> {{ __('navigation.global_chat') }}</a>

        <a href="{{ route('ai.index') }}"><i class="fas fa-robot"></i> {{ __('navigation.ai_assistant') }}</a>
        @if(auth()->user()->is_admin)
        <a href="{{ route('admin.dashboard') }}"><i class="fas fa-shield-alt"></i> {{ __('navigation.admin_panel') }}</a>
        @endif
        <div class="divider"></div>

        <!-- Push Notifications Settings -->
        <a href="javascript:void(0)" onclick="closeAllDropdowns(); setTimeout(() => showPushSettings(), 100);">
            <i class="fas fa-bell"></i> {{ __('notifications.enable_push') }}
        </a>

        <!-- Link to Notifications Page -->
        <a href="{{ route('notifications.index') }}">
            <i class="fas fa-bell"></i> {{ __('navigation.notifications') }}
        </a>

        <!-- Link to My Reports Page -->
        <a href="{{ route('reports.my-reports') }}">
            <i class="fas fa-flag"></i> {{ __('messages.my_reports') }}
        </a>
        
        <div class="divider"></div>
        
        <div class="lang-menu-item-pill" onclick="switchUserLanguage('{{ app()->getLocale() === 'en' ? 'ar' : 'en' }}'); event.stopPropagation();">
            <div class="menu-pill-label">
                <i class="fas fa-globe"></i>
                <span>{{ __('messages.language') }}</span>
            </div>
            <div class="language-switcher-pill">
                <div class="lang-slide-bg"></div>
                <div class="lang-option-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}" data-loc-btn="en">EN</div>
                <div class="lang-option-btn {{ app()->getLocale() === 'ar' ? 'active' : '' }}" data-loc-btn="ar">ع</div>
            </div>
        </div>

        <div class="theme-menu-item-pill" onclick="toggleTheme(); event.stopPropagation();">
            <div class="menu-pill-label">
                <i class="fas fa-palette" id="theme-icon-main"></i>
                <span>{{ __('messages.theme') }}</span>
            </div>
            <div class="theme-switcher-pill" id="theme-switch">
                <div class="theme-slide-bg"></div>
                <div class="theme-option-btn btn-sun" data-theme-btn="light">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-linecap="round"/></svg>
                </div>
                <div class="theme-option-btn btn-moon" data-theme-btn="dark">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </div>
            </div>
        </div>

        <div class="divider"></div>
        <button onclick="logout()" class="danger"><i class="fas fa-sign-out-alt"></i> {{ __('navigation.logout') }}</button>
    </div>
    @endauth

    @auth
    {{-- Mobile Bottom Navigation --}}
    <nav class="mobile-bottom-nav">
        <div class="mobile-nav-inner">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
                <span>{{ __('navigation.home') }}</span>
            </a>
            <a href="{{ route('stories.index') }}" class="{{ request()->routeIs('stories.*') ? 'active' : '' }}">
                <i class="{{ request()->routeIs('stories.*') ? 'fa-solid' : 'fa-regular' }} fa-circle-play"></i>
                <span>{{ __('navigation.stories') }}</span>
            </a>
            <a href="{{ route('communities.index') }}" class="{{ request()->routeIs('communities.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>{{ __('navigation.groups') }}</span>
            </a>
            <a href="{{ route('chat.index') }}" class="{{ request()->routeIs('chat.*') ? 'active' : '' }}">
                <i class="{{ request()->routeIs('chat.*') ? 'fa-solid' : 'fa-regular' }} fa-comment"></i>
                <span class="mobile-msg-badge" id="mobileMsgBadge" style="{{ $unreadMessages > 0 ? 'display: flex !important;' : 'display: none !important;' }}">
                    {{ $unreadMessages > 0 ? ($unreadMessages > 99 ? '99+' : $unreadMessages) : '' }}
                </span>
                <span>{{ __('navigation.messages') }}</span>
            </a>
            <a href="{{ route('users.show', auth()->user()) }}" class="{{ request()->routeIs('users.show') && request()->route('user') && (is_object(request()->route('user')) ? request()->route('user')->id : request()->route('user')) == auth()->id() ? 'active' : '' }}">
                <i class="{{ request()->routeIs('users.show') && request()->route('user') && (is_object(request()->route('user')) ? request()->route('user')->id : request()->route('user')) == auth()->id() ? 'fa-solid' : 'fa-regular' }} fa-user"></i>
                <span>{{ __('navigation.profile') }}</span>
            </a>
        </div>
    </nav>
    @endauth

    <main class="app-layout @yield('main_class')">
        <div class="main-content @yield('content_class')">
            <script>
                // Essential translations for global JS functions (posts, comments, chat)
                window.chatTranslations = {
                    you: '{{ __('chat.you') }}',
                    online: '{{ __('chat.online') }}',
                    offline: '{{ __('chat.offline') }}',
                    typing: '{{ __('chat.typing') }}',
                    sent: '{{ __('chat.sent') }}',
                    seen: '{{ __('chat.seen') }}',
                    delivered: '{{ __('chat.delivered') }}',
                    read: '{{ __('chat.read') }}',
                    message_deleted: '{{ __('chat.message_deleted') }}',
                    mark_as_read: '{{ __('messages.mark_as_read') }}',
                    delete: '{{ __('messages.delete') }}',
                    delete_message: '{{ __('chat.delete_message') }}',
                    today: '{{ __('messages.today') }}',
                    yesterday: '{{ __('messages.yesterday') }}',
                    cleared_the_chat: '{{ __('chat.cleared_the_chat') }}',
                    invited_you_to_join: '{{ __('chat.invited_you_to_join') }}',
                    group: '{{ __('chat.group') }}',
                    join: '{{ __('chat.join') }}',
                    playback_speed: '{{ __('chat.playback_speed') }}',
                    story_reply: '{{ __('chat.story_reply') }}',
                    failed_to_send_media: '{{ __('chat.failed_to_send_media') }}',
                    error_sending_media: '{{ __('chat.error_sending_media') }}',
                    follow: '{{ __('chat.follow') }}',
                    following: '{{ __('chat.following') }}',
                    post_saved_success: '{{ __('messages.post_saved_success') }}',
                    post_removed_from_saved: '{{ __('messages.post_removed_from_saved') }}',
                    post_link_copied: '{{ __('messages.post_link_copied') }}',
                    failed_to_copy_link: '{{ __('messages.failed_to_copy_link') }}',
                    no_likes_yet: '{{ __('messages.no_likes_yet') }}',
                    could_not_load_likers: '{{ __('messages.could_not_load_likers') }}',
                    likes: '{{ __('messages.likes') }}',
                    click_to_remove: '{{ __('chat.click_to_remove') }}',
                    report_reason_prefix: '{{ __('messages.report_reason_prefix') ?? 'New report for: ' }}'
                };

                // Post translations for posts.js
                window.postTranslations = {
                    delete_post_confirm: '{{ __('messages.delete_post_confirm') }}',
                    delete_comment_confirm: '{{ __('messages.delete_comment_confirm') }}',
                    post_deleted: '{{ __('messages.post_deleted') }}',
                    failed_to_delete_post: '{{ __('messages.failed_to_delete_post') }}',
                    new_posts_loaded: '{{ __('messages.new_posts_loaded') }}',
                    failed_to_load_posts: '{{ __('messages.failed_to_load_posts') }}',
                    load_more: '{{ __('messages.load_more') }}',
                    confirm_pin_post: '{{ __('users.confirm_pin_post') }}',
                    confirm_unpin_post: '{{ __('users.confirm_unpin_post') }}',
                    post_pinned: '{{ __('users.post_pinned') }}',
                    post_unpinned: '{{ __('users.post_unpinned') }}',
                    pin_post: '{{ __('users.pin_post') }}',
                    unpin_post: '{{ __('users.unpin_post') }}',
                    pinned: '{{ __('users.pinned') }}',
                };
            </script>
            <script>
                 window.reactionImages = {!! json_encode(\App\Models\Post::REACTION_IMAGES) !!};
                 
                 window.getReactionImage = function(emoji) {
                     if (!window.reactionImages || !emoji) return null;
                     const basic = emoji.replace(/[\uFE00-\uFE0F]/g, '');
                     if (window.reactionImages[emoji]) return window.reactionImages[emoji];
                     if (window.reactionImages[basic]) return window.reactionImages[basic];
                     const withSelector = basic + '\uFE0F';
                     if (window.reactionImages[withSelector]) return window.reactionImages[withSelector];
                     return null;
                 };
            </script>
            @yield('content')
        </div>
    </main>



    <div id="toast-container"></div>

    @auth
        <script>
            window.currentUserId = {{ auth()->id() }};
            window.currentUserUsername = "{{ auth()->user()->username }}";
            window.layoutTranslations = {
                failed_to_join_group: "{{ __('messages.failed_to_join_group') }}",
                members: "{{ __('messages.members_label') }}"
            };
        </script>
    @endauth

    <script>
        // GLOBAL UTILITIES - MUST LOAD FIRST
        window.escapeHtml = function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        };

        window.sanitizeMessage = function(message) {
            if (!message || typeof message !== 'string') return message;
            const replyRegex = /\{\s*"__nexus_reply__"\s*:\s*true/;
            if (replyRegex.test(message)) {
                try {
                    const jsonStart = message.indexOf('{');
                    if (jsonStart !== -1) {
                        const prefixPart = message.substring(0, jsonStart);
                        const jsonPart = message.substring(jsonStart);
                        const replyData = JSON.parse(jsonPart);
                        return prefixPart + '↩ ' + (replyData.content || '');
                    }
                } catch(e) {}
            }
            return message;
        };

        window.showToast = function(message, type = 'info', avatar = null, link = null, duration = 4000, extraData = null) {
            // Native Android Integration - Mirror to System Notifications

            const container = document.getElementById('toast-container');
            if (!container) return;

            // Silence incoming notification toasts if DND is on
            const isDND = localStorage.getItem('nexus_dnd_enabled') === 'true';
            if (isDND && (avatar || link) && typeof message === 'string' && !message.includes('Disturb')) {
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast ${type} ${avatar ? 'has-avatar' : ''} ${link ? 'is-clickable' : ''}`;
            
            if (link) {
                toast.style.cursor = 'pointer';
                toast.onclick = (e) => {
                    // Prevent propagation to container if any
                    e.stopPropagation();
                    
                    const notifId = extraData?.notification_id || extraData?.id;
                    console.log('[Toast] Clicked. NotifID:', notifId, 'Link:', link);
                    
                    if (window.handleNotifClick) {
                        window.handleNotifClick(notifId, link);
                    } else {
                        window.location.href = link;
                    }
                };
            }

            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            
            // Handle reply JSON in toasts
            const displayMessage = window.sanitizeMessage(message);

            toast.innerHTML = `
                ${avatar ? `<div class="toast-avatar"><img src="${avatar}" alt="user"></div>` : `<i class="fas ${icon}"></i>`}
                <div class="toast-content">
                    <span>${window.escapeHtml(displayMessage)}</span>
                </div>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 250);
            }, duration);
        };
    </script>

    @vite(['resources/js/app.js', 'resources/js/legacy/ui-utils.js', 'resources/js/legacy/comments.js', 'resources/js/legacy/posts.js'])
    @auth
        @vite(['resources/js/socket-manager.js'])
    @endauth
    <script>
        function toggleUserMenu(event) {
            event.stopPropagation();
            event.preventDefault();
            const menu = document.getElementById('userMenu');
            const btn = event.currentTarget;
            const isOpen = menu.classList.contains('show');
            closeAllDropdowns();
            if (!isOpen) {
                const rect = btn.getBoundingClientRect();
                const isRTL = document.documentElement.dir === 'rtl';
                
                // Position dropdown based on language direction
                menu.style.top = (rect.bottom + 8) + 'px';
                if (isRTL) {
                    // Arabic: align to left
                    let left = rect.left;
                    const menuWidth = 280;
                    const padding = 16;
                    if (left + menuWidth > window.innerWidth - padding) {
                        left = window.innerWidth - menuWidth - padding;
                    }
                    if (left < padding) left = padding;
                    
                    menu.style.left = left + 'px';
                    menu.style.right = 'auto';
                } else {
                    // English: align to right
                    let right = window.innerWidth - rect.right;
                    const menuWidth = 280;
                    const padding = 16;
                    if (right + menuWidth > window.innerWidth - padding) {
                        right = window.innerWidth - menuWidth - padding;
                    }
                    if (right < padding) right = padding;

                    menu.style.right = right + 'px';
                    menu.style.left = 'auto';
                }
                menu.classList.add('show');
                document.getElementById('dropdownOverlay').classList.add('active');
            }
        }

        function toggleNotifications(event) {
            event.stopPropagation();
            event.preventDefault();
            const menu = document.getElementById('notifMenu');
            const btn = document.getElementById('notifBtn');
            const isOpen = menu.classList.contains('show');
            const isRTL = document.documentElement.dir === 'rtl';
            
            closeAllDropdowns();
            if (!isOpen) {
                const rect = btn.getBoundingClientRect();
                const menuWidth = 380;
                const padding = 16;

                menu.style.top = (rect.bottom + 8) + 'px';
                
                if (isRTL) {
                    // Arabic: align to left of button, but check if it overflows left side of screen
                    let left = rect.left;
                    if (left + menuWidth > window.innerWidth - padding) {
                        left = window.innerWidth - menuWidth - padding;
                    }
                    if (left < padding) left = padding;
                    
                    menu.style.left = left + 'px';
                    menu.style.right = 'auto';
                } else {
                    // English: align to right of button, but check if it overflows right side of screen
                    let right = window.innerWidth - rect.right;
                    if (right + menuWidth > window.innerWidth - padding) {
                        right = window.innerWidth - menuWidth - padding;
                    }
                    if (right < padding) right = padding;

                    menu.style.right = right + 'px';
                    menu.style.left = 'auto';
                }
                
                menu.classList.add('show');
                document.getElementById('dropdownOverlay').classList.add('active');
                loadNotifications();
            }
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
            document.getElementById('dropdownOverlay').classList.remove('active');
            
            // Close user language dropdown
            const userLangDropdown = document.getElementById('user-language-dropdown');
            const userLangArrow = document.getElementById('user-lang-arrow');
            const userLangToggle = document.querySelector('#userMenu .language-option');
            if (userLangDropdown && userLangDropdown.style.display === 'block') {
                userLangDropdown.style.display = 'none';
                if (userLangArrow) userLangArrow.style.transform = 'rotate(0deg)';
                if (userLangToggle) userLangToggle.setAttribute('aria-expanded', 'false');
            }
        }

        function logout() { if (confirm('{{ __('auth.sign_out_confirm') }}')) document.getElementById('logout-form').submit(); }

        // Do Not Disturb Logic
        function initDND() {
            const isDND = localStorage.getItem('nexus_dnd_enabled') === 'true';
            updateDNDUI(isDND);
        }

        function toggleDND() {
            const isDND = localStorage.getItem('nexus_dnd_enabled') === 'true';
            const newState = !isDND;
            localStorage.setItem('nexus_dnd_enabled', newState);
            updateDNDUI(newState);
            
            // Show toast feedback
            if (window.showToast) {
                const msg = newState ? 'Do Not Disturb Enabled' : 'Do Not Disturb Disabled';
                window.showToast(msg, 'info', null, null, 1000);
            }
        }

        function updateDNDUI(enabled) {
            const bellIcon = document.getElementById('notif-bell-icon');
            const dndBtn = document.getElementById('dndToggleBtn');
            const dndText = document.getElementById('dndText');
            
            if (bellIcon) {
                if (enabled) {
                    bellIcon.classList.remove('fa-bell');
                    bellIcon.classList.add('fa-bell-slash');
                    bellIcon.style.color = '#ffb347'; // Warm moon color
                } else {
                    bellIcon.classList.remove('fa-bell-slash');
                    bellIcon.classList.add('fa-bell');
                    bellIcon.style.color = '';
                }
            }
            
            if (dndBtn) {
                if (enabled) {
                    dndBtn.classList.add('active');
                    if (dndText) dndText.textContent = 'DND On';
                } else {
                    dndBtn.classList.remove('active');
                    if (dndText) dndText.textContent = 'DND Off';
                }
            }
        }

        // Initialize DND on load
        document.addEventListener('DOMContentLoaded', initDND);

        function updateNotificationBadge(count) {
            const badge = document.getElementById('notif-badge');
            if (badge) {
                const oldCount = parseInt(badge.textContent) || 0;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                    
                    // Only pulse if count increased
                    if (count > oldCount) {
                        badge.classList.remove('pulse');
                        void badge.offsetWidth; // trigger reflow
                        badge.classList.add('pulse');
                    }
                } else {
                    badge.style.display = 'none';
                    badge.classList.remove('pulse');
                }
            }
        }

        window.updateMobileBadge = function() {
            fetch('/chat/conversations', {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                const mobileBadge = document.getElementById('mobileMsgBadge');
                const desktopBadge = document.getElementById('desktopMsgBadge');
                
                let totalUnread = 0;
                if (data.conversations) {
                    data.conversations.forEach(c => {
                        const count = parseInt(c.unread_count || 0);
                        totalUnread += count;
                    });
                } else if (typeof data.unread_count !== 'undefined') {
                    totalUnread = parseInt(data.unread_count || 0);
                }

                const displayStyle = totalUnread > 0 ? 'flex' : 'none';
                const displayText = totalUnread > 0 ? (totalUnread > 99 ? '99+' : totalUnread) : '';

                if (mobileBadge) {
                    const oldCount = parseInt(mobileBadge.textContent) || 0;
                    mobileBadge.textContent = displayText;
                    mobileBadge.style.setProperty('display', displayStyle, 'important');
                    
                    if (totalUnread > oldCount && displayStyle === 'flex') {
                        mobileBadge.classList.remove('pulse');
                        void mobileBadge.offsetWidth; // trigger reflow
                        mobileBadge.classList.add('pulse');
                    }
                }
                if (desktopBadge) {
                    const oldCount = parseInt(desktopBadge.textContent) || 0;
                    desktopBadge.textContent = displayText;
                    desktopBadge.style.setProperty('display', displayStyle, 'important');

                    if (totalUnread > oldCount && displayStyle === 'flex') {
                        desktopBadge.classList.remove('pulse');
                        void desktopBadge.offsetWidth; // trigger reflow
                        desktopBadge.classList.add('pulse');
                    }
                }
            })
            .catch(err => console.warn('Failed to update badges:', err));
        };

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        window.loadNotifications = function loadNotifications() {
            fetch('/notifications', {
                credentials: 'include',
                headers: { 
                    'X-CSRF-TOKEN': getCsrfToken(), 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                }
                return r.json();
            })
            .then(data => {
                const list = document.getElementById('notif-list');
                const badge = document.getElementById('notif-badge');
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
                if (!data.notifications || data.notifications.length === 0) {
                    list.innerHTML = `<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>{{ __('notifications.no_notifications') }}</p></div>`;
                    return;
                }
                list.innerHTML = data.notifications.map(n => {
                    const iconClass = getNotificationIconClass(n.type);
                    const notifIcon = getNotificationIcon(n.type);
                    const timeAgo = getTimeAgo(n.created_at);
                    
                    // Parse data if string
                    let notifData = n.data;
                    if (typeof notifData === 'string') {
                        try { notifData = JSON.parse(notifData); } catch(e) { notifData = {}; }
                    }

                    // Sanitize message before truncation
                    const cleanMessage = window.sanitizeMessage(n.message || '');
                    const truncatedMessage = cleanMessage.length > 120 ? cleanMessage.substring(0, 120) + '...' : cleanMessage;
                    
                    return `
                    <div class="notif-item ${n.read_at ? '' : 'unread'}" id="notif-${n.id}" data-id="${n.id}">
                        <div class="notif-icon ${iconClass}" onclick="handleNotifClick(${n.id}, '${n.link || ''}')">
                            <i class="fas ${notifIcon}"></i>
                        </div>
                        <div class="notif-content ${n.read_at ? '' : 'unread'}" onclick="handleNotifClick(${n.id}, '${n.link || ''}')">
                            <p>${escapeHtml(truncatedMessage)}</p>
                            <span class="notif-time">${timeAgo}</span>
                        </div>
                        <div class="notif-item-actions">
                            ${!n.read_at ? `<button class="notif-item-btn" onclick="markAsRead(${n.id}); return false;" title="${window.chatTranslations.mark_as_read}"><i class="fas fa-check"></i></button>` : ''}
                            <button class="notif-item-btn delete" onclick="dismissNotification(${n.id}); return false;" title="${window.chatTranslations.delete}"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                `}).join('');
            })
            .catch(err => {
                console.error('loadNotifications: Error:', err);
                // Silently fail - don't show error to user for notification loading issues
            });
        }

        function markAsRead(id) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Update UI immediately
            const notifItem = document.querySelector(`.notif-item[data-id="${id}"]`);
            if (notifItem) {
                // Remove unread class (this hides the dot)
                notifItem.classList.remove('unread');
                notifItem.querySelector('.notif-content')?.classList.remove('unread');

                // Update actions to show only delete button
                const actionsDiv = notifItem.querySelector('.notif-item-actions');
                if (actionsDiv) {
                    actionsDiv.innerHTML = `<button class="notif-item-btn delete" onclick="dismissNotification(${id}); return false;" title="${window.chatTranslations.delete}"><i class="fas fa-trash"></i></button>`;
                }

                // Update badge immediately
                const badge = document.getElementById('notif-badge');
                if (badge && badge.style.display !== 'none') {
                    const count = parseInt(badge.textContent) || 0;
                    if (count > 1) {
                        badge.textContent = count - 1;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).then(() => {
                // Refresh list to apply new sorting (unread on top)
                setTimeout(() => loadNotifications(), 500);
            }).catch(() => {});
        }

        function markAllRead() {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Update UI immediately
            const notifItems = document.querySelectorAll('.notif-item.unread');
            notifItems.forEach(item => {
                // Remove unread class (this hides the dot)
                item.classList.remove('unread');
                item.querySelector('.notif-content')?.classList.remove('unread');

                const id = item.getAttribute('data-id');
                const actionsDiv = item.querySelector('.notif-item-actions');
                if (actionsDiv && id) {
                    actionsDiv.innerHTML = `<button class="notif-item-btn delete" onclick="dismissNotification(${id}); return false;" title="${window.chatTranslations.delete}"><i class="fas fa-trash"></i></button>`;
                }
            });
            document.getElementById('notif-badge').style.display = 'none';

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).then(() => {
                // Refresh list to apply new sorting (unread on top)
                setTimeout(() => loadNotifications(), 500);
            }).catch(() => {});
        }

        function getNotificationIconClass(type) {
            const classes = {
                'follow': 'notif-follow',
                'like': 'notif-like',
                'comment': 'notif-comment',
                'mention': 'notif-mention',
                'message': 'notif-msg',
                'group_invite': 'notif-group',
                'post_reaction': 'notif-reaction',
                'chat_reaction': 'notif-reaction',
                'story_reaction': 'notif-reaction'
            };
            return classes[type] || 'default';
        }

        function getNotificationIcon(type) {
            const icons = {
                'follow': 'fa-user-plus',
                'like': 'fa-heart',
                'post_reaction': 'fa-heart',
                'chat_reaction': 'fa-heart',
                'story_reaction': 'fa-heart',
                'comment': 'fa-comment',
                'mention': 'fa-at',
                'message': 'fa-envelope',
                'group_invite': 'fa-users',
                'post': 'fa-newspaper',
                'story': 'fa-circle-play'
            };
            return icons[type] || 'fa-bell';
        }

        function getTimeAgo(dateStr) {
            const date = new Date(dateStr);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            if (seconds < 60) return 'Just now';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return minutes + 'm ago';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + 'h ago';
            const days = Math.floor(hours / 24);
            if (days < 7) return days + 'd ago';
            return date.toLocaleDateString();
        }

        window.handleNotifClick = function(id, link) {
            console.log('[handleNotifClick] Called for ID:', id, 'Link:', link);
            
            // Mark as read (fire and forget)
            if (id) {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    keepalive: true
                }).catch(err => console.error('[handleNotifClick] Mark read failed:', err));
                
                // Update UI badge immediately
                const badge = document.getElementById('notif-badge');
                if (badge && badge.style.display !== 'none') {
                    const count = parseInt(badge.textContent) || 0;
                    if (count > 1) {
                        badge.textContent = count - 1;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            // Navigate
            if (link) {
                console.log('[handleNotifClick] Navigating to:', link);
                closeAllDropdowns();
                
                // If it's a same-page hash link, just update hash
                const currentUrl = window.location.origin + window.location.pathname;
                const targetUrl = link.split('#')[0];
                
                if (currentUrl === targetUrl || targetUrl === window.location.href.split('#')[0]) {
                    const hash = link.split('#')[1];
                    if (hash) {
                        window.location.hash = hash;
                    }
                } else {
                    window.location.href = link;
                }
            } else {
                closeAllDropdowns();
                if (window.loadNotifications) loadNotifications();
            }
        }

        function dismissNotification(id) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Update UI immediately
            const notifItem = document.querySelector(`.notif-item[data-id="${id}"]`);
            if (notifItem) {
                notifItem.remove();

                // Update badge immediately
                const badge = document.getElementById('notif-badge');
                if (badge && badge.style.display !== 'none') {
                    const count = parseInt(badge.textContent) || 0;
                    if (count > 1) {
                        badge.textContent = count - 1;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications/' + id, {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).catch(() => {});
        }

        function clearAllNotifications() {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Clear UI immediately
            const notifList = document.getElementById('notif-list');
            if (notifList) {
                notifList.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>{{ __('notifications.no_notifications') }}</p></div>';
            }
            document.getElementById('notif-badge').style.display = 'none';

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications', {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).catch(() => {});
        }

        document.addEventListener('DOMContentLoaded', () => {
            if ({{ auth()->check() ? 'true' : 'false' }}) {
                window.currentUserId = {{ auth()->id() }};
                window.updateMobileBadge();
            }
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAllDropdowns(); });

            // Initialize Theme Switch and Icon
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const themeSwitch = document.getElementById('theme-switch');
            const themeIcon = document.getElementById('theme-icon-main');
            if (themeSwitch && savedTheme === 'dark') themeSwitch.classList.add('on');
            if (themeIcon) themeIcon.className = savedTheme === 'light' ? 'fas fa-sun' : 'fas fa-moon';

            // Auto-detect Arabic text and apply RTL direction
            applyRTLToArabicText();
        });

        // User Menu Language Dropdown Functions
        function toggleUserLanguageDropdown() {
            const dropdown = document.getElementById('user-language-dropdown');
            const arrow = document.getElementById('user-lang-arrow');
            const toggle = document.querySelector('#userMenu .language-option');

            if (!dropdown) return;

            const isRTL = document.documentElement.dir === 'rtl';
            const isVisible = dropdown.style.display === 'block';

            if (isVisible) {
                dropdown.style.display = 'none';
                if (arrow) arrow.style.transform = 'rotate(0deg)';
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            } else {
                // Just toggle the display since it's now inline
                dropdown.style.display = 'block';
                if (arrow) arrow.style.transform = 'rotate(180deg)';
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            }
        }

        function switchUserLanguage(locale) {
            // Show loading indicator
            const loading = document.getElementById('language-loading');
            if (loading) {
                loading.style.display = 'flex';
            }

            // Close dropdown
            toggleUserLanguageDropdown();

            // Navigate to language switch route with current URL as return
            const currentPath = window.location.pathname + window.location.search;
            window.location.href = '/lang/' + locale + '?return=' + encodeURIComponent(currentPath);
        }

        // Close user language dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userLangSwitcher = userMenu?.querySelector('.language-switcher');

            if (userLangSwitcher && !userLangSwitcher.contains(event.target)) {
                const dropdown = document.getElementById('user-language-dropdown');
                const arrow = document.getElementById('user-lang-arrow');
                const toggle = document.querySelector('#userMenu .language-option');

                if (dropdown && dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                    if (arrow) arrow.style.transform = 'rotate(0deg)';
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                }
            }
        });

        /**
         * Detect Arabic/Persian/Hebrew text and apply RTL direction
         */
        function applyRTLToArabicText() {
            // Arabic Unicode range: \u0600-\u06FF, Arabic Supplement: \u0750-\u077F
            // Persian/Arabic Extended: \u08A0-\u08FF
            // Hebrew: \u0590-\u05FF
            const arabicPattern = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/;
            
            // Apply to post content
            document.querySelectorAll('.post-content p, .comment-content p, .message-content .text').forEach(el => {
                const text = el.textContent || el.innerText || '';
                if (arabicPattern.test(text)) {
                    el.setAttribute('dir', 'rtl');
                    el.style.direction = 'rtl';
                    el.style.textAlign = 'right';
                }
            });
        }
    </script>

    {{-- Push Notification Settings Modal --}}
    @auth
        @include('partials.push-notification-settings')
    @endauth
    {{-- Reactors List Modal (Global) --}}
    <div id="reactorsModalOverlay" class="global-reactors-overlay" onclick="closeReactorsModal(event)">
        <div class="global-reactors-modal" onclick="event.stopPropagation()">
            <div class="global-reactors-header">
                <h3>{{ __('chat.reactions') }}</h3>
                <button class="global-reactors-close" onclick="closeReactorsModal()"><i class="fas fa-times"></i></button>
            </div>
            <div id="reactorsList" class="global-reactors-list">
                {{-- Dynamic content --}}
            </div>
        </div>
    </div>

    {{-- Global Modals --}}
    @include('partials.global-modals')

    <script>
        window.closeReactorsModal = function(event) {
            const overlay = document.getElementById('reactorsModalOverlay');
            if (!overlay) return;
            
            // If event exists, only close if clicking overlay itself or the close button
            if (event) {
                if (event.target === overlay || event.target.closest('.global-reactors-close')) {
                    overlay.style.display = 'none';
                }
            } else {
                overlay.style.display = 'none';
            }
        };
    </script>
    @stack('scripts')
    
    {{-- Predictive Pre-loading (Kills the 1-second lag) --}}
</body>
</html>
