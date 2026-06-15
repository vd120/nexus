import './bootstrap';

// Vue is available but not mounted automatically
// This project uses Blade templates, not Inertia.js
// Import Vue only if needed for specific components
// import { createApp } from 'vue';

// Push Notifications
import './push-notifications';

// ── PWA modules (additive — loaded after all existing code) ──────────────────
import { init as initInstallManager } from './pwa/install-manager.js';
import { initSWUpdateDetection } from './pwa/sw-update.js';
import { init as initPushManager } from './pwa/push-manager.js';
import { init as initOfflineQueue, enqueue as pwaEnqueue } from './pwa/offline-queue.js';
import { setBadge } from './pwa/badge-manager.js';

initInstallManager();
initSWUpdateDetection();
initPushManager();
initOfflineQueue();

// Expose enqueue for legacy scripts that can't import ES modules
window._pwaEnqueue = pwaEnqueue;

// Wire badge-manager to the existing notification count (additive)
window.addEventListener('DOMContentLoaded', () => {
    // Seed badge from initial server-rendered count
    const badgeEl = document.getElementById('notif-badge');
    if (badgeEl) {
        const initialCount = parseInt(badgeEl.textContent) || 0;
        setBadge(initialCount);
    }

    // Wrap window.updateNotificationBadge to also update the app icon badge
    const _origUpdateBadge = window.updateNotificationBadge;
    window.updateNotificationBadge = function (count) {
        if (_origUpdateBadge) _origUpdateBadge(count);
        setBadge(count || 0);
    };

    // Clear badge when visiting /notifications
    if (window.location.pathname === '/notifications') {
        setBadge(0);
    }
});

// Wire badge to foreground push messages (increment by 1 for non-call pushes)
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data?.type === 'PUSH_FOREGROUND') {
            const payload = event.data.payload;
            if (payload?.data?.type !== 'call') {
                const badgeEl = document.getElementById('notif-badge');
                const current = badgeEl ? (parseInt(badgeEl.textContent) || 0) : 0;
                setBadge(current + 1);
            }
        }
    });
}
