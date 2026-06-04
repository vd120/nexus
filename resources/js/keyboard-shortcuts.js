/**
 * Nexus keyboard shortcuts
 * J/K  — next/previous post
 * L    — like focused post
 * N    — open new post composer
 * /    — focus search
 * G+H  — go home
 * G+P  — go to profile
 * G+N  — go to notifications
 * G+M  — go to messages
 * ?    — show shortcuts modal
 * Esc  — close modals
 */

(function () {
    'use strict';

    const FOCUSED_POST_CLASS = 'kb-focused';
    let focusedPostIndex = -1;
    let gPending = false;
    let gTimeout = null;
    const USERNAME = window.currentUserUsername || null;

    function isTyping() {
        const el = document.activeElement;
        if (!el) return false;
        const tag = el.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
    }

    function getPosts() {
        return Array.from(document.querySelectorAll('.post-card, .post-wrapper, [data-post-id], article.post'));
    }

    function focusPost(index) {
        const posts = getPosts();
        if (!posts.length) return;
        posts.forEach(p => p.classList.remove(FOCUSED_POST_CLASS));
        focusedPostIndex = Math.max(0, Math.min(index, posts.length - 1));
        const post = posts[focusedPostIndex];
        post.classList.add(FOCUSED_POST_CLASS);
        post.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function likeCurrentPost() {
        const posts = getPosts();
        if (focusedPostIndex < 0 || focusedPostIndex >= posts.length) return;
        const likeBtn = posts[focusedPostIndex].querySelector('[data-like-btn], .like-btn, button.action-btn[data-post-slug]');
        if (likeBtn) likeBtn.click();
    }

    function openComposer() {
        const textarea = document.getElementById('post-content');
        if (textarea) {
            textarea.focus();
            textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function focusSearch() {
        const search = document.getElementById('search-input') ||
                       document.querySelector('input[type="search"], input[placeholder*="earch"]');
        if (search) {
            search.focus();
            search.select();
        }
    }

    function closeTopModal() {
        // Try common close patterns
        const modalClose = document.querySelector('.modal:not([style*="display: none"]) .modal-close, .modal-overlay:not([style*="display: none"]) .close-btn');
        if (modalClose) { modalClose.click(); return; }
        // Dispatch Escape to any open dialog
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    }

    function toggleShortcutsModal() {
        let modal = document.getElementById('kb-shortcuts-modal');
        if (!modal) {
            modal = buildShortcutsModal();
            document.body.appendChild(modal);
        }
        const isVisible = modal.style.display !== 'none';
        modal.style.display = isVisible ? 'none' : 'flex';
        if (!isVisible) modal.querySelector('[tabindex]')?.focus();
    }

    function buildShortcutsModal() {
        const shortcuts = [
            ['J / K',  'Next / previous post'],
            ['L',      'Like focused post'],
            ['N',      'New post'],
            ['/',      'Focus search'],
            ['G then H', 'Go to home feed'],
            ['G then P', 'Go to your profile'],
            ['G then N', 'Go to notifications'],
            ['G then M', 'Go to messages'],
            ['?',      'Show this help'],
            ['Esc',    'Close modal'],
        ];

        const overlay = document.createElement('div');
        overlay.id = 'kb-shortcuts-modal';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Keyboard shortcuts');
        overlay.style.cssText = `
            position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99999;
            display:flex;align-items:center;justify-content:center;padding:1rem;
        `;
        overlay.innerHTML = `
            <div style="background:var(--card-bg,#1e1e2e);border:1px solid var(--border-color,#2a2a3e);border-radius:16px;padding:1.5rem;max-width:420px;width:100%;max-height:80vh;overflow-y:auto;" tabindex="-1">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
                    <h2 style="font-size:1rem;font-weight:700;margin:0;">Keyboard Shortcuts</h2>
                    <button onclick="document.getElementById('kb-shortcuts-modal').style.display='none'" style="background:none;border:none;cursor:pointer;opacity:.5;font-size:1.125rem;padding:.25rem;" aria-label="Close">&times;</button>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                    ${shortcuts.map(([key, label]) => `
                    <tr style="border-bottom:1px solid var(--border-color,#2a2a3e);">
                        <td style="padding:.5rem .75rem .5rem 0;white-space:nowrap;">
                            ${key.split(' / ').map(k => `<kbd style="background:var(--input-bg,#12121f);border:1px solid var(--border-color,#2a2a3e);border-radius:4px;padding:.15rem .45rem;font-size:.8rem;font-family:monospace;">${k}</kbd>`).join(' / ')}
                        </td>
                        <td style="padding:.5rem 0;font-size:.875rem;opacity:.75;">${label}</td>
                    </tr>`).join('')}
                </table>
                <p style="font-size:.75rem;opacity:.4;margin:.875rem 0 0;text-align:center;">Shortcuts are disabled while typing in inputs.</p>
            </div>
        `;
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.style.display = 'none'; });
        return overlay;
    }

    document.addEventListener('keydown', function (e) {
        // Never fire during text input
        if (isTyping()) return;

        const key = e.key;

        // Handle G-sequence
        if (gPending) {
            clearTimeout(gTimeout);
            gPending = false;
            switch (key.toLowerCase()) {
                case 'h': window.location.href = '/'; return;
                case 'p': if (USERNAME) window.location.href = '/users/' + USERNAME; return;
                case 'n': window.location.href = '/notifications'; return;
                case 'm': window.location.href = '/chat'; return;
            }
        }

        switch (key) {
            case 'j': case 'J':
                focusPost(focusedPostIndex + 1);
                e.preventDefault(); break;
            case 'k': case 'K':
                focusPost(Math.max(0, focusedPostIndex - 1));
                e.preventDefault(); break;
            case 'l': case 'L':
                likeCurrentPost(); break;
            case 'n': case 'N':
                openComposer(); break;
            case '/':
                e.preventDefault();
                focusSearch(); break;
            case '?':
                toggleShortcutsModal(); break;
            case 'g': case 'G':
                gPending = true;
                gTimeout = setTimeout(() => { gPending = false; }, 1000);
                break;
            case 'Escape':
                if (document.getElementById('kb-shortcuts-modal')?.style.display !== 'none') {
                    document.getElementById('kb-shortcuts-modal').style.display = 'none';
                } else {
                    closeTopModal();
                }
                break;
        }
    });

    // Add focused post styles
    const style = document.createElement('style');
    style.textContent = `.${FOCUSED_POST_CLASS}{outline:2px solid var(--accent,#6366f1);outline-offset:2px;border-radius:inherit;}`;
    document.head.appendChild(style);
})();
