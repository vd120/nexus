<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="no-transition">
<head>
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);document.documentElement.style.background=t==='dark'?'#000000':'#ffffff'})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">

    <title data-t="home.nexus" data-t-type="title">Nexus — {{ __('home.your_social_platform') }}</title>
    <link rel="stylesheet" href="/fonts/all.css">
    <link rel="preload" href="/vendor/fontawesome/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css"></noscript>

    <style>
        :root {
            --bg: #000000;
            --bg-rgb: 0, 0, 0;
            --bg-secondary: #0a0a0b;
            --text: #f5f5f7;
            --text-dim: rgba(255,255,255,0.55);
            --primary: #0071e3;
            --border: rgba(255, 255, 255, 0.08);
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        html { background-color: #000000; }
        html[data-theme="light"] { background-color: #ffffff; }
        html[data-theme="dark"] { background-color: #000000; }

        [data-theme="light"] {
            --bg: #ffffff;
            --bg-rgb: 255, 255, 255;
            --bg-secondary: #f5f5f7;
            --text: #1d1d1f;
            --text-dim: #6e6e73;
            --border: rgba(0, 0, 0, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; -webkit-tap-highlight-color: transparent; }
        body { 
            background: var(--bg); 
            color: var(--text); 
            font-family: 'Inter', -apple-system, sans-serif; 
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            line-height: 1.47;
            will-change: background-color, color;
        }

        /* Cinema-Grade Smooth Transitions */
        html:not(.no-transition) body:not(.switching-theme), 
        html:not(.no-transition) nav:not(.switching-theme), 
        html:not(.no-transition) .hero:not(.switching-theme), 
        html:not(.no-transition) .section-focus:not(.switching-theme), 
        html:not(.no-transition) .grid-section:not(.switching-theme), 
        html:not(.no-transition) footer:not(.switching-theme),
        html:not(.no-transition) .simple-btn:not(.switching-theme), 
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

        .card {
            transition: background 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                        color 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                        border-color 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.5s ease;
        }

        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation-duration: 0.4s;
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

        /* Nav Glassmorphism Enhancement */
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
            transition: var(--transition);
            overflow: visible;
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
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; height: 100%; min-width: 40px; }
        .nav-logo img { height: 96px; width: auto; transition: 0.3s; display: block; }
        .nav-logo:hover img { transform: scale(1.05); }
        
        .logo-white, .logo-black { display: none !important; }
        html[data-theme="dark"] .logo-white { display: block !important; }
        html[data-theme="light"] .logo-black { display: block !important; }
        
        .nav-links { display: flex; gap: 32px; align-items: center; }
        .nav-links a { color: var(--text); text-decoration: none; font-size: 13px; font-weight: 500; opacity: 0.6; transition: 0.3s; }
        .nav-links a:hover { opacity: 1; }

        /* Header Actions & Precision Switchers */
        .nav-actions { display: flex; gap: 12px; align-items: center; }
        
        /* Standardized Language & Theme Switchers */
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

        .language-switcher-pill:hover, .theme-switcher-pill:hover {
            background: rgba(255, 255, 255, 0.08); border-color: rgba(255, 255, 255, 0.2);
        }

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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        [data-theme="light"] .lang-option-btn, [data-theme="light"] .theme-option-btn { color: rgba(0, 0, 0, 0.4); }
        [data-theme="light"] .lang-option-btn.active, [data-theme="light"] .theme-option-btn.active { color: #111111; }
        [data-theme="light"] .btn-sun.active { color: #f59e0b; }
        .btn-moon.active { color: #fbbf24; }

        @media (max-width: 900px) {
            .nav-links { display: none; }
            .nav-inner { padding: 0 16px; }
            .nav-actions { gap: 8px; }
        }

        @media (max-width: 480px) {
            nav { top: 12px; height: 56px; width: calc(100% - 24px); }
            .nav-logo img { height: 72px; }
            .language-switcher-pill { width: 72px; height: 30px; }
            .lang-slide-bg { width: 32px; height: 22px; }
            html[lang="ar"] .lang-slide-bg { left: 36px; }
            .theme-switcher-pill { width: 62px; height: 30px; }
            .theme-slide-bg { width: 27px; height: 22px; }
            [data-theme="dark"] .theme-slide-bg { left: 31px; }
            .lang-option-btn, .theme-option-btn { font-size: 10px; }
        }

        /* Container */
        .container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }

        /* Hero - Full Screen Viewport */
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
            background: var(--bg);
            overflow: hidden;
        }

        .hero .container {
            position: relative;
            z-index: 10;
        }
        .hero h1 { 
            font-size: clamp(18px, 3vw, 24px); 
            font-weight: 700; 
            letter-spacing: 0.4em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 32px;
            opacity: 0.9;
        }
        .hero p { 
            font-size: clamp(32px, 5.5vw, 62px); 
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15; 
            max-width: 1100px; 
            margin: 0 auto 64px; 
            padding: 10px 0;
            background: linear-gradient(180deg, var(--text) 50%, var(--text-dim) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.15));
        }
        
        .btn { 
            padding: 14px 32px; border-radius: 980px; font-size: 18px; font-weight: 600; 
            text-decoration: none; display: inline-block; transition: 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .btn-blue { 
            background: var(--primary); 
            color: #fff !important; 
            border: none; 
            cursor: pointer;
            padding: 18px 64px;
            font-size: 22px;
            letter-spacing: -0.01em;
        }
        .btn-blue:hover { transform: scale(1.02); filter: brightness(1.1); }
        .btn-glass { 
            background: rgba(255, 255, 255, 0.05); 
            color: var(--text); 
            border: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }
        .btn-glass:hover { background: rgba(255, 255, 255, 0.1); transform: scale(1.02); }

        /* Pristine Simple CTA */
        .simple-cta-container {
            display: flex; justify-content: center; align-items: center;
            min-height: 80px; position: relative; margin-top: 40px;
        }
        
        #getStartedSimple { 
            position: relative; z-index: 100; transition: all 0.4s ease;
            cursor: pointer; border: none; font-weight: 700;
        }
        .simple-cta-container.active #getStartedSimple { 
            animation: glitch-cta 0.3s steps(2) forwards;
            opacity: 0; pointer-events: none; visibility: hidden;
        }

        /* The Quantum Glitch Effect */
        @keyframes glitch-cta {
            0% { transform: translate(0); clip-path: inset(0); opacity: 1; filter: hue-rotate(0deg); }
            20% { transform: translate(-8px, 4px); clip-path: inset(10% 0 60% 0); background: #ff00c1; }
            40% { transform: translate(8px, -4px); clip-path: inset(40% 0 20% 0); background: #00fff0; }
            60% { transform: translate(-4px, 8px); clip-path: inset(80% 0 5% 0); filter: hue-rotate(90deg); }
            80% { transform: translate(4px, -8px); clip-path: inset(30% 0 50% 0); opacity: 0.8; }
            100% { transform: translate(0); clip-path: inset(0); opacity: 0; }
        }

        .simple-reveal {
            position: absolute; top: 0; left: 50%; transform: translateX(-50%);
            display: flex; gap: 16px; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: all 0.5s ease 0.2s;
            width: 100%; z-index: 90;
        }
        .simple-cta-container.active .simple-reveal { opacity: 1; pointer-events: auto; }

        .simple-btn {
            padding: 16px 32px; border-radius: 100px; font-weight: 700; font-size: 16px;
            text-decoration: none; transition: all 0.3s;
            background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);
            border: 1px solid var(--border); color: var(--text);
        }
        .simple-btn.primary { background: var(--primary); color: #fff !important; border: none; backdrop-filter: none; }
        .simple-btn:not(.primary):hover { background: rgba(255,255,255,0.1); transform: translateY(-2px); }
        [data-theme="light"] .simple-btn:not(.primary) { background: rgba(0,0,0,0.03); }
        [data-theme="light"] .simple-btn:not(.primary):hover { background: rgba(0,0,0,0.05); }
        .simple-btn.primary:hover { background: var(--primary); color: #fff !important; transform: translateY(-2px); opacity: 0.95; }

        @media (max-width: 480px) {
            .arrows-row { width: 100%; justify-content: center; gap: 40px; }
        }

        /* Focus Sections Redesign */
        .section-focus { 
            padding: 100px 0;
            border-bottom: 1px solid var(--border);
        }
        .focus-grid { 
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }
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
            max-width: 700px;
            margin: 0 auto 40px;
        }
        .focus-visual { display: none; }
        .focus-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 24px;
            margin-bottom: 32px;
        }
        .focus-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: var(--text);
        }
        .focus-list li i { color: var(--primary); font-size: 14px; }

        .privacy-subgrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            text-align: left;
            margin-top: 40px;
        }
        html[lang="ar"] .privacy-subgrid { text-align: right; }
        @media (max-width: 768px) {
            .privacy-subgrid { grid-template-columns: 1fr; text-align: center; }
        }

        /* Grid Sections */
        .grid-section { padding: 120px 0; background: var(--bg); }
        .section-header { text-align: center; margin-bottom: 80px; }
        .section-header h2 { font-size: 48px; font-weight: 700; margin-bottom: 20px; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; }
        .card { 
            background: rgba(255, 255, 255, 0.02); 
            border: 1px solid var(--border); 
            padding: 48px; 
            border-radius: 32px; 
            position: relative; 
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .card:hover { 
            transform: translateY(-8px); 
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 40px 100px rgba(0,0,0,0.4);
        }
        .card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            opacity: 0; transition: 0.3s;
        }
        .card:hover::after { opacity: 1; }
        .card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at var(--x) var(--y), rgba(255,255,255,0.05) 0%, transparent 60%);
            opacity: 0; transition: opacity 0.4s; pointer-events: none;
        }
        .card:hover::before { opacity: 1; }
        .card h3 { font-size: 24px; font-weight: 700; margin-bottom: 16px; }
        .card p { font-size: 16px; color: var(--text-dim); line-height: 1.6; }

        /* Tech Section */
        .tech-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 80px; }
        .tech-item h4 { font-size: 20px; margin-bottom: 12px; }
        .tech-item p { color: var(--text-dim); font-size: 15px; margin-bottom: 20px; }
        
        .download-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 100px;
            background: rgba(255, 255, 255, 0.1); border: 1px solid var(--border);
            color: var(--text); text-decoration: none; font-size: 13px; font-weight: 600;
            transition: 0.3s;
        }
        .download-btn:hover { background: var(--text); color: var(--bg); transform: translateY(-2px); }

        /* PWA Banner */
        .pwa-banner { 
            padding: 120px 48px; 
            background: radial-gradient(circle at top right, #0071e3, #003a75); 
            border-radius: 48px; 
            text-align: center; 
            color: white; 
            margin: 120px 0;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 60px 120px rgba(0,0,0,0.4);
        }
        .pwa-banner h2 { font-size: clamp(32px, 6vw, 64px); font-weight: 800; margin-bottom: 24px; letter-spacing: -0.04em; }
        .pwa-banner p { font-size: 20px; opacity: 0.8; max-width: 600px; margin: 0 auto 48px; }
        .pwa-pills { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .pill { 
            padding: 12px 32px; 
            background: rgba(255,255,255,0.1); 
            border: 1px solid rgba(255,255,255,0.2); 
            border-radius: 100px; 
            font-size: 15px; 
            font-weight: 700; 
            backdrop-filter: blur(10px);
        }

        /* Download Section */
        .download-section {
            text-align: center;
            padding: 100px 24px;
            max-width: 700px;
            margin: 0 auto;
        }
        .download-section h2 {
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -0.03em;
        }
        .download-section p {
            font-size: 19px;
            color: var(--text-dim);
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .download-cta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 40px;
            border-radius: 100px;
            background: var(--text);
            color: var(--bg);
            font-size: 17px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .download-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }

        /* Footer */
        footer { 
            padding: 120px 0 60px; 
            border-top: 1px solid var(--border); 
            background: linear-gradient(to bottom, transparent, rgba(var(--bg-rgb), 0.5));
            position: relative;
            overflow: hidden;
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
            font-size: 32px; 
            font-weight: 800; 
            letter-spacing: -0.04em;
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
        }
        .footer-legal { display: flex; gap: 24px; }
        .footer-legal a { color: var(--text-dim); text-decoration: none; transition: 0.3s; opacity: 0.6; }
        .footer-legal a:hover { color: var(--text); opacity: 1; }

        /* Reveal */
        /* Framer-like Reveal */
        .reveal { 
            opacity: 0; 
            transform: translateY(40px) scale(0.95); 
            transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1), 
                        transform 1.2s cubic-bezier(0.16, 1, 0.3, 1); 
        }
        .reveal.visible { opacity: 1; transform: translateY(0) scale(1); }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(5%, 5%) scale(1.1); }
            100% { transform: translate(-2%, 8%) scale(0.9); }
        }

        /* Toast Notification */
        .toast-container {
            position: fixed; top: 32px; left: 50%; 
            transform: translateX(-50%) translateY(-150px) scale(0.9);
            background: rgba(20, 20, 20, 0.7); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15); padding: 14px 28px;
            border-radius: 100px; color: var(--text); font-weight: 700; font-size: 15px;
            z-index: 9999; transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s;
            display: flex; align-items: center; gap: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 20px rgba(0, 113, 227, 0.2);
            opacity: 0; pointer-events: none;
            width: max-content; max-width: calc(100% - 32px);
            text-align: center;
        }
        .toast-container.show { transform: translateX(-50%) translateY(0) scale(1); opacity: 1; pointer-events: auto; }
        .toast-icon { 
            flex-shrink: 0; width: 24px; height: 24px; background: var(--primary); 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            font-size: 12px; color: white; box-shadow: 0 0 15px var(--primary);
        }

        @media (max-width: 480px) {
            .toast-container { padding: 12px 20px; font-size: 14px; top: 24px; border-radius: 16px; }
        }

        .privacy-subgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; text-align: left; }
        @media (max-width: 480px) {
            .privacy-subgrid { grid-template-columns: 1fr; gap: 32px; text-align: center; }
        }

        /* Mobile Menu Styles */
        .hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; z-index: 1001; padding: 4px; }
        .hamburger .bar { width: 24px; height: 2px; background: var(--text); transition: var(--transition); }
        .hamburger.open .bar:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .hamburger.open .bar:nth-child(2) { opacity: 0; }
        .hamburger.open .bar:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        .mobile-menu {
            position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
            background: rgba(var(--bg), 0.98); backdrop-filter: blur(20px);
            z-index: 999; display: flex; flex-direction: column; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: var(--transition);
            transform: translateY(-20px);
        }
        .mobile-menu.open { opacity: 1; pointer-events: auto; transform: translateY(0); }
        .mobile-menu a { font-size: 28px; font-weight: 600; color: var(--text); text-decoration: none; margin-bottom: 32px; transition: 0.3s; }
        .mobile-menu a:active { color: var(--primary); transform: scale(0.95); }
        .mobile-switchers { display: flex; gap: 20px; margin-top: 32px; }

        /* Responsive Refinements - Bulletproof Mobile */
        @media (max-width: 1024px) {
            .container { padding: 0 24px; }
            .focus-grid { grid-template-columns: 1fr; gap: 60px; text-align: center; }
            .focus-grid.flipped { direction: ltr; }
            .focus-list { display: inline-block; text-align: left; margin: 0 auto; }
            .section-focus { padding: 80px 0; }
            .feature-grid, .tech-grid, .footer-grid { grid-template-columns: 1fr; gap: 48px; text-align: center; }
            .footer-brand { align-items: center; margin-bottom: 24px; }
            .footer-col { align-items: center; display: flex; flex-direction: column; }
            .footer-col a { width: 100%; text-align: center; padding: 8px 0; }
            .footer-bottom { flex-direction: column; gap: 24px; text-align: center; }
            .footer-legal { justify-content: center; flex-wrap: wrap; gap: 16px; }
        }

        @media (max-width: 768px) {
            html { scroll-behavior: auto !important; }
            html, body { overflow-x: hidden; width: 100%; position: relative; }
            .container { padding: 0 20px; }
            
            nav { top: 12px; width: calc(100% - 24px); height: 60px; border-radius: 24px; }
            .nav-inner { padding: 0 12px 0 16px; }
            .nav-logo img { height: 64px; }
            .nav-links { display: none !important; }
            
            .hamburger { display: flex; order: 3; }
            .nav-actions { display: none !important; }
            
            .language-switcher-pill { width: 72px; height: 32px; }
            .theme-switcher-pill { width: 64px; height: 32px; }

            .hero { padding: 0 20px; min-height: 100vh; min-height: 100dvh; }
            .hero h1 { font-size: 12px; letter-spacing: 0.3em; margin-bottom: 16px; }
            .hero p { font-size: clamp(28px, 6vw, 40px); line-height: 1.1; margin-bottom: 40px; max-width: 100%; }
            .btn, .btn-blue { width: 100%; max-width: 260px; font-size: 16px; padding: 12px 20px; }

            .section-focus, .grid-section { padding: 100px 0; }
            .focus-text h2 { font-size: 28px; margin-bottom: 12px; }
            .focus-text p { font-size: 15px; margin-bottom: 24px; }
            .focus-list { font-size: 15px; }
            .focus-visual { font-size: 48px; border-radius: 32px; }
            .privacy-subgrid { grid-template-columns: 1fr; gap: 20px; text-align: center; }
            .privacy-subgrid h4 { font-size: 16px !important; }
            
            .section-header { margin-bottom: 32px; }
            .section-header h2 { font-size: 28px; }
            .section-desc { font-size: 15px !important; }
            .card { padding: 24px; }
            .card h3 { font-size: 18px; }
            .card p { font-size: 14px; }
            
            .pwa-banner { padding: 48px 20px; margin: 40px 0; }
            .pwa-banner h2 { font-size: 26px; }
            .pwa-pills { flex-direction: column; width: 100%; }
            .pill { width: 100%; font-size: 13px; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 11px; letter-spacing: 0.25em; }
            .hero p { font-size: 24px; }
            .nav-actions { gap: 6px; }
            .focus-text h2 { font-size: 24px; }
            .focus-text p { font-size: 14px; }
            .section-header h2 { font-size: 24px; }
            
            /* Get Started Reveal Buttons */
            .simple-reveal { gap: 10px; width: 100%; max-width: 300px; }
            .simple-btn { flex: 1; padding: 12px 10px; font-size: 13px; text-align: center; white-space: nowrap; }
        }
            
            footer { padding: 60px 0 40px; }
            .footer-col { margin-bottom: 24px; text-align: center; }
            .footer-brand { text-align: center; margin-bottom: 32px; }
            .footer-brand p { margin: 0 auto; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 32px; }
            .nav-actions { gap: 4px; }
            .language-switcher-pill, .theme-switcher-pill { width: 54px; }
            .language-switcher-pill .slide-bg, .theme-switcher-pill .slide-bg { width: 25px; height: 24px; }
            html[lang="ar"] .language-switcher-pill .slide-bg { transform: translateX(25px); }
            [data-theme="dark"] .theme-switcher-pill .slide-bg { transform: translateX(25px); }
            .option-btn { width: 25px; font-size: 8px; }
        }

        /* Typing Effect Styles */
        .waiting-for-typing {
            opacity: 0 !important;
            pointer-events: none;
            transition: opacity 1.2s ease, transform 1.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }
        
        .typing-complete .waiting-for-typing {
            opacity: 1 !important;
            pointer-events: auto;
            transform: translate(0) !important;
        }
        
        nav.waiting-for-typing {
            transform: translate(-50%, -20px) !important;
        }
        .typing-complete nav.waiting-for-typing {
            transform: translate(-50%, 0) !important;
        }

        #typing-text {
            display: block;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        #typing-text::after {
            content: '|';
            animation: blink 0.8s steps(2, start) infinite;
            margin-left: 6px;
            color: var(--primary);
            background: none;
            -webkit-text-fill-color: var(--primary); /* Overrides parent transparent fill */
            -webkit-background-clip: initial;        /* Overrides parent background clipping */
            font-weight: 300;
            transition: opacity 0.5s ease;
            opacity: 1;
        }

        .cursor-fade #typing-text::after {
            animation: none;
            opacity: 0;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
    </style>
