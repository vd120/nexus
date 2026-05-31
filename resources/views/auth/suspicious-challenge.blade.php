<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
<title>{{ __('auth.suspicious_login_title') }} — Nexus</title>

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
@vite('resources/css/auth-login.css')
<style>
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes pulse { 0% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.05); opacity: 1; } 100% { transform: scale(1); opacity: 0.8; } }
    .page { animation: fadeIn 0.3s ease-out forwards; }
    .challenge-icon {
        font-size: 64px;
        color: #ff4757;
        margin-bottom: 24px;
        animation: pulse 2s infinite ease-in-out;
    }
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
            <a href="{{ route('login.view') }}" class="back-pill-btn"><span>{{ __('messages.back') }}</span></a>
        </div>
    </div>
</nav>

<div class="page">
    <div class="login-card" style="text-align: center;">
        <div class="challenge-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h1 class="login-title">{{ __('auth.suspicious_login_title') }}</h1>
        <p class="login-sub">{{ __('auth.suspicious_login_subtitle') }}</p>

        <form method="POST" action="{{ route('login.suspicious.verify', $uuid) }}" id="suspiciousForm" style="text-align: start; margin-top: 24px;">
            @csrf

            @if($type === 'manual')
                <!-- Manual Login: Email Code -->
                <div class="field">
                    <label for="code">{{ __('auth.verification_code') }}</label>
                    <input id="code" type="text" name="code" required autocomplete="one-time-code" maxlength="6" 
                           placeholder="000000" class="@error('code') is-invalid @enderror" 
                           style="text-align: center; letter-spacing: 6px; font-size: 20px; font-weight: 700;">
                    @error('code')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <p class="login-sub" style="margin-top: 12px; font-size: 13px; text-align: center;">
                        {{ __('auth.code_sent_to_email') ?? __('A 6-digit code has been sent to') }} <strong>{{ substr($email, 0, 3) }}***@***</strong>
                    </p>
                </div>

                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <button type="button" id="resendBtn" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); background: transparent; border-radius: 8px; cursor: pointer; color: var(--text-muted);">
                        <i class="fas fa-redo"></i> {{ __('auth.resend_code') }}
                    </button>
                    <span id="resendTimer" style="margin-inline-start: 8px; font-size: 13px; opacity: 0.7;"></span>
                </div>
            @else
                <!-- OAuth Login: Password -->
                <div class="field">
                    <label for="password">{{ __('auth.password') }}</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="password" required placeholder="••••••••" class="@error('password') is-invalid @enderror">
                        <button type="button" class="toggle-pw" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <p class="login-sub" style="margin-top: 12px; font-size: 13px; line-height: 1.4;">
                        {{ __('auth.confirm_password_oauth_help') ?? __('Since you are logging in from a new location/device via Google, please confirm your account password.') }}
                    </p>
                </div>
            @endif

            <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    {{ __('auth.verify_and_continue') }} <i class="fas fa-arrow-right" style="margin-inline-start: 8px;"></i>
                </button>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn" style="width: 100%; text-align: center; text-decoration: none; display: block; border: 1px solid var(--border-color); padding: 12px; border-radius: 12px; font-weight: 500; color: var(--text-muted);">
                    {{ __('auth.cancel') }}
                </a>
            </div>
        </form>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden" style="display: none;">
            @csrf
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resendBtn');
    const resendTimer = document.getElementById('resendTimer');
    
    if (resendBtn) {
        resendBtn.addEventListener('click', async function() {
            resendBtn.disabled = true;
            try {
                const response = await fetch('{{ route("login.suspicious.resend", $uuid) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    startTimer(60);
                } else {
                    resendBtn.disabled = false;
                }
            } catch (err) {
                resendBtn.disabled = false;
            }
        });
    }

    function startTimer(seconds) {
        let remaining = seconds;
        resendTimer.textContent = `({{ __('auth.wait') }} ${remaining}s)`;
        
        const interval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(interval);
                resendTimer.textContent = '';
                resendBtn.disabled = false;
            } else {
                resendTimer.textContent = `({{ __('auth.wait') }} ${remaining}s)`;
            }
        }, 1000);
    }
});
</script>

@vite(['resources/js/legacy/ui-utils.js', 'resources/js/legacy/auth-login.js'])
</body>
</html>
