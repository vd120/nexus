/* Auth Reset Password Functions */

(function () {
    "use strict";

    window.showToast = function (message, type = "info") {
        const container = document.getElementById("toast-container");
        if (!container) return;

        const toast = document.createElement("div");
        toast.className = "toast " + type;
        const icon =
            type === "success"
                ? "fa-check-circle"
                : type === "error"
                  ? "fa-exclamation-circle"
                  : "fa-info-circle";
        toast.innerHTML =
            '<i class="fas ' + icon + '"></i><span>' + message + "</span>";

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add("removing");
            setTimeout(() => toast.remove(), 250);
        }, 3000);
    };

    window.togglePw = function (inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;
        input.type = input.type === "password" ? "text" : "password";
        icon.className =
            input.type === "password" ? "fas fa-eye" : "fas fa-eye-slash";
        if (inputId === "password" || inputId === "password_confirmation")
            checkMatch();
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
            const labelMap = {
                weak: "Weak",
                medium: "Medium",
                strong: "Strong",
                "very-strong": "Very Strong",
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

        if (pass.value === conf.value) {
            div.textContent = "Passwords match";
            div.className = "field-status matching";
        } else {
            div.textContent = "Passwords do not match";
            div.className = "field-status not-matching";
        }
    };

    (function () {
        const confirmInput = document.getElementById("password_confirmation");
        if (confirmInput) {
            confirmInput.addEventListener("input", checkMatch);
        }
    })();
})();
