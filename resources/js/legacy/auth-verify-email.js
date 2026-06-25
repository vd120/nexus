/* Auth Verify Email Functions */

(function () {
    "use strict";

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

    window.showVerificationForm = function () {
        const sendCodeSection = document.getElementById("sendCodeSection");
        const verifyForm = document.getElementById("verifyForm");
        const resendSection = document.getElementById("resendSection");

        if (sendCodeSection) sendCodeSection.style.display = "none";
        if (verifyForm) verifyForm.classList.add("active");
        if (resendSection) resendSection.classList.add("active");

        const firstInput = document.querySelector(".code-input");
        if (firstInput) firstInput.focus();

        startCountdown();
    };

    window.getCsrfToken = function () {
        return (
            document.querySelector('meta[name="csrf-token"]')?.content ||
            document.querySelector('input[name="_token"]')?.value ||
            ""
        );
    };

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

    let countdownInterval = null;
    window.startCountdown = function () {
        if (countdownInterval) clearInterval(countdownInterval);

        let seconds = 60;
        const countdownEl = document.getElementById("countdown");
        const resendBtn = document.getElementById("resendBtn");

        if (!countdownEl || !resendBtn) return;

        countdownEl.textContent = seconds;
        resendBtn.disabled = true;

        countdownInterval = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(countdownInterval);
                resendBtn.disabled = false;
            }
        }, 1000);
    };

    // Code input auto-focus and combine logic
    window.runOnPageLoad(function () {
        const codeInputs = document.querySelectorAll(".code-input");
        const fullCodeInput = document.getElementById("fullCode");
        const verifyForm = document.getElementById("verifyForm");

        if (!codeInputs.length || !verifyForm) return;

        codeInputs.forEach((input, index) => {
            // Handle input
            input.addEventListener("input", function (e) {
                const value = e.target.value;

                // Only allow numbers
                if (!/^\d*$/.test(value)) {
                    e.target.value = "";
                    return;
                }

                // Move to next input if value entered
                if (value.length === 1 && index < codeInputs.length - 1) {
                    codeInputs[index + 1].focus();
                }

                // Combine all codes
                updateFullCode();
            });

            // Handle backspace
            input.addEventListener("keydown", function (e) {
                if (e.key === "Backspace" && !e.target.value && index > 0) {
                    codeInputs[index - 1].focus();
                }
            });

            // Handle paste
            input.addEventListener("paste", function (e) {
                e.preventDefault();
                const pasteData = (
                    e.clipboardData || window.clipboardData
                ).getData("text");
                const numbers = pasteData.replace(/\D/g, "").slice(0, 6);

                numbers.split("").forEach((char, i) => {
                    if (codeInputs[i]) {
                        codeInputs[i].value = char;
                    }
                });

                // Focus on the next empty input or last input
                const nextIndex = Math.min(
                    numbers.length,
                    codeInputs.length - 1,
                );
                codeInputs[nextIndex].focus();

                // Update full code
                updateFullCode();
            });
        });

        // Update hidden full code input before submit
        verifyForm.addEventListener("submit", function (e) {
            updateFullCode();

            const fullCode = fullCodeInput.value;
            if (fullCode.length !== 6) {
                e.preventDefault();
                showToast(
                    window.verifyEmailTranslations.enter6DigitCode,
                    "error",
                );
            }
        });

        function updateFullCode() {
            let code = "";
            codeInputs.forEach((input) => {
                code += input.value;
            });
            if (fullCodeInput) {
                fullCodeInput.value = code;
            }
        }
    });
})();
