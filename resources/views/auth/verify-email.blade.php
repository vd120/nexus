<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('auth.verify_email_title') }} — Nexus</title>

<script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);document.documentElement.style.background=t==='dark'?'#0a0a0b':'#ffffff';window.runOnPageLoad=function(cb){if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',cb);}else{setTimeout(cb,0);}};})();</script>
<style>
    html[data-theme="dark"] { background: #0a0a0b; color: #f5f5f7; }
    html[data-theme="light"] { background: #ffffff; color: #111111; }

    .verification-warning {
        display: flex !important;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: rgba(255, 214, 10, 0.1);
        border: 1px solid rgba(255, 214, 10, 0.2);
        border-radius: 16px;
        color: #ffd60a !important;
        font-size: 13.5px;
        line-height: 1.5;
        margin: 0 auto 30px;
        text-align: left;
        width: 100%;
        max-width: 100%;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }
    
    html[lang="ar"] .verification-warning {
        text-align: right;
        flex-direction: row-reverse;
    }
    
    .verification-warning i {
        font-size: 16px;
        color: #ffd60a;
        flex-shrink: 0;
    }
</style>

    <link rel="stylesheet" href="/fonts/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    @vite('resources/css/auth-verify-email.css')
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
            @if(auth()->check())
                <a href="{{ route('users.show', auth()->user()) }}" class="back-pill-btn"><span>{{ __('auth.back_to_profile') }}</span></a>
            @else
                <a href="{{ route('login') }}" class="back-pill-btn"><span>{{ __('messages.back') }}</span></a>
            @endif
        </div>
    </div>
</nav>

<div class="page">
    <div id="toast-container"></div>
    <div class="login-card">
        <div class="auth-icon">
            <i class="fas fa-envelope-open-text"></i>
        </div>

        <h1 class="login-title">{{ __('auth.verify_email_title') }}</h1>
        <p class="login-sub" id="instruction-text">{{ __('auth.verify_email_subtitle') }}</p>
        <p class="verification-warning">
            <i class="fas fa-clock"></i> {{ __('auth.verify_email_warning') }}
        </p>

        @if(session('status'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> {{ session('status') }}
            </div>
        @endif

        @if(session('message'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        @endif

        @if($errors->has('code'))
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> {{ $errors->first('code') }}
            </div>
        @endif

        <!-- Send Code Section -->
        <div class="send-code-section" id="sendCodeSection">
            <form method="POST" action="{{ route('verification.send') }}" id="sendCodeForm">
                @csrf
                <button type="submit" class="btn btn-primary" id="sendCodeBtn">
                    <i class="fas fa-paper-plane"></i> {{ __('auth.send_verification_code') }}
                </button>
            </form>
        </div>

        <!-- Verification Code Form -->
        <form class="verification-code-form" method="POST" action="{{ route('verification.verify-code') }}" id="verifyForm">
            @csrf
            <div class="code-inputs" dir="ltr">
                <input type="text" name="code[]" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required autofocus dir="ltr">
                <input type="text" name="code[]" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required dir="ltr">
                <input type="text" name="code[]" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required dir="ltr">
                <input type="text" name="code[]" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required dir="ltr">
                <input type="text" name="code[]" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required dir="ltr">
                <input type="text" name="code[]" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required dir="ltr">
            </div>

            <input type="hidden" name="code" id="fullCode">

            <button type="submit" class="btn btn-verify">
                <i class="fas fa-check-circle"></i> {{ __('auth.verify_email_button') }}
            </button>
        </form>

        <div class="resend-section" id="resendSection">
            <p>{{ __('auth.didnt_receive_code') }}</p>
            <form method="POST" action="{{ route('verification.send') }}" id="resendForm" style="display: inline;">
                @csrf
                <button type="submit" class="resend-btn" id="resendBtn">{{ __('auth.resend_code') }}</button>
            </form>
            <div class="timer">{{ __('auth.resend_available_in') }} <span id="countdown">60</span>s</div>
        </div>

        <div class="card-footer">
            @if(auth()->check())
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> {{ __('auth.sign_out') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
            @else
                <a href="{{ route('login') }}">
                    <i class="fas fa-sign-in-alt"></i> {{ __('auth.back_to_login') }}
                </a>
            @endif
        </div>
    </div>
</div>

<script>
    // Check if user is already verified
    const userAlreadyVerified = @if(auth()->check() && auth()->user()->hasVerifiedEmail() && !session('auth.suspicious')) true @else false @endif;

    // Translations for JavaScript
    window.verifyEmailTranslations = {
        sending: '{{ __('auth.sending') }}',
        accountAlreadyVerified: '{{ __('auth.account_already_verified') }}',
        verificationCodeSent: '{{ __('auth.verification_code_sent') }}',
        enter6DigitCode: '{{ __('auth.enter_6_digit_code') }}',
        codeMustBeNumbers: '{{ __('auth.code_must_be_numbers') }}',
        error: '{{ __('auth.error') }}'
    };

    // Show verification form if there's a message about code being sent
    @if(session('message') && (str_contains(session('message'), 'sent') || str_contains(session('message'), 'code')))
        window.runOnPageLoad( function() {
            showVerificationForm();
        });
    @endif

    // Show verification form if there was an error with the code
    @if($errors->has('code'))
        window.runOnPageLoad( function() {
            showVerificationForm();
        });
    @endif
</script>

<script>
    window.authTranslations = {
        account_already_verified: "{{ __('messages.account_already_verified') }}",
        verification_code_sent: "{{ __('messages.verification_code_sent') }}",
        error: "{{ __('messages.error') }}",
        enter_6_digit_code: "{{ __('messages.enter_6_digit_code') }}",
        code_must_be_numbers: "{{ __('messages.code_must_be_numbers') }}",
        sending: "{{ __('messages.sending') }}"
    };
</script>
@vite(['resources/js/legacy/ui-utils.js', 'resources/js/legacy/auth-verify-email.js'])

</body>
</html>
