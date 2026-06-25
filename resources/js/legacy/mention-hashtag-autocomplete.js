(function () {
    "use strict";
    if (window.__mhAutocompleteAttached) return;
    window.__mhAutocompleteAttached = true;

    const TARGET_SELECTOR = "#post-content";
    const DEBOUNCE_MS = 180;
    const MAX_RESULTS = 8;

    let popover = null;
    let popoverItems = [];
    let activeIndex = 0;
    let activeTextarea = null;
    let activeTrigger = null;
    let activeTokenStart = -1;
    let activeTokenEnd = -1;
    let lastQueryKey = null;
    let inputDebounceTimer = null;
    let abortCtl = null;

    function escapeHtml(s) {
        return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;",
            }[c];
        });
    }

    function ensurePopover() {
        if (popover) return popover;
        popover = document.createElement("div");
        popover.className = "mh-autocomplete";
        popover.setAttribute("role", "listbox");
        popover.style.display = "none";
        document.body.appendChild(popover);
        popover.addEventListener("mousedown", function (e) {
            const item = e.target.closest("[data-mh-index]");
            if (!item) return;
            e.preventDefault();
            selectItem(parseInt(item.getAttribute("data-mh-index"), 10));
        });
        return popover;
    }

    function hidePopover() {
        if (popover) popover.style.display = "none";
        popoverItems = [];
        activeIndex = 0;
        activeTrigger = null;
        activeTokenStart = -1;
        activeTokenEnd = -1;
        lastQueryKey = null;
        if (abortCtl) {
            try {
                abortCtl.abort();
            } catch (_) {}
            abortCtl = null;
        }
    }

    // Detect a `@token` or `#token` at the cursor. Returns {trigger, start, end, query} or null.
    function detectToken(textarea) {
        const value = textarea.value;
        const cursor = textarea.selectionStart;
        if (cursor == null || cursor !== textarea.selectionEnd) return null;

        let i = cursor - 1;
        while (i >= 0) {
            const ch = value[i];
            if (ch === "@" || ch === "#") {
                // Trigger only when at start or preceded by whitespace/punctuation
                const prev = i > 0 ? value[i - 1] : null;
                if (prev !== null && /[\p{L}\p{N}_]/u.test(prev)) return null;

                const query = value.slice(i + 1, cursor);
                if (query.length && !/^[\p{L}\p{N}_-]+$/u.test(query))
                    return null;
                return { trigger: ch, start: i, end: cursor, query: query };
            }
            if (/\s/.test(ch)) return null;
            i--;
        }
        return null;
    }

    function renderUsers(users) {
        if (!users.length) {
            popover.innerHTML = '<div class="mh-empty">No users found</div>';
            popoverItems = [];
            return;
        }
        popoverItems = users.map(function (u) {
            return { type: "user", value: u.username, data: u };
        });
        popover.innerHTML = users
            .map(function (u, idx) {
                const initial = String(u.username || u.name || "?")
                    .slice(0, 1)
                    .toUpperCase();
                const avatar = u.avatar_url
                    ? '<img src="' +
                      escapeHtml(u.avatar_url) +
                      '" alt="" class="mh-avatar">'
                    : '<div class="mh-avatar mh-avatar-fallback">' +
                      escapeHtml(initial) +
                      "</div>";
                const vb = u.is_verified
                    ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                    : "";
                return (
                    '<div class="mh-item' +
                    (idx === activeIndex ? " mh-active" : "") +
                    '" role="option" data-mh-index="' +
                    idx +
                    '">' +
                    avatar +
                    '<div class="mh-meta">' +
                    '<span class="mh-primary" style="display:inline-flex;align-items:center;gap:.15em;">@' +
                    escapeHtml(u.username || "") +
                    vb +
                    "</span>" +
                    (u.name
                        ? '<span class="mh-secondary">' +
                          escapeHtml(u.name) +
                          "</span>"
                        : "") +
                    "</div>" +
                    "</div>"
                );
            })
            .join("");
    }

    function renderHashtags(tags) {
        if (!tags.length) {
            popover.innerHTML = '<div class="mh-empty">No hashtags found</div>';
            popoverItems = [];
            return;
        }
        popoverItems = tags.map(function (t) {
            return { type: "hashtag", value: t.name, data: t };
        });
        popover.innerHTML = tags
            .map(function (t, idx) {
                const count =
                    t.usage_count != null
                        ? '<span class="mh-secondary">' +
                          t.usage_count +
                          " posts</span>"
                        : "";
                return (
                    '<div class="mh-item' +
                    (idx === activeIndex ? " mh-active" : "") +
                    '" role="option" data-mh-index="' +
                    idx +
                    '">' +
                    '<div class="mh-hashicon">#</div>' +
                    '<div class="mh-meta">' +
                    '<span class="mh-primary">#' +
                    escapeHtml(t.name || "") +
                    "</span>" +
                    count +
                    "</div>" +
                    "</div>"
                );
            })
            .join("");
    }

    function positionPopover(textarea) {
        const rect = textarea.getBoundingClientRect();
        const top = rect.bottom + window.scrollY + 4;
        const left = rect.left + window.scrollX;
        const width = Math.max(rect.width, 280);
        popover.style.top = top + "px";
        popover.style.left = left + "px";
        popover.style.width = width + "px";
        popover.style.display = "block";
    }

    async function fetchSuggestions(trigger, query) {
        if (abortCtl) {
            try {
                abortCtl.abort();
            } catch (_) {}
        }
        abortCtl = new AbortController();
        const opts = {
            credentials: "same-origin",
            signal: abortCtl.signal,
            headers: { Accept: "application/json" },
        };
        try {
            if (trigger === "@") {
                const url =
                    "/api/users/following/suggestions?search=" +
                    encodeURIComponent(query || "");
                const r = await fetch(url, opts);
                if (!r.ok) return [];
                const data = await r.json();
                return Array.isArray(data.data) ? data.data : [];
            }
            // hashtag
            if (!query) {
                const r = await fetch(
                    "/api/sidebar/trending-hashtags?limit=" + MAX_RESULTS,
                    opts,
                );
                if (!r.ok) return [];
                const data = await r.json();
                return Array.isArray(data.data) ? data.data : [];
            }
            const r = await fetch(
                "/api/hashtags/suggestions?search=" + encodeURIComponent(query),
                opts,
            );
            if (!r.ok) return [];
            const data = await r.json();
            const matching = (data.data && data.data.matching) || [];
            return Array.isArray(matching) ? matching : [];
        } catch (e) {
            if (e && e.name === "AbortError") return null;
            return [];
        }
    }

    function handleInput(e) {
        const ta = e.target;
        if (!ta || !ta.matches || !ta.matches(TARGET_SELECTOR)) return;
        activeTextarea = ta;
        const token = detectToken(ta);
        if (!token) {
            hidePopover();
            return;
        }

        activeTrigger = token.trigger;
        activeTokenStart = token.start;
        activeTokenEnd = token.end;

        const queryKey = token.trigger + "|" + token.query;
        if (
            queryKey === lastQueryKey &&
            popover &&
            popover.style.display !== "none"
        )
            return;
        lastQueryKey = queryKey;

        clearTimeout(inputDebounceTimer);
        inputDebounceTimer = setTimeout(async function () {
            ensurePopover();
            const results = await fetchSuggestions(token.trigger, token.query);
            if (results === null) return;
            activeIndex = 0;
            if (token.trigger === "@") renderUsers(results);
            else renderHashtags(results);
            positionPopover(ta);
        }, DEBOUNCE_MS);
    }

    function selectItem(idx) {
        if (!popoverItems.length || !activeTextarea || activeTokenStart < 0)
            return;
        const item = popoverItems[idx];
        if (!item) return;
        const replacement =
            (item.type === "user" ? "@" : "#") + item.value + " ";
        const ta = activeTextarea;
        const before = ta.value.slice(0, activeTokenStart);
        const after = ta.value.slice(activeTokenEnd);
        ta.value = before + replacement + after;
        const newCursor = before.length + replacement.length;
        ta.setSelectionRange(newCursor, newCursor);
        ta.focus();
        ta.dispatchEvent(new Event("input", { bubbles: true }));
        hidePopover();
    }

    function updateActiveStyles() {
        if (!popover) return;
        const items = popover.querySelectorAll("[data-mh-index]");
        items.forEach(function (el, i) {
            if (i === activeIndex) el.classList.add("mh-active");
            else el.classList.remove("mh-active");
        });
        const activeEl = popover.querySelector(".mh-active");
        if (activeEl && activeEl.scrollIntoView)
            activeEl.scrollIntoView({ block: "nearest" });
    }

    function handleKeydown(e) {
        if (
            !popover ||
            popover.style.display === "none" ||
            !popoverItems.length
        )
            return;
        if (e.target !== activeTextarea) return;
        if (e.key === "ArrowDown") {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % popoverItems.length;
            updateActiveStyles();
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            activeIndex =
                (activeIndex - 1 + popoverItems.length) % popoverItems.length;
            updateActiveStyles();
        } else if (e.key === "Enter" || e.key === "Tab") {
            e.preventDefault();
            selectItem(activeIndex);
        } else if (e.key === "Escape") {
            hidePopover();
        }
    }

    function attach() {
        document.addEventListener("input", handleInput, true);
        document.addEventListener("keydown", handleKeydown, true);
        document.addEventListener("click", function (e) {
            if (!popover) return;
            if (popover.contains(e.target)) return;
            if (e.target === activeTextarea) return;
            hidePopover();
        });
        document.addEventListener(
            "blur",
            function (e) {
                if (e.target === activeTextarea) setTimeout(hidePopover, 150);
            },
            true,
        );
        const reposition = function () {
            if (popover && popover.style.display !== "none" && activeTextarea)
                positionPopover(activeTextarea);
        };
        window.addEventListener("scroll", reposition, true);
        window.addEventListener("resize", reposition);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", attach);
    } else {
        attach();
    }
})();
