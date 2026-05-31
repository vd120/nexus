@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="admin-page">
    <div class="admin-page-header admin-flex-header">
        <div class="header-text">
            <h1 class="admin-page-title">{{ __('community_admin.reports') ?? 'Reports' }}</h1>
            <p class="admin-page-subtitle">{{ __('community_admin.reports_subtitle') ?? 'Review reports filed by members against posts in this community.' }}</p>
        </div>
        <div class="reports-filter-pills" id="reports-filter">
            <button type="button" class="filter-pill active" data-status="pending">{{ __('community_admin.pending') ?? 'Pending' }}</button>
            <button type="button" class="filter-pill" data-status="accepted">{{ __('community_admin.accepted') ?? 'Accepted' }}</button>
            <button type="button" class="filter-pill" data-status="rejected">{{ __('community_admin.rejected') ?? 'Rejected' }}</button>
            <button type="button" class="filter-pill" data-status="">{{ __('community_admin.all') ?? 'All' }}</button>
        </div>
    </div>

    <div id="reports-list" class="reports-list">
        <div class="reports-loading" style="padding:40px;text-align:center;color:var(--text-muted);">
            <i class="fas fa-spinner fa-spin"></i> {{ __('community_admin.loading') ?? 'Loading…' }}
        </div>
    </div>

    <div id="reports-empty" class="admin-empty-state" style="display:none;">
        <div class="empty-icon-wrap"><i class="fas fa-flag"></i></div>
        <h3>{{ __('community_admin.no_reports') ?? 'No reports' }}</h3>
        <p>{{ __('community_admin.no_reports_desc') ?? 'Nothing to review right now.' }}</p>
    </div>

    <div id="reports-pagination" class="reports-pagination" style="display:flex;justify-content:center;gap:8px;margin-top:18px;"></div>
</div>

        </div>
    </main>
</div>

