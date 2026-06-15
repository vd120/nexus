export function initSWUpdateDetection() {
    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.ready.then((registration) => {
        // SW was already waiting before this page loaded
        if (registration.waiting && navigator.serviceWorker.controller) {
            showUpdateBanner(registration);
        }

        registration.addEventListener('updatefound', () => {
            const newSW = registration.installing;
            if (!newSW) return;

            newSW.addEventListener('statechange', () => {
                if (newSW.state === 'installed' && navigator.serviceWorker.controller) {
                    showUpdateBanner(registration);
                }
            });
        });
    }).catch(() => {});

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        window.location.reload();
    });
}

function showUpdateBanner(registration) {
    const banner = document.getElementById('sw-update-banner');
    if (!banner) return;
    banner.removeAttribute('hidden');

    const refreshBtn = document.getElementById('sw-update-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            if (registration.waiting) {
                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }
            // Fallback in case controllerchange doesn't fire
            setTimeout(() => window.location.reload(), 3000);
        }, { once: true });
    }
}