</head>
<body>

<div class="blooms">
    <div class="bloom bloom-1"></div>
    <div class="bloom bloom-2"></div>
</div>

<nav class="waiting-for-typing">
    <div class="nav-inner">
        <a href="/" class="nav-logo">
            <img src="/images/nexus_logo.svg" alt="Nexus">
        </a>
        <div class="nav-links">
            <a href="/privacy" data-t="home.privacy_nav">{{ __('home.privacy_nav') }}</a>
            <a href="#features" data-t="home.features_nav">{{ __('home.features_nav') }}</a>
            <a href="#technology" data-t="home.architecture_nav">{{ __('home.architecture_nav') }}</a>
        </div>
        <div class="nav-actions">
            <!-- Language Switcher Pill -->
            @include('partials.language-switcher')

            <!-- Theme Switcher Pill -->
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
        <div class="hamburger" onclick="toggleMobileMenu()">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu" id="mobileMenu">
    <a href="{{ route('privacy') }}" onclick="toggleMobileMenu()" data-t="home.privacy_nav">{{ __('home.privacy_nav') }}</a>
    <a href="/download" onclick="toggleMobileMenu()" data-t="home.download_app" style="color: var(--primary); font-size: 32px;">{{ __('home.download_app') }}</a>
    <a href="{{ route('terms') }}" onclick="toggleMobileMenu()" data-t="home.terms_of_service">{{ __('home.terms_of_service') }}</a>
    <a href="{{ route('cookies') }}" onclick="toggleMobileMenu()" data-t="home.cookies_policy">{{ __('home.cookies_policy') }}</a>
    <a href="#features" onclick="toggleMobileMenu()" data-t="home.features_nav">{{ __('home.features_nav') }}</a>
    <a href="#technology" onclick="toggleMobileMenu()" data-t="home.architecture_nav">{{ __('home.architecture_nav') }}</a>
    
    <div class="mobile-switchers">
        @include('partials.language-switcher')
        
        <div id="themeToggleMobile" class="theme-switcher-pill" onclick="toggleTheme()" title="{{ __('home.toggle_theme') }}">
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

