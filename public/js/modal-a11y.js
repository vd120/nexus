/**
 * Global modal accessibility helper.
 *
 * Adds three behaviours that should apply to every modal in the project
 * regardless of which CSS system it uses (.modal / .modal-overlay /
 * .nexus-modal-overlay / .reactors-modal):
 *
 *   1. Escape key closes the topmost visible modal
 *   2. Tab cycles focus inside the modal instead of escaping to the page
 *   3. Click on the backdrop (but not on the inner content) closes the modal
 *
 * This script is layout-load deferred and never throws — it only acts if
 * a modal is actually visible.
 */
(function () {
    'use strict';

    const MODAL_SELECTORS = [
        '.modal.show',
        '.modal-overlay',
        '.nexus-modal-overlay',
        '.reactors-modal.active',
        '.media-modal.active',
    ];

    const FOCUSABLE = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    function isVisible(el) {
        if (!el || !el.offsetParent === null) return false;
        const cs = getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden') return false;
        if (el.style && el.style.display === 'none') return false;
        return true;
    }

    function getOpenModals() {
        const found = [];
        MODAL_SELECTORS.forEach(sel => {
            document.querySelectorAll(sel).forEach(el => {
                if (isVisible(el)) found.push(el);
            });
        });
        return found;
    }

    function getTopModal() {
        const open = getOpenModals();
        if (!open.length) return null;
        // Highest z-index wins; fall back to last in DOM
        let top = open[0];
        let topZ = parseInt(getComputedStyle(top).zIndex, 10) || 0;
        for (let i = 1; i < open.length; i++) {
            const z = parseInt(getComputedStyle(open[i]).zIndex, 10) || 0;
            if (z >= topZ) { top = open[i]; topZ = z; }
        }
        return top;
    }

    function closeModal(modal) {
        if (!modal) return;
        // Try the close button first — preserves whatever app-specific cleanup
        // each modal's close handler does (form reset, body.overflow restore, etc.)
        const closeBtn = modal.querySelector(
            '.modal-close, .modal-close-btn, .close-btn, .close-modal, ' +
            '.reactors-modal-close, .media-modal-close, [data-modal-close]'
        );
        if (closeBtn) { closeBtn.click(); return; }

        // Fall back to manually hiding the modal in whichever style it uses
        modal.classList.remove('show', 'active');
        if (modal.style && modal.style.display) {
            modal.style.display = 'none';
        }
        document.body.style.overflow = '';
    }

    // --- Escape key ---
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' && e.keyCode !== 27) return;
        const top = getTopModal();
        if (!top) return;
        e.stopPropagation();
        closeModal(top);
    });

    // --- Focus trap on Tab ---
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab' && e.keyCode !== 9) return;
        const top = getTopModal();
        if (!top) return;

        const focusables = Array.from(top.querySelectorAll(FOCUSABLE)).filter(isVisible);
        if (!focusables.length) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        const active = document.activeElement;

        if (e.shiftKey && active === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && active === last) {
            e.preventDefault();
            first.focus();
        } else if (!top.contains(active)) {
            e.preventDefault();
            first.focus();
        }
    });

    // --- Click backdrop to close ---
    // Only attaches when click target IS the modal/overlay itself, not children.
    // Modals that already handle backdrop click via inline onclick still work —
    // theirs runs first; we don't double-close.
    document.addEventListener('click', function (e) {
        const target = e.target;
        if (!target || !target.matches) return;
        // Only the backdrop element itself, never an inner child
        if (!target.matches(MODAL_SELECTORS.join(','))) return;
        // Skip if there's already an inline onclick handler — it will fire first
        if (target.hasAttribute('onclick')) return;
        closeModal(target);
    });
})();