<style>
.reports-filter-pills { display:flex; gap:6px; }
.filter-pill {
    padding: 6px 14px; border-radius: 999px; border: 1px solid var(--border);
    background: transparent; color: var(--text-muted); font-size: 13px; font-weight: 600; cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.filter-pill:hover { background: var(--surface-hover); color: var(--text); }
.filter-pill.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.reports-list { display:flex; flex-direction:column; gap:12px; }
.report-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
    padding: 16px; display:flex; flex-direction:column; gap:10px;
}
.report-card-header { display:flex; align-items:center; gap:10px; }
.report-reporter-avatar { width:36px; height:36px; border-radius:50%; object-fit:cover; flex-shrink:0; background:var(--surface-2); }
.report-reporter-name { font-weight:700; font-size:14px; color:var(--text); }
.report-reporter-handle { font-size:12px; color:var(--text-muted); }
.report-time { margin-left:auto; font-size:11px; color:var(--text-dim); }
.report-reason {
    display:inline-block; padding:3px 10px; border-radius:999px;
    background:rgba(239,68,68,.12); color:#ef4444; font-size:11px; font-weight:600;
    text-transform:uppercase; letter-spacing:.04em;
}
.report-content {
    background: var(--surface-2); border-radius: 10px; padding: 10px 12px;
    font-size: 13px; color: var(--text); border-left: 3px solid var(--primary);
}
.report-content a { color: var(--primary); text-decoration: none; font-weight: 600; }
.report-actions { display:flex; gap:8px; }
.btn-resolve-accept {
    padding:8px 14px; border-radius:8px; border:none; cursor:pointer;
    background:#dc2626; color:#fff; font-weight:600; font-size:13px;
}
.btn-resolve-accept:hover { background:#b91c1c; }
.btn-resolve-reject {
    padding:8px 14px; border-radius:8px; border:1px solid var(--border-strong); cursor:pointer;
    background:transparent; color:var(--text); font-weight:600; font-size:13px;
}
.btn-resolve-reject:hover { background:var(--surface-hover); }
.report-status-badge {
    padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600;
    text-transform:uppercase; letter-spacing:.04em;
}
.report-status-badge.accepted { background:rgba(34,197,94,.12); color:#22c55e; }
.report-status-badge.rejected { background:rgba(156,163,175,.15); color:var(--text-muted); }
.pagination-btn {
    padding:6px 14px; border-radius:8px; border:1px solid var(--border);
    background:var(--surface); color:var(--text); cursor:pointer; font-size:13px;
}
.pagination-btn:disabled { opacity:.4; cursor:not-allowed; }
</style>

<script>
(function () {
    const groupSlug = @json($group->slug);
    const baseUrl = '/communities/' + groupSlug + '/admin/reports/data';
    const resolveUrlFor = (id) => '/communities/' + groupSlug + '/admin/reports/' + id + '/resolve';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const list = document.getElementById('reports-list');
    const empty = document.getElementById('reports-empty');
    const pagWrap = document.getElementById('reports-pagination');

    let currentStatus = 'pending';
    let currentPage = 1;

    function fmtTime(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        const diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) return Math.floor(diff) + 's ago';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function render(payload) {
        const data = payload.data || {};
        const rows = data.data || [];
        list.innerHTML = '';
        if (!rows.length) {
            empty.style.display = '';
            pagWrap.innerHTML = '';
            return;
        }
        empty.style.display = 'none';
        rows.forEach(r => {
            const post = r.post || {};
            const reporter = r.reporter || {};
            const isPending = r.status === 'pending';
            const statusBadge = !isPending
                ? `<span class="report-status-badge ${escapeHtml(r.status)}">${escapeHtml(r.status)}</span>`
                : '';
            const actions = isPending
                ? `<div class="report-actions">
                       <button class="btn-resolve-accept" data-action="accept" data-id="${r.id}">
                           <i class="fas fa-check"></i> Delete post
                       </button>
                       <button class="btn-resolve-reject" data-action="reject" data-id="${r.id}">
                           <i class="fas fa-times"></i> Dismiss
                       </button>
                   </div>`
                : '';
            const card = document.createElement('div');
            card.className = 'report-card';
            card.id = 'report-' + r.id;
            card.innerHTML = `
                <div class="report-card-header">
                    ${reporter.avatar_url
                        ? `<img class="report-reporter-avatar" src="${escapeHtml(reporter.avatar_url)}" alt="">`
                        : `<div class="report-reporter-avatar"></div>`}
                    <div>
                        <div class="report-reporter-name">${escapeHtml(reporter.name || reporter.username || 'Unknown')}</div>
                        <div class="report-reporter-handle">@${escapeHtml(reporter.username || '')}</div>
                    </div>
                    <span class="report-time">${fmtTime(r.created_at)}</span>
                </div>
                <div><span class="report-reason">${escapeHtml(r.reason || 'Other')}</span> ${statusBadge}</div>
                ${r.content ? `<div class="report-content">${escapeHtml(r.content)}</div>` : ''}
                ${post.slug
                    ? `<div><a href="/posts/${escapeHtml(post.slug)}" target="_blank"><i class="fas fa-external-link-alt"></i> View reported post</a></div>`
                    : '<div style="color:var(--text-muted);font-size:12px;"><i class="fas fa-trash"></i> Post already deleted</div>'}
                ${actions}
            `;
            list.appendChild(card);
        });
        renderPagination(data);
    }

    function renderPagination(data) {
        pagWrap.innerHTML = '';
        if ((data.last_page || 1) <= 1) return;
        const prev = document.createElement('button');
        prev.className = 'pagination-btn';
        prev.textContent = '←';
        prev.disabled = currentPage <= 1;
        prev.addEventListener('click', () => { currentPage--; load(); });
        const label = document.createElement('span');
        label.style.cssText = 'padding:6px 12px;color:var(--text-muted);font-size:13px;';
        label.textContent = `${data.current_page} / ${data.last_page}`;
        const next = document.createElement('button');
        next.className = 'pagination-btn';
        next.textContent = '→';
        next.disabled = currentPage >= data.last_page;
        next.addEventListener('click', () => { currentPage++; load(); });
        pagWrap.appendChild(prev);
        pagWrap.appendChild(label);
        pagWrap.appendChild(next);
    }

    async function load() {
        list.innerHTML = '<div class="reports-loading" style="padding:40px;text-align:center;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Loading…</div>';
        empty.style.display = 'none';
        const params = new URLSearchParams();
        if (currentStatus) params.set('status', currentStatus);
        params.set('page', currentPage);
        params.set('per_page', '15');
        try {
            const r = await fetch(baseUrl + '?' + params.toString(), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const j = await r.json();
            if (!r.ok || !j.success) {
                list.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;">Failed to load reports.</div>';
                return;
            }
            render(j);
        } catch (e) {
            list.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;">Network error.</div>';
        }
    }

    // Filter pills
    document.getElementById('reports-filter').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-status]');
        if (!btn) return;
        document.querySelectorAll('#reports-filter .filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentStatus = btn.getAttribute('data-status');
        currentPage = 1;
        load();
    });

    // Resolve actions
    list.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        const confirmMsg = action === 'accept'
            ? 'Delete the reported post? This cannot be undone.'
            : 'Dismiss this report?';
        if (!window.confirm(confirmMsg)) return;
        btn.disabled = true;
        try {
            const r = await fetch(resolveUrlFor(id), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action })
            });
            const j = await r.json();
            if (!r.ok || !j.success) {
                btn.disabled = false;
                if (window.showToast) window.showToast(j.message || 'Failed', 'error');
                return;
            }
            const card = document.getElementById('report-' + id);
            if (card) {
                card.style.transition = 'opacity .2s, transform .2s';
                card.style.opacity = '0';
                card.style.transform = 'scale(.96)';
                setTimeout(() => card.remove(), 200);
            }
            if (window.showToast) window.showToast(action === 'accept' ? 'Post deleted' : 'Report dismissed', 'success');
        } catch (err) {
            btn.disabled = false;
            if (window.showToast) window.showToast('Network error', 'error');
        }
    });

    // Initial load
    load();
})();
</script>
@endsection
