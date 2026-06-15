const VISIT_KEY            = 'nexus_pwa_visit_count';
const INSTALLED_KEY        = 'nexus_pwa_installed';
const IOS_DISMISS_KEY      = 'nexus_ios_banner_dismissed_at';
const IOS_NOSAFARI_DISMISS = 'nexus_ios_nosafari_dismissed_at';
const ENGAGE_MS            = 30_000;  // 30s engagement threshold
const IOS_DISMISS_TTL      = 7 * 24 * 60 * 60 * 1000; // 7 days

let deferredPrompt = null;

// Measure the actual rendered height of the mobile bottom nav and lift the
// banner above it. Uses setProperty('important') so it beats any inline style.
function liftAboveNav(banner) {
    const nav = document.querySelector('.mobile-bottom-nav');
    if (!nav || window.getComputedStyle(nav).display === 'none') return;
    const navH = nav.getBoundingClientRect().height;
    if (navH > 0) {
        banner.style.setProperty('bottom', navH + 'px', 'important');
        banner.style.setProperty('padding-bottom', '12px', 'important');
    }
}

function isIOS() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

function isInStandaloneMode() {
    return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
}

function isIOSSafari() {
    const ua = navigator.userAgent;
    return isIOS() && /safari/i.test(ua) && !/crios|fxios|opios|mercury/i.test(ua);
}

function isIOSNonSafari() {
    return isIOS() && !isIOSSafari();
}

function getVisitCount() {
    return parseInt(localStorage.getItem(VISIT_KEY) || '0', 10);
}

function incrementVisitCount() {
    const count = getVisitCount() + 1;
    localStorage.setItem(VISIT_KEY, String(count));
    return count;
}

function showAndroidBanner(prompt) {
    const banner = document.getElementById('pwa-install-banner');
    if (!banner) return;
    banner.removeAttribute('hidden');
    liftAboveNav(banner);

    const installBtn = document.getElementById('pwa-install-btn');
    const dismissBtn = document.getElementById('pwa-install-dismiss');

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            banner.setAttribute('hidden', '');
            try {
                prompt.prompt();
                const { outcome } = await prompt.userChoice;
                if (outcome === 'accepted') {
                    localStorage.setItem(INSTALLED_KEY, '1');
                }
            } catch (e) { /* prompt already used */ }
            deferredPrompt = null;
        }, { once: true });
    }

    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            banner.setAttribute('hidden', '');
        }, { once: true });
    }
}

function showIOSBanner() {
    const banner = document.getElementById('ios-install-banner');
    if (!banner) return;
    banner.removeAttribute('hidden');
    liftAboveNav(banner);

    const dismissBtn = document.getElementById('ios-install-dismiss');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            banner.setAttribute('hidden', '');
            localStorage.setItem(IOS_DISMISS_KEY, String(Date.now()));
        }, { once: true });
    }
}

function showIOSNonSafariBanner() {
    const dismissedAt = parseInt(localStorage.getItem(IOS_NOSAFARI_DISMISS) || '0', 10);
    if (dismissedAt && (Date.now() - dismissedAt) < IOS_DISMISS_TTL) return;

    const banner = document.getElementById('ios-open-in-safari-banner');
    if (!banner) return;
    banner.removeAttribute('hidden');
    liftAboveNav(banner);

    const dismissBtn = document.getElementById('ios-open-in-safari-dismiss');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            banner.setAttribute('hidden', '');
            localStorage.setItem(IOS_NOSAFARI_DISMISS, String(Date.now()));
        }, { once: true });
    }
}

export function init() {
    if (isInStandaloneMode()) return;

    const visitCount = incrementVisitCount();

    // ── Android install prompt ──
    let engagementFired = false;
    let scrollFired = false;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        if (localStorage.getItem(INSTALLED_KEY)) return;

        const tryShow = () => {
            if (visitCount >= 2 && deferredPrompt && (engagementFired || scrollFired)) {
                showAndroidBanner(deferredPrompt);
            }
        };

        setTimeout(() => { engagementFired = true; tryShow(); }, ENGAGE_MS);

        window.addEventListener('scroll', function onScroll() {
            if (window.scrollY > 200) {
                scrollFired = true;
                window.removeEventListener('scroll', onScroll);
                tryShow();
            }
        }, { passive: true });
    });

    window.addEventListener('appinstalled', () => {
        localStorage.setItem(INSTALLED_KEY, '1');
        deferredPrompt = null;
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.setAttribute('hidden', '');
    });

    // ── iOS guide ──
    if (isIOSNonSafari()) {
        showIOSNonSafariBanner();
        return;
    }

    if (isIOSSafari()) {
        const dismissedAt = parseInt(localStorage.getItem(IOS_DISMISS_KEY) || '0', 10);
        const withinDismissTTL = dismissedAt && (Date.now() - dismissedAt) < IOS_DISMISS_TTL;
        if (!withinDismissTTL && visitCount >= 2) {
            showIOSBanner();
        }
    }
}
