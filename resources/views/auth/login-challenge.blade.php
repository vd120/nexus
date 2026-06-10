<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ __('auth.approval_required') }} — Nexus</title>

<script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);document.documentElement.style.background=t==='dark'?'#0a0a0b':'#ffffff'})();</script>
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
        color: var(--primary, #1da1f2);
        margin-bottom: 24px;
        animation: pulse 2s infinite ease-in-out;
    }
    .loading-dots:after {
        content: '.';
        animation: dots 1.5s steps(5, end) infinite;
    }
    @keyframes dots {
        0%, 20% { content: '.'; }
        40% { content: '..'; }
        60% { content: '...'; }
        80%, 100% { content: ''; }
    }
    .device-info {
        background: rgba(255, 255, 255, 0.05);
        padding: 16px;
        border-radius: 12px;
        margin: 20px 0;
        font-size: 14px;
        border: 1px solid var(--border-color);
    }
    [data-theme="light"] .device-info { background: rgba(0, 0, 0, 0.03); }
</style>
</head>
<body>

<nav>
    <div class="nav-container">
        <a href="{{ route('home') }}" class="nav-brand">
            <x-logo-text />
        </a>
    </div>
</nav>

<div class="page">
    <div class="login-card" style="text-align: center;">
        <div class="challenge-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        
        <h1 class="login-title">{{ __('auth.approve_this_login') }}</h1>
        <p class="login-sub">{!! __('auth.challenge_sent_message', ['device' => $device]) !!}</p>

        <div id="status-container" class="device-info">
            <p><i class="fas fa-spinner fa-spin"></i> <span class="loading-dots">{{ __('auth.waiting_for_approval') }}</span></p>
        </div>

        <!-- Email Fallback Container -->
        <div id="email-fallback-container" style="display: none; margin-top: 24px; animation: fadeIn 0.3s ease;">
            <div class="device-info" style="border-color: var(--primary);">
                <p style="margin-bottom: 16px;" id="email-fallback-hint">{{ __('auth.verify_with_email_code') }}</p>
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <input type="text" id="email-code" maxlength="6" placeholder="000000"
                           style="width: 120px; text-align: center; padding: 12px; border-radius: 8px; border: 1px solid var(--border); background: rgba(255,255,255,0.05); color: white; font-size: 18px; letter-spacing: 4px;">
                    <button onclick="verifyEmailCode()" class="btn" style="padding: 10px 20px;">{{ __('auth.verify') }}</button>
                </div>
                <p id="email-error" style="color: #ff4b4b; font-size: 12px; margin-top: 8px; display: none;"></p>
            </div>
        </div>

        <div style="margin-top: 32px; display: flex; flex-direction: column; gap: 12px;">
            <button id="btn-show-email" onclick="sendEmailCode()" class="btn" style="background: var(--primary); color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 600;">
                <i class="fas fa-envelope"></i> {{ __('auth.send_code_to_email') }}
            </button>
            
            <a href="{{ route('login.view') }}" class="btn" style="background: transparent; border: 1px solid var(--border); color: var(--text-muted); display: block; text-decoration: none; padding: 12px; border-radius: 12px;">
                {{ __('auth.cancel_and_go_back') }}
            </a>
        </div>

        <div class="card-footer" style="margin-top: 24px; font-size: 13px; color: var(--text-muted);">
            {{ __('auth.no_access_device_help') }}
        </div>
    </div>
</div>

<script>
    const challengeUuid = "{{ $uuid }}";
    let isApproved = false;
    let emailSent = false;

    function checkStatus() {
        if (isApproved) return;

        fetch(`/login/challenge/${challengeUuid}/status`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'approved' && data.redirect) {
                    isApproved = true;
                    window.location.href = data.redirect;
                } else if (data.status === 'denied') {
                    window.location.href = "{{ route('login.view') }}?error=denied";
                } else if (data.status === 'expired') {
                    window.location.href = "{{ route('login.view') }}?error=expired";
                } else {
                    setTimeout(checkStatus, 3000);
                }
            })
            .catch(() => {
                setTimeout(checkStatus, 5000);
            });
    }

    // After 30 seconds with no approval, auto-send email code and reveal the input
    setTimeout(() => {
        if (!isApproved && !emailSent) {
            sendEmailCode(true);
        }
    }, 30000);

    function sendEmailCode(auto = false) {
        if (emailSent) return;
        emailSent = true;

        const btn = document.getElementById('btn-show-email');
        const originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("auth.sending") }}...';
        }

        fetch(`/login/challenge/${challengeUuid}/email`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status-container').style.display = 'none';
                document.getElementById('email-fallback-container').style.display = 'block';
                if (btn) btn.style.display = 'none';

                if (auto) {
                    const hint = document.getElementById('email-fallback-hint');
                    if (hint) hint.textContent = '{{ __("auth.no_response_email_sent") }}';
                }
            } else {
                emailSent = false;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
                alert(data.message || '{{ __("auth.error_sending_email") }}');
            }
        })
        .catch(() => {
            emailSent = false;
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    function verifyEmailCode() {
        const code = document.getElementById('email-code').value;
        const errorEl = document.getElementById('email-error');

        if (code.length !== 6) {
            errorEl.innerText = '{{ __("auth.enter_6_digit_code") }}';
            errorEl.style.display = 'block';
            return;
        }

        fetch(`/login/challenge/${challengeUuid}/verify`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ code })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                errorEl.innerText = '{{ __("auth.verified_redirecting") }}';
                errorEl.style.color = '#00E5FF';
                errorEl.style.display = 'block';
            } else {
                errorEl.innerText = data.message || '{{ __("auth.invalid_verification_code") }}';
                errorEl.style.color = '#ff4b4b';
                errorEl.style.display = 'block';
            }
        })
        .catch(() => {
            errorEl.innerText = '{{ __("auth.error_verifying_code") }}';
            errorEl.style.display = 'block';
        });
    }

    // Start polling
    setTimeout(checkStatus, 2000);
</script>

@vite(['resources/js/legacy/ui-utils.js'])
</body>
</html>
