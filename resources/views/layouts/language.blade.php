{{-- Language Layout Partial --}}
{{-- Include this in all pages: @include('layouts.language') --}}

@php
    $currentLocale = app()->getLocale();
    $direction = $currentLocale === 'ar' ? 'rtl' : 'ltr';
    $fontFamily = $currentLocale === 'ar' ? "'Cairo', 'Inter', sans-serif" : "'Inter', sans-serif";
@endphp

{{-- Set HTML lang and dir attributes --}}
<script>
    document.documentElement.setAttribute('lang', '{{ $currentLocale }}');
    document.documentElement.setAttribute('dir', '{{ $direction }}');
</script>

{{-- Language CSS Styles --}}
<style>
/* ============================================
   MULTILINGUAL SUPPORT - Centralized
   ============================================ */

/* Base Direction Settings */
html[lang="en"] {
    direction: ltr;
}
html[lang="ar"] {
    direction: rtl;
}

/* Font Families */
html[lang="en"] body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
html[lang="ar"] body {
    font-family: 'Cairo', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* ============================================
   HEADER - Follows Language Direction
   ============================================ */
nav,
.nav-container,
.nav-links,
.language-switcher,
.header,
.header-inner {
    /* Direction follows language - RTL for Arabic, LTR for English */
}

/* ============================================
   CONTENT SECTIONS - Language Dependent
   ============================================ */

/* English - LTR */
html[lang="en"] .fade-content,
html[lang="en"] .section-label,
html[lang="en"] .section-title,
html[lang="en"] .feature-card,
html[lang="en"] .feature-card-title,
html[lang="en"] .feature-card-desc,
html[lang="en"] .list-item,
html[lang="en"] .blur-text,
html[lang="en"] .typewriter,
html[lang="en"] .growing-title,
html[lang="en"] .stat-row,
html[lang="en"] .feature-grid,
html[lang="en"] .login-card,
html[lang="en"] .field,
html[lang="en"] .extras,
html[lang="en"] .terms-row,
html[lang="en"] .card-footer,
html[lang="en"] .alert-error,
html[lang="en"] .strength-track,
html[lang="en"] .strength-label,
html[lang="en"] .password-wrap,
html[lang="en"] .divider {
    direction: ltr !important;
}

/* Arabic - RTL */
html[lang="ar"] .fade-content,
html[lang="ar"] .section-label,
html[lang="ar"] .section-title,
html[lang="ar"] .feature-card,
html[lang="ar"] .feature-card-title,
html[lang="ar"] .feature-card-desc,
html[lang="ar"] .list-item,
html[lang="ar"] .blur-text,
html[lang="ar"] .typewriter,
html[lang="ar"] .growing-title,
html[lang="ar"] .stat-row,
html[lang="ar"] .feature-grid,
html[lang="ar"] .login-card,
html[lang="ar"] .field,
html[lang="ar"] .extras,
html[lang="ar"] .terms-row,
html[lang="ar"] .card-footer,
html[lang="ar"] .alert-error,
html[lang="ar"] .strength-track,
html[lang="ar"] .strength-label,
html[lang="ar"] .password-wrap,
html[lang="ar"] .divider {
    direction: rtl !important;
}

/* Special RTL Adjustments */
html[lang="ar"] .list-effect {
    direction: rtl !important;
    padding-right: 10% !important;
    padding-left: 0 !important;
}

html[lang="ar"] .feature-grid {
    direction: rtl !important;
}

/* Keep icons LTR */
i, svg, .fas, .fab, .far {
    direction: ltr !important;
}

/* Hero, CTA, and centered sections - always centered in both languages */
.hero, .hero-content, .hero h1, .hero h2, .hero-cta,
.cta-content, .cta-title, .cta-desc,
.section-label, .section-title,
.blur-text, .typewriter,
#section-growing .text-line, #section-growing .growing-title, #section-growing .stat-row,
.fade-content h2, .fade-content p {
    text-align: center !important;
}

/* ============================================
   LANGUAGE DROPDOWN - Theme-Aware Styles
   ============================================ */
.language-dropdown {
    background: var(--surface, rgba(22, 22, 22, 0.95)) !important;
    backdrop-filter: blur(40px) !important;
    border: 1px solid var(--border, rgba(255, 255, 255, 0.08)) !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
}

[data-theme="light"] .language-dropdown {
    background: var(--surface, rgba(249, 250, 251, 0.95)) !important;
    border: 1px solid var(--border, rgba(0, 0, 0, 0.1)) !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
}

.language-option {
    color: var(--text, #111111) !important;
    transition: all 0.2s !important;
}

[data-theme="dark"] .language-option {
    color: var(--text, #f5f5f7) !important;
}

.language-option:hover {
    background: var(--surface-hover, rgba(0, 0, 0, 0.05)) !important;
}

[data-theme="dark"] .language-option:hover {
    background: var(--surface-hover, rgba(255, 255, 255, 0.1)) !important;
}

.language-option.active {
    background: rgba(99, 102, 241, 0.1) !important;
    color: var(--primary, #6366f1) !important;
    font-weight: 600 !important;
}

.language-option.active:hover {
    background: rgba(99, 102, 241, 0.15) !important;
}

/* Language Switcher Button */
.language-toggle {
    color: var(--text, #111111) !important;
    transition: all 0.3s ease !important;
}

[data-theme="dark"] .language-toggle {
    color: var(--text, #ffffff) !important;
}

.language-toggle:hover {
    background: var(--surface-hover, rgba(0, 0, 0, 0.05)) !important;
    border-color: var(--border, rgba(0, 0, 0, 0.1)) !important;
}

[data-theme="dark"] .language-toggle:hover {
    background: var(--surface-hover, rgba(255, 255, 255, 0.1)) !important;
    border-color: var(--border, rgba(255, 255, 255, 0.2)) !important;
}
</style>

{{-- Language Switcher Component --}}
<div class="language-switcher" style="position: relative; display: inline-block;">
    <button
        type="button"
        class="language-toggle"
        onclick="toggleLanguageDropdown()"
        aria-label="{{ __('messages.language') }}"
        aria-haspopup="true"
        aria-expanded="false"
        style="
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 8px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            color: var(--text-primary, #ffffff);
            font-size: 13px;
            font-weight: 600;
        "
    >
        <span style="opacity: 0.9;">🌐</span>
        <span class="current-locale">
            @if($currentLocale === 'ar')
                ع
            @else
                EN
            @endif
        </span>
        <span style="opacity: 0.5;">|</span>
        <span class="alternate-locale" style="opacity: 0.7;">
            @if($currentLocale === 'ar')
                EN
            @else
                ع
            @endif
        </span>
        <svg style="width: 12px; height: 12px; transition: transform 0.2s;" id="lang-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div
        id="language-dropdown"
        class="language-dropdown"
        style="
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            min-width: 160px;
            z-index: 1001;
            overflow: hidden;
            padding: 8px;
        "
    >
        @php
            $supportedLocales = \App\Http\Controllers\LanguageController::getSupportedLocales();
        @endphp
        @foreach($supportedLocales as $locale => $details)
            <a
                href="#"
                onclick="switchLanguage('{{ $locale }}'); return false;"
                class="language-option {{ $currentLocale === $locale ? 'active' : '' }}"
                data-locale="{{ $locale }}"
                style="
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 10px 14px;
                    border-radius: 8px;
                    text-decoration: none;
                    margin-bottom: 4px;
                "
            >
                <span style="font-size: 18px;">{{ $details['flag'] }}</span>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 14px; font-weight: 500;">{{ $details['native_name'] }}</span>
                    @if($details['name'] !== $details['native_name'])
                        <span style="font-size: 11px; opacity: 0.6;">{{ $details['name'] }}</span>
                    @endif
                </div>
                @if($currentLocale === $locale)
                    <svg style="width: 16px; height: 16px; margin-left: auto; color: var(--primary);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Loading Overlay --}}
    <div id="language-loading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: rgba(22, 22, 22, 0.98); backdrop-filter: blur(20px); padding: 30px 50px; border-radius: 16px; text-align: center;">
            <div style="width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-top-color: #6366f1; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 15px;"></div>
            <p style="color: #fff; font-size: 14px; margin: 0;">{{ __('messages.loading') }}</p>
        </div>
    </div>
</div>

{{-- Overlay for mobile --}}
<div
    id="language-overlay"
    onclick="toggleLanguageDropdown()"
    style="
        display: none;
        position: fixed;
        inset: 0;
        z-index: 999;
        background: transparent;
    "
></div>

<script>
function toggleLanguageDropdown() {
    const dropdown = document.getElementById('language-dropdown');
    const overlay = document.getElementById('language-overlay');
    const arrow = document.getElementById('lang-arrow');
    const toggle = document.querySelector('.language-toggle');

    const isVisible = dropdown.style.display === 'block';

    if (isVisible) {
        dropdown.style.display = 'none';
        overlay.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
        toggle.setAttribute('aria-expanded', 'false');
    } else {
        dropdown.style.display = 'block';
        overlay.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
        toggle.setAttribute('aria-expanded', 'true');
    }
}

function switchLanguage(locale) {
    // Show loading indicator
    const loading = document.getElementById('language-loading');
    if (loading) {
        loading.style.display = 'flex';
    }

    // Close dropdown
    toggleLanguageDropdown();

    // Navigate to language switch route with current URL as return
    const currentPath = window.location.pathname + window.location.search;
    window.location.href = '/lang/' + locale + '?return=' + encodeURIComponent(currentPath);
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const switcher = document.querySelector('.language-switcher');
    if (switcher && !switcher.contains(event.target)) {
        const dropdown = document.getElementById('language-dropdown');
        const overlay = document.getElementById('language-overlay');
        const arrow = document.getElementById('lang-arrow');
        const toggle = document.querySelector('.language-toggle');

        dropdown.style.display = 'none';
        overlay.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
        toggle.setAttribute('aria-expanded', 'false');
    }
});

// Theme-aware styling
(function() {
    const checkTheme = () => {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        const toggle = document.querySelector('.language-toggle');
        const dropdown = document.getElementById('language-dropdown');

        if (toggle) {
            toggle.style.borderColor = isLight ? 'rgba(0, 0, 0, 0.2)' : 'rgba(255, 255, 255, 0.2)';
            toggle.style.color = isLight ? '#111111' : '#ffffff';
        }

        if (dropdown) {
            // Inherit background from parent when inside user menu
            dropdown.style.background = 'inherit';
            dropdown.style.borderColor = isLight ? 'rgba(0, 0, 0, 0.1)' : 'rgba(255, 255, 255, 0.1)';
            dropdown.style.boxShadow = isLight ? '0 10px 40px rgba(0, 0, 0, 0.15)' : '0 10px 40px rgba(0, 0, 0, 0.4)';
        }
    };

    checkTheme();

    const observer = new MutationObserver(checkTheme);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
})();
</script>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Mobile - Hide language text, show only icon */
@media(max-width: 480px) {
    .language-switcher .current-locale,
    .language-switcher .lang-divider,
    .language-switcher .lang-alt {
        display: none;
    }
    .language-switcher {
        padding: 6px 10px !important;
    }
    .language-switcher span:first-child {
        font-size: 16px !important;
    }
}
</style>
