/* UI Utilities - Toast and Common Functions */

(function () {
    "use strict";

    // Global helper for page initialization (replacing livewire:navigated/DOMContentLoaded combo)
    if (!window.runOnPageLoad) {
        window.runOnPageLoad = function (callback) {
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", callback);
            } else {
                // If DOM is already ready, run it immediately (but asynchronously to be consistent)
                setTimeout(callback, 0);
            }
        };
    }

    // Theme toggle
    // Theme toggle
    window.toggleTheme = function () {
        const html = document.documentElement;
        const currentTheme = html.getAttribute("data-theme") || "dark";
        const newTheme = currentTheme === "dark" ? "light" : "dark";

        html.setAttribute("data-theme", newTheme);
        localStorage.setItem("theme", newTheme);

        // Update all theme switchers (pill design)
        document.querySelectorAll(".theme-option-btn").forEach((btn) => {
            btn.classList.toggle(
                "active",
                btn.getAttribute("data-theme-btn") === newTheme,
            );
        });

        // Legacy icon support (if still used)
        const icon = document.getElementById("theme-icon-main");
        if (icon) {
            icon.className =
                newTheme === "light" ? "fas fa-sun" : "fas fa-moon";
        }
    };

    // Get CSRF token
    window.getCsrfToken = function () {
        return document.querySelector('meta[name="csrf-token"]')?.content || "";
    };

    // Update real-time connection status dot
    window.updateConnectionStatus = function (status) {
        const dot = document.getElementById("connection-status-dot");
        if (!dot) return;

        dot.classList.remove("online", "pending");

        if (status === "online") {
            dot.classList.add("online");
            dot.title = "Connected to real-time server";
        } else {
            dot.classList.add("pending");
            dot.title = "Connecting to real-time server...";
        }
    };

    // Initialize Switchers (Pills) on page load
    window.runOnPageLoad(function () {
        const currentTheme =
            document.documentElement.getAttribute("data-theme") || "dark";
        const currentLocale = document.documentElement.lang || "en";

        // Sync Theme Switchers
        document.querySelectorAll(".theme-option-btn").forEach((btn) => {
            btn.classList.toggle(
                "active",
                btn.getAttribute("data-theme-btn") === currentTheme,
            );
        });

        // Sync Language Switchers
        document.querySelectorAll(".lang-option-btn").forEach((btn) => {
            btn.classList.toggle(
                "active",
                btn.getAttribute("data-loc-btn") === currentLocale,
            );
        });
    });
})();