<section class="hero" id="hero">
    <div class="container reveal">
        <h1 data-t="home.nexus" class="stagger-1 waiting-for-typing">Nexus</h1>
        <p id="typing-text" data-t="home.connect_share_belong" style="opacity: 0;">{{ __('home.connect_share_belong') }}</p>
        <div class="simple-cta-container stagger-3 waiting-for-typing" id="simpleCta">
            <button class="btn btn-blue" id="getStartedSimple" data-t="home.get_started_free">{{ __('home.get_started_free') }}</button>
            <div class="simple-reveal">
                <a href="/register" class="simple-btn primary" data-t="home.join_nexus">{{ __('home.join_nexus') }}</a>
                <a href="/login" class="simple-btn" data-t="home.welcome_back">{{ __('home.welcome_back') }}</a>
            </div>
        </div>
    </div>
</section>

<!-- Privacy Pillar -->
<section class="section-focus" id="privacy">
    <div class="container">
        <div class="focus-grid">
            <div class="focus-text reveal">
                <h2 data-t="home.privacy_section_title">{{ __('home.privacy_section_title') }}</h2>
                <p data-t="home.nexus_mission">{{ __('home.nexus_mission') }}</p>
                <div class="privacy-subgrid">
                    <div><h4 data-t="home.no_ads_title" style="font-size: 18px; margin-bottom: 8px;">{{ __('home.no_ads_title') }}</h4><p data-t="home.no_ads_desc" style="font-size: 14px; color: var(--text-dim);">{{ __('home.no_ads_desc') }}</p></div>
                    <div><h4 data-t="home.no_tracking_title" style="font-size: 18px; margin-bottom: 8px;">{{ __('home.no_tracking_title') }}</h4><p data-t="home.no_tracking_desc" style="font-size: 14px; color: var(--text-dim);">{{ __('home.no_tracking_desc') }}</p></div>
                </div>
                <div style="margin-top: 32px;">
                    <a href="/privacy" class="btn btn-outline" style="padding: 12px 24px; border: 1px solid var(--border); border-radius: 12px; color: var(--text); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease;">
                        <i class="fas fa-book-open"></i>
                        <span data-t="home.read_full_policy">{{ __('home.read_full_policy') }}</span>
                    </a>
                </div>
            </div>
    </div>
