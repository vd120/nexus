<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="data:,">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('auth.create_account') }} — Nexus</title>

<script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);document.documentElement.style.background=t==='dark'?'#0a0a0b':'#ffffff'})();</script>
<style>
    html[data-theme="dark"] { background: #0a0a0b; color: #f5f5f7; }
    html[data-theme="light"] { background: #ffffff; color: #111111; }
</style>

    <link rel="stylesheet" href="/fonts/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    @vite('resources/css/auth-register.css')
    <style>
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .page { animation: fadeIn 0.3s ease-out forwards; }
    </style>
</head>
<body>

<!-- NAV (identical to login, with theme toggle) -->
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
            <a href="{{ route('home') }}" class="back-pill-btn"><span>{{ __('messages.back') }}</span></a>
        </div>
    </div>
</nav>

<div class="page">
    <div class="login-card">
        <h1 class="login-title">{{ __('auth.create_account') }}</h1>
        <p class="login-sub">{{ __('auth.join_us') }}</p>

        <!-- validation errors (same as register page) -->
        @if ($errors->any())
        <div class="alert-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <a href="{{ route('login.google') }}?prompt=select_account" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            {{ __('auth.continue_with_google') }}
        </a>

        <div class="divider">{{ __('auth.or') }}</div>

        <!-- REGISTER FORM – fields exactly preserved, only design changed -->
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- full name field -->
            <div class="field">
                <label for="name">{{ __('auth.full_name') }}</label>
                <input type="text" name="name" id="name"
                    value="{{ old('name') }}"
                    placeholder="{{ __('auth.enter_full_name') }}"
                    required autocomplete="name">
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <!-- username field (with status) -->
            <div class="field">
                <label for="username">{{ __('auth.username') }}</label>
                <input type="text" name="username" id="username"
                    value="{{ old('username') }}"
                    placeholder="{{ __('auth.choose_username') }}"
                    required minlength="3" maxlength="50"
                    pattern="[a-zA-Z0-9_\-]+"
                    title="{{ __('auth.username_requirements') }}"
                    autocomplete="username">
                <div class="field-status" id="username-status"></div>
                @error('username')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <!-- email field -->
            <div class="field">
                <label for="email">{{ __('auth.email') }}</label>
                <input type="email" name="email" id="email"
                    value="{{ old('email') }}"
                    placeholder="{{ __('auth.email_placeholder') }}"
                    required autocomplete="email">
                @error('email')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <!-- password field + strength meter -->
            <div class="field">
                <label for="password">{{ __('auth.password') }}</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="password"
                        placeholder="{{ __('auth.create_password') }}"
                        required autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password','eye-icon')">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
                <div class="strength-track"><div class="strength-fill" id="strength-fill"></div></div>
                <div class="strength-label" id="strength-label"></div>
                @error('password')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <!-- confirm password + match status -->
            <div class="field">
                <label for="password_confirmation">{{ __('auth.confirm_password') }}</label>
                <div class="password-wrap">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        placeholder="{{ __('auth.repeat_password') }}"
                        required autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password_confirmation','conf-eye-icon')">
                        <i class="fas fa-eye" id="conf-eye-icon"></i>
                    </button>
                </div>
                <div class="field-status" id="match-status"></div>
            </div>

            <!-- terms checkbox -->
            <div class="terms-row">
                <input type="checkbox" name="terms" id="terms" value="1" required>
                <label for="terms">{{ __('auth.i_agree') }} <a href="{{ route('terms') }}" target="_blank">{{ __('auth.terms_of_service') }}</a> {{ __('auth.and') }} <a href="{{ route('privacy') }}" target="_blank">{{ __('auth.privacy_policy') }}</a></label>
            </div>

            <!-- submit button -->
            <button type="submit" class="btn btn-primary">
                {{ __('auth.create_account_button') }} <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="card-footer">
            {{ __('auth.already_have_account') }} <a href="{{ route('login') }}">{{ __('auth.sign_in') }}</a>
        </div>
    </div>
</div>

<script>
    window.authTranslations = {
        password_strength_weak: "{{ __('messages.password_strength_weak') }}",
        password_strength_medium: "{{ __('messages.password_strength_medium') }}",
        password_strength_strong: "{{ __('messages.password_strength_strong') }}",
        password_strength_very_strong: "{{ __('messages.password_strength_very_strong') }}",
        passwords_match: "{{ __('messages.passwords_match') }}",
        passwords_do_not_match: "{{ __('messages.passwords_do_not_match') }}",
        username_available: "{{ __('messages.username_available') }}",
        username_taken: "{{ __('messages.username_taken') }}",
        username_min_length: "{{ __('messages.username_min_length') }}",
        username_checking: "{{ __('messages.username_checking') }}"
    };
</script>
@vite(['resources/js/legacy/ui-utils.js', 'resources/js/legacy/auth-register.js'])

</body>
</html>