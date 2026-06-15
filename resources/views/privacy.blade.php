<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">

    <title data-t="home.nexus" data-t-type="title">Privacy & Security — Nexus</title>
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
        html { scroll-behavior: smooth; background: var(--bg); }
        [data-theme="light"] html { background: #ffffff; }
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
        .nav-inner { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 12px 0 24px; height: 100%; }
        .nav-logo img { height: 32px; width: auto; transition: 0.3s; }

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
        @media (prefers-reduced-motion: no-preference) {
            .hero { animation: nx-reveal 0.8s cubic-bezier(0.16,1,0.3,1) 0.05s both; }
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
        .footer-grid { 
            display: grid; 
            grid-template-columns: 2fr 1fr 1fr 1fr; 
            gap: 60px; 
            text-align: left; 
            margin-bottom: 80px;
        }
        html[lang="ar"] .footer-grid { text-align: right; }
        .footer-brand { display: flex; flex-direction: column; gap: 20px; }
        .footer-brand h2 { 
            font-size: 28px; 
            font-weight: 800; 
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--text) 0%, var(--text-dim) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .footer-brand p { 
            font-size: 15px; 
            color: var(--text-dim); 
            line-height: 1.6; 
            max-width: 320px; 
        }
        .footer-socials { display: flex; gap: 16px; margin-top: 10px; }
        .social-link { 
            width: 36px; height: 36px; border-radius: 50%; 
            background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-dim); text-decoration: none; transition: 0.3s;
        }
        .social-link:hover { 
            background: var(--primary); color: #fff; border-color: var(--primary);
            transform: translateY(-3px);
        }
        .footer-col h5 { 
            font-size: 12px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.15em; 
            color: var(--text); 
            margin-bottom: 28px; 
            opacity: 0.9;
        }
        .footer-col a { 
            display: block; 
            font-size: 14px; 
            color: var(--text-dim); 
            text-decoration: none; 
            margin-bottom: 14px; 
            transition: 0.3s; 
            width: fit-content;
        }
        .footer-col a:hover { color: var(--primary); transform: translateX(4px); }
        html[lang="ar"] .footer-col a:hover { transform: translateX(-4px); }

        .footer-bottom {
            padding-top: 40px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text-dim);
            opacity: 0.6;
        }
        .footer-legal { display: flex; gap: 24px; }
        .footer-legal a { color: var(--text-dim); text-decoration: none; transition: 0.3s; }
        .footer-legal a:hover { color: var(--text); }

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

        @media (max-width: 900px) {
            .section-focus { grid-template-columns: 1fr; gap: 40px; text-align: center; }
            .section-focus.flipped { direction: ltr; }
            .focus-visual { order: -1; aspect-ratio: 16/9; }
            .hero { padding-top: 140px; min-height: 100dvh; padding-bottom: 60px; }
        }

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

<nav>
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <x-logo-text />
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
        <h1 data-t="home.privacy_first_title">{{ __('home.privacy_first_title') }}</h1>
        <p data-t="home.privacy_hero_desc">{{ __('home.privacy_hero_desc') }}</p>
    </div>
</header>

<main class="container">
    <!-- Section 1: Data Philosophy -->
    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.data_philosophy">{{ __('home.data_philosophy') }}</h2>
            <p data-t="home.data_philosophy_desc">{{ __('home.data_philosophy_desc') }}</p>
            <div class="focus-features">
                <div class="feature-box">
                    <i class="fas fa-eye-slash"></i>
                    <h3 data-t="home.zero_tracking">{{ __('home.zero_tracking') }}</h3>
                    <p data-t="home.zero_tracking_item_desc">{{ __('home.zero_tracking_item_desc') }}</p>
                </div>
                <div class="feature-box">
                    <i class="fas fa-lock"></i>
                    <h3 data-t="home.encrypted_storage">{{ __('home.encrypted_storage') }}</h3>
                    <p data-t="home.encrypted_storage_item_desc">{{ __('home.encrypted_storage_item_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: App Security (Flipped) -->
    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.app_security">{{ __('home.app_security') }}</h2>
            <p data-t="home.app_security_desc">{{ __('home.app_security_desc') }}</p>
            <div class="focus-features">
                <div class="feature-box">
                    <i class="fas fa-user-lock"></i>
                    <h3 data-t="home.biometric_lock_item">{{ __('home.biometric_lock_item') }}</h3>
                    <p data-t="home.biometric_lock_item_desc">{{ __('home.biometric_lock_item_desc') }}</p>
                </div>
                <div class="feature-box">
                    <i class="fas fa-server"></i>
                    <h3 data-t="home.screenshot_protection">{{ __('home.screenshot_protection') }}</h3>
                    <p data-t="home.screenshot_protection_desc">{{ __('home.screenshot_protection_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Community Privacy -->
    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.community_privacy">{{ __('home.community_privacy') }}</h2>
            <p data-t="home.community_privacy_desc">{{ __('home.community_privacy_desc') }}</p>
            <div class="focus-features">
                <div class="feature-box">
                    <i class="fas fa-mask"></i>
                    <h3 data-t="home.anonymous_posting">{{ __('home.anonymous_posting') }}</h3>
                    <p data-t="home.anonymous_posting_desc">{{ __('home.anonymous_posting_desc') }}</p>
                </div>
                <div class="feature-box">
                    <i class="fas fa-users-viewfinder"></i>
                    <h3 data-t="home.private_communities">{{ __('home.private_communities') }}</h3>
                    <p data-t="home.private_communities_desc">{{ __('home.private_communities_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 4: Data Rights (Flipped) -->
    <section class="section-focus reveal">
        <div class="focus-text">
            <h2 data-t="home.data_rights">{{ __('home.data_rights') }}</h2>
            <p data-t="home.data_rights_desc">{{ __('home.data_rights_desc') }}</p>
            <div class="focus-features">
                <div class="feature-box">
                    <i class="fas fa-trash-can"></i>
                    <h3 data-t="home.instant_deletion">{{ __('home.instant_deletion') }}</h3>
                    <p data-t="home.instant_deletion_desc">{{ __('home.instant_deletion_desc') }}</p>
                </div>
                <div class="feature-box">
                    <i class="fas fa-list-check"></i>
                    <h3 data-t="home.activity_logs_item">{{ __('home.activity_logs_item') }}</h3>
                    <p data-t="home.activity_logs_item_desc">{{ __('home.activity_logs_item_desc') }}</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-bottom" style="border-top: none; padding-top: 0; flex-direction: column; gap: 32px; text-align: center;">
            <div class="footer-brand" style="align-items: center; text-align: center;">
                <h2 data-t="home.nexus">Nexus</h2>
                <p data-t="home.nexus_is_built" style="max-width: 100%;">{{ __('home.nexus_is_built') }}</p>
                <div class="footer-socials">
                </div>
            </div>
            
            <div class="footer-legal">
                <a href="{{ route('home') }}" data-t="home.back_to_home">{{ __('home.back_to_home') }}</a>
                <a href="{{ route('terms') }}" data-t="home.terms_of_service">{{ __('home.terms_of_service') }}</a>
                <a href="{{ route('cookies') }}" data-t="home.cookies_policy">{{ __('home.cookies_policy') }}</a>
                <a href="mailto:socialapp.noreply@gmail.com" data-t="home.support">{{ __('home.support') }}</a>
            </div>

            <div style="opacity: 0.5; font-size: 13px;">
                © 2026 Nexus Global Architecture. <span data-t="home.built_for_authentic_connections">{{ __('home.built_for_authentic_connections') }}</span>
            </div>
        </div>
    </div>
</footer>

<script>
    // Real-time Translation Dictionary (Sync with Landing)
    @php
        $enHome = include resource_path('lang/en/home.php');
        $arHome = include resource_path('lang/ar/home.php');
        $translations = ['en' => $enHome, 'ar' => $arHome];
    @endphp
    const translations = @json($translations);

    window.switchRealTimeLanguage = function(locale) {
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
        
        // Update elements with data-t
        document.querySelectorAll('[data-t]').forEach(el => {
            const key = el.getAttribute('data-t').replace('home.', '');
            const type = el.getAttribute('data-t-type') || 'text';
            const translation = translations[locale][key];
            
            if (translation) {
                if (type === 'text') el.innerHTML = translation;
                else if (type === 'placeholder') el.placeholder = translation;
                else if (type === 'title') document.title = translation;
            }
        });

        // Update switcher UI
        if (typeof updateSwitcherUI === 'function') updateSwitcherUI(locale);
        
        // Silent sync with server
        fetch(`/lang/${locale}?silent=1`).catch(() => {});
        
        // Save to localStorage
        localStorage.setItem('locale', locale);
    };

    // Theme Logic (Sync with Landing)
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

    // Reveal Animation Logic
    const observer = new IntersectionObserver((es) => {
        es.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });

    requestAnimationFrame(() => {
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    });

    // Init
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    updateThemeUI(currentTheme);
</script>

</body>
</html>
