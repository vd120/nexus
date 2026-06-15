<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">

    <title>{{ __('download.page_title') }} — Nexus</title>
    <link rel="stylesheet" href="/fonts/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script data-cfasync="false">(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);document.documentElement.style.background=t==='dark'?'#0a0a0b':'#ffffff'})();</script>

    <style>
        :root {
            --bg: #0a0a0b;
            --bg-rgb: 10, 10, 11;
            --bg-secondary: #0a0a0b;
            --text: #f5f5f7;
            --text-dim: rgba(255,255,255,0.55);
            --primary: #0071e3;
            --border: rgba(255, 255, 255, 0.08);
            --glass: rgba(255, 255, 255, 0.02);
        }
        [data-theme="light"] {
            --bg: #ffffff;
            --bg-rgb: 255, 255, 255;
            --bg-secondary: #f5f5f7;
            --text: #1d1d1f;
            --text-dim: #6e6e73;
            --border: rgba(0, 0, 0, 0.08);
            --glass: rgba(0, 0, 0, 0.01);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; background: var(--bg); }
        [data-theme="light"] html { background: #ffffff; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        body:not(.switching-theme),
        nav:not(.switching-theme),
        .theme-switcher-pill, .language-switcher-pill {
            transition: background-color 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                        color 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        ::view-transition-old(root), ::view-transition-new(root) {
            animation-duration: 0.3s;
            animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
        }

        html[lang="ar"] body { font-family: 'Cairo', sans-serif; }
        html[lang="ar"] * { letter-spacing: normal !important; }

        /* --- NAV --- */
        body > nav {
            position: fixed; top: 24px; left: 50%; transform: translateX(-50%);
            width: calc(100% - 48px); max-width: 1000px; height: 64px;
            background: rgba(var(--bg-rgb), 0.6);
            backdrop-filter: saturate(200%) blur(30px);
            -webkit-backdrop-filter: saturate(200%) blur(30px);
            z-index: 1000; border: 1px solid var(--border); border-radius: 100px;
            display: flex; justify-content: center;
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.2);
        }
        body > nav::before {
            content: ''; position: absolute; inset: 0; border-radius: 100px;
            padding: 1px; background: linear-gradient(to bottom, rgba(255,255,255,0.1), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
        }
        .nav-inner { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 12px 0 20px; height: 100%; gap: 8px; }
        .nav-logo { display: flex; align-items: center; text-decoration: none; flex-shrink: 0; }

        /* Dark/light logo swap — app-layout.css is not loaded on this page */
        .logo-icon { display: block; width: 32px; height: 32px; object-fit: contain; flex-shrink: 0; }
        .logo-icon--light { display: none; }
        .logo-icon--dark  { display: block; }
        [data-theme="light"] .logo-icon--dark  { display: none; }
        [data-theme="light"] .logo-icon--light { display: block; }

        .nav-logo-label {
            font-size: 15px; font-weight: 700; letter-spacing: -0.02em;
            color: var(--text); margin-left: 8px;
        }
        html[lang="ar"] .nav-logo-label { margin-left: 0; margin-right: 8px; }

        .nav-actions { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }

        /* Switchers */
        .language-switcher-pill, .theme-switcher-pill {
            display: flex; align-items: center; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 100px;
            padding: 3px; position: relative; height: 34px; cursor: pointer;
            backdrop-filter: blur(10px); user-select: none; direction: ltr !important;
        }
        .language-switcher-pill { width: 82px; }
        .theme-switcher-pill { width: 72px; }
        .lang-slide-bg, .theme-slide-bg {
            position: absolute; top: 3px; left: 3px; height: 26px;
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);
            border-radius: 100px; transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
            z-index: 1; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .lang-slide-bg { width: 37px; }
        .theme-slide-bg { width: 31px; }
        html[lang="ar"] .lang-slide-bg { left: 42px; }
        [data-theme="dark"] .theme-slide-bg { left: 35px; }
        .lang-option-btn, .theme-option-btn {
            flex: 1; display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,0.5); z-index: 2; font-size: 11px; font-weight: 700;
            transition: all 0.3s ease; height: 100%;
        }
        .lang-option-btn.active, .theme-option-btn.active { color: #ffffff; }
        [data-theme="light"] .language-switcher-pill, [data-theme="light"] .theme-switcher-pill {
            background: rgba(0,0,0,0.05); border-color: rgba(0,0,0,0.08);
        }
        [data-theme="light"] .lang-slide-bg, [data-theme="light"] .theme-slide-bg {
            background: #ffffff; border-color: rgba(0,0,0,0.05);
        }
        [data-theme="light"] .lang-option-btn, [data-theme="light"] .theme-option-btn { color: rgba(0,0,0,0.4); }
        [data-theme="light"] .lang-option-btn.active, [data-theme="light"] .theme-option-btn.active { color: #111111; }

        /* --- PAGE BODY --- */
        .page-wrap {
            padding: 80px 24px 80px;
            max-width: 860px;
            margin: 0 auto;
        }

        /* --- HERO --- */
        .hero {
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            padding: 0 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 10;
        }
        @media (prefers-reduced-motion: no-preference) {
            .hero { animation: nx-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.05s both; }
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
        .hero h1 {
            font-size: clamp(48px, 8vw, 90px);
            font-weight: 800;
            letter-spacing: -0.05em;
            margin-bottom: 24px;
            line-height: 1.1;
            padding: 10px 0;
            background: linear-gradient(135deg, var(--text) 0%, var(--text-dim) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: clamp(18px, 2.5vw, 24px);
            color: var(--text-dim);
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* --- WHAT IS A PWA --- */
        .whatis {
            margin-bottom: 64px;
        }
        .whatis-head {
            text-align: center;
            max-width: 640px;
            margin: 0 auto 40px;
        }
        .whatis-head h2 {
            font-size: clamp(26px, 4vw, 40px);
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
        }
        .whatis-head p {
            font-size: clamp(15px, 2vw, 18px);
            color: var(--text-dim);
            line-height: 1.6;
        }
        .whatis-head strong { color: var(--text); font-weight: 700; }
        .whatis-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .whatis-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            display: flex;
            gap: 18px;
            align-items: flex-start;
        }
        .whatis-card-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; flex-shrink: 0;
        }
        .whatis-card:nth-child(1) .whatis-card-icon { background: rgba(0,113,227,0.12); color: #0071e3; }
        .whatis-card:nth-child(2) .whatis-card-icon { background: rgba(99,102,241,0.12); color: #818cf8; }
        .whatis-card:nth-child(3) .whatis-card-icon { background: rgba(16,185,129,0.12); color: #34d399; }
        .whatis-card:nth-child(4) .whatis-card-icon { background: rgba(245,158,11,0.12); color: #fbbf24; }
        .whatis-card-body h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .whatis-card-body p { font-size: 14px; color: var(--text-dim); line-height: 1.6; }
        .whatis-note {
            margin-top: 28px;
            text-align: center;
            font-size: 15px;
            color: var(--text-dim);
            background: rgba(99,102,241,0.07);
            border: 1px solid rgba(99,102,241,0.18);
            border-radius: 16px;
            padding: 18px 24px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* --- PLATFORM TABS --- */
        .platform-tabs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 100px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-dim);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
        }
        .tab-btn:hover { color: var(--text); border-color: rgba(255,255,255,0.2); }
        .tab-btn.active {
            background: var(--text);
            color: var(--bg);
            border-color: transparent;
        }
        [data-theme="light"] .tab-btn.active { background: #111; color: #fff; }

        /* --- INSTALL PANELS --- */
        .install-panel {
            display: none;
            animation: panel-in 0.4s cubic-bezier(0.16,1,0.3,1) both;
        }
        .install-panel.active { display: block; }
        @keyframes panel-in {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Android panel */
        .install-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px 40px;
            text-align: center;
        }
        .install-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        .install-card > p {
            color: var(--text-dim);
            font-size: 16px;
            margin-bottom: 36px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }
        .install-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: opacity 0.2s, transform 0.2s;
        }
        .install-btn-primary:hover { opacity: 0.85; transform: translateY(-1px); }
        .install-btn-primary:active { transform: translateY(0); }
        .install-btn-primary:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
        .install-note {
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-dim);
        }

        /* Step list (iOS + Desktop) */
        .step-list {
            display: flex;
            flex-direction: column;
            gap: 0;
            text-align: left;
        }
        html[lang="ar"] .step-list { text-align: right; }
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 24px 0;
            border-bottom: 1px solid var(--border);
        }
        .step-item:last-child { border-bottom: none; }
        .step-num {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0,113,227,0.15);
            color: var(--primary);
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(99,102,241,0.12);
            color: #818cf8;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .step-body h3 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .step-body p {
            font-size: 14px;
            color: var(--text-dim);
            line-height: 1.5;
        }
        .step-body code {
            background: rgba(99,102,241,0.15);
            color: #818cf8;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 13px;
            font-family: monospace;
        }

        /* QR panel */
        .qr-layout {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 48px;
            align-items: center;
        }
        .qr-box {
            width: 180px;
            height: 180px;
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            background: #fff;
            padding: 12px;
            flex-shrink: 0;
        }
        .qr-box img { width: 100%; height: 100%; display: block; }
        .qr-text h2 { font-size: 26px; font-weight: 700; margin-bottom: 12px; letter-spacing: -0.02em; }
        .qr-text p { color: var(--text-dim); font-size: 15px; margin-bottom: 20px; line-height: 1.6; }
        .qr-url {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .qr-url:hover { border-color: rgba(255,255,255,0.25); }
        .qr-url i { color: var(--text-dim); font-size: 12px; }

        /* --- BENEFITS GRID --- */
        .benefits-section {
            margin-top: 80px;
        }
        .benefits-section h2 {
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 40px;
            text-align: center;
        }
        .benefits-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .benefit-card {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            transition: border-color 0.3s;
        }
        .benefit-card:hover { border-color: rgba(99,102,241,0.35); }
        .benefit-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
        }
        .benefit-card:nth-child(1) .benefit-icon { background: rgba(0,113,227,0.12); color: #0071e3; }
        .benefit-card:nth-child(2) .benefit-icon { background: rgba(99,102,241,0.12); color: #818cf8; }
        .benefit-card:nth-child(3) .benefit-icon { background: rgba(16,185,129,0.12); color: #34d399; }
        .benefit-card:nth-child(4) .benefit-icon { background: rgba(245,158,11,0.12); color: #fbbf24; }
        .benefit-card h3 { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
        .benefit-card p { font-size: 14px; color: var(--text-dim); line-height: 1.6; }


        /* Reveal — fade-in on scroll via IntersectionObserver + CSS animation */
        .reveal {
            opacity: 0;
            transform: translateY(80px);
        }
        .reveal.visible {
            animation: nx-reveal 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes nx-reveal {
            from { opacity: 0; transform: translateY(80px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Toast */
        #install-toast {
            position: fixed; bottom: 32px; left: 50%; transform: translateX(-50%);
            background: rgba(22,22,24,0.96); color: #f5f5f7;
            padding: 13px 20px; border-radius: 14px;
            font-size: 14px; font-weight: 500; line-height: 1.5;
            z-index: 99999; max-width: calc(100% - 48px); text-align: center;
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.1);
            opacity: 0; pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.16,1,0.3,1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        #install-toast.show {
            opacity: 1; pointer-events: auto;
        }

        /* --- FOOTER --- */
        footer {
            margin-top: 80px;
            border-top: 1px solid var(--border);
            padding: 40px 24px;
            text-align: center;
        }
        footer nav a { color: var(--text-dim); text-decoration: none; font-size: 14px; margin: 0 12px; transition: color 0.2s; }
        footer nav a:hover { color: var(--text); }
        footer p { font-size: 13px; color: var(--text-dim); margin-top: 16px; opacity: 0.5; }

        /* Tablet — QR stacks */
        @media (max-width: 860px) {
            .hero { padding-top: 140px; min-height: 100dvh; padding-bottom: 60px; }
            .qr-layout { grid-template-columns: 1fr; text-align: center; gap: 32px; }
            .qr-box { margin: 0 auto; }
            .qr-text { display: flex; flex-direction: column; align-items: center; }
            .qr-url { align-self: center; }
        }

        /* Large mobile */
        @media (max-width: 640px) {
            body > nav { top: 10px; height: 54px; width: calc(100% - 20px); }
            .nav-inner { padding: 0 10px 0 16px; }
            .nav-actions { gap: 8px; }
            .install-card { padding: 32px 20px; }
            .install-card h2 { font-size: 22px; }
            .benefits-grid { grid-template-columns: 1fr; }
            .benefits-section { margin-top: 56px; }
            .whatis-grid { grid-template-columns: 1fr; }
            .whatis-card { padding: 22px; }
            footer nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 2px 0; }
            footer nav a { margin: 4px 10px; }
        }

        /* Medium mobile */
        @media (max-width: 480px) {
            .hero { padding-top: 80px; min-height: 100dvh; }
            .hero h1 { font-size: 40px; }
            .page-wrap { padding: 90px 16px 60px; }
            .install-card { padding: 24px 16px; }
            .install-card h2 { font-size: 20px; }
            .install-card > p { font-size: 14px; margin-bottom: 24px; }
            .tab-btn { padding: 9px 13px; font-size: 13px; }
            .platform-tabs { gap: 6px; margin-bottom: 28px; }
            .step-item { gap: 14px; padding: 18px 0; }
            .step-body h3 { font-size: 15px; }
            .benefit-card { padding: 20px; }
            .benefits-section h2 { margin-bottom: 24px; }
            .qr-box { width: 150px; height: 150px; }
        }

        /* Small mobile */
        @media (max-width: 380px) {
            body > nav { height: 50px; }
            .nav-inner { padding: 0 8px 0 14px; }
            .tab-btn { padding: 8px 10px; font-size: 12px; gap: 4px; }
            .step-num, .step-icon { width: 34px; height: 34px; font-size: 14px; }
            .install-btn-primary { padding: 14px 22px; font-size: 15px; }
            .language-switcher-pill { width: 72px; }
            .lang-slide-bg { width: 32px; }
            .theme-switcher-pill { width: 62px; }
            .theme-slide-bg { width: 27px; }
            html[lang="ar"] .lang-slide-bg { left: 37px; }
            [data-theme="dark"] .theme-slide-bg { left: 30px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-inner">
        <a href="{{ route('home') }}" class="nav-logo">
            <x-logo-text />
        </a>
        <div class="nav-actions">
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
    </div>
</nav>

<header class="hero">
    <div class="container reveal">
        <h1>{{ __('download.hero_title') }}</h1>
        <p>{{ __('download.hero_desc') }}</p>
    </div>
</header>

<div class="page-wrap">

    {{-- What is a PWA? --}}
    <section class="whatis reveal">
        <div class="whatis-head">
            <h2>{{ __('download.whatis_title') }}</h2>
            <p>{!! __('download.whatis_lead') !!}</p>
        </div>
        <div class="whatis-grid">
            <div class="whatis-card">
                <div class="whatis-card-icon"><i class="fas fa-globe"></i></div>
                <div class="whatis-card-body">
                    <h3>{{ __('download.whatis_p1_title') }}</h3>
                    <p>{{ __('download.whatis_p1_desc') }}</p>
                </div>
            </div>
            <div class="whatis-card">
                <div class="whatis-card-icon"><i class="fas fa-arrows-rotate"></i></div>
                <div class="whatis-card-body">
                    <h3>{{ __('download.whatis_p2_title') }}</h3>
                    <p>{{ __('download.whatis_p2_desc') }}</p>
                </div>
            </div>
            <div class="whatis-card">
                <div class="whatis-card-icon"><i class="fas fa-shield-halved"></i></div>
                <div class="whatis-card-body">
                    <h3>{{ __('download.whatis_p3_title') }}</h3>
                    <p>{{ __('download.whatis_p3_desc') }}</p>
                </div>
            </div>
            <div class="whatis-card">
                <div class="whatis-card-icon"><i class="fas fa-mobile-screen"></i></div>
                <div class="whatis-card-body">
                    <h3>{{ __('download.whatis_p4_title') }}</h3>
                    <p>{{ __('download.whatis_p4_desc') }}</p>
                </div>
            </div>
        </div>
        <p class="whatis-note"><i class="fas fa-circle-info"></i> {{ __('download.whatis_note') }}</p>
    </section>

    {{-- Platform tabs --}}
    <div class="reveal">
        <div class="platform-tabs">
            <button class="tab-btn" id="tab-android" onclick="switchTab('android')">
                <i class="fab fa-android"></i> {{ __('download.tab_android') }}
            </button>
            <button class="tab-btn" id="tab-ios" onclick="switchTab('ios')">
                <i class="fab fa-apple"></i> {{ __('download.tab_ios') }}
            </button>
            <button class="tab-btn" id="tab-desktop" onclick="switchTab('desktop')">
                <i class="fas fa-desktop"></i> {{ __('download.tab_desktop') }}
            </button>
        </div>

        {{-- Android panel --}}
        <div class="install-panel" id="panel-android">
            <div class="install-card">
                <h2>{{ __('download.android_title') }}</h2>
                <p>{{ __('download.android_desc') }}</p>
                <button class="install-btn-primary" id="android-install-btn" onclick="triggerInstall()">
                    <i class="fas fa-download"></i>
                    <span id="android-btn-text">{{ __('download.android_btn') }}</span>
                </button>
                <p class="install-note" id="android-install-note" style="display:none;">{{ __('download.android_note') }}</p>
                <div class="step-list" style="margin-top: 32px;">
                    <div class="step-item">
                        <div class="step-icon"><i class="fab fa-chrome"></i></div>
                        <div class="step-body">
                            <h3>{{ __('download.android_s1_title') }}</h3>
                            <p>{{ __('download.android_s1_desc') }}</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon"><i class="fas fa-ellipsis-vertical"></i></div>
                        <div class="step-body">
                            <h3>{{ __('download.android_s2_title') }}</h3>
                            <p>{{ __('download.android_s2_desc') }}</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon"><i class="fas fa-square-plus"></i></div>
                        <div class="step-body">
                            <h3>{{ __('download.android_s3_title') }}</h3>
                            <p>{!! __('download.android_s3_desc') !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- iOS panel --}}
        <div class="install-panel" id="panel-ios">
            <div class="install-card">
                <h2>{{ __('download.ios_title') }}</h2>
                <p>{{ __('download.ios_desc') }}</p>
                <div class="step-list">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-body">
                            <h3>{{ __('download.ios_s1_title') }}</h3>
                            <p>{{ __('download.ios_s1_desc') }}</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-body">
                            <h3>{{ __('download.ios_s2_title') }}</h3>
                            <p>{!! __('download.ios_s2_desc') !!} <i class="fas fa-arrow-up-from-bracket" style="font-size:12px;"></i></p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-body">
                            <h3>{{ __('download.ios_s3_title') }}</h3>
                            <p>{!! __('download.ios_s3_desc') !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Desktop panel --}}
        <div class="install-panel" id="panel-desktop">
            <div class="install-card">
                <div class="qr-layout">
                    <div class="qr-box">
                        <img id="qr-img" src="" alt="{{ __('download.qr_alt') }}" loading="lazy">
                    </div>
                    <div class="qr-text">
                        <h2>{{ __('download.desktop_title') }}</h2>
                        <p>{{ __('download.desktop_desc') }}</p>
                        <div class="qr-url" onclick="copyUrl()" title="{{ __('download.copy_link') }}">
                            <i class="fas fa-link"></i>
                            <span id="url-display"></span>
                            <i class="fas fa-copy" id="copy-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="step-list" style="margin-top: 40px;">
                    <div class="step-item">
                        <div class="step-icon"><i class="fab fa-chrome"></i></div>
                        <div class="step-body">
                            <h3>{{ __('download.desktop_s1_title') }}</h3>
                            <p>{!! __('download.desktop_s1_desc') !!}</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon"><i class="fas fa-window-maximize"></i></div>
                        <div class="step-body">
                            <h3>{{ __('download.desktop_s2_title') }}</h3>
                            <p>{!! __('download.desktop_s2_desc') !!}</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon"><i class="fas fa-download"></i></div>
                        <div class="step-body">
                            <h3>{{ __('download.desktop_s3_title') }}</h3>
                            <p>{{ __('download.desktop_s3_desc') }}</p>
                        </div>
                    </div>
                </div>
                <div style="text-align:center; margin-top: 24px;">
                    <button class="install-btn-primary" id="desktop-install-btn" onclick="triggerInstall()">
                        <i class="fas fa-download"></i>
                        <span>{{ __('download.desktop_install_btn') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Benefits --}}
    <div class="benefits-section reveal">
        <h2>{{ __('download.benefits_title') }}</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-bolt"></i></div>
                <h3>{{ __('download.benefit1_title') }}</h3>
                <p>{{ __('download.benefit1_desc') }}</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-cloud-arrow-down"></i></div>
                <h3>{{ __('download.benefit2_title') }}</h3>
                <p>{{ __('download.benefit2_desc') }}</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-bell"></i></div>
                <h3>{{ __('download.benefit3_title') }}</h3>
                <p>{{ __('download.benefit3_desc') }}</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-expand"></i></div>
                <h3>{{ __('download.benefit4_title') }}</h3>
                <p>{{ __('download.benefit4_desc') }}</p>
            </div>
        </div>
    </div>

</div>

<footer>
    <nav>
        <a href="{{ route('home') }}">{{ __('download.footer_home') }}</a>
        <a href="{{ route('privacy') }}">{{ __('download.footer_privacy') }}</a>
        <a href="{{ route('terms') }}">{{ __('download.footer_terms') }}</a>
        <a href="{{ route('cookies') }}">{{ __('download.footer_cookies') }}</a>
        <a href="mailto:socialapp.noreply@gmail.com">{{ __('download.footer_support') }}</a>
    </nav>
    <p>{{ __('download.footer_copy') }}</p>
</footer>

<script>
    var __dl = {
        androidBtnReady:    "{{ __('download.android_btn_ready') }}",
        toastAlreadyInstalled: "{{ __('download.toast_already_installed') }}",
        toastInstalled:     "{{ __('download.toast_installed') }}",
        toastIos:           "{{ __('download.toast_ios') }}",
        toastAndroid:       "{{ __('download.toast_android') }}",
        toastDesktop:       "{{ __('download.toast_desktop') }}",
    };
    // ── Theme ──
    function updateThemeUI(t) {
        document.querySelectorAll('.theme-option-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-theme-btn') === t);
        });
    }
    function toggleTheme() {
        const t = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        if (document.startViewTransition && !window.matchMedia('(max-width:768px)').matches) {
            document.documentElement.classList.add('switching-theme');
            document.startViewTransition(() => {
                document.documentElement.setAttribute('data-theme', t);
                localStorage.setItem('theme', t);
                updateThemeUI(t);
            }).finished.finally(() => document.documentElement.classList.remove('switching-theme'));
        } else {
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('theme', t);
            updateThemeUI(t);
        }
    }
    updateThemeUI(document.documentElement.getAttribute('data-theme') || 'dark');

    // ── Toast ──
    var _toastTimer = null;
    function showToast(msg) {
        var t = document.getElementById('install-toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'install-toast';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(function() { t.classList.remove('show'); }, 3500);
    }

    // ── PWA install prompt ──
    var _installPrompt = null;
    var _alreadyInstalled = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;

    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        _installPrompt = e;
        var androidBtn = document.getElementById('android-btn-text');
        if (androidBtn) androidBtn.textContent = __dl.androidBtnReady;
        var note = document.getElementById('android-install-note');
        if (note) note.style.display = 'none';
    });

    window.addEventListener('appinstalled', function() {
        _installPrompt = null;
        _alreadyInstalled = true;
        showToast(__dl.toastInstalled);
    });

    function triggerInstall() {
        if (_alreadyInstalled) {
            showToast(__dl.toastAlreadyInstalled);
            return;
        }
        if (_installPrompt) {
            _installPrompt.prompt();
            _installPrompt.userChoice.then(function(result) {
                if (result.outcome === 'accepted') _installPrompt = null;
            });
            return;
        }
        var p = detectPlatform();
        if (p === 'ios') {
            showToast(__dl.toastIos);
        } else if (p === 'android') {
            showToast(__dl.toastAndroid);
            var note = document.getElementById('android-install-note');
            if (note) note.style.display = 'block';
        } else {
            showToast(__dl.toastDesktop);
        }
    }

    // ── Platform detection & auto-tab ──
    function detectPlatform() {
        var ua = navigator.userAgent;
        var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var isAndroid = /Android/.test(ua);
        if (isIOS) return 'ios';
        if (isAndroid) return 'android';
        return 'desktop';
    }

    function switchTab(name) {
        ['android', 'ios', 'desktop'].forEach(function(p) {
            document.getElementById('tab-' + p).classList.toggle('active', p === name);
            var panel = document.getElementById('panel-' + p);
            panel.classList.toggle('active', p === name);
            if (p === name) { panel.style.animation = 'none'; panel.offsetHeight; panel.style.animation = ''; }
        });
    }

    // ── QR code ──
    (function() {
        var url = window.location.origin + '/download';
        var qr = document.getElementById('qr-img');
        if (qr) qr.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&color=000000&bgcolor=ffffff&data=' + encodeURIComponent(url);
        var display = document.getElementById('url-display');
        if (display) display.textContent = window.location.hostname + '/download';
    })();

    function copyUrl() {
        navigator.clipboard.writeText(window.location.origin + '/download').then(function() {
            var icon = document.getElementById('copy-icon');
            if (icon) { icon.className = 'fas fa-check'; setTimeout(function() { icon.className = 'fas fa-copy'; }, 1800); }
        });
    }

    // ── Reveal Animation ──
    var revealObserver = new IntersectionObserver(function(es) {
        es.forEach(function(e) {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                revealObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });
    requestAnimationFrame(function() {
        document.querySelectorAll('.reveal').forEach(function(el) {
            revealObserver.observe(el);
        });
    });

    // ── Boot ──
    document.addEventListener('DOMContentLoaded', function() {
        switchTab(detectPlatform());
    });
</script>

</body>
</html>
