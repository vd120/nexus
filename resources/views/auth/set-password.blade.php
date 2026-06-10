<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ __('auth.set_password_title') }} — Nexus</title>

<script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);document.documentElement.style.background=t==='dark'?'#0a0a0b':'#ffffff'})();</script>
<style>
    html[data-theme="dark"] { background: #0a0a0b; color: #f5f5f7; }
    html[data-theme="light"] { background: #ffffff; color: #111111; }
</style>

<link rel="stylesheet" href="/fonts/all.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
@vite('resources/css/auth-set-password.css')
</head>
<body>

<nav>
    <div class="nav-container">
        <a href="{{ route('home') }}" class="nav-brand">
            <x-logo-text />
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
        <h1 class="login-title">{{ __('auth.set_password_title') }}</h1>
        <p class="login-sub">{{ __('auth.set_password_desc') }}</p>

        @if(session('message'))
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <span>{{ session('message') }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('password.set-password.store') }}">
            @csrf

            <div class="field">
                <label for="password">{{ __('auth.password') }}</label>
                <div class="password-wrap">
                    <input type="password"
                           name="password"
                           id="password"
                           placeholder="{{ __('auth.create_password') }}"
                           required
                           autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password','eye-icon')">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
                <div class="strength-track"><div class="strength-fill" id="strength-fill"></div></div>
                <div class="strength-label" id="strength-label"></div>
                @error('password')
                    <div class="field-error">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">{{ __('auth.confirm_password') }}</label>
                <div class="password-wrap">
                    <input type="password"
                           name="password_confirmation"
                           id="password_confirmation"
                           placeholder="{{ __('auth.repeat_password') }}"
                           required
                           autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password_confirmation','conf-eye-icon')">
                        <i class="fas fa-eye" id="conf-eye-icon"></i>
                    </button>
                </div>
                <div class="field-status" id="match-status"></div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-lock"></i> {{ __('auth.set_password_button') }}
            </button>
        </form>
    </div>
</div>

<script>
    window.authTranslations = {
        password_strength_weak: "{{ __('messages.password_strength_weak') }}",
        password_strength_medium: "{{ __('messages.password_strength_medium') }}",
        password_strength_strong: "{{ __('messages.password_strength_strong') }}",
        password_strength_very_strong: "{{ __('messages.password_strength_very_strong') }}",
        passwords_match: "{{ __('messages.passwords_match') }}",
        passwords_do_not_match: "{{ __('messages.passwords_do_not_match') }}"
    };
</script>
@vite(['resources/js/legacy/ui-utils.js', 'resources/js/legacy/auth-set-password.js'])

</body>
</html>
