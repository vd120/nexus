/**
 * Push Notification Manager - Nexus
 * Handles browser push notifications via WebPush and service workers.
 */

class PushNotificationManager {
    constructor() {
        this.registration = null;
        this.subscription = null;
        this.vapidPublicKey = null;
        this.isSupported =
            "serviceWorker" in navigator && "PushManager" in window;
        this.permission = Notification.permission;

        // Translations
        this.translations = {
            en: {
                permissionTitle: "Enable Notifications",
                permissionBody:
                    "Get notified about likes, comments, messages, and more",
                allow: "Allow",
                deny: "Not Now",
                enabled: "Notifications enabled",
                disabled: "Notifications disabled",
                error: "Failed to enable notifications",
                notSupported: "Push notifications require HTTPS",
                httpsRequired: "Push notifications only work on HTTPS sites",
                braveNotSupported:
                    "Brave browser has limited push support on mobile. Try Chrome or Firefox instead.",
                settings: "Notification Settings",
            },
            ar: {
                permissionTitle: "تفعيل الإشعارات",
                permissionBody:
                    "احصل على إشعارات حول الإعجابات والتعليقات والرسائل والمزيد",
                allow: "سماح",
                deny: "ليس الآن",
                enabled: "تم تفعيل الإشعارات",
                disabled: "تم تعطيل الإشعارات",
                error: "فشل تفعيل الإشعارات",
                notSupported: "لازم https عشان تشتغل",
                httpsRequired: "الإشعارات تعمل فقط على مواقع HTTPS",
                braveNotSupported:
                    "متصفح برايفر دعمه محدود للإشعارات على الموبايل. جرب كروم بدلاً من ذلك.",
                settings: "إعدادات الإشعارات",
            },
        };

        this.currentLang = document.documentElement.lang || "en";
    }

    /**
     * Initialize push notifications
     */
    async init() {
        // Check if running on HTTPS or localhost
        const isSecure =
            window.location.protocol === "https:" ||
            window.location.hostname === "localhost" ||
            window.location.hostname === "127.0.0.1";

        if (!isSecure) {
            console.warn("[Push] Push notifications require HTTPS");
            return false;
        }

        // Check browser support
        const hasServiceWorker = "serviceWorker" in navigator;
        const hasPushManager = "PushManager" in window;
        const hasNotifications = "Notification" in window;

        if (!hasServiceWorker || !hasPushManager || !hasNotifications) {
            console.warn(
                "[Push] Push notifications not supported on this browser",
            );
            return false;
        }

        // Get VAPID key from server
        const vapidLoaded = await this.getVapidKey();
        if (!vapidLoaded) {
            console.error("[Push] Failed to load VAPID key");
            return false;
        }

        // Register service worker
        const swRegistered = await this.registerServiceWorker();
        if (!swRegistered) {
            console.error("[Push] Failed to register service worker");
            return false;
        }

        // Get existing subscription
        await this.getSubscription();

        return true;
    }

    /**
     * Get VAPID public key from server
     */
    async getVapidKey() {
        const VAPID_CACHE_KEY = "nexus_vapid_pub";
        try {
            const cached = localStorage.getItem(VAPID_CACHE_KEY);
            if (cached) {
                this.vapidPublicKey = cached;
                return true;
            }
            const response = await fetch("/api/push/vapid-key", {
                credentials: "omit",
                headers: { Accept: "application/json" },
            });
            const data = await response.json();

            if (data.configured && data.public_key) {
                this.vapidPublicKey = data.public_key;
                localStorage.setItem(VAPID_CACHE_KEY, data.public_key);
                return true;
            }

            console.warn("[Push] Push notifications not configured on server");
            return false;
        } catch (error) {
            console.error("[Push] Error getting VAPID key:", error);
            return false;
        }
    }

    /**
     * Register service worker
     */
    async registerServiceWorker() {
        try {
            this.registration = await navigator.serviceWorker.register(
                window.SW_PATH || "/sw.js",
                {
                    scope: "/",
                },
            );

            console.log(
                "[Push] Service Worker registered:",
                this.registration.scope,
            );

            // Handle updates
            this.registration.addEventListener("updatefound", () => {
                const newWorker = this.registration.installing;
                console.log("[Push] Service Worker update found");

                newWorker.addEventListener("statechange", () => {
                    if (
                        newWorker.state === "installed" &&
                        navigator.serviceWorker.controller
                    ) {
                        // New service worker available, ask user to reload
                        if (
                            confirm("New version available! Reload to update.")
                        ) {
                            window.location.reload();
                        }
                    }
                });
            });

            return true;
        } catch (error) {
            console.error("[Push] Service Worker registration failed:", error);
            return false;
        }
    }

    /**
     * Get existing subscription
     */
    async getSubscription() {
        try {
            this.subscription =
                await this.registration.pushManager.getSubscription();
            return this.subscription;
        } catch (error) {
            console.error("[Push] Error getting subscription:", error);
            return null;
        }
    }

