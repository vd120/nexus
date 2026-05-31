/* eslint-disable */
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    function toast(msg, type) {
        if (window.showToast) { window.showToast(msg, type || 'success'); return; }
        const el = document.createElement('div');
        el.textContent = msg;
        el.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:' + (type === 'error' ? '#dc2626' : '#16a34a') + ';color:#fff;padding:10px 18px;border-radius:8px;z-index:99999;font-weight:600;';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2400);
    }

    async function apiCall(method, url, body) {
        const opts = { method, credentials: 'same-origin', headers: jsonHeaders() };
        if (body) opts.body = JSON.stringify(body);
        const r = await fetch(url, opts);
        let data = null;
        try { data = await r.json(); } catch (_) {}
        return { ok: r.ok, status: r.status, data };
    }

    function $(sel, root) { return (root || document).querySelector(sel); }

    function modal() { return document.getElementById('lcModal'); }

    function openModal(mode, payload) {
        const m = modal();
        if (!m) return;
        const title = $('#lcModalTitle', m);
        const idF = $('#lcFieldId', m);
        const emF = $('#lcFieldEmoji', m);
        const tF = $('#lcFieldTitle', m);
        const sF = $('#lcFieldStarts', m);
        const eF = $('#lcFieldEnds', m);
        const dF = $('#lcFieldDesc', m);
        const err = $('#lcFormError', m);
        if (err) { err.hidden = true; err.textContent = ''; }
        if (mode === 'edit' && payload) {
            title.textContent = title.dataset.editLabel || (window.lcStrings && window.lcStrings.edit) || title.textContent;
            idF.value = payload.id || '';
            emF.value = payload.emoji || '';
            tF.value = payload.title || '';
            sF.value = payload.starts_on || '';
            eF.value = payload.ends_on || '';
            dF.value = payload.description || '';
        } else {
            idF.value = '';
            emF.value = '';
            tF.value = '';
            sF.value = '';
            eF.value = '';
            dF.value = '';
        }
        m.hidden = false;
        document.body.style.overflow = 'hidden';
        setTimeout(() => tF && tF.focus(), 50);
    }

    function closeModal() {
        const m = modal();
        if (!m) return;
        m.hidden = true;
        document.body.style.overflow = '';
    }

    function readCardData(card) {
        return {
            id: card.dataset.lcId,
            title: card.dataset.lcTitle,
            emoji: card.dataset.lcEmoji,
            starts_on: card.dataset.lcStarts,
            ends_on: card.dataset.lcEnds,
            description: card.dataset.lcDesc,
        };
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-lc-open-create]')) {
            e.preventDefault();
            openModal('create');
            return;
        }
        const editBtn = e.target.closest('[data-lc-open-edit]');
        if (editBtn) {
            e.preventDefault();
            const id = editBtn.getAttribute('data-lc-open-edit');
            const card = document.getElementById('lcCard-' + id);
            if (card) openModal('edit', readCardData(card));
            return;
        }
        if (e.target.closest('[data-lc-close]')) {
            e.preventDefault();
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const m = modal();
            if (m && !m.hidden) closeModal();
        }
    });

    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (form.id !== 'lcForm') return;
        e.preventDefault();
        const m = modal();
        const err = $('#lcFormError', m);
        if (err) { err.hidden = true; err.textContent = ''; }

        const id = $('#lcFieldId', m).value.trim();
        const body = {
            title: $('#lcFieldTitle', m).value.trim(),
            emoji: $('#lcFieldEmoji', m).value.trim(),
            starts_on: $('#lcFieldStarts', m).value || null,
            ends_on: $('#lcFieldEnds', m).value || null,
            description: $('#lcFieldDesc', m).value.trim(),
        };
        if (!body.title) {
            if (err) { err.hidden = false; err.textContent = 'Title is required'; }
            return;
        }
        const url = id ? ('/api/chapters/' + id) : '/api/chapters';
        const method = id ? 'PUT' : 'POST';

        const saveBtn = $('#lcSaveBtn', m);
        if (saveBtn) saveBtn.disabled = true;

        const { ok, data } = await apiCall(method, url, body);

        if (saveBtn) saveBtn.disabled = false;

        if (!ok || !(data && data.success)) {
            const msg = (data && data.errors)
                ? Object.values(data.errors).flat().join(' ')
                : (data && data.message) || 'Save failed';
            if (err) { err.hidden = false; err.textContent = msg; }
            return;
        }
        toast('Saved', 'success');
        closeModal();
        // Simple, reliable: reload to re-render server-side partial
        window.location.reload();
    });

    // Hook our own delete success to reload counts. The community-admin-inline.js
    // handler already does the DELETE + DOM removal — we just trigger a soft refresh
    // when a chapter card disappears so the empty state can render if it was the last.
    const observer = new MutationObserver(() => {
        const shelf = document.getElementById('lcShelf');
        if (!shelf) return;
        const remaining = shelf.querySelectorAll('.lc-card:not(.lc-card-add)').length;
        if (remaining === 0 && shelf.dataset.lcEmptyHandled !== '1') {
            shelf.dataset.lcEmptyHandled = '1';
            setTimeout(() => window.location.reload(), 350);
        }
    });
    document.addEventListener('DOMContentLoaded', () => {
        const shelf = document.getElementById('lcShelf');
        if (shelf) observer.observe(shelf, { childList: true, subtree: true });
    });
})();
