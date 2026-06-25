const PUSH_PROMPTED_KEY = "nexus_push_prompted";
const PUSH_SESSION_KEY = "nexus_push_session_count";

function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, "+")
        .replace(/_/g, "/");
    const raw = atob(base64);
    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
}

async function getVapidKey() {
    const VAPID_CACHE_KEY = "nexus_vapid_pub";
    try {
        const cached = localStorage.getItem(VAPID_CACHE_KEY);
        if (cached) return cached;
        const res = await fetch("/api/push/vapid-key", {
            credentials: "omit",
            headers: { Accept: "application/json" },
        });
        const data = await res.json();
        if (data.public_key) {
            localStorage.setItem(VAPID_CACHE_KEY, data.public_key);
        }
        return data.public_key || null;
    } catch (e) {
        return null;
    }
}

async function subscribeToPush() {
    if (!("serviceWorker" in navigator) || !("PushManager" in window)) return;
    try {
        const vapidKey = await getVapidKey();
        if (!vapidKey) return;

        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        });

        const key = sub.getKey("p256dh");
        const auth = sub.getKey("auth");

        await fetch("/api/push/subscribe", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
                endpoint: sub.endpoint,
                p256dh: key
                    ? btoa(String.fromCharCode(...new Uint8Array(key)))
                    : null,
                auth: auth
                    ? btoa(String.fromCharCode(...new Uint8Array(auth)))
                    : null,
                content_encoding: (PushManager.supportedContentEncodings || [
                    "aesgcm",
                ])[0],
            }),
        });
    } catch (e) {
        // Non-fatal — user may have denied or browser does not support
    }
}

async function ensureSubscribed() {
    if (!("serviceWorker" in navigator) || !("PushManager" in window)) return;
    if (Notification.permission !== "granted") return;
    try {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (!sub) {
            await subscribeToPush();
            return;
        }
        // Once per hour: verify server still holds this subscription.
        // If server deleted it (e.g. FCM 410 expiry), unsubscribe browser + re-subscribe fresh.
        const SYNC_KEY = "nexus_push_synced_at";
        const lastSync = parseInt(localStorage.getItem(SYNC_KEY) || "0", 10);
        const ONE_HOUR = 60 * 60 * 1000;
        if (Date.now() - lastSync > ONE_HOUR) {
            localStorage.setItem(SYNC_KEY, String(Date.now()));
            try {
                const res = await fetch("/api/push/status", {
                    headers: { Accept: "application/json" },
                });
                const data = await res.json();
                if (!data.subscribed) {
                    await sub.unsubscribe();
                    await subscribeToPush();
                }
            } catch (e) {}
        }
    } catch (e) {}
}

function showPushPrompt() {
    Notification.requestPermission()
        .then((permission) => {
            if (permission === "granted") {
                subscribeToPush();
            }
            localStorage.setItem(PUSH_PROMPTED_KEY, "1");
        })
        .catch(() => {});
}

function wireLogoutCleanup() {
    const logoutForms = document.querySelectorAll(
        'form[action*="logout"], #logout-form',
    );
    logoutForms.forEach((form) => {
        form.addEventListener(
            "submit",
            async (e) => {
                if (
                    !("serviceWorker" in navigator) ||
                    !("PushManager" in window)
                )
                    return;
                try {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    if (!sub) return;
                    const endpoint = sub.endpoint;
                    // Delete from server before session is invalidated
                    await fetch("/api/push/unsubscribe", {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN":
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                )?.content || "",
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: JSON.stringify({ endpoint }),
                        keepalive: true,
                    });
                    await sub.unsubscribe();
                } catch (e) {}
            },
            { capture: true },
        );
    });
}

function wireForegroundPushToast() {
    if (!("serviceWorker" in navigator)) return;
    navigator.serviceWorker.addEventListener("message", (event) => {
        if (event.data?.type === "PUSH_RESUBSCRIBE") {
            // Browser rotated the push endpoint — re-subscribe with the new one
            subscribeToPush();
            return;
        }
        if (event.data?.type === "REFRESH_BADGE") {
            if (window.loadNotifications) window.loadNotifications();
            if (window.updateMobileBadge) window.updateMobileBadge();
            return;
        }
        if (!event.data || event.data.type !== "PUSH_FOREGROUND") return;
        
        // Skip duplicate toast if WebSocket is connected and already handles notifications
        if (window.NexusSocket && window.NexusSocket.status === "CONNECTED") {
            return;
        }

        const payload = event.data.payload || {};
        if (window.showToast) {
            window.showToast(
                payload.body || payload.title || "New notification",
                "info",
                payload.icon || null,
                payload.data?.url || null,
            );
        }
    });
}

export function init() {
    if (!("Notification" in window)) return;

    wireLogoutCleanup();
    wireForegroundPushToast();

    // Re-subscribe on login if permission already granted but no active subscription
    ensureSubscribed();

    // Contextual prompt: show on feed page, 2nd+ session, only if not already prompted
    const sessionCount =
        parseInt(sessionStorage.getItem(PUSH_SESSION_KEY) || "0", 10) + 1;
    sessionStorage.setItem(PUSH_SESSION_KEY, String(sessionCount));

    const alreadyPrompted = localStorage.getItem(PUSH_PROMPTED_KEY);
    const isOnFeed =
        window.location.pathname === "/" ||
        window.location.pathname === "/home";
    const permissionDefault = Notification.permission === "default";

    if (
        !alreadyPrompted &&
        isOnFeed &&
        sessionCount >= 2 &&
        permissionDefault
    ) {
        setTimeout(showPushPrompt, 5000);
    }
}
