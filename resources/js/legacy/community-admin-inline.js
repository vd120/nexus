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
        // tiny fallback
        const el = document.createElement('div');
        el.textContent = msg;
        el.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:' + (type === 'error' ? '#dc2626' : '#16a34a') + ';color:#fff;padding:10px 18px;border-radius:8px;z-index:99999;font-weight:600;';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2400);
    }

    async function apiCall(method, url, body) {
        const opts = {
            method,
            credentials: 'same-origin',
            headers: jsonHeaders(),
        };
        if (body) opts.body = JSON.stringify(body);
        const r = await fetch(url, opts);
        let data = null;
        try { data = await r.json(); } catch (_) {}
        return { ok: r.ok, status: r.status, data };
    }

    // ----------- DELETE with confirm -----------
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-admin-delete]');
        if (!btn) return;
        e.preventDefault();
        const url = btn.getAttribute('data-admin-delete');
        const target = btn.getAttribute('data-target'); // selector to remove from DOM
        const confirmMsg = btn.getAttribute('data-confirm') || 'Delete this item?';
        if (!window.confirm(confirmMsg)) return;
        btn.disabled = true;
        const { ok, data } = await apiCall('DELETE', url);
        btn.disabled = false;
        if (!ok || !(data && data.success)) {
            toast((data && data.message) || 'Delete failed', 'error');
            return;
        }
        if (target) {
            const node = document.querySelector(target);
            if (node) {
                node.style.transition = 'opacity 0.2s, transform 0.2s';
                node.style.opacity = '0';
                node.style.transform = 'scale(0.96)';
                setTimeout(() => node.remove(), 200);
            }
        }
        toast('Deleted', 'success');
    });

    // ----------- INLINE EDIT -----------
    // pattern:
    // <div class="ed-row" id="X">
    //   <div class="ed-view"> ...view markup...
    //     <button data-admin-edit data-target="#X">Edit</button>
    //   </div>
    //   <div class="ed-form" style="display:none">
    //     <input data-field="title">
    //     <textarea data-field="description"></textarea>
    //     <button data-admin-save data-url="/.../id" data-target="#X">Save</button>
    //     <button data-admin-cancel data-target="#X">Cancel</button>
    //   </div>
    // </div>

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-admin-edit]');
        if (!btn) return;
        e.preventDefault();
        const sel = btn.getAttribute('data-target');
        const root = document.querySelector(sel);
        if (!root) return;
        root.querySelector('.ed-view').style.display = 'none';
        root.querySelector('.ed-form').style.display = 'block';
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-admin-cancel]');
        if (!btn) return;
        e.preventDefault();
        const sel = btn.getAttribute('data-target');
        const root = document.querySelector(sel);
        if (!root) return;
        root.querySelector('.ed-form').style.display = 'none';
        root.querySelector('.ed-view').style.display = '';
    });

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-admin-save]');
        if (!btn) return;
        e.preventDefault();
        const sel = btn.getAttribute('data-target');
        const url = btn.getAttribute('data-url');
        const root = document.querySelector(sel);
        if (!root || !url) return;
        const fields = root.querySelectorAll('.ed-form [data-field]');
        const body = {};
        fields.forEach(f => { body[f.getAttribute('data-field')] = f.value; });
        btn.disabled = true;
        const { ok, data } = await apiCall('PUT', url, body);
        btn.disabled = false;
        if (!ok || !(data && data.success)) {
            const msg = (data && data.errors) ? Object.values(data.errors).flat().join(' ') : (data && data.message) || 'Save failed';
            toast(msg, 'error');
            return;
        }
        // Update the view text from the form values
        const view = root.querySelector('.ed-view');
        Object.entries(body).forEach(([k, v]) => {
            const target = view.querySelector('[data-view="' + k + '"]');
            if (target) target.textContent = v;
        });
        root.querySelector('.ed-form').style.display = 'none';
        view.style.display = '';
        toast('Saved', 'success');
    });

})();
