<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
<title>{{ __('auth.sign_in') }} — Nexus</title>

<script>
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>
<style>
    html[data-theme="dark"] { background: #0d0d0d; color: #f5f5f7; }
    html[data-theme="light"] { background: #ffffff; color: #111111; }
</style>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/auth-login.css') }}">
<style>
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .page { animation: fadeIn 0.3s ease-out forwards; }
</style>
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
            <a href="{{ route('home') }}" class="back-pill-btn"><span>{{ __('messages.back') }}</span></a>
        </div>
    </div>
</nav>

<div class="page">
    <div class="login-card">
        @if(session('suspended'))
            <div class="field-error" style="margin-bottom: 20px; text-align: center;">
                <i class="fas fa-exclamation-triangle"></i>
                {{ __('auth.account_suspended') }}
            </div>
        @endif

        @if(session('concurrent_login'))
            <div class="field-error" style="margin-bottom: 20px; text-align: center;">
                <i class="fas fa-shield-alt"></i>
                {{ __('auth.concurrent_login') }}
            </div>
        @endif

        @if(session('account_deleted'))
            <div class="field-error" style="margin-bottom: 20px; text-align: center;">
                <i class="fas fa-user-slash"></i>
                {{ __('auth.account_deleted') }}
            </div>
        @endif

        <h1 class="login-title">{{ __('auth.welcome_back') }}</h1>
        <p class="login-sub">{{ __('auth.sign_in_to_continue') }}</p>

        <a href="{{ route('login.google') }}?prompt=select_account" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            {{ __('auth.continue_with_google') }}
        </a>

        <div class="divider">{{ __('auth.or_continue_with') }}</div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label>{{ __('auth.email_address') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label>{{ __('auth.password') }}</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="password" required autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="extras">
                <label>
                    <input type="checkbox" name="remember" value="1"> {{ __('auth.remember_me') }}
                </label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}">{{ __('auth.forgot_password') }}</a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary">
                {{ __('auth.sign_in_button') }}
            </button>
        </form>

        <div class="card-footer">
            {{ __('auth.dont_have_account') }}
            <a href="{{ route('register') }}">{{ __('auth.sign_up') }}</a>
        </div>
    </div>
</div>

@vite(['resources/js/legacy/ui-utils.js', 'resources/js/legacy/auth-login.js'])
<div id="login-config" 
     data-status="{{ session('status') }}" 
     data-error="{{ session('error') }}"
     data-concurrent="{{ session('concurrent_login') }}"
     data-deleted="{{ session('account_deleted') }}"
     data-suspended="{{ session('account_suspended') }}"
     style="display:none;">
</div>
</body>
</html>