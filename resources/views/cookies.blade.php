<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title data-t="home.cookies_title" data-t-type="title">{{ __('home.cookies_title') }} — Nexus</title>
    <link rel="stylesheet" href="/fonts/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t)})();</script>

    <style>
        :root {
            --bg: #000000;
            --bg-rgb: 0, 0, 0;
            --bg-secondary: #0a0a0b;
            --text: #f5f5f7;
            --text-dim: rgba(255,255,255,0.55);
            --primary: #0071e3;
            --primary-glow: rgba(0, 113, 227, 0.4);
            --border: rgba(255, 255, 255, 0.08);
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            --glass: rgba(255, 255, 255, 0.02);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        [data-theme="light"] {
            --bg: #ffffff;
            --bg-rgb: 255, 255, 255;
            --bg-secondary: #f5f5f7;
            --text: #1d1d1f;
            --text-dim: #6e6e73;
            --border: rgba(0, 0, 0, 0.08);
            --glass: rgba(0, 0, 0, 0.01);
            --glass-border: rgba(0, 0, 0, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { 
            background: var(--bg); 
            color: var(--text); 
            font-family: 'Inter', -apple-system, sans-serif; 
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
            will-change: background-color, color;
        }

        /* Cinema-Grade Smooth Transitions */
        body:not(.switching-theme), 
        nav:not(.switching-theme), 
        .hero:not(.switching-theme), 
        .section-focus:not(.switching-theme), 
        footer:not(.switching-theme), 
        .back-home-btn:not(.switching-theme), 
        .feature-box:not(.switching-theme), 
        .focus-visual:not(.switching-theme), 
        .theme-switcher-pill, .language-switcher-pill {
            transition: background-color 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                        color 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                        border-color 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation-duration: 0.3s;
            animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
        }

        html[lang="ar"] body { font-family: 'Cairo', sans-serif; }
        html[lang="ar"] * { letter-spacing: normal !important; }
        html[lang="ar"] .hero h1 { line-height: 1.2; padding: 10px 0; }

        /* --- BACKGROUND BLOOMS --- */
        .blooms {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: -1; overflow: hidden;
        }
        .bloom {
            position: absolute; border-radius: 50%; filter: blur(120px);
            opacity: 0.15; transition: all 1s ease;
        }
        .bloom-1 { top: -10%; left: 20%; width: 40%; height: 40%; background: var(--primary); }
        .bloom-2 { bottom: 10%; right: 10%; width: 30%; height: 30%; background: #6366f1; }
        [data-theme="light"] .bloom { opacity: 0.05; }

        /* --- NAVIGATION --- */
        nav {
            position: fixed; top: 24px; left: 50%; transform: translateX(-50%);
            width: calc(100% - 48px); max-width: 1000px; height: 64px;
            background: rgba(var(--bg-rgb), 0.6);
            backdrop-filter: saturate(200%) blur(30px);
            -webkit-backdrop-filter: saturate(200%) blur(30px);
            z-index: 1000; 
            border: 1px solid var(--border);
            border-radius: 100px;
            display: flex; justify-content: center;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }
        nav::before {
            content: ''; position: absolute; inset: 0; border-radius: 100px;
            padding: 1px; background: linear-gradient(to bottom, rgba(255,255,255,0.1), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude;
            pointer-events: none;
        }
        nav::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 8s infinite;
        }
        @keyframes shimmer { 0% { left: -100%; } 30% { left: 200%; } 100% { left: 200%; } }

        .nav-inner { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 12px 0 24px; height: 100%; }
        .nav-logo img { height: 32px; width: auto; transition: 0.3s; }
        .logo-white, .logo-black { display: none !important; }
        html[data-theme="dark"] .logo-white { display: block !important; }
        html[data-theme="light"] .logo-black { display: block !important; }

        .nav-actions { display: flex; gap: 12px; align-items: center; }

        .back-home-btn {
            display: flex; align-items: center; gap: 8px;
            color: var(--text); text-decoration: none; font-size: 13px; font-weight: 600;
            opacity: 0.6; transition: 0.3s; padding: 8px 12px; border-radius: 100px;
        }
        .back-home-btn:hover { opacity: 1; background: var(--glass); }

        /* Switchers */
        .language-switcher-pill, .theme-switcher-pill {
            display: flex; align-items: center; background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 100px;
            padding: 3px; position: relative; height: 34px; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            user-select: none; direction: ltr !important;
        }
        .language-switcher-pill { width: 82px; }
        .theme-switcher-pill { width: 72px; }
        .lang-slide-bg, .theme-slide-bg {
            position: absolute; top: 3px; left: 3px; height: 26px;
            background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 100px; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .lang-slide-bg { width: 37px; }
        .theme-slide-bg { width: 31px; }
        html[lang="ar"] .lang-slide-bg { left: 41px; }
        [data-theme="dark"] .theme-slide-bg { left: 35px; }
        .lang-option-btn, .theme-option-btn {
            flex: 1; display: flex; align-items: center; justify-content: center;
            color: rgba(255, 255, 255, 0.5); z-index: 2; font-size: 11px;
            font-weight: 700; transition: all 0.3s ease; height: 100%;
        }
        .lang-option-btn.active, .theme-option-btn.active { color: #ffffff; }
        [data-theme="light"] .language-switcher-pill, [data-theme="light"] .theme-switcher-pill {
            background: rgba(0, 0, 0, 0.05); border-color: rgba(0, 0, 0, 0.08);
        }
        [data-theme="light"] .lang-slide-bg, [data-theme="light"] .theme-slide-bg {
            background: #ffffff; border-color: rgba(0, 0, 0, 0.05);
        }
        [data-theme="light"] .lang-option-btn, [data-theme="light"] .theme-option-btn { color: rgba(0, 0, 0, 0.4); }
        [data-theme="light"] .lang-option-btn.active, [data-theme="light"] .theme-option-btn.active { color: #111111; }

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

        /* --- ARCHITECTURAL SECTIONS --- */
        .container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
        
        .section-focus {
            padding: 80px 0;
            max-width: 800px;
            margin: 0 auto;
        }
        .focus-text { text-align: left; }
        html[lang="ar"] .focus-text { text-align: right; }

        .focus-text h2 {
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }
        .focus-text p {
            font-size: 19px;
            color: var(--text-dim);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .focus-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .focus-features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 48px;
            margin-top: 32px;
        }
        .feature-box {
            background: transparent;
            border: none;
            padding: 0;
            transition: none;
        }
        .feature-box h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
        }
        .feature-box p {
            font-size: 16px;
            color: var(--text-dim);
            line-height: 1.6;
        }

        .focus-visual {
            display: none;
        }

        /* --- FOOTER --- */
        footer { 
            padding: 120px 0 60px; 
            border-top: 1px solid var(--border); 
            background: linear-gradient(to bottom, transparent, rgba(var(--bg-rgb), 0.5));
            position: relative;
            overflow: hidden;
            margin-top: 100px;
        }
        footer::before {
            content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
            width: 80%; height: 1px; background: linear-gradient(90deg, transparent, var(--primary), transparent);
            opacity: 0.2;
        }
        .footer-brand { display: flex; flex-direction: column; gap: 20px; }
        .footer-brand h2 { 
            font-size: 28px; 
            font-weight: 800; 
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--text) 0%, var(--text-dim) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .footer-brand p { font-size: 15px; color: var(--text-dim); line-height: 1.6; }

        .footer-bottom {
            padding-top: 40px;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 32px;
            align-items: center;
            text-align: center;
        }
        .footer-legal { display: flex; gap: 24px; }
        .footer-legal a { color: var(--text-dim); text-decoration: none; transition: 0.3s; font-size: 14px; }
        .footer-legal a:hover { color: var(--text); }

        /* Reveal Animations */
        .reveal { opacity: 0; transform: translateY(40px); transition: 1.2s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr; gap: 40px; text-align: center; }
            .footer-brand { align-items: center; }
            .footer-legal { flex-direction: column; gap: 16px; align-items: center; }
        }

        @media (max-width: 480px) {
            nav { top: 12px; height: 56px; width: calc(100% - 24px); }
            .nav-logo img { height: 26px; }
            .back-home-btn span { display: none; }
            .hero { padding-top: 80px; min-height: 100dvh; }
            .hero h1 { font-size: 40px; }
            .focus-features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="blooms">
    <div class="bloom bloom-1"></div>
    <div class="bloom bloom-2"></div>
</div>

<nav>
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <img src="{{ asset('images/nexus-logo-white.png') }}" alt="Nexus" class="logo-white">
            <img src="{{ asset('images/nexus-logo-black.png') }}" alt="Nexus" class="logo-black">
        </a>
        
        <div class="nav-actions">
            <a href="{{ route('home') }}" class="back-home-btn">
                <i class="fas fa-home"></i>
                <span data-t="home.back">{{ __('home.back') }}</span>
            </a>
            
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
        <h1 data-t="home.cookies_title">{{ __('home.cookies_title') }}</h1>
        <p data-t="home.cookies_hero_desc">{{ __('home.cookies_hero_desc') }}</p>
    </div>
</header>

<main class="container">
    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.essential_cookies">{{ __('home.essential_cookies') }}</h2>
            <p data-t="home.essential_cookies_desc">{{ __('home.essential_cookies_desc') }}</p>
        </div>
    </section>

    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.no_tracking_cookies">{{ __('home.no_tracking_cookies') }}</h2>
            <p data-t="home.no_tracking_cookies_desc">{{ __('home.no_tracking_cookies_desc') }}</p>
        </div>
    </section>

    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.pref_cookies">{{ __('home.pref_cookies') }}</h2>
            <p data-t="home.pref_cookies_desc">{{ __('home.pref_cookies_desc') }}</p>
        </div>
    </section>

    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.how_to_manage">{{ __('home.how_to_manage') }}</h2>
            <p data-t="home.how_to_manage_desc">{{ __('home.how_to_manage_desc') }}</p>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom">
            <div class="footer-brand">
                <h2 data-t="home.nexus">Nexus</h2>
                <p data-t="home.nexus_is_built">{{ __('home.nexus_is_built') }}</p>
            </div>
            
            <div class="footer-legal">
                <a href="{{ route('home') }}" data-t="home.back_to_home">{{ __('home.back_to_home') }}</a>
                <a href="{{ route('privacy') }}" data-t="home.privacy_nav">{{ __('home.privacy_nav') }}</a>
                <a href="{{ route('terms') }}" data-t="home.terms_of_service">{{ __('home.terms_of_service') }}</a>
                <a href="mailto:socialapp.noreply@gmail.com" data-t="home.support">{{ __('home.support') }}</a>
            </div>

            <div style="opacity: 0.5; font-size: 13px;">
                © 2026 Nexus Global Architecture. <span data-t="home.built_for_authentic_connections">{{ __('home.built_for_authentic_connections') }}</span>
            </div>
        </div>
    </div>
</footer>

<script>
    @php
        $enHome = include resource_path('lang/en/home.php');
        $arHome = include resource_path('lang/ar/home.php');
        $translations = ['en' => $enHome, 'ar' => $arHome];
    @endphp
    const translations = @json($translations);

    window.switchRealTimeLanguage = function(locale) {
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
        document.querySelectorAll('[data-t]').forEach(el => {
            const key = el.getAttribute('data-t').replace('home.', '');
            const type = el.getAttribute('data-t-type') || 'text';
            const translation = translations[locale][key];
            if (translation) {
                if (type === 'text') el.innerHTML = translation;
                else if (type === 'title') document.title = translation;
            }
        });
        if (typeof updateSwitcherUI === 'function') updateSwitcherUI(locale);
        fetch(`/lang/${locale}?silent=1`).catch(() => {});
        localStorage.setItem('locale', locale);
    };

    function updateThemeUI(t) {
        document.querySelectorAll('.theme-option-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-theme-btn') === t);
        });
    }

    function toggleTheme() {
        const t = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (document.startViewTransition && !isMobile) {
            document.documentElement.classList.add('switching-theme');
            const transition = document.startViewTransition(() => {
                document.documentElement.setAttribute('data-theme', t);
                localStorage.setItem('theme', t);
                updateThemeUI(t);
            });
            transition.finished.finally(() => {
                document.documentElement.classList.remove('switching-theme');
            });
        } else {
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('theme', t);
            updateThemeUI(t);
        }
    }

    const observer = new IntersectionObserver((es) => {
        es.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    updateThemeUI(currentTheme);
</script>

</body>
</html>
