<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.account_suspended') }} — Nexus</title>

<script>
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>
<style>
    html[data-theme="dark"] { background: #0a0a0b; color: #f5f5f7; }
    html[data-theme="light"] { background: #ffffff; color: #111111; }
</style>

    <link rel="stylesheet" href="/fonts/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    @vite('resources/css/auth-suspended.css')
</head>
<body>

<nav>
    <div class="nav-container">
        <a href="{{ route('home') }}" class="nav-brand">
            <img src="{{ asset('images/nexus-logo-white.png') }}" alt="Nexus" class="logo-dark">
            <img src="{{ asset('images/nexus-logo-black.png') }}" alt="Nexus" class="logo-light">
        </a>
        <div class="auth-header-actions">
            @include('partials.language-switcher')
            <div id="themeToggle" class="theme-switcher-pill" onclick="toggleTheme()" title="{{ __('messages.theme') }}">
                <div class="theme-slide-bg"></div>
                <div class="theme-option-btn btn-sun" data-theme-btn="light">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2" stroke-linecap="round"/><path d="M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-linecap="round"/></svg>
                </div>
                <div class="theme-option-btn btn-moon" data-theme-btn="dark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </div>
            </div>
            <a href="{{ route('login') }}" class="back-pill-btn"><span>{{ __('auth.back_to_login') }}</span></a>
        </div>
    </div>
</nav>

<div class="page">
    <div class="login-card">
        <div class="auth-icon">
            <i class="fas fa-ban"></i>
        </div>

        <h1 class="login-title">{{ __('auth.account_suspended_title') }}</h1>
        <p class="login-sub">{{ __('auth.account_suspended_message') }}</p>

        <div class="contact-section">
            <h3><i class="fas fa-envelope"></i> {{ __('auth.need_help') }}</h3>
            <p>{{ __('auth.contact_support_message') }}</p>
        </div>

        <div class="card-footer">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt"></i> {{ __('auth.sign_out') }}
            </a>
        </div>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
    </div>
</div>

@vite(['resources/js/legacy/ui-utils.js', 'resources/js/legacy/auth-suspended.js'])

</body>
</html>