</section>

<!-- Communities Pillar -->
<section class="section-focus">
    <div class="container">
        <div class="focus-grid">
            <div class="focus-text reveal">
                <h2 data-t="home.communities_title">Communities</h2>
                <p data-t="home.communities_desc">{{ __('home.communities_desc') }}</p>
                <ul class="focus-list">
                    <li><i class="fas fa-check-circle"></i> <span data-t="home.roles_badges">{{ __('home.roles_badges') }}</span></li>
                    <li><i class="fas fa-check-circle"></i> <span data-t="home.post_approval">{{ __('home.post_approval') }}</span></li>
                    <li><i class="fas fa-check-circle"></i> <span data-t="home.private_groups">{{ __('home.private_groups') }}</span></li>
                </ul>
            </div>
    </div>
</section>

<!-- Feature Grid -->
<section class="grid-section" id="features">
    <div class="container">
        <div class="section-header reveal">
            <h2 data-t="home.everything_you_need">Everything you need.</h2>
        </div>
        <div class="feature-grid">
            <div class="card reveal">
                <h3 data-t="home.posts">{{ __('home.posts') }}</h3>
                <p data-t="home.posts_desc">{{ __('home.posts_desc') }}</p>
            </div>
            <div class="card reveal">
                <h3 data-t="home.stories">{{ __('home.stories') }}</h3>
                <p data-t="home.stories_desc">{{ __('home.stories_desc') }}</p>
            </div>
            <div class="card reveal">
                <h3 data-t="home.private_chat">{{ __('home.private_chat') }}</h3>
                <p data-t="home.private_chat_desc">{{ __('home.private_chat_desc') }}</p>
            </div>
            <div class="card reveal">
                <h3 data-t="home.ai_assistant">AI Assistant</h3>
                <p data-t="home.ai_assistant_desc">{{ __('home.ai_assistant_desc') }}</p>
            </div>
            <div class="card reveal">
                <h3 data-t="home.global_chat">Global Chat</h3>
                <p data-t="home.global_chat_desc">{{ __('home.global_chat_desc') }}</p>
            </div>
            <div class="card reveal">
                <h3 data-t="home.qr_profile">QR Profiles</h3>
                <p data-t="home.qr_profile_desc">{{ __('home.qr_profile_desc') }}</p>
            </div>
        </div>
    </div>
