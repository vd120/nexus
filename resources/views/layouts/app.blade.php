<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Speed & Performance Optimization --}}

    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.documentElement.style.background = savedTheme === 'dark' ? '#0a0a0b' : '#ffffff';
            // If we navigated here from a link, show the spinner immediately on first paint
            // so there is no blank gap between the old page unloading and this page rendering.
            if (sessionStorage.getItem('_nx_loading') === '1') {
                document.documentElement.classList.add('nx-page-loading');
            }
            if (window.self !== window.top) {
                document.documentElement.classList.add('in-iframe');
            }
            // PWA animated splash: activate only in standalone mode, once per session
            if (!sessionStorage.getItem('_nx_splash') &&
                (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone)) {
                document.documentElement.classList.add('pwa-launch');
            }
        })();

        window.runOnPageLoad = function(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
            } else {
                setTimeout(callback, 0);
            }
        };

        // Fix sticky hover on touch devices
        if ('ontouchstart' in window) {
            document.documentElement.classList.add('touch-device');
        }


    </script>

    <style>
        /* Immediate Theme Background to prevent Flash */
        html[data-theme="dark"] { background: #0a0a0b; color: #f5f5f7; }
        html[data-theme="light"] { background: #ffffff; color: #111111; }
        body { background: inherit; color: inherit; }
    </style>

    <meta name="theme-color" content="#111111">
    <link rel="manifest" href="/manifest.json">

    {{-- Favicon — theme-aware SVG with ICO fallback for legacy browsers --}}
    <link rel="icon" type="image/svg+xml" href="/images/nexus-light.svg" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/svg+xml" href="/images/nexus-dark.svg" media="(prefers-color-scheme: dark)">
    <link rel="icon" type="image/svg+xml" href="/images/nexus-dark.svg">
    <link rel="icon" href="/favicon.ico" sizes="any">

    {{-- iOS PWA --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Nexus">
    <link rel="apple-touch-icon" href="/images/icons/nexus-icon-192.png">
    {{-- Additional apple-touch-icon sizes (PWA additive) --}}
    <link rel="apple-touch-icon" sizes="57x57"   href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="60x60"   href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="72x72"   href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="76x76"   href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/images/icons/nexus-icon-192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/nexus-icon-512.png">
    {{-- iOS Splash Screens (PWA additive — 13 unique sizes × 2 orientations) --}}
    {{-- iPhone SE 2nd/3rd gen --}}
    <link rel="apple-touch-startup-image" media="(device-width:375px)and(device-height:667px)and(-webkit-device-pixel-ratio:2)and(orientation:portrait)"  href="/images/splash/iphone-se2-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:375px)and(device-height:667px)and(-webkit-device-pixel-ratio:2)and(orientation:landscape)" href="/images/splash/iphone-se2-landscape.png">
    {{-- iPhone 14 / 15 / 15 Pro / 16 --}}
    <link rel="apple-touch-startup-image" media="(device-width:390px)and(device-height:844px)and(-webkit-device-pixel-ratio:3)and(orientation:portrait)"  href="/images/splash/iphone-14-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:390px)and(device-height:844px)and(-webkit-device-pixel-ratio:3)and(orientation:landscape)" href="/images/splash/iphone-14-landscape.png">
    {{-- iPhone 14 Plus / 15 Plus / 15 Pro Max / 16 Plus --}}
    <link rel="apple-touch-startup-image" media="(device-width:428px)and(device-height:926px)and(-webkit-device-pixel-ratio:3)and(orientation:portrait)"  href="/images/splash/iphone-14-plus-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:428px)and(device-height:926px)and(-webkit-device-pixel-ratio:3)and(orientation:landscape)" href="/images/splash/iphone-14-plus-landscape.png">
    {{-- iPhone 14 Pro / 15 / 15 Pro / 16 --}}
    <link rel="apple-touch-startup-image" media="(device-width:393px)and(device-height:852px)and(-webkit-device-pixel-ratio:3)and(orientation:portrait)"  href="/images/splash/iphone-14-pro-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:393px)and(device-height:852px)and(-webkit-device-pixel-ratio:3)and(orientation:landscape)" href="/images/splash/iphone-14-pro-landscape.png">
    {{-- iPhone 14 Pro Max / 15 Plus / 15 Pro Max / 16 Plus --}}
    <link rel="apple-touch-startup-image" media="(device-width:430px)and(device-height:932px)and(-webkit-device-pixel-ratio:3)and(orientation:portrait)"  href="/images/splash/iphone-14-pro-max-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:430px)and(device-height:932px)and(-webkit-device-pixel-ratio:3)and(orientation:landscape)" href="/images/splash/iphone-14-pro-max-landscape.png">
    {{-- iPhone 16 Pro --}}
    <link rel="apple-touch-startup-image" media="(device-width:402px)and(device-height:874px)and(-webkit-device-pixel-ratio:3)and(orientation:portrait)"  href="/images/splash/iphone-16-pro-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:402px)and(device-height:874px)and(-webkit-device-pixel-ratio:3)and(orientation:landscape)" href="/images/splash/iphone-16-pro-landscape.png">
    {{-- iPhone 16 Pro Max --}}
    <link rel="apple-touch-startup-image" media="(device-width:440px)and(device-height:956px)and(-webkit-device-pixel-ratio:3)and(orientation:portrait)"  href="/images/splash/iphone-16-pro-max-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:440px)and(device-height:956px)and(-webkit-device-pixel-ratio:3)and(orientation:landscape)" href="/images/splash/iphone-16-pro-max-landscape.png">
    {{-- iPad 9th gen --}}
    <link rel="apple-touch-startup-image" media="(device-width:810px)and(device-height:1080px)and(-webkit-device-pixel-ratio:2)and(orientation:portrait)"  href="/images/splash/ipad-9-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:810px)and(device-height:1080px)and(-webkit-device-pixel-ratio:2)and(orientation:landscape)" href="/images/splash/ipad-9-landscape.png">
    {{-- iPad Mini 6 --}}
    <link rel="apple-touch-startup-image" media="(device-width:744px)and(device-height:1133px)and(-webkit-device-pixel-ratio:2)and(orientation:portrait)"  href="/images/splash/ipad-mini6-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:744px)and(device-height:1133px)and(-webkit-device-pixel-ratio:2)and(orientation:landscape)" href="/images/splash/ipad-mini6-landscape.png">
    {{-- iPad Air 5 --}}
    <link rel="apple-touch-startup-image" media="(device-width:820px)and(device-height:1180px)and(-webkit-device-pixel-ratio:2)and(orientation:portrait)"  href="/images/splash/ipad-air5-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:820px)and(device-height:1180px)and(-webkit-device-pixel-ratio:2)and(orientation:landscape)" href="/images/splash/ipad-air5-landscape.png">
    {{-- iPad Pro 11" --}}
    <link rel="apple-touch-startup-image" media="(device-width:834px)and(device-height:1194px)and(-webkit-device-pixel-ratio:2)and(orientation:portrait)"  href="/images/splash/ipad-pro11-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:834px)and(device-height:1194px)and(-webkit-device-pixel-ratio:2)and(orientation:landscape)" href="/images/splash/ipad-pro11-landscape.png">
    {{-- iPad Pro 12.9" --}}
    <link rel="apple-touch-startup-image" media="(device-width:1024px)and(device-height:1366px)and(-webkit-device-pixel-ratio:2)and(orientation:portrait)"  href="/images/splash/ipad-pro129-portrait.png">
    <link rel="apple-touch-startup-image" media="(device-width:1024px)and(device-height:1366px)and(-webkit-device-pixel-ratio:2)and(orientation:landscape)" href="/images/splash/ipad-pro129-landscape.png">

    
    <script>
        // Register Service Worker for PWA support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Nexus Service Worker registered'))
                    .catch(err => console.log('Nexus Service Worker failed', err));
            });
        }
    </script>
    @auth
        <script>
            window.SOCKET_CONFIG = {
                url: window.location.origin,
                userId: {{ auth()->id() }},
                sessionId: '{{ session()->getId() }}',
                isAdmin: {{ auth()->user()->is_admin ? 'true' : 'false' }},
                username: '{{ auth()->user()->username }}',
                token: '{{ auth()->user()->createSocketToken() }}',
                following: @json(auth()->user()->following()->pluck('followed_id'))
            };

            // Synchronize with Android Native Bridge if available
        </script>
    @endauth
    <title>@yield('title', 'Nexus')</title>

    {{-- Default OG / Twitter meta (can be overridden per-page via @push('meta')) --}}
    <meta property="og:site_name" content="{{ config('app.name', 'Nexus') }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    @stack('meta')

    {{-- Performance: System font stacks. Actual @font-face declarations for
         Cairo + Inter + Plus Jakarta Sans + Bricolage Grotesque live in
         /fonts/all.css (self-hosted, cached once across the whole site). --}}
    <style>
        :root {
            --font-sans: 'Inter', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            --font-arabic: "Cairo", "Segoe UI", Tahoma, sans-serif;
        }
        body { font-family: var(--font-sans); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; letter-spacing: -0.005em; }
        [lang="ar"] body { font-family: var(--font-arabic); }
    </style>

    {{-- Self-hosted font bundle — Cairo (Arabic) + Inter (body) + Plus Jakarta Sans + Bricolage Grotesque (display).
         No external CDN, no DNS handshake, no third-party tracking. Served from
         the same origin so the browser reuses an open HTTP/2 connection. --}}
    {{-- Preload the two most-used latin font files so they are fetched in parallel with HTML parsing,
         eliminating the FOUT caused by the browser discovering them late inside all.css --}}
    <link rel="preload" href="{{ asset('fonts/gfonts/UcC73FwrK3iLTeHuS_nVMrMxCp50SjIa1ZL7.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">
    <link rel="preload" href="{{ asset('fonts/gfonts/3y9K6as8bTXq_nANBjzKo3IeZx8z6up5BeSl9D4dj_x9PpZBMlGIInE.woff2') }}" as="font" type="font/woff2" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('fonts/all.css') }}">
    
    {{-- Critical CSS Fallback: Ensures branded background/text even if external CSS has a delay --}}
    <style>
        :root {
            --bg: #0a0a0b;
            --text: #f5f5f7;
        }
        html, body {
            background-color: #0a0a0b !important;
            color: #f5f5f7 !important;
            margin: 0;
            padding: 0;
        }
        [data-theme="light"], [data-theme="light"] body {
            background-color: #ffffff !important;
            color: #111111 !important;
        }
    </style>

    {{-- Global skeleton loader styles --}}
    <style>
    :root{--skeleton-a:oklch(22% .005 260);--skeleton-b:oklch(28% .005 260);}
    [data-theme="light"]{--skeleton-a:oklch(90% .005 260);--skeleton-b:oklch(94% .005 260);}
    .sk{border-radius:4px;background:linear-gradient(90deg,var(--skeleton-a) 0%,var(--skeleton-b) 50%,var(--skeleton-a) 100%);background-size:200% 100%;animation:sk-pulse 1.6s ease-in-out infinite;}
    @keyframes sk-pulse{0%{background-position:200% 0}100%{background-position:-200% 0}}
    .sk-avatar{width:40px;height:40px;border-radius:50%;flex-shrink:0;}
    .sk-avatar-sm{width:32px;height:32px;border-radius:50%;flex-shrink:0;}
    .sk-line{height:11px;border-radius:4px;}
    .sk-block{border-radius:8px;}
    .sk-notif-row{display:flex;gap:10px;padding:12px 16px;align-items:flex-start;border-bottom:1px solid var(--border-color,#2a2a3e);}
    .sk-sidebar-row{display:flex;gap:10px;padding:10px 14px;align-items:center;}
    </style>

    {{-- Icons: async-load FA so it never blocks first paint --}}
    <link rel="preload" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}"></noscript>
    
    {{-- Nav loading indicator styles — inlined so they apply before any stylesheet loads --}}
    <style>
        /* Metaball gooey dots loader — visible while new page loads */
        #nx-page-loader {
            position: fixed;
            inset: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        .nx-page-loading #nx-page-loader {
            opacity: 1;
            pointer-events: all;
        }
        @media (min-width: 768px) {
            .nx-page-loading #nx-page-loader { opacity: 0; pointer-events: none; }
        }
        .nx-loader-ring {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid rgba(99, 102, 241, 0.2);
            border-top-color: var(--accent-500, #6366f1);
            animation: nxSpin .7s linear infinite;
        }
        @keyframes nxSpin { to { transform: rotate(360deg); } }
    </style>

    {{-- PWA Animated Splash — inline so it applies before any stylesheet --}}
    <style>
        #pwa-splash {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: #0a0a0b;
            align-items: center;
            justify-content: center;
        }
        html.pwa-launch #pwa-splash {
            display: flex;
            animation: pwa-splash-out 0.5s cubic-bezier(0.4, 0, 1, 1) 1.6s forwards;
        }
        html.pwa-launch #pwa-splash svg {
            width: 160px;
            height: 160px;
            animation: pwa-logo-wipe 0.9s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
        }
        @keyframes pwa-logo-wipe {
            from { clip-path: inset(110% 0 0 0); }
            to   { clip-path: inset(0% 0 0 0); }
        }
        @keyframes pwa-splash-out {
            to { opacity: 0; pointer-events: none; }
        }
    </style>

    {{-- Main Styles --}}
    @vite(['resources/css/app-layout.css', 'resources/css/mobile-header.css', 'resources/css/comments.css', 'resources/css/partial-posts.css', 'resources/css/modals.css', 'resources/css/life-chapters.css'])

    {{-- Page-specific styles --}}
    @stack('styles')

    <style>
    /* Mobile message badge */
    .mobile-msg-badge {
        position: absolute;
        top: -6px;
        left: 50%;
        margin-left: 10px;
        background: var(--accent-500);
        color: white;
        font-size: 11px;
        font-weight: 800;
        font-family: var(--font-sans);
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.3px;
        min-width: 20px;
        height: 20px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 4.5px;
        box-shadow: 0 3px 10px var(--accent-glow);
        border: 2px solid var(--bg);
        z-index: 10;
        line-height: 1;
        text-align: center;
    }
    .mobile-nav-inner a {
        position: relative;
    }
    /* Desktop message badge */
    .desktop-msg-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: var(--accent-500);
        color: white;
        font-size: 11px;
        font-weight: 800;
        font-family: var(--font-sans);
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.3px;
        min-width: 18px;
        height: 18px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 4.5px;
        box-shadow: 0 3px 10px var(--accent-glow);
        border: 2px solid var(--bg);
        z-index: 10;
        line-height: 1;
        text-align: center;
        transition: transform var(--dur) var(--ease-spring), box-shadow var(--dur) var(--ease-default);
    }

    .desktop-msg-badge.pulse,
    .mobile-msg-badge.pulse {
        animation: badgePulse 0.5s ease-in-out;
    }

    html[dir="rtl"] .desktop-msg-badge {
        right: auto;
        left: -6px;
    }

    /* Light Theme Badges */
    [data-theme="light"] .desktop-msg-badge {
        border: 2px solid var(--bg);
        box-shadow: 0 2px 8px var(--accent-glow);
    }
    .notif-reaction {
        background: var(--accent-glow) !important;
        color: var(--accent-500) !important;
    }
    .notif-reaction i {
        color: var(--accent-500) !important;
    }
    .notif-item .notif-icon img {
        border-radius: 4px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    .toast-reaction-badge img {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    /* Page entrance fade-in — no transform/filter to avoid trapping position:fixed descendants */
    @keyframes fadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    .main-content {
        animation: fadeIn 0.5s ease both;
    }
    </style>
</head>
<body id="app-body">
    {{-- PWA Animated Splash Screen --}}
    <div id="pwa-splash" aria-hidden="true">
        <svg viewBox="0 0 2048 2048" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill="#fefefe" d="M 1691.24 206.02 C 1696.46 205.148 1701.53 207.17 1706.8 208.634 C 1709.6 218.326 1709.53 228.043 1710.33 237.998 C 1713.48 277.105 1713.04 315.858 1713.03 355.049 C 1713.03 383.38 1713.7 411.894 1712.77 440.196 C 1712.15 459.294 1710.1 478.285 1709.7 497.413 C 1709.08 527.711 1710.21 558.241 1708.75 588.492 C 1707.61 612.411 1704.92 636.262 1703.04 660.121 C 1702.32 669.188 1702.33 678.3 1701.58 687.355 C 1696.77 745.555 1689.64 803.378 1682.21 861.273 C 1672.12 939.892 1658.45 1016.85 1639.61 1093.88 C 1634.64 1114.21 1630.22 1134.97 1624.19 1154.99 C 1618.65 1173.43 1612.15 1191.67 1606.03 1209.92 C 1576.56 1297.78 1539.64 1389.79 1467.77 1451.81 C 1457.21 1460.92 1447.03 1469.99 1434.62 1476.56 C 1385.03 1502.8 1347.17 1495.96 1300.45 1467.87 C 1196.04 1405.1 1110.21 1248.47 1048.96 1144.63 C 1029.71 1111.99 1011.03 1078.78 991.053 1046.61 C 979.464 1027.94 966.95 1009.8 954.913 991.419 C 919.814 937.809 881.391 882.96 832.23 841.235 C 812.007 824.071 790.434 808.313 764.762 800.495 C 737.352 792.147 703.554 793.523 678.098 807.299 C 635.196 830.516 604.648 881.263 583.357 923.497 C 523.132 1042.97 494.892 1175.06 470.867 1305.74 C 466.316 1330.5 460.787 1355.02 457.019 1379.94 C 450.651 1422.03 445.805 1464.86 441.592 1507.21 C 440.493 1518.26 440.796 1529.42 439.807 1540.5 C 438.004 1560.67 434.889 1580.74 433.873 1600.98 C 432.972 1618.96 433.546 1636.95 432.774 1654.91 C 432.2 1668.25 430.345 1681.7 428.743 1694.95 C 427.746 1703.19 427.325 1712.77 424.714 1720.65 C 422.758 1726.56 418.811 1729.11 413.473 1731.81 C 408.715 1732.27 402.777 1732.67 398.358 1730.56 C 395.141 1729.03 393.613 1726.54 392.426 1723.31 C 383.799 1699.9 386.786 1588.29 386.758 1558.88 C 386.724 1522.59 386.065 1486.07 387.125 1449.8 C 387.524 1436.17 389.699 1422.65 390.065 1409.03 C 390.807 1381.43 389.887 1353.74 391.073 1326.16 C 392.119 1301.85 395.378 1277.6 396.809 1253.35 C 397.551 1240.77 397.145 1228.12 398.186 1215.56 C 399.476 1200 401.757 1184.5 403.356 1168.97 C 404.502 1157.84 404.812 1146.63 406.133 1135.52 C 407.64 1122.86 410.059 1110.31 411.473 1097.64 C 414.022 1074.79 415.481 1051.99 418.519 1029.13 C 430.831 942.071 447.811 855.737 469.383 770.499 C 487.139 702.31 507.509 634.595 538.94 571.228 C 571.426 505.735 615.089 431.521 688.309 407.071 C 752.5 385.636 810.229 429.756 853.65 472.28 C 865.207 483.599 877.025 494.886 887.48 507.244 C 898.73 520.541 908.518 535.068 919.07 548.91 C 926.17 558.224 934.148 566.902 941.016 576.384 C 950.775 589.856 959.307 604.444 968.459 618.35 C 979.099 634.517 990.549 650.085 1000.84 666.499 C 1023.48 702.59 1044.25 739.825 1066.49 776.156 C 1108 843.952 1149.05 913.582 1198.92 975.688 C 1235.36 1021.09 1271.43 1057.59 1324.2 1083.68 C 1331.17 1087.13 1338.31 1090.13 1345.97 1091.63 C 1362.21 1094.83 1390.92 1094.93 1406.63 1089.51 C 1418.51 1085.41 1428.63 1078.8 1438.73 1071.46 C 1445.17 1066.78 1451.85 1062.09 1457.62 1056.6 C 1569.54 950.131 1628.47 644.894 1650.58 492.223 C 1651.92 482.921 1652.35 473.475 1653.51 464.14 L 1665.89 357.02 C 1668.09 336.765 1670.99 226.226 1677.35 214.474 C 1680.54 208.589 1685.24 207.539 1691.24 206.02 z"/>
        </svg>
    </div>

    {{-- Navigation loading indicator --}}
    <div id="nx-page-loader" aria-hidden="true">
        <div class="nx-loader-ring"></div>
    </div>
    <a href="#main-content" class="skip-link" style="
        position:absolute;top:-100%;left:1rem;
        background:var(--accent,#6366f1);color:#fff;
        padding:.5rem 1rem;border-radius:0 0 8px 8px;
        font-size:.875rem;font-weight:600;text-decoration:none;
        z-index:999999;transition:top .15s;
    " onfocus="this.style.top='0'" onblur="this.style.top='-100%'">
        Skip to content
    </a>

    <header class="header">
        <div class="header-inner">
            <a href="{{ route('home') }}" class="logo">
                <x-logo-text />
            </a>

            @auth
            @php
                $unreadMessages = \Illuminate\Support\Facades\Cache::remember(
                    'unread_messages_' . auth()->id(), 60,
                    fn() => \App\Models\Message::where('sender_id', '!=', auth()->id())
                        ->where('type', '!=', 'system')
                        ->where('content', '!=', 'system_cleared')
                        ->whereNull('read_at')
                        ->whereHas('conversation', function($q) {
                            $q->where('user1_id', auth()->id())
                              ->orWhere('user2_id', auth()->id())
                              ->orWhereHas('group.members', function($q2) {
                                  $q2->where('user_id', auth()->id());
                              });
                        })
                        ->count()
                );
            @endphp
            <nav class="nav-links">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}"><i class="fas fa-home"></i> {{ __('navigation.home') }}</a>
                <a href="{{ route('stories.index') }}" class="{{ request()->routeIs('stories.*') ? 'active' : '' }}"><i class="fas fa-circle-play"></i> {{ __('navigation.stories') }}</a>
                <a href="{{ route('communities.index') }}" class="{{ request()->routeIs('communities.*') ? 'active' : '' }}"><i class="fas fa-users"></i> {{ __('navigation.groups') }}</a>
                <a href="{{ route('chat.index') }}" class="{{ request()->routeIs('chat.*') ? 'active' : '' }}" style="position: relative;">
                    <i class="fas fa-message"></i> 
                    {{ __('navigation.messages') }}
                    <span class="desktop-msg-badge" id="desktopMsgBadge" style="display: none !important;"></span>
                </a>
                <a href="{{ route('ai.index') }}" class="{{ request()->routeIs('ai.*') ? 'active' : '' }}"><i class="fas fa-robot"></i> {{ __('navigation.ai_assistant') }}</a>
            </nav>
            @endauth

            @auth
            <a href="{{ route('search') }}" class="nav-action-btn {{ request()->routeIs('search') ? 'active' : '' }}" title="{{ __('users.search') }}" style="text-decoration:none;">
                <i class="fas fa-search"></i>
            </a>
            @endauth

            <div class="user-actions">
                @guest
                <div class="guest-nav-actions" style="display: flex; align-items: center; gap: 12px;">
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
                @endguest

                @auth
                <div style="position: relative;">
                    @php
                        $unreadCount = \Illuminate\Support\Facades\Cache::remember(
                            'unread_notifications_' . auth()->id(), 60,
                            fn() => auth()->user()->notifications()->unread()->count()
                        );
                    @endphp
                    <button class="nav-action-btn" id="notifBtn" onclick="toggleNotifications(event)">
                    <i class="fas fa-bell" id="notif-bell-icon"></i>
                        <span class="badge" id="notif-badge" {!! $unreadCount > 0 ? '' : 'style="display: none;"' !!}>
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                    </button>
                </div>

                <div style="position: relative;">
                    <button class="nav-user-btn" id="userBtn" onclick="toggleUserMenu(event)">
                        <div class="user-avatar">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}">
                        </div>
                        <span>{{ auth()->user()->username }}</span>
                        <i class="fas fa-chevron-down" style="font-size: 10px; color: var(--text-muted);"></i>
                    </button>
                </div>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                @else
                <a href="{{ route('login') }}" class="nav-action-btn">{{ __('auth.sign_in') }}</a>
                <a href="{{ route('register') }}" class="nav-action-btn primary">{{ __('auth.sign_up') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <div class="dropdown-overlay" id="dropdownOverlay" onclick="closeAllDropdowns()"></div>

    @auth
    {{-- Real-time Feed Update Banner --}}
    <div id="new-posts-banner" class="new-posts-banner"></div>
    
    <!-- Notification Dropdown - Modern Design -->
    <div class="dropdown-menu notif-panel" id="notifMenu">
        <div class="notif-header">
            <h3>{{ __('navigation.notifications') }}</h3>
            <div class="notif-header-actions">
                <button class="notif-action-btn" onclick="markAllRead(); return false;" title="{{ __('notifications.mark_all_read') }}">
                    <i class="fas fa-check"></i>
                    <span>{{ __('notifications.mark_all_read') }}</span>
                </button>
                <button class="notif-action-btn danger" onclick="clearAllNotifications(); return false;" title="{{ __('notifications.clear_all') }}">
                    <i class="fas fa-trash"></i>
                    <span>{{ __('notifications.clear_all') }}</span>
                </button>
                <button class="notif-action-btn" id="dndToggleBtn" onclick="toggleDND(); return false;" title="{{ __('notifications.dnd') }}">
                    <i class="fas fa-moon"></i>
                    <span id="dndText">{{ __('notifications.dnd') }}</span>
                </button>
            </div>
        </div>
        <div class="notif-list" id="notif-list">
        </div>
        <div class="notif-footer" style="padding: 12px 16px; border-top: 1px solid var(--border);">
            <a href="{{ route('notifications.index') }}" style="display: block; text-align: center; color: var(--primary); text-decoration: none; font-size: 14px; font-weight: 600;">
                <i class="fas fa-th-list"></i> {{ __('notifications.view_all') }}
            </a>
        </div>
    </div>

    <!-- User Menu Dropdown - outside header for proper z-index -->
    <div class="dropdown-menu" id="userMenu">
        <a href="{{ route('users.show', auth()->user()) }}"><i class="fas fa-user"></i> {{ __('navigation.profile') }}</a>
        <a href="{{ route('users.saved-posts') }}"><i class="fas fa-bookmark"></i> {{ __('navigation.saved_posts') }}</a>
        <a href="{{ route('pulse.index') }}"><i class="fas fa-heart-pulse"></i> {{ __('messages.pulse') }}</a>
        <a href="{{ route('memories.index') }}"><i class="fas fa-book-open"></i> {{ __('messages.memories') }}</a>
        <div class="divider"></div>
        <a href="{{ route('explore') }}"><i class="fas fa-compass"></i> {{ __('navigation.explore') }}</a>
        <a href="{{ route('hashtags.index') }}"><i class="fas fa-hashtag"></i> {{ __('hashtags.hashtags') }}</a>
        <a href="{{ route('global-chat.index') }}"><i class="fas fa-globe-americas"></i> {{ __('navigation.global_chat') }}</a>

        <a href="{{ route('ai.index') }}"><i class="fas fa-robot"></i> {{ __('navigation.ai_assistant') }}</a>
        @if(auth()->user()->is_admin)
        <a href="{{ route('admin.dashboard') }}"><i class="fas fa-shield-alt"></i> {{ __('navigation.admin_panel') }}</a>
        @endif
        <div class="divider"></div>

        <!-- Download / Install App -->
        <a href="{{ route('download') }}">
            <i class="fas fa-download"></i> Get the App
        </a>

        <!-- Push Notifications Settings -->
        <a href="javascript:void(0)" onclick="closeAllDropdowns(); setTimeout(() => showPushSettings(), 100);">
            <i class="fas fa-bell"></i> {{ __('notifications.enable_push') }}
        </a>

        <!-- Link to Notifications Page -->
        <a href="{{ route('notifications.index') }}">
            <i class="fas fa-bell"></i> {{ __('navigation.notifications') }}
        </a>

        <!-- Link to My Reports Page -->
        <a href="{{ route('reports.my-reports') }}">
            <i class="fas fa-flag"></i> {{ __('messages.my_reports') }}
        </a>
        
        <div class="divider"></div>
        
        <div class="lang-menu-item-pill" onclick="switchUserLanguage('{{ app()->getLocale() === 'en' ? 'ar' : 'en' }}'); event.stopPropagation();">
            <div class="menu-pill-label">
                <i class="fas fa-globe"></i>
                <span>{{ __('messages.language') }}</span>
            </div>
            <div class="language-switcher-pill">
                <div class="lang-slide-bg"></div>
                <div class="lang-option-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}" data-loc-btn="en">EN</div>
                <div class="lang-option-btn {{ app()->getLocale() === 'ar' ? 'active' : '' }}" data-loc-btn="ar">ع</div>
            </div>
        </div>

        <div class="theme-menu-item-pill" onclick="toggleTheme(); event.stopPropagation();">
            <div class="menu-pill-label">
                <i class="fas fa-palette" id="theme-icon-main"></i>
                <span>{{ __('messages.theme') }}</span>
            </div>
            <div class="theme-switcher-pill" id="theme-switch">
                <div class="theme-slide-bg"></div>
                <div class="theme-option-btn btn-sun" data-theme-btn="light">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke-linecap="round"/></svg>
                </div>
                <div class="theme-option-btn btn-moon" data-theme-btn="dark">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </div>
            </div>
        </div>

        <div class="divider"></div>
        <button onclick="logout()" class="danger"><i class="fas fa-sign-out-alt"></i> {{ __('navigation.logout') }}</button>
    </div>
    @endauth

    @auth
    {{-- Mobile Bottom Navigation --}}
    <nav class="mobile-bottom-nav">
        <div class="mobile-nav-inner">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
                <span>{{ __('navigation.home') }}</span>
            </a>
            <a href="{{ route('stories.index') }}" class="{{ request()->routeIs('stories.*') ? 'active' : '' }}">
                <i class="{{ request()->routeIs('stories.*') ? 'fa-solid' : 'fa-regular' }} fa-circle-play"></i>
                <span>{{ __('navigation.stories') }}</span>
            </a>
            <a href="{{ route('communities.index') }}" class="{{ request()->routeIs('communities.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>{{ __('navigation.groups') }}</span>
            </a>
            <a href="{{ route('chat.index') }}" class="{{ request()->routeIs('chat.*') ? 'active' : '' }}">
                <i class="{{ request()->routeIs('chat.*') ? 'fa-solid' : 'fa-regular' }} fa-comment"></i>
                <span class="mobile-msg-badge" id="mobileMsgBadge" style="display: none !important;"></span>
                <span>{{ __('navigation.messages') }}</span>
            </a>
            <a href="{{ route('users.show', auth()->user()) }}" class="{{ request()->routeIs('users.show') && request()->route('user') && (is_object(request()->route('user')) ? request()->route('user')->id : request()->route('user')) == auth()->id() ? 'active' : '' }}">
                <i class="{{ request()->routeIs('users.show') && request()->route('user') && (is_object(request()->route('user')) ? request()->route('user')->id : request()->route('user')) == auth()->id() ? 'fa-solid' : 'fa-regular' }} fa-user"></i>
                <span>{{ __('navigation.profile') }}</span>
            </a>
        </div>
    </nav>
    @endauth

    <main class="app-layout @yield('main_class')" id="main-content" tabindex="-1">
        <div class="main-content @yield('content_class')">
            <script>
                // Essential translations for global JS functions (posts, comments, chat)
                window.chatTranslations = {
                    you: '{{ __('chat.you') }}',
                    online: '{{ __('chat.online') }}',
                    offline: '{{ __('chat.offline') }}',
                    typing: '{{ __('chat.typing') }}',
                    sent: '{{ __('chat.sent') }}',
                    seen: '{{ __('chat.seen') }}',
                    delivered: '{{ __('chat.delivered') }}',
                    read: '{{ __('chat.read') }}',
                    message_deleted: '{{ __('chat.message_deleted') }}',
                    mark_as_read: '{{ __('messages.mark_as_read') }}',
                    delete: '{{ __('messages.delete') }}',
                    delete_message: '{{ __('chat.delete_message') }}',
                    today: '{{ __('messages.today') }}',
                    yesterday: '{{ __('messages.yesterday') }}',
                    cleared_the_chat: '{{ __('chat.cleared_the_chat') }}',
                    invited_you_to_join: '{{ __('chat.invited_you_to_join') }}',
                    group: '{{ __('chat.group') }}',
                    join: '{{ __('chat.join') }}',
                    playback_speed: '{{ __('chat.playback_speed') }}',
                    story_reply: '{{ __('chat.story_reply') }}',
                    failed_to_send_media: '{{ __('chat.failed_to_send_media') }}',
                    error_sending_media: '{{ __('chat.error_sending_media') }}',
                    follow: '{{ __('chat.follow') }}',
                    following: '{{ __('chat.following') }}',
                    post_saved_success: '{{ __('messages.post_saved_success') }}',
                    post_removed_from_saved: '{{ __('messages.post_removed_from_saved') }}',
                    post_link_copied: '{{ __('messages.post_link_copied') }}',
                    failed_to_copy_link: '{{ __('messages.failed_to_copy_link') }}',
                    no_likes_yet: '{{ __('messages.no_likes_yet') }}',
                    could_not_load_likers: '{{ __('messages.could_not_load_likers') }}',
                    likes: '{{ __('messages.likes') }}',
                    click_to_remove: '{{ __('chat.click_to_remove') }}',
                    report_reason_prefix: '{{ __('messages.report_reason_prefix') ?? 'New report for: ' }}'
                };

                // Post translations for posts.js
                window.postTranslations = {
                    delete_post_confirm: '{{ __('messages.delete_post_confirm') }}',
                    delete_comment_confirm: '{{ __('messages.delete_comment_confirm') }}',
                    post_deleted: '{{ __('messages.post_deleted') }}',
                    failed_to_delete_post: '{{ __('messages.failed_to_delete_post') }}',
                    new_posts_loaded: '{{ __('messages.new_posts_loaded') }}',
                    failed_to_load_posts: '{{ __('messages.failed_to_load_posts') }}',
                    load_more: '{{ __('messages.load_more') }}',
                    confirm_pin_post: '{{ __('users.confirm_pin_post') }}',
                    confirm_unpin_post: '{{ __('users.confirm_unpin_post') }}',
                    post_pinned: '{{ __('users.post_pinned') }}',
                    post_unpinned: '{{ __('users.post_unpinned') }}',
                    pin_post: '{{ __('users.pin_post') }}',
                    unpin_post: '{{ __('users.unpin_post') }}',
                    pinned: '{{ __('users.pinned') }}',
                };
            </script>
            <script>
                 window.reactionImages = {!! json_encode(\App\Models\Post::REACTION_IMAGES) !!};
                 window.reactionLabels = {
                     you: '{{ __("messages.you") }}',
                     and: '{{ __("messages.and") }}',
                     others: '{{ __("messages.others") }}',
                 };
                 
                 window.getReactionImage = function(emoji) {
                     if (!window.reactionImages || !emoji) return null;
                     const basic = emoji.replace(/[\uFE00-\uFE0F]/g, '');
                     if (window.reactionImages[emoji]) return window.reactionImages[emoji];
                     if (window.reactionImages[basic]) return window.reactionImages[basic];
                     const withSelector = basic + '\uFE0F';
                     if (window.reactionImages[withSelector]) return window.reactionImages[withSelector];
                     return null;
                 };
            </script>
            {{-- 2FA security nudge for new/unprotected users --}}
            @auth
                @if(!auth()->user()->two_factor_secret && !auth()->user()->onboarding_completed_at && !session('2fa_nudge_dismissed'))
                    <div id="2fa-nudge-banner" style="
                        position:fixed; bottom:1.5rem; right:1.5rem; z-index:8888;
                        max-width:360px; width:calc(100vw - 3rem);
                        background:var(--card-bg,#1e1e2e);
                        border:1px solid #8b5cf660; border-radius:12px;
                        padding:1rem 1.25rem;
                        box-shadow:0 8px 32px rgba(0,0,0,.4);
                        display:flex; gap:.875rem; align-items:flex-start;
                    ">
                        <div style="font-size:1.25rem; margin-top:.1rem;">🔒</div>
                        <div style="flex:1; min-width:0;">
                            <strong style="display:block; font-size:.9375rem; margin-bottom:.25rem;">{{ __('messages.onboarding_security_tip') }}</strong>
                            <p style="font-size:.8rem; opacity:.7; margin:0 0 .75rem; line-height:1.4;">{{ __('messages.onboarding_2fa_prompt') }}</p>
                            <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                                <a href="{{ route('profile.edit', auth()->user()) }}#2fa-card" class="btn btn-primary" style="font-size:.8rem; padding:.375rem .75rem;">{{ __('users.enable_2fa') }}</a>
                                <button type="button" onclick="dismiss2FANudge()" class="btn btn-ghost" style="font-size:.8rem; padding:.375rem .75rem;">{{ __('auth.close') }}</button>
                            </div>
                        </div>
                        <button onclick="dismiss2FANudge()" style="background:none;border:none;cursor:pointer;opacity:.4;padding:0;font-size:1rem;line-height:1;color:inherit;" aria-label="{{ __('auth.close') }}">&times;</button>
                    </div>
                    <script>
                    (function() {
                        var b = document.getElementById('2fa-nudge-banner');
                        if (!b) return;
                        function stackNudgeBanners() {
                            var nudge = document.getElementById('prompt-nudge-banner');
                            if (window.innerWidth <= 900) {
                                b.style.bottom = 'calc(72px + env(safe-area-inset-bottom))';
                                b.style.right = '0';
                                b.style.left = '0';
                                b.style.margin = '0 auto';
                                b.style.width = 'calc(100vw - 2rem)';
                                b.style.maxWidth = '480px';
                                if (nudge) {
                                    var gap = 8;
                                    nudge.style.bottom = 'calc(72px + env(safe-area-inset-bottom) + ' + (b.offsetHeight + gap) + 'px)';
                                    nudge.style.right = '0';
                                    nudge.style.left = '0';
                                    nudge.style.margin = '0 auto';
                                    nudge.style.width = 'calc(100vw - 2rem)';
                                    nudge.style.maxWidth = '480px';
                                }
                            } else {
                                b.style.bottom = '1.5rem';
                                b.style.right = '1.5rem';
                                b.style.left = '';
                                b.style.margin = '';
                                b.style.width = 'calc(100vw - 3rem)';
                                b.style.maxWidth = '360px';
                                if (nudge) {
                                    nudge.style.bottom = '';
                                    nudge.style.right = '';
                                    nudge.style.left = '';
                                    nudge.style.margin = '';
                                    nudge.style.width = '';
                                    nudge.style.maxWidth = '';
                                }
                            }
                        }
                        window.stack2faNudge = stackNudgeBanners;
                        window.addEventListener('DOMContentLoaded', stackNudgeBanners);
                        window.addEventListener('resize', stackNudgeBanners);
                    })();
                    </script>
                    <script>
                    function dismiss2FANudge() {
                        document.getElementById('2fa-nudge-banner')?.remove();
                        // Reset REMINDER banner to its default CSS-driven position
                        var nudge = document.getElementById('prompt-nudge-banner');
                        if (nudge) {
                            nudge.style.bottom = '';
                            nudge.style.right = '';
                            nudge.style.left = '';
                            nudge.style.margin = '';
                            nudge.style.width = '';
                            nudge.style.maxWidth = '';
                        }
                        fetch('/settings/2fa/nudge-dismiss', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        });
                    }
                    </script>
                @endif
            @endauth

            {{-- Pulse & Memory reminder nudge --}}
            @auth
                @if($showPulseNudge || $showMemoryNudge)
                <style>
                #prompt-nudge-banner {
                    position: fixed;
                    bottom: 1.5rem;
                    left: 1.5rem;
                    z-index: 8888;
                    max-width: 340px;
                    width: calc(100vw - 3rem);
                    background: var(--card-bg, #1e1e2e);
                    border: 1px solid #8b5cf660;
                    border-radius: 12px;
                    padding: .875rem 1rem;
                    box-shadow: 0 8px 32px rgba(0,0,0,.4);
                }
                #prompt-nudge-banner .nudge-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: .5rem;
                }
                #prompt-nudge-banner .nudge-title {
                    font-size: .8rem;
                    font-weight: 600;
                    opacity: .5;
                    text-transform: uppercase;
                    letter-spacing: .04em;
                }
                #prompt-nudge-banner .nudge-row {
                    display: flex;
                    align-items: center;
                    gap: .625rem;
                    padding: .5rem .625rem;
                    border-radius: 8px;
                    text-decoration: none;
                    color: inherit;
                    transition: background .15s;
                }
                #prompt-nudge-banner .nudge-row:hover {
                    background: rgba(139,92,246,.1);
                }
                #prompt-nudge-banner .nudge-row + .nudge-row {
                    margin-top: .25rem;
                }
                #prompt-nudge-banner .nudge-icon {
                    font-size: 1.25rem;
                    width: 2rem;
                    text-align: center;
                    flex-shrink: 0;
                }
                #prompt-nudge-banner .nudge-text strong {
                    display: block;
                    font-size: .875rem;
                    line-height: 1.2;
                }
                #prompt-nudge-banner .nudge-text span {
                    font-size: .75rem;
                    opacity: .55;
                }
                #prompt-nudge-banner .nudge-arrow {
                    margin-left: auto;
                    opacity: .35;
                    font-size: .75rem;
                    flex-shrink: 0;
                }
                #prompt-nudge-banner .nudge-close {
                    background: none;
                    border: none;
                    cursor: pointer;
                    opacity: .35;
                    padding: 0;
                    font-size: 1.1rem;
                    line-height: 1;
                    color: inherit;
                }
                #prompt-nudge-banner .nudge-close:hover { opacity: .7; }
                @@media (max-width: 900px) {
                    #prompt-nudge-banner {
                        left: 0;
                        right: 0;
                        margin: 0 auto;
                        width: calc(100vw - 2rem);
                        max-width: 480px;
                        bottom: calc(72px + env(safe-area-inset-bottom));
                    }
                }
                </style>
                <div id="prompt-nudge-banner">
                    <div class="nudge-header">
                        <span class="nudge-title">{{ __('messages.nudge_reminder') }}</span>
                        <button class="nudge-close" onclick="dismissPromptNudge('both')" aria-label="{{ __('auth.close') }}">&times;</button>
                    </div>

                    @if($showPulseNudge)
                    <a href="{{ route('pulse.index') }}" class="nudge-row" onclick="dismissPromptNudge('pulse', event)">
                        <div class="nudge-icon">📝</div>
                        <div class="nudge-text">
                            <strong>{{ __('messages.nudge_pulse_title') }}</strong>
                            <span>{{ __('messages.nudge_pulse_sub') }}</span>
                        </div>
                        <i class="fas fa-chevron-right nudge-arrow"></i>
                    </a>
                    @endif

                    @if($showMemoryNudge)
                    <a href="{{ route('memories.index') }}" class="nudge-row" onclick="dismissPromptNudge('memory', event)">
                        <div class="nudge-icon">🧠</div>
                        <div class="nudge-text">
                            <strong>{{ __('messages.nudge_memory_title') }}</strong>
                            <span>{{ __('messages.nudge_memory_sub') }}</span>
                        </div>
                        <i class="fas fa-chevron-right nudge-arrow"></i>
                    </a>
                    @endif
                </div>
                <script>
                function dismissPromptNudge(type, e) {
                    if (e && type !== 'both') {
                        // Allow navigation but still mark dismissed
                    }
                    var banner = document.getElementById('prompt-nudge-banner');
                    if (type === 'both') {
                        banner?.remove();
                    } else {
                        // Remove just that row
                        var rows = banner?.querySelectorAll('.nudge-row');
                        rows?.forEach(function(row) {
                            if ((type === 'pulse' && row.href && row.href.includes('/pulse')) ||
                                (type === 'memory' && row.href && row.href.includes('/memories'))) {
                                row.remove();
                            }
                        });
                        // If no rows left, remove the whole banner
                        if (banner && !banner.querySelector('.nudge-row')) {
                            banner.remove();
                        }
                    }
                    if (typeof window.stack2faNudge === 'function') window.stack2faNudge();
                    fetch('/settings/prompt-nudge/dismiss', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ type: type })
                    });
                }
                // Stack banners now that both are in the DOM
                if (typeof window.stack2faNudge === 'function') window.stack2faNudge();
                </script>
                @endif
            @endauth

            @yield('content')
        </div>
    </main>

    {{-- ── PWA Banners (additive — do not affect website behavior) ─────────── --}}
    {{-- Android install banner --}}
    <div id="pwa-install-banner" hidden style="
        position:fixed;bottom:0;left:0;right:0;z-index:9000;
        background:var(--card-bg,#1e1e2e);border-top:1px solid rgba(99,102,241,.3);
        display:flex;align-items:center;gap:12px;padding:12px 16px;
        padding-bottom:calc(12px + env(safe-area-inset-bottom));
    ">
        <div style="flex:1;min-width:0;">
            <strong style="display:block;font-size:.9rem;">{{ __('pwa.install_title') }}</strong>
            <span style="font-size:.78rem;opacity:.65;">{{ __('pwa.install_subtitle') }}</span>
        </div>
        <button id="pwa-install-btn" style="
            background:var(--accent-500,#6366f1);color:#fff;border:none;
            border-radius:8px;padding:8px 16px;font-size:.875rem;font-weight:600;cursor:pointer;
        ">{{ __('pwa.install_btn') }}</button>
        <button id="pwa-install-dismiss" aria-label="{{ __('pwa.dismiss') }}" style="
            background:none;border:none;color:inherit;opacity:.4;cursor:pointer;font-size:1.2rem;padding:4px;
        ">&times;</button>
    </div>

    {{-- iOS Safari install guide --}}
    <div id="ios-install-banner" hidden style="
        position:fixed;bottom:0;left:0;right:0;z-index:9000;
        background:var(--card-bg,#1e1e2e);border-top:1px solid rgba(99,102,241,.3);
        padding:16px;padding-bottom:calc(16px + env(safe-area-inset-bottom));text-align:center;
    ">
        <strong style="display:block;margin-bottom:6px;">{{ __('pwa.ios_install_guide') }}</strong>
        <p style="font-size:.82rem;opacity:.7;margin:0 0 12px;">{{ __('pwa.ios_share_then_add') }}</p>
        <button id="ios-install-dismiss" style="
            background:var(--accent-500,#6366f1);color:#fff;border:none;
            border-radius:8px;padding:8px 24px;font-size:.875rem;font-weight:600;cursor:pointer;
        ">{{ __('pwa.got_it') }}</button>
    </div>

    {{-- iOS non-Safari (Chrome/Firefox on iOS) --}}
    <div id="ios-open-in-safari-banner" hidden style="
        position:fixed;bottom:0;left:0;right:0;z-index:9000;
        background:var(--card-bg,#1e1e2e);border-top:1px solid rgba(99,102,241,.3);
        padding:12px 16px;padding-bottom:calc(12px + env(safe-area-inset-bottom));
        display:flex;align-items:center;gap:12px;
    ">
        <span style="font-size:.85rem;flex:1;">{{ __('pwa.ios_open_in_safari') }}</span>
        <button id="ios-open-in-safari-dismiss" aria-label="{{ __('pwa.dismiss') }}" style="
            background:none;border:none;color:inherit;opacity:.4;cursor:pointer;font-size:1.2rem;padding:4px;flex-shrink:0;
        ">&times;</button>
    </div>

    {{-- SW update banner --}}
    <div id="sw-update-banner" hidden style="
        position:fixed;top:0;left:0;right:0;z-index:9001;
        background:var(--accent-500,#6366f1);color:#fff;
        display:flex;align-items:center;justify-content:center;gap:12px;
        padding:10px 16px;padding-top:calc(10px + env(safe-area-inset-top));
        font-size:.875rem;
    ">
        <span>{{ __('pwa.update_available') }}</span>
        <button id="sw-update-refresh" style="
            background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.4);
            border-radius:6px;padding:4px 12px;font-size:.8rem;cursor:pointer;font-weight:600;
        ">{{ __('pwa.refresh') }}</button>
    </div>

    {{-- Offline indicator --}}
    <div id="offline-indicator" hidden style="
        position:fixed;bottom:0;left:0;right:0;z-index:8999;
        background:#ef4444;color:#fff;text-align:center;
        padding:8px 16px;padding-bottom:calc(8px + env(safe-area-inset-bottom));
        font-size:.82rem;font-weight:600;
    ">{{ __('pwa.offline_message') }}</div>

    <div id="toast-container"></div>

    @stack('body-modals')

    @auth
        <script>
            window.currentUserId = {{ auth()->id() }};
            window.currentUserUsername = "{{ auth()->user()->username }}";
            window.layoutTranslations = {
                failed_to_join_group:    "{{ __('messages.failed_to_join_group') }}",
                members:                 "{{ __('messages.members_label') }}",
                approved_another_device: "{{ __('auth.approved_another_device') }}",
                grant_access:            "{{ __('auth.grant_access') }}",
                authorizing:             "{{ __('auth.authorizing') }}",
                approval_failed:         "{{ __('auth.approval_failed') }}",
                connection_error_retry:  "{{ __('auth.connection_error_retry') }}",
            };
        </script>
    @endauth

    <script>
        // Nav loading indicator
        var nxLoaderTimer = null;

        function nxHideLoader() {
            sessionStorage.removeItem('_nx_loading');
            document.documentElement.classList.remove('nx-page-loading');
            if (nxLoaderTimer) { clearTimeout(nxLoaderTimer); nxLoaderTimer = null; }
        }

        function nxShowLoader() {
            sessionStorage.setItem('_nx_loading', '1');
            document.documentElement.classList.add('nx-page-loading');
            if (nxLoaderTimer) clearTimeout(nxLoaderTimer);
            nxLoaderTimer = setTimeout(nxHideLoader, 8000);
        }

        // Hide as soon as the new page's DOM is ready
        document.addEventListener('DOMContentLoaded', nxHideLoader);

        // bfcache: hitting back/forward restores page instantly — no loading needed
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) nxHideLoader();
        });

        // Show loader on navigation clicks — bubble phase so child handlers run first
        // This means if a child calls preventDefault(), we see it correctly
        document.addEventListener('click', function(e) {
            if (e.defaultPrevented) return;
            var a = e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href) return;
            // Skip non-navigation hrefs
            if (href.startsWith('#') || href.startsWith('javascript') || href.startsWith('mailto') || href.startsWith('tel')) return;
            if (a.hasAttribute('download')) return;
            if (a.target === '_blank') return;
            try {
                var url = new URL(href, window.location.origin);
                if (url.origin !== window.location.origin) return;
                // Same page — only a hash change, no real navigation
                if (url.pathname === window.location.pathname && url.search === window.location.search) return;
            } catch(err) { return; }
            nxShowLoader();
        }, false); // false = bubble phase
    </script>

    <script>
        // GLOBAL UTILITIES - MUST LOAD FIRST
        window.escapeHtml = function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        };

        window.sanitizeMessage = function(message) {
            if (!message || typeof message !== 'string') return message;
            const replyRegex = /\{\s*"__nexus_reply__"\s*:\s*true/;
            if (replyRegex.test(message)) {
                try {
                    const jsonStart = message.indexOf('{');
                    if (jsonStart !== -1) {
                        const prefixPart = message.substring(0, jsonStart);
                        const jsonPart = message.substring(jsonStart);
                        const replyData = JSON.parse(jsonPart);
                        return prefixPart + '↩ ' + (replyData.content || '');
                    }
                } catch(e) {}
            }
            return message;
        };

        window.showToast = function(message, type = 'info', avatar = null, link = null, duration = 4000, extraData = null) {
            // Native Android Integration - Mirror to System Notifications

            const container = document.getElementById('toast-container');
            if (!container) return;

            // Silence incoming notification toasts if DND is on
            const isDND = localStorage.getItem('nexus_dnd_enabled') === 'true';
            if (isDND && (avatar || link) && typeof message === 'string' && !message.includes('Disturb')) {
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast ${type} ${avatar ? 'has-avatar' : ''} ${link ? 'is-clickable' : ''}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'polite');
            toast.setAttribute('aria-atomic', 'true');
            
            if (link) {
                toast.style.cursor = 'pointer';
                toast.onclick = (e) => {
                    // Prevent propagation to container if any
                    e.stopPropagation();
                    
                    const notifId = extraData?.notification_id || extraData?.id;
                    console.log('[Toast] Clicked. NotifID:', notifId, 'Link:', link);
                    
                    if (window.handleNotifClick) {
                        window.handleNotifClick(notifId, link);
                    } else {
                        window.location.href = link;
                    }
                };
            }

            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            
            // Handle reply JSON in toasts
            const displayMessage = window.sanitizeMessage(message);

            toast.innerHTML = `
                ${avatar ? `<div class="toast-avatar"><img src="${avatar}" alt="user"></div>` : `<i class="fas ${icon}"></i>`}
                <div class="toast-content">
                    <span>${window.escapeHtml(displayMessage)}</span>
                </div>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 250);
            }, duration);
        };

        // Guest action prompt: redirects to login with return URL.
        // Called from inline onclick handlers on post/comment buttons when user is not authenticated.
        window.showLoginModal = function(action, message) {
            if (window.showToast && message) {
                window.showToast(message, 'info');
            }
            const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
            setTimeout(() => { window.location.href = '/login?return=' + returnUrl; }, 600);
        };
    </script>

    @vite(['resources/js/app.js', 'resources/js/nexus-soul.js', 'resources/js/legacy/ui-utils.js', 'resources/js/legacy/comments.js', 'resources/js/legacy/posts.js', 'resources/js/legacy/mention-hashtag-autocomplete.js', 'resources/js/legacy/community-admin-inline.js', 'resources/js/legacy/life-chapters.js'])
    @auth
        @vite(['resources/js/socket-manager.js'])
    @endauth
    <script>
        function toggleUserMenu(event) {
            event.stopPropagation();
            event.preventDefault();
            const menu = document.getElementById('userMenu');
            const btn = event.currentTarget;
            const isOpen = menu.classList.contains('show');
            closeAllDropdowns();
            if (!isOpen) {
                const rect = btn.getBoundingClientRect();
                const isRTL = document.documentElement.dir === 'rtl';
                
                // Position dropdown based on language direction
                menu.style.top = (rect.bottom + 8) + 'px';
                if (isRTL) {
                    // Arabic: align to left
                    let left = rect.left;
                    const menuWidth = 280;
                    const padding = 16;
                    if (left + menuWidth > window.innerWidth - padding) {
                        left = window.innerWidth - menuWidth - padding;
                    }
                    if (left < padding) left = padding;
                    
                    menu.style.left = left + 'px';
                    menu.style.right = 'auto';
                } else {
                    // English: align to right
                    let right = window.innerWidth - rect.right;
                    const menuWidth = 280;
                    const padding = 16;
                    if (right + menuWidth > window.innerWidth - padding) {
                        right = window.innerWidth - menuWidth - padding;
                    }
                    if (right < padding) right = padding;

                    menu.style.right = right + 'px';
                    menu.style.left = 'auto';
                }
                menu.classList.add('show');
                document.getElementById('dropdownOverlay').classList.add('active');
            }
        }

        function toggleNotifications(event) {
            event.stopPropagation();
            event.preventDefault();
            const menu = document.getElementById('notifMenu');
            const btn = document.getElementById('notifBtn');
            const isOpen = menu.classList.contains('show');
            const isRTL = document.documentElement.dir === 'rtl';
            
            closeAllDropdowns();
            if (!isOpen) {
                const rect = btn.getBoundingClientRect();
                const menuWidth = 380;
                const padding = 16;

                menu.style.top = (rect.bottom + 8) + 'px';
                
                if (isRTL) {
                    // Arabic: align to left of button, but check if it overflows left side of screen
                    let left = rect.left;
                    if (left + menuWidth > window.innerWidth - padding) {
                        left = window.innerWidth - menuWidth - padding;
                    }
                    if (left < padding) left = padding;
                    
                    menu.style.left = left + 'px';
                    menu.style.right = 'auto';
                } else {
                    // English: align to right of button, but check if it overflows right side of screen
                    let right = window.innerWidth - rect.right;
                    if (right + menuWidth > window.innerWidth - padding) {
                        right = window.innerWidth - menuWidth - padding;
                    }
                    if (right < padding) right = padding;

                    menu.style.right = right + 'px';
                    menu.style.left = 'auto';
                }
                
                menu.classList.add('show');
                document.getElementById('dropdownOverlay').classList.add('active');
                // Show skeleton immediately — don't wait for the debounce delay
                const notifListEl = document.getElementById('notif-list');
                if (notifListEl && !notifListEl.querySelector('.notif-item, .sk-notif-row')) {
                    notifListEl.innerHTML = notifSkeletonHTML(4);
                }
                loadNotifications();
            }
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
            document.getElementById('dropdownOverlay').classList.remove('active');
            
            // Close user language dropdown
            const userLangDropdown = document.getElementById('user-language-dropdown');
            const userLangArrow = document.getElementById('user-lang-arrow');
            const userLangToggle = document.querySelector('#userMenu .language-option');
            if (userLangDropdown && userLangDropdown.style.display === 'block') {
                userLangDropdown.style.display = 'none';
                if (userLangArrow) userLangArrow.style.transform = 'rotate(0deg)';
                if (userLangToggle) userLangToggle.setAttribute('aria-expanded', 'false');
            }
        }

        function logout() { if (confirm('{{ __('auth.sign_out_confirm') }}')) document.getElementById('logout-form').submit(); }

        // Do Not Disturb Logic
        function initDND() {
            const isDND = localStorage.getItem('nexus_dnd_enabled') === 'true';
            updateDNDUI(isDND);
        }

        function toggleDND() {
            const isDND = localStorage.getItem('nexus_dnd_enabled') === 'true';
            const newState = !isDND;
            localStorage.setItem('nexus_dnd_enabled', newState);
            updateDNDUI(newState);
            
            // Show toast feedback
            if (window.showToast) {
                const msg = newState ? 'Do Not Disturb Enabled' : 'Do Not Disturb Disabled';
                window.showToast(msg, 'info', null, null, 1000);
            }
        }

        function updateDNDUI(enabled) {
            const bellIcon = document.getElementById('notif-bell-icon');
            const dndBtn = document.getElementById('dndToggleBtn');
            const dndText = document.getElementById('dndText');
            
            if (bellIcon) {
                if (enabled) {
                    bellIcon.classList.remove('fa-bell');
                    bellIcon.classList.add('fa-bell-slash');
                    bellIcon.style.color = '#ffb347'; // Warm moon color
                } else {
                    bellIcon.classList.remove('fa-bell-slash');
                    bellIcon.classList.add('fa-bell');
                    bellIcon.style.color = '';
                }
            }
            
            if (dndBtn) {
                if (enabled) {
                    dndBtn.classList.add('active');
                    if (dndText) dndText.textContent = 'DND On';
                } else {
                    dndBtn.classList.remove('active');
                    if (dndText) dndText.textContent = 'DND Off';
                }
            }
        }

        // Initialize DND on load
        document.addEventListener('DOMContentLoaded', initDND);

        function updateNotificationBadge(count) {
            const badge = document.getElementById('notif-badge');
            if (badge) {
                const oldCount = parseInt(badge.textContent) || 0;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                    
                    // Only pulse if count increased
                    if (count > oldCount) {
                        badge.classList.remove('pulse');
                        void badge.offsetWidth; // trigger reflow
                        badge.classList.add('pulse');
                    }
                } else {
                    badge.style.display = 'none';
                    badge.classList.remove('pulse');
                }
            }
        }

        // Debounce helper — collapses rapid repeated calls into a single execution.
        function debounce(fn, delay) {
            let timer;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        }

        const _updateMobileBadgeRaw = function() {
            fetch('/chat/conversations', {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                const mobileBadge = document.getElementById('mobileMsgBadge');
                const desktopBadge = document.getElementById('desktopMsgBadge');
                
                let totalUnread = 0;
                if (data.conversations) {
                    data.conversations.forEach(c => {
                        const count = parseInt(c.unread_count || 0);
                        totalUnread += count;
                    });
                } else if (typeof data.unread_count !== 'undefined') {
                    totalUnread = parseInt(data.unread_count || 0);
                }

                const displayStyle = totalUnread > 0 ? 'flex' : 'none';
                const displayText = totalUnread > 0 ? (totalUnread > 99 ? '99+' : totalUnread) : '';

                if (mobileBadge) {
                    const oldCount = parseInt(mobileBadge.textContent) || 0;
                    mobileBadge.textContent = displayText;
                    mobileBadge.style.setProperty('display', displayStyle, 'important');
                    
                    if (totalUnread > oldCount && displayStyle === 'flex') {
                        mobileBadge.classList.remove('pulse');
                        void mobileBadge.offsetWidth; // trigger reflow
                        mobileBadge.classList.add('pulse');
                    }
                }
                if (desktopBadge) {
                    const oldCount = parseInt(desktopBadge.textContent) || 0;
                    desktopBadge.textContent = displayText;
                    desktopBadge.style.setProperty('display', displayStyle, 'important');

                    if (totalUnread > oldCount && displayStyle === 'flex') {
                        desktopBadge.classList.remove('pulse');
                        void desktopBadge.offsetWidth; // trigger reflow
                        desktopBadge.classList.add('pulse');
                    }
                }
                const drawerBadge = document.getElementById('chat-drawer-badge');
                if (drawerBadge) {
                    drawerBadge.textContent = displayText;
                    drawerBadge.style.display = displayStyle;
                }
            })
            .catch(err => console.warn('Failed to update badges:', err));
        };
        window.updateMobileBadge = debounce(_updateMobileBadgeRaw, 300);

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        function notifSkeletonHTML(n) {
            return Array.from({length:n}, () =>
                `<div class="sk-notif-row">
                    <div class="sk sk-avatar-sm"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                        <div class="sk sk-line" style="width:70%;"></div>
                        <div class="sk sk-line" style="width:45%;"></div>
                    </div>
                </div>`
            ).join('');
        }

        const _loadNotificationsRaw = function loadNotifications() {
            const list = document.getElementById('notif-list');
            // Only show skeleton on first load (list has no real items yet)
            if (list && !list.querySelector('.notif-item, .notif-empty, .sk-notif-row')) {
                list.innerHTML = notifSkeletonHTML(4);
            }

            fetch('/notifications', {
                credentials: 'include',
                headers: { 
                    'X-CSRF-TOKEN': getCsrfToken(), 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                }
                return r.json();
            })
            .then(data => {
                const list = document.getElementById('notif-list');
                const badge = document.getElementById('notif-badge');
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
                if (!data.notifications || data.notifications.length === 0) {
                    list.innerHTML = `<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>{{ __('notifications.no_notifications') }}</p></div>`;
                    return;
                }
                list.innerHTML = data.notifications.map(n => {
                    const iconClass = getNotificationIconClass(n.type);
                    const notifIcon = getNotificationIcon(n.type);
                    const timeAgo = getTimeAgo(n.created_at);
                    
                    // Parse data if string
                    let notifData = n.data;
                    if (typeof notifData === 'string') {
                        try { notifData = JSON.parse(notifData); } catch(e) { notifData = {}; }
                    }

                    // Sanitize message before truncation
                    const cleanMessage = window.sanitizeMessage(n.message || '');
                    const truncatedMessage = cleanMessage.length > 120 ? cleanMessage.substring(0, 120) + '...' : cleanMessage;
                    
                    return `
                    <div class="notif-item ${n.read_at ? '' : 'unread'}" id="notif-${n.id}" data-id="${n.id}">
                        <div class="notif-icon ${iconClass}" onclick="handleNotifClick(${n.id}, '${n.link || ''}')">
                            <i class="fas ${notifIcon}"></i>
                        </div>
                        <div class="notif-content ${n.read_at ? '' : 'unread'}" onclick="handleNotifClick(${n.id}, '${n.link || ''}')">
                            <p>${escapeHtml(truncatedMessage)}</p>
                            <span class="notif-time">${timeAgo}</span>
                        </div>
                        <div class="notif-item-actions">
                            ${!n.read_at ? `<button class="notif-item-btn" onclick="markAsRead(${n.id}); return false;" title="${window.chatTranslations.mark_as_read}"><i class="fas fa-check"></i></button>` : ''}
                            <button class="notif-item-btn delete" onclick="dismissNotification(${n.id}); return false;" title="${window.chatTranslations.delete}"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                `}).join('');
            })
            .catch(err => {
                console.error('loadNotifications: Error:', err);
                // Silently fail - don't show error to user for notification loading issues
            });
        }
        window.loadNotifications = debounce(_loadNotificationsRaw, 300);

        function markAsRead(id) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Update UI immediately
            const notifItem = document.querySelector(`.notif-item[data-id="${id}"]`);
            if (notifItem) {
                // Remove unread class (this hides the dot)
                notifItem.classList.remove('unread');
                notifItem.querySelector('.notif-content')?.classList.remove('unread');

                // Update actions to show only delete button
                const actionsDiv = notifItem.querySelector('.notif-item-actions');
                if (actionsDiv) {
                    actionsDiv.innerHTML = `<button class="notif-item-btn delete" onclick="dismissNotification(${id}); return false;" title="${window.chatTranslations.delete}"><i class="fas fa-trash"></i></button>`;
                }

                // Update badge immediately
                const badge = document.getElementById('notif-badge');
                if (badge && badge.style.display !== 'none') {
                    const count = parseInt(badge.textContent) || 0;
                    if (count > 1) {
                        badge.textContent = count - 1;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).then(() => {
                // Refresh list to apply new sorting (unread on top)
                setTimeout(() => loadNotifications(), 500);
            }).catch(() => {});
        }

        function markAllRead() {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Update UI immediately
            const notifItems = document.querySelectorAll('.notif-item.unread');
            notifItems.forEach(item => {
                // Remove unread class (this hides the dot)
                item.classList.remove('unread');
                item.querySelector('.notif-content')?.classList.remove('unread');

                const id = item.getAttribute('data-id');
                const actionsDiv = item.querySelector('.notif-item-actions');
                if (actionsDiv && id) {
                    actionsDiv.innerHTML = `<button class="notif-item-btn delete" onclick="dismissNotification(${id}); return false;" title="${window.chatTranslations.delete}"><i class="fas fa-trash"></i></button>`;
                }
            });
            document.getElementById('notif-badge').style.display = 'none';

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).then(() => {
                // Refresh list to apply new sorting (unread on top)
                setTimeout(() => loadNotifications(), 500);
            }).catch(() => {});
        }

        function getNotificationIconClass(type) {
            const classes = {
                'follow': 'notif-follow',
                'like': 'notif-like',
                'comment': 'notif-comment',
                'mention': 'notif-mention',
                'message': 'notif-msg',
                'group_invite': 'notif-group',
                'post_reaction': 'notif-reaction',
                'chat_reaction': 'notif-reaction',
                'story_reaction': 'notif-reaction'
            };
            return classes[type] || 'default';
        }

        function getNotificationIcon(type) {
            const icons = {
                'follow': 'fa-user-plus',
                'like': 'fa-heart',
                'post_reaction': 'fa-heart',
                'chat_reaction': 'fa-heart',
                'story_reaction': 'fa-heart',
                'comment': 'fa-comment',
                'mention': 'fa-at',
                'message': 'fa-envelope',
                'group_invite': 'fa-users',
                'post': 'fa-newspaper',
                'story': 'fa-circle-play'
            };
            return icons[type] || 'fa-bell';
        }

        function getTimeAgo(dateStr) {
            const date = new Date(dateStr);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            if (seconds < 60) return 'Just now';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return minutes + 'm ago';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + 'h ago';
            const days = Math.floor(hours / 24);
            if (days < 7) return days + 'd ago';
            return date.toLocaleDateString();
        }

        window.handleNotifClick = function(id, link) {
            console.log('[handleNotifClick] Called for ID:', id, 'Link:', link);
            
            // Mark as read (fire and forget)
            if (id) {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    keepalive: true
                }).catch(err => console.error('[handleNotifClick] Mark read failed:', err));
                
                // Update UI badge immediately
                const badge = document.getElementById('notif-badge');
                if (badge && badge.style.display !== 'none') {
                    const count = parseInt(badge.textContent) || 0;
                    if (count > 1) {
                        badge.textContent = count - 1;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            // Navigate
            if (link) {
                console.log('[handleNotifClick] Navigating to:', link);
                closeAllDropdowns();
                
                // If it's a same-page hash link, just update hash
                const currentUrl = window.location.origin + window.location.pathname;
                const targetUrl = link.split('#')[0];
                
                if (currentUrl === targetUrl || targetUrl === window.location.href.split('#')[0]) {
                    const hash = link.split('#')[1];
                    if (hash) {
                        window.location.hash = hash;
                    }
                } else {
                    window.location.href = link;
                }
            } else {
                closeAllDropdowns();
                if (window.loadNotifications) loadNotifications();
            }
        }

        function dismissNotification(id) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Update UI immediately
            const notifItem = document.querySelector(`.notif-item[data-id="${id}"]`);
            if (notifItem) {
                notifItem.remove();

                // Update badge immediately
                const badge = document.getElementById('notif-badge');
                if (badge && badge.style.display !== 'none') {
                    const count = parseInt(badge.textContent) || 0;
                    if (count > 1) {
                        badge.textContent = count - 1;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications/' + id, {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).catch(() => {});
        }

        function clearAllNotifications() {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // Clear UI immediately
            const notifList = document.getElementById('notif-list');
            if (notifList) {
                notifList.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>{{ __('notifications.no_notifications') }}</p></div>';
            }
            document.getElementById('notif-badge').style.display = 'none';

            // Send API request (fire and forget with CSRF)
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/notifications', {
                method: 'DELETE',
                headers: { 
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                keepalive: true
            }).catch(() => {});
        }

        document.addEventListener('DOMContentLoaded', () => {
            if ({{ auth()->check() ? 'true' : 'false' }}) {
                window.currentUserId = {{ auth()->id() }};
                window.updateMobileBadge();
            }
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAllDropdowns(); });

            // Initialize Theme Switch and Icon
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const themeSwitch = document.getElementById('theme-switch');
            const themeIcon = document.getElementById('theme-icon-main');
            if (themeSwitch && savedTheme === 'dark') themeSwitch.classList.add('on');
            if (themeIcon) themeIcon.className = savedTheme === 'light' ? 'fas fa-sun' : 'fas fa-moon';

            // Auto-detect Arabic text and apply RTL direction
            applyRTLToArabicText();
        });

        // User Menu Language Dropdown Functions
        function toggleUserLanguageDropdown() {
            const dropdown = document.getElementById('user-language-dropdown');
            const arrow = document.getElementById('user-lang-arrow');
            const toggle = document.querySelector('#userMenu .language-option');

            if (!dropdown) return;

            const isRTL = document.documentElement.dir === 'rtl';
            const isVisible = dropdown.style.display === 'block';

            if (isVisible) {
                dropdown.style.display = 'none';
                if (arrow) arrow.style.transform = 'rotate(0deg)';
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            } else {
                // Just toggle the display since it's now inline
                dropdown.style.display = 'block';
                if (arrow) arrow.style.transform = 'rotate(180deg)';
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            }
        }

        function switchUserLanguage(locale) {
            // Show loading indicator
            const loading = document.getElementById('language-loading');
            if (loading) {
                loading.style.display = 'flex';
            }

            // Close dropdown
            toggleUserLanguageDropdown();

            // Navigate to language switch route with current URL as return
            const currentPath = window.location.pathname + window.location.search;
            window.location.href = '/lang/' + locale + '?return=' + encodeURIComponent(currentPath);
        }

        // Close user language dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userLangSwitcher = userMenu?.querySelector('.language-switcher');

            if (userLangSwitcher && !userLangSwitcher.contains(event.target)) {
                const dropdown = document.getElementById('user-language-dropdown');
                const arrow = document.getElementById('user-lang-arrow');
                const toggle = document.querySelector('#userMenu .language-option');

                if (dropdown && dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                    if (arrow) arrow.style.transform = 'rotate(0deg)';
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                }
            }
        });

        /**
         * Detect Arabic/Persian/Hebrew text and apply RTL direction
         */
        function applyRTLToArabicText() {
            // Arabic Unicode range: \u0600-\u06FF, Arabic Supplement: \u0750-\u077F
            // Persian/Arabic Extended: \u08A0-\u08FF
            // Hebrew: \u0590-\u05FF
            const arabicPattern = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/;
            
            // Apply to post content
            document.querySelectorAll('.post-content p, .comment-content p, .message-content .text').forEach(el => {
                const text = el.textContent || el.innerText || '';
                if (arabicPattern.test(text)) {
                    el.setAttribute('dir', 'rtl');
                    el.style.direction = 'rtl';
                    el.style.textAlign = 'right';
                }
            });
        }
    </script>

    {{-- Push Notification Settings Modal --}}
    @auth
        @include('partials.push-notification-settings')
    @endauth
    {{-- Reactors List Modal (Global) --}}
    <div id="reactorsModalOverlay" class="global-reactors-overlay" onclick="closeReactorsModal(event)">
        <div class="global-reactors-modal" onclick="event.stopPropagation()">
            <div class="global-reactors-header">
                <h3>{{ __('chat.reactions') }}</h3>
                <button class="global-reactors-close" onclick="closeReactorsModal()"><i class="fas fa-times"></i></button>
            </div>
            <div id="reactorsList" class="global-reactors-list">
                {{-- Dynamic content --}}
            </div>
        </div>
    </div>

    {{-- Global Modals --}}
    @include('partials.global-modals')

    <script>
        window.closeReactorsModal = function(event) {
            const overlay = document.getElementById('reactorsModalOverlay');
            if (!overlay) return;

            // If event exists, only close if clicking overlay itself or the close button
            if (event) {
                if (event.target === overlay || event.target.closest('.global-reactors-close')) {
                    overlay.style.display = 'none';
                }
            } else {
                overlay.style.display = 'none';
            }
        };
    </script>

    {{-- Chat Mini Container --}}
    @include("partials.chat.mini-chat-container")

    {{-- Floating chat button + drawer moved to posts/index.blade.php (feed page only) --}}

    {{-- Global modal a11y: Escape to close, focus trap, backdrop click --}}
    <script src="{{ asset('js/modal-a11y.js') }}?v={{ filemtime(public_path('js/modal-a11y.js')) }}" defer></script>

    {{-- Sensitive content reveal --}}
    <script>
    function revealSensitive(postId) {
        const overlay = document.getElementById('overlay-' + postId);
        if (overlay) {
            const isTextOnly = overlay.dataset.textOnly === '1';
            overlay.style.transition = 'opacity .2s';
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
                if (isTextOnly) {
                    const wrapper = document.getElementById('sensitive-' + postId);
                    if (wrapper) {
                        wrapper.classList.remove('sensitive-text-only');
                        wrapper.style.minHeight = '0';
                    }
                }
            }, 200);
        }
        sessionStorage.setItem('revealed_post_' + postId, '1');
    }
    </script>

    @stack('scripts')

    {{-- Poll voting --}}
    <script>
    (function pollExpiryTicker() {
        function refreshPollExpiry() {
            document.querySelectorAll('.poll-expires-label[data-expires]').forEach(el => {
                const expires = new Date(el.dataset.expires);
                const now = new Date();
                const diff = expires - now;
                if (diff <= 0) {
                    el.textContent = '{{ __('posts.poll_final_results') }}';
                    return;
                }
                const minutes = Math.floor(diff / 60000);
                const hours   = Math.floor(diff / 3600000);
                const days    = Math.floor(diff / 86400000);
                let label;
                if (minutes < 60)       label = minutes <= 1 ? 'ends in 1 minute' : `ends in ${minutes} minutes`;
                else if (hours < 24)    label = hours === 1  ? 'ends in 1 hour'   : `ends in ${hours} hours`;
                else                    label = days === 1   ? 'ends in 1 day'    : `ends in ${days} days`;
                el.textContent = '{{ __('posts.poll_ends') }} ' + label;
            });
        }
        refreshPollExpiry();
        setInterval(refreshPollExpiry, 60000);
    })();
    async function castPollVote(btn) {
        const optionId = btn.dataset.optionId;
        const postSlug = btn.dataset.postSlug;
        const container = btn.closest('.post-poll');
        if (!container || container.dataset.voting) return;
        container.dataset.voting = '1';

        const allBtns = container.querySelectorAll('.poll-option');
        allBtns.forEach(b => { b.style.opacity = '.5'; b.style.pointerEvents = 'none'; });

        try {
            const res = await fetch(`/posts/${postSlug}/poll/vote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ option_id: optionId }),
            });
            const data = await res.json();
            if (!res.ok) {
                if (window.showToast) showToast(data.message || 'Vote failed.', 'error');
            } else {
                // Pass userVoted=true so updatePollUI disables buttons for this user
                updatePollUI(container, data.voted_option_id, data.results, data.total_votes, true);
            }
        } catch(e) {
            if (window.showToast) showToast('Something went wrong.', 'error');
        } finally {
            // Always restore regardless of success or failure
            delete container.dataset.voting;
            allBtns.forEach(b => { b.style.opacity = ''; b.style.pointerEvents = ''; });
        }
    }

    // userVoted = true  → this user just voted, disable all buttons
    // userVoted = false → someone else voted via socket, only update counts/bars
    window.updatePollUI = function updatePollUI(container, votedOptionId, results, totalVotes, userVoted) {
        const allBtns = container.querySelectorAll('.poll-option');
        allBtns.forEach(btn => {
            const id = parseInt(btn.dataset.optionId);
            const result = results.find(r => r.id === id);
            if (!result) return;

            // Update fill bar width
            let fill = btn.querySelector('.poll-fill');
            if (!fill) {
                fill = document.createElement('span');
                fill.className = 'poll-fill';
                btn.insertBefore(fill, btn.firstChild);
            }
            fill.style.width = result.percentage + '%';

            if (userVoted) {
                // Remove radio dots, add result classes
                btn.querySelectorAll('.poll-radio').forEach(el => el.remove());
                btn.classList.add('poll-option--result');
                btn.style.cursor = 'default';
                btn.onclick = null;

                const isChosen = id === parseInt(votedOptionId);
                if (isChosen) {
                    btn.classList.add('poll-option--chosen');
                }

                // Add percentage if missing
                let pctEl = btn.querySelector('.poll-option-pct');
                if (!pctEl) {
                    pctEl = document.createElement('span');
                    pctEl.className = 'poll-option-pct';
                    btn.appendChild(pctEl);
                }
                pctEl.textContent = result.percentage + '%';

                // Add checkmark on chosen option
                if (isChosen && !btn.querySelector('.poll-option-check')) {
                    const check = document.createElement('span');
                    check.className = 'poll-option-check';
                    check.setAttribute('aria-hidden', 'true');
                    check.innerHTML = '<i class="fas fa-check-circle"></i>';
                    btn.appendChild(check);
                }
            } else {
                // Someone else voted — just update percentage if already in result state
                let pctEl = btn.querySelector('.poll-option-pct');
                if (pctEl) pctEl.textContent = result.percentage + '%';
            }
        });

        const meta = container.querySelector('.poll-vote-count');
        if (meta) meta.textContent = `${totalVotes} ${totalVotes === 1 ? 'vote' : 'votes'}`;
    }

    </script>

    {{-- Predictive Pre-loading (Kills the 1-second lag) --}}

    @include('partials.call-modal')
    @vite(['resources/css/call-modal.css'])
    @php
        $callIceServers = [];
        if (env('TURN_URL')) {
            $callIceServers[] = ['urls' => env('TURN_URL'), 'username' => env('TURN_USERNAME', ''), 'credential' => env('TURN_CREDENTIAL', '')];
        }
        if (env('TURN_URL_TCP')) {
            $callIceServers[] = ['urls' => env('TURN_URL_TCP'), 'username' => env('TURN_USERNAME', ''), 'credential' => env('TURN_CREDENTIAL', '')];
        }
    @endphp
    <script>window.CALL_CONFIG = { iceServers: @json($callIceServers) };</script>
    <script>
        // PWA install prompt — capture event and expose globally so any button can trigger it
        window.__pwaInstallPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.__pwaInstallPrompt = e;
            document.dispatchEvent(new CustomEvent('pwa:installable'));
        });
        window.addEventListener('appinstalled', () => {
            window.__pwaInstallPrompt = null;
            document.dispatchEvent(new CustomEvent('pwa:installed'));
        });
        window.nexusInstallPWA = async () => {
            if (!window.__pwaInstallPrompt) return false;
            window.__pwaInstallPrompt.prompt();
            const { outcome } = await window.__pwaInstallPrompt.userChoice;
            if (outcome === 'accepted') window.__pwaInstallPrompt = null;
            return outcome === 'accepted';
        };
    </script>
    <script src="{{ asset('js/call-manager.js') }}?v={{ filemtime(public_path('js/call-manager.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.NexusSocket?.socket && window.CallManager) {
                window.CallManager.init(window.NexusSocket.socket);
            } else {
                window.addEventListener('socket:ready', function() {
                    if (window.CallManager) window.CallManager.init(window.NexusSocket?.socket);
                });
            }
        });
    </script>

    {{-- PWA splash cleanup: hide after animation completes, mark session so it won't repeat --}}
    <script>
    (function() {
        if (!document.documentElement.classList.contains('pwa-launch')) return;
        setTimeout(function() {
            var s = document.getElementById('pwa-splash');
            if (s) s.style.display = 'none';
            sessionStorage.setItem('_nx_splash', '1');
        }, 2100);
    })();
    </script>
</body>
</html>