    /**
     * Request permission and subscribe
     */
    async requestPermission() {
        // Check HTTPS
        const isSecure =
            window.location.protocol === "https:" ||
            window.location.hostname === "localhost" ||
            window.location.hostname === "127.0.0.1";
        if (!isSecure) {
            this.showToast(this.t("httpsRequired"), "error");
            return false;
        }

        if (!this.isSupported) {
            this.showToast(this.t("notSupported"), "error");
            return false;
        }

        // Check current permission
        if (this.permission === "denied") {
            this.showToast(this.t("disabled"), "error");
            return false;
        }

        // If init() failed earlier, retry now before proceeding
        if (!this.registration) {
            console.warn("[Push] registration is null, retrying init...");
            const initialized = await this.init();
            if (!initialized || !this.registration) {
                console.error(
                    "[Push] Re-init failed — service worker could not be registered",
                );
                this.showToast(this.t("notSupported"), "error");
                return false;
            }
        }

        // Ensure VAPID key is loaded
        if (!this.vapidPublicKey) {
            const loaded = await this.getVapidKey();
            if (!loaded) {
                console.error("[Push] VAPID key unavailable");
                this.showToast(this.t("error"), "error");
                return false;
            }
        }

        // Request permission
        if (this.permission !== "granted") {
            const permission = await Notification.requestPermission();
            this.permission = permission;

            if (permission !== "granted") {
                this.showToast(this.t("disabled"), "error");
                return false;
            }
        }

        // Subscribe to push notifications
        try {
            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(
                    this.vapidPublicKey,
                ),
            });

            // Send subscription to server
            await this.saveSubscription(subscription);
            this.subscription = subscription;

            this.showToast(this.t("enabled"), "success");
            return true;
        } catch (error) {
            console.error("[Push] Subscription error:", error);
            this.showToast(this.t("error"), "error");
            return false;
        }
    }

    /**
     * Save subscription to server
     */
    async saveSubscription(subscription) {
        try {
            const response = await fetch("/api/push/subscribe", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    p256dh: this.arrayBufferToBase64(
                        subscription.getKey("p256dh"),
                    ),
                    auth: this.arrayBufferToBase64(subscription.getKey("auth")),
                    content_encoding: subscription.options?.applicationServerKey
                        ? "aesgcm"
                        : "aes128gcm",
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || "Failed to save subscription");
            }

            console.log("[Push] Subscription saved:", data);
            return data;
        } catch (error) {
            console.error("[Push] Error saving subscription:", error);
            throw error;
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        try {
            if (this.subscription) {
                await this.subscription.unsubscribe();

                // Remove from server
                await fetch("/api/push/unsubscribe", {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({
                        endpoint: this.subscription.endpoint,
                    }),
                });

                this.subscription = null;

                this.showToast(this.t("disabled"), "success");
                return true;
            }
        } catch (error) {
            console.error("[Push] Unsubscribe error:", error);
            this.showToast(this.t("error"), "error");
            return false;
        }
    }

    /**
     * Update app badge
     */
    updateBadge(count) {
        if ("setAppBadge" in navigator) {
            navigator.setAppBadge(count);
        }
    }

    /**
     * Clear app badge
     */
    clearBadge() {
        if ("clearAppBadge" in navigator) {
            navigator.clearAppBadge();
        }
    }

    /**
     * Get translation
     */
    t(key) {
        return (
            this.translations[this.currentLang]?.[key] ||
            this.translations.en[key] ||
            key
        );
    }

    /**
     * Show toast message
     */
    showToast(message, type = "info") {
        // Use existing showToast if available, otherwise create simple toast
        if (typeof window.showToast === "function") {
            window.showToast(message, type);
        } else {
            // Simple fallback
            const toast = document.createElement("div");
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: ${type === "error" ? "#ef4444" : type === "success" ? "#22c55e" : "#3b82f6"};
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                z-index: 9999;
                font-size: 14px;
                animation: slideUp 0.3s ease;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    }

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute("content") : "";
    }

    /**
     * Convert base64 to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, "+")
            .replace(/_/g, "/");

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Convert ArrayBuffer to Base64
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = "";
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    /**
     * Check if notifications are enabled
     */
    isEnabled() {
        return (
            this.isSupported &&
            this.subscription !== null &&
            this.permission === "granted"
        );
    }

    /**
     * Update notification settings on server
     */
    async updateSettings(settings) {
        try {
            const response = await fetch("/api/push/settings", {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                body: JSON.stringify({ settings }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || "Failed to update settings");
            }

            return data;
        } catch (error) {
            console.error("[Push] Settings update error:", error);
            throw error;
        }
    }

    /**
     * Get current settings from server
     */
    async getSettings() {
        try {
            const response = await fetch("/api/push/settings", {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || "Failed to get settings");
            }

            return data.settings || {};
        } catch (error) {
            console.error("[Push] Settings get error:", error);
            return null;
        }
    }
}

// Create global instance
window.PushNotificationManager = PushNotificationManager;

// Self-healing runOnPageLoad for standalone usage
if (!window.runOnPageLoad) {
    window.runOnPageLoad = function (cb) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", cb);
        } else {
            setTimeout(cb, 0);
        }
    };
}

// Auto-initialize when DOM is ready
window.runOnPageLoad(async () => {
    const pushManager = new PushNotificationManager();
    window.pushManager = pushManager;

    // Initialize but don't request permission yet
    await pushManager.init();

    console.log("[Push] Push Notification Manager initialized");
});