</section>

<!-- Technology Architecture -->
<section class="section-focus" id="technology">
    <div class="container">
        <div class="section-header reveal">
            <h2 data-t="home.the_protocol">{{ __('home.the_protocol') }}</h2>
            <p data-t="home.protocol_desc" class="section-desc">{{ __('home.protocol_desc') }}</p>
        </div>
        <div class="tech-grid">
            <div class="tech-item reveal">
                <h4 data-t="home.pwa">{{ __('home.pwa') }}</h4>
                <p data-t="home.pwa_desc">{{ __('home.pwa_desc') }}</p>
            </div>
            <div class="tech-item reveal">
                <h4 data-t="home.platform_security">{{ __('home.platform_security') }}</h4>
                <p data-t="home.platform_security_desc">{{ __('home.platform_security_desc') }}</p>
            </div>
            <div class="tech-item reveal">
                <h4 data-t="home.realtime">Real-Time Core</h4>
                <p data-t="home.realtime_desc">{{ __('home.realtime_desc') }}</p>
            </div>
            <div class="tech-item reveal">
                <h4 data-t="home.open_source">Open Source</h4>
                <p data-t="home.open_source_desc">{{ __('home.open_source_desc') }}</p>
            </div>
            <div class="tech-item reveal">
                <h4 data-t="home.privacy_first">{{ __('home.privacy_first') }}</h4>
                <p data-t="home.encryption_desc">{{ __('home.encryption_desc') }}</p>
            </div>
            <div class="tech-item reveal">
                <h4 data-t="home.laravel_octane">{{ __('home.laravel_octane') }}</h4>
                <p data-t="home.octane_desc">{{ __('home.octane_desc') }}</p>
            </div>
        </div>
        
        <div class="pwa-banner reveal">
            <h2 data-t="home.experience_everywhere">{{ __('home.experience_everywhere') }}</h2>
            <p data-t="home.experience_desc">{{ __('home.experience_desc') }}</p>
            <div class="pwa-pills">
                <div class="pill" data-t="home.websockets">{{ __('home.websockets') }}</div>
                <div class="pill" data-t="home.push_notifications">{{ __('home.push_notifications') }}</div>
                <div class="pill" data-t="home.encrypted_storage">{{ __('home.encrypted_storage') }}</div>
            </div>
        </div>
    </div>
