/* Auth Register - External Config and Functions */

(function () {
    "use strict";

    (function () {
        const savedTheme = localStorage.getItem("theme") || "dark";
        document.documentElement.setAttribute("data-theme", savedTheme);
    })();

    window.togglePw = function (inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;
        input.type = input.type === "password" ? "text" : "password";
        icon.className =
            input.type === "password" ? "fas fa-eye" : "fas fa-eye-slash";
        if (inputId === "password" || inputId === "password_confirmation") {
            checkMatch();
        }
    };

    (function () {
        const passwordInput = document.getElementById("password");
        if (!passwordInput) return;

        passwordInput.addEventListener("input", function () {
            const val = this.value;
            const fill = document.getElementById("strength-fill");
            const lbl = document.getElementById("strength-label");

            if (!val.length) {
                if (fill) fill.className = "strength-fill";
                if (lbl) lbl.className = "strength-label";
                if (lbl) lbl.textContent = "";
                checkMatch();
                return;
            }

            let score = 0;
            if (val.length >= 8) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const level =
                score <= 2
                    ? "weak"
                    : score === 3
                      ? "medium"
                      : score === 4
                        ? "strong"
                        : "very-strong";
            const t = window.chatTranslations || window.authTranslations || {};
            const labelMap = {
                weak: t.password_strength_weak || "Weak",
                medium: t.password_strength_medium || "Medium",
                strong: t.password_strength_strong || "Strong",
                "very-strong": t.password_strength_very_strong || "Very Strong",
            };

            if (fill) fill.className = "strength-fill " + level;
            if (lbl) lbl.className = "strength-label " + level;
            if (lbl) lbl.textContent = labelMap[level];
            checkMatch();
        });
    })();

    window.checkMatch = function () {
        const pass = document.getElementById("password");
        const conf = document.getElementById("password_confirmation");
        const div = document.getElementById("match-status");
        if (!pass || !conf || !div) return;

        if (!conf.value.length) {
            div.textContent = "";
            div.className = "field-status";
            return;
        }

        const t = window.chatTranslations || window.authTranslations || {};
        if (pass.value === conf.value) {
            div.textContent = t.passwords_match || "Passwords match";
            div.className = "field-status matching";
        } else {
            div.textContent =
                t.passwords_do_not_match || "Passwords do not match";
            div.className = "field-status not-matching";
        }
    };

    (function () {
        const confirmInput = document.getElementById("password_confirmation");
        if (confirmInput) {
            confirmInput.addEventListener("input", checkMatch);
        }
    })();

    (function () {
        const usernameInput = document.getElementById("username");
        const statusDiv = document.getElementById("username-status");
        let checkTimeout;

        if (!usernameInput || !statusDiv) return;

        usernameInput.addEventListener("input", function () {
            clearTimeout(checkTimeout);
            const val = this.value.trim();
            const t = window.authTranslations || {};

            if (val.length < 3) {
                statusDiv.textContent =
                    t.username_min_length ||
                    "Username must be at least 3 characters";
                statusDiv.className = "field-status error";
                return;
            }

            statusDiv.textContent = t.username_checking || "Checking...";
            statusDiv.className = "field-status checking";

            checkTimeout = setTimeout(() => {
                fetch(
                    "/api/check-username?username=" + encodeURIComponent(val),
                    {
                        method: "GET",
                        headers: {
                            "X-CSRF-TOKEN":
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                )?.content || "",
                            Accept: "application/json",
                        },
                    },
                )
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.available) {
                            statusDiv.textContent =
                                t.username_available || "Username available";
                            statusDiv.className = "field-status available";
                        } else {
                            statusDiv.textContent =
                                t.username_taken || "Username taken";
                            statusDiv.className = "field-status taken";
                        }
                    })
                    .catch(() => {
                        statusDiv.textContent = "";
                        statusDiv.className = "field-status";
                    });
            }, 500);
        });
    })();
})();
