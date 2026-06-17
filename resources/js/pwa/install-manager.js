const VISIT_KEY        = 'nexus_pwa_visit_count';
const INSTALLED_KEY    = 'nexus_pwa_installed';
const SESSION_KEY      = 'nexus_install_banner_dismissed'; // sessionStorage — clears on new session
const ENGAGE_MS        = 30_000;

let deferredPrompt = null;

function liftAboveNav(banner) {
    const nav = document.querySelector('.mobile-bottom-nav');
    if (!nav || window.getComputedStyle(nav).display === 'none') return;
    const navH = nav.getBoundingClientRect().height;
    if (navH > 0) {
        banner.style.setProperty('bottom', navH + 'px', 'important');
        banner.style.setProperty('padding-bottom', '12px', 'important');
        banner.style.setProperty('z-index', '10000', 'important');
    }
}

function isDismissedThisSession() {
    return sessionStorage.getItem(SESSION_KEY) === '1';
}

function dismissForSession(banner) {
    sessionStorage.setItem(SESSION_KEY, '1');
    banner.setAttribute('hidden', '');
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
            deferredPrompt = null;
            try {
                prompt.prompt();
                const { outcome } = await prompt.userChoice;
                if (outcome === 'accepted') {
                    localStorage.setItem(INSTALLED_KEY, '1');
                }
            } catch (e) { /* prompt already used */ }
        }, { once: true });
    }

    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            dismissForSession(banner);
            deferredPrompt = null;
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
            dismissForSession(banner);
        }, { once: true });
    }
}

function showIOSNonSafariBanner() {
    const banner = document.getElementById('ios-open-in-safari-banner');
    if (!banner) return;
    banner.removeAttribute('hidden');
    liftAboveNav(banner);

    const dismissBtn = document.getElementById('ios-open-in-safari-dismiss');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            dismissForSession(banner);
        }, { once: true });
    }
}

export function init() {
    if (isInStandaloneMode()) return;
    if (isDismissedThisSession()) return;

    const visitCount = incrementVisitCount();

    // ── Android install prompt ──
    let engagementFired = false;
    let scrollFired = false;
    let bannerShown = false;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        if (localStorage.getItem(INSTALLED_KEY)) return;

        const tryShow = () => {
            if (!bannerShown && !isDismissedThisSession() && visitCount >= 2 && deferredPrompt && (engagementFired || scrollFired)) {
                bannerShown = true;
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
    if (visitCount < 2) return;

    if (isIOSNonSafari()) {
        showIOSNonSafariBanner();
        return;
    }

    if (isIOSSafari()) {
        showIOSBanner();
    }
}