</section>

<section class="download-section reveal">
    <h2 data-t="home.download_section_title">{{ __('home.download_section_title') }}</h2>
    <p data-t="home.download_section_desc">{{ __('home.download_section_desc') }}</p>
    <a href="/download" class="download-cta">
        <i class="fas fa-download"></i>
        <span data-t="home.download_app">{{ __('home.download_app') }}</span>
    </a>
</section>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h2 data-t="home.nexus">Nexus</h2>
                <p data-t="home.nexus_is_built">{{ __('home.nexus_is_built') }}</p>
                <div class="footer-socials">
                </div>
            </div>
            
            <div class="footer-col">
                <h5 data-t="home.platform">{{ __('home.platform') }}</h5>
                <a href="#features" data-t="home.features_nav">{{ __('home.features_nav') }}</a>
                <a href="#technology" data-t="home.architecture_nav">{{ __('home.architecture_nav') }}</a>
                <a href="/communities" data-t="home.communities">{{ __('home.communities') }}</a>
            </div>

            <div class="footer-col">
                <h5 data-t="home.connect_footer">{{ __('home.connect_footer') }}</h5>
                <a href="/register" data-t="home.join_today">{{ __('home.join_today') }}</a>
                <a href="/login" data-t="home.sign_in">{{ __('home.sign_in') }}</a>
                <a href="mailto:socialapp.noreply@gmail.com" data-t="home.support">{{ __('home.support') }}</a>
            </div>

            <div class="footer-col">
                <h5 data-t="home.legal">{{ __('home.legal') }}</h5>
                <a href="{{ route('privacy') }}" data-t="home.privacy_nav">{{ __('home.privacy_nav') }}</a>
                <a href="{{ route('terms') }}" data-t="home.terms_of_service">{{ __('home.terms_of_service') }}</a>
                <a href="{{ route('cookies') }}" data-t="home.cookies_policy">{{ __('home.cookies_policy') }}</a>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                © 2026 Nexus Global Architecture. <span data-t="home.built_for_authentic_connections">{{ __('home.built_for_authentic_connections') }}</span>
            </div>
            <div class="footer-legal">
                <a href="{{ route('privacy') }}" data-t="home.privacy_nav">{{ __('home.privacy_nav') }}</a>
                <a href="{{ route('terms') }}" data-t="home.terms_of_service">{{ __('home.terms_of_service') }}</a>
            </div>
        </div>
    </div>
</footer>

<script>
    // Real-time Translation Dictionary
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

        // Update Logo manually if needed (though CSS handles them)
        
        // Silent sync with server
        fetch(`/lang/${locale}?silent=1`).catch(() => {});
        
        // Save to localStorage for subsequent non-refresh loads
        localStorage.setItem('locale', locale);
    };

    // Theme Logic
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
            // Direct attribute change on mobile is much smoother
            // It uses the standard CSS transitions instead of heavy snapshotting
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('theme', t);
            updateThemeUI(t);
        }
    }

    // Mobile Menu Logic
    function toggleMobileMenu() {
        document.getElementById('mobileMenu').classList.toggle('open');
        document.querySelector('.hamburger').classList.toggle('open');
        document.body.style.overflow = document.getElementById('mobileMenu').classList.contains('open') ? 'hidden' : '';
    }

    // Init
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    updateThemeUI(currentTheme);

    // Card Hover Effect (Light Follow)
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        });
    });

    // Magnetic Buttons
    document.querySelectorAll('.btn-magnetic').forEach(btn => {
        btn.addEventListener('mousemove', e => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - (rect.left + rect.width / 2);
            const y = e.clientY - (rect.top + rect.height / 2);
            btn.style.transform = `translate(${x * 0.2}px, ${y * 0.4}px)`;
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = '';
        });
    });


    // Pristine Simple Logic
    const simpleCta = document.getElementById('simpleCta');
    const getStartedSimple = document.getElementById('getStartedSimple');
    if (getStartedSimple && simpleCta) {
        getStartedSimple.addEventListener('click', function() {
            simpleCta.classList.add('active');
            // Force removal after animation to prevent ghosting
            setTimeout(() => {
                getStartedSimple.style.display = 'none';
            }, 350);
        });
    }

    // Reveal Animation
    const observer = new IntersectionObserver((es) => {
        es.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -100px 0px' });

    requestAnimationFrame(() => {
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    });

    // Stagger delays
    document.querySelectorAll('.reveal').forEach(parent => {
        const children = parent.querySelectorAll('[class*="stagger-"]');
        children.forEach(child => {
            const delayMatch = child.className.match(/stagger-(\d+)/);
            if (delayMatch) {
                child.style.transitionDelay = `${delayMatch[1] * 0.1}s`;
            }
        });
    });

    // Typing Effect Implementation
    function initTypingEffect() {
        const textElement = document.getElementById('typing-text');
        if (!textElement) return;

        // Store original text if not already stored
        if (!textElement.dataset.fullText) {
            textElement.dataset.fullText = textElement.innerText.trim();
        }
        
        const fullText = textElement.dataset.fullText;
        textElement.innerText = '';
        textElement.style.opacity = '1';
        
        let i = 0;
        const speed = 35; // Perfect balanced typing sweet spot!

        function type() {
            if (i < fullText.length) {
                textElement.textContent += fullText.charAt(i);
                i++;
                setTimeout(type, speed);
            } else {
                // Instantly trigger staggered reveal
                document.body.classList.add('typing-complete');
                
                // Fade out the cursor smoothly
                setTimeout(() => {
                    document.body.classList.add('cursor-fade');
                }, 1000);
            }
        }

        // Snappy but premium initial start delay
        setTimeout(type, 600);
    }

    // Initialize on load
    window.addEventListener('load', () => {
        document.documentElement.classList.remove('no-transition');
        initTypingEffect();
    });


</script>

</body>
</html>
