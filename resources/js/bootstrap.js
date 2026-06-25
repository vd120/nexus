import axios from "axios";
window.axios = axios;

// Global helper for page initialization
if (!window.runOnPageLoad) {
    window.runOnPageLoad = function (callback) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", callback);
        } else {
            setTimeout(callback, 0);
        }
    };
}

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.withCredentials = true;

// Add CSRF token to Axios
const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");
if (csrfToken) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = csrfToken;
}

// Global fetch override to ensure credentials and CSRF tokens are always sent
const originalFetch = window.fetch;
window.fetch = async function () {
    let [resource, config] = arguments;

    if (!config) {
        config = {};
    }

    // Always include credentials to prevent session loss
    if (config.credentials === undefined) {
        config.credentials = "include";
    }

    // Ensure headers exist
    if (!config.headers) {
        config.headers = {};
    }

    // Convert Headers object to plain object if necessary
    let headersObj = {};
    if (config.headers instanceof Headers) {
        for (let [key, value] of config.headers.entries()) {
            headersObj[key] = value;
        }
        config.headers = headersObj;
    }

    // Add CSRF token if missing and method is not GET/HEAD
    if (
        config.method &&
        ["POST", "PUT", "PATCH", "DELETE"].includes(config.method.toUpperCase())
    ) {
        if (!config.headers["X-CSRF-TOKEN"]) {
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");
            if (token) {
                config.headers["X-CSRF-TOKEN"] = token;
            }
        }
    }

    return originalFetch(resource, config);
};
