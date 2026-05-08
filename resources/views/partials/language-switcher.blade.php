{{-- Sleek Language Toggle Switcher --}}
@php
    $currentLocale = app()->getLocale();
@endphp

<div 
    class="language-switcher-pill" 
    onclick="toggleLanguageSelection()"
    role="button"
    aria-label="{{ __('messages.language') }}"
    data-t="messages.language"
    data-t-type="aria"
>
    <div class="lang-slide-bg"></div>
    <div class="lang-option-btn {{ $currentLocale === 'en' ? 'active' : '' }}" data-loc-btn="en">EN</div>
    <div class="lang-option-btn {{ $currentLocale === 'ar' ? 'active' : '' }}" data-loc-btn="ar">ع</div>
</div>

<script>
function toggleLanguageSelection() {
    const nextLocale = document.documentElement.lang === 'en' ? 'ar' : 'en';
    switchUnifiedLanguage(nextLocale);
}

function switchUnifiedLanguage(locale) {
    // Check if real-time switching is available (landing page)
    if (typeof window.switchRealTimeLanguage === 'function') {
        window.switchRealTimeLanguage(locale);
        return;
    }

    // Fallback for other pages
    const currentPath = window.location.pathname + window.location.search;
    window.location.href = '/lang/' + locale + '?return=' + encodeURIComponent(currentPath);
}

// Ensure the switcher UI is in sync if the language changes via other means
function updateSwitcherUI(locale) {
    document.querySelectorAll('.lang-option-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-loc-btn') === locale);
    });
}
</script>
