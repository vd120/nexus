<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title data-t="home.page_not_found">{{ __('home.page_not_found') }} | Nexus</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg: #000000;
            --bg-rgb: 0, 0, 0;
            --text: #f5f5f7;
            --text-dim: #86868b;
            --primary: #0071e3;
            --primary-glow: rgba(0, 113, 227, 0.4);
            --transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        [data-theme="light"] {
            --bg: #ffffff;
            --bg-rgb: 255, 255, 255;
            --text: #1d1d1f;
            --text-dim: #6e6e73;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: var(--bg); 
            color: var(--text); 
            font-family: 'Inter', -apple-system, sans-serif; 
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            text-align: center;
            transition: background 0.4s ease;
        }

        html[lang="ar"] body { font-family: 'Cairo', sans-serif; }

        /* --- BACKGROUND BLOOMS --- */
        .blooms {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: -1;
        }
        .bloom {
            position: absolute; border-radius: 50%; filter: blur(120px);
            opacity: 0.15; transition: all 1s ease;
        }
        .bloom-1 { top: -10%; left: 20%; width: 40%; height: 40%; background: var(--primary); }
        .bloom-2 { bottom: 10%; right: 10%; width: 30%; height: 30%; background: #5e60ce; }

        .container { position: relative; z-index: 10; padding: 24px; }

        .error-code {
            font-size: clamp(120px, 20vw, 250px);
            font-weight: 900;
            line-height: 0.8;
            letter-spacing: -0.08em;
            margin-bottom: 20px;
            background: linear-gradient(to bottom, var(--text) 40%, var(--text-dim));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.1;
        }

        .error-content { margin-top: -100px; }

        h1 {
            font-size: clamp(32px, 5vw, 64px);
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 24px;
        }

        p {
            font-size: clamp(16px, 2vw, 20px);
            color: var(--text-dim);
            max-width: 500px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }

        .go-home-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 32px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 100px;
            font-weight: 700;
            font-size: 15px;
            transition: 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 30px var(--primary-glow);
        }

        .go-home-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px var(--primary-glow);
        }

        .glitch { animation: glitch 5s infinite; }
        @keyframes glitch {
            0% { transform: translate(0); }
            1% { transform: translate(-2px, 2px); }
            2% { transform: translate(2px, -2px); }
            3% { transform: translate(0); }
            100% { transform: translate(0); }
        }
    </style>
</head>
<body>

<div class="blooms">
    <div class="bloom bloom-1"></div>
    <div class="bloom bloom-2"></div>
</div>

<div class="container">
    <div class="error-code glitch">404</div>
    <div class="error-content">
        <h1 data-t="home.page_not_found">{{ __('home.page_not_found') }}</h1>
        <p data-t="home.lost_in_space">{{ __('home.lost_in_space') }}</p>
        <a href="/" class="go-home-btn">
            <i class="fas fa-home"></i>
            <span data-t="home.go_back_home">{{ __('home.go_back_home') }}</span>
        </a>
    </div>
</div>

<script>
    // Real-time Translation
    @php
        $enHome = include resource_path('lang/en/home.php');
        $arHome = include resource_path('lang/ar/home.php');
        $translations = ['en' => $enHome, 'ar' => $arHome];
    @endphp
    const translations = @json($translations);
    const locale = localStorage.getItem('locale') || '{{ app()->getLocale() }}';
    
    function applyTranslations(l) {
        document.documentElement.lang = l;
        document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
        document.querySelectorAll('[data-t]').forEach(el => {
            const key = el.getAttribute('data-t').replace('home.', '');
            if (translations[l][key]) el.innerHTML = translations[l][key];
        });
    }
    applyTranslations(locale);

    // Theme Sync
    const theme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', theme);
</script>

</body>
</html>