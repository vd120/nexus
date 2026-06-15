@extends('layouts.app')

@section('title', __('users.search'))

@section('content')
<style>
.search-container { max-width: 680px; margin: 0 auto; padding: 16px 12px 100px; }

.search-box {
    position: relative; margin-bottom: 20px;
}
.search-input {
    width: 100%; padding: 14px 16px 14px 50px; font-size: 16px;
    border: 1px solid var(--border); border-radius: var(--radius-lg);
    background: var(--surface); color: var(--text);
}
.search-input:focus {
    outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}
.search-icon {
    position: absolute; left: 17px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 16px; pointer-events: none;
}

/* Tabs */
.search-tabs {
    display: flex; gap: 0; margin-bottom: 20px;
    border-bottom: 1px solid var(--border);
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}
.search-tabs::-webkit-scrollbar { display: none; }
.search-tab {
    flex-shrink: 0;
    white-space: nowrap;
    padding: 10px 18px; font-size: 14px; font-weight: 500;
    color: var(--text-muted); background: none; border: none;
    border-bottom: 2px solid transparent; margin-bottom: -1px;
    cursor: pointer; transition: color .15s, border-color .15s;
}
.search-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.search-tab:hover:not(.active) { color: var(--text); }
.tab-count { display: inline-block; font-size: 11px; font-weight: 600; background: var(--border); color: var(--text-muted); border-radius: 10px; padding: 1px 6px; margin-left: 4px; min-width: 18px; text-align: center; }
.search-tab.active .tab-count { background: rgba(139, 92, 246, 0.15); color: var(--primary); }

.search-results { display: flex; flex-direction: column; gap: 10px; }

/* User card */
.user-card {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px;
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
    text-decoration: none; color: inherit; transition: border-color .15s;
}
.user-card:hover { border-color: var(--primary); }
.user-avatar-wrap {
    width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
}
.user-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
.user-info { flex: 1; min-width: 0; }
.user-name { font-size: 15px; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 4px; overflow: hidden; }
.user-handle { font-size: 13px; color: var(--text-muted); direction: ltr; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }

/* Post card */
.post-card {
    display: flex; flex-direction: column; gap: 8px; padding: 14px 16px;
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
    text-decoration: none; color: inherit; transition: border-color .15s;
}
.post-card:hover { border-color: var(--primary); }
.post-card-meta { display: flex; align-items: center; gap: 8px; }
.post-card-avatar { width: 30px; height: 30px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
.post-card-avatar img { width: 100%; height: 100%; object-fit: cover; }
.post-card-author { font-size: 13px; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.post-card-time { font-size: 12px; color: var(--text-muted); margin-left: auto; flex-shrink: 0; }
.post-card-content { font-size: 14px; color: var(--text); line-height: 1.55; word-break: break-word; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }

/* Community card */
.group-card {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px;
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
    text-decoration: none; color: inherit; transition: border-color .15s;
}
.group-card:hover { border-color: var(--primary); }
.group-avatar { width: 48px; height: 48px; border-radius: 12px; overflow: hidden; flex-shrink: 0; background: var(--primary); display: flex; align-items: center; justify-content: center; }
.group-avatar img { width: 100%; height: 100%; object-fit: cover; }
.group-avatar-placeholder { font-size: 20px; font-weight: 700; color: #fff; }
.group-info { flex: 1; min-width: 0; }
.group-name { font-size: 15px; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.group-meta { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.group-desc { font-size: 13px; color: var(--text-muted); margin-top: 4px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 48px 20px; width: 100%; }
.empty-state i { font-size: 48px; color: var(--text-muted); margin-bottom: 16px; opacity: 0.4; display: flex; justify-content: center; width: 100%; direction: ltr; }
.empty-state h3 { font-size: 16px; margin-bottom: 6px; }
.empty-state p { color: var(--text-muted); font-size: 14px; }

@media (max-width: 380px) {
    .search-tab { padding: 9px 12px; font-size: 13px; }
    .user-card, .post-card, .group-card { padding: 12px 12px; gap: 10px; }
    .user-avatar-wrap, .group-avatar { width: 42px; height: 42px; }
}
</style>

<div class="search-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="search-input" class="search-input"
               placeholder="{{ __('users.search_users_placeholder') }}"
               autocomplete="off" value="{{ request('q') }}">
    </div>

    <div class="search-tabs" id="search-tabs" style="display:none;">
        <button class="search-tab active" data-tab="users"><i class="fas fa-user" style="margin-right:6px;font-size:12px;"></i>{{ __('users.people') }} <span class="tab-count" id="count-users"></span></button>
        <button class="search-tab" data-tab="posts"><i class="fas fa-file-alt" style="margin-right:6px;font-size:12px;"></i>{{ __('users.posts') }} <span class="tab-count" id="count-posts"></span></button>
        <button class="search-tab" data-tab="communities"><i class="fas fa-users" style="margin-right:6px;font-size:12px;"></i>{{ __('users.communities') }} <span class="tab-count" id="count-communities"></span></button>
    </div>

    <div id="search-results" class="search-results">
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>{{ __('users.search_for_users') }}</h3>
            <p>{{ __('users.type_to_search') }}</p>
        </div>
    </div>
</div>

<script>
let searchTimeout;
let currentData = { users: [], posts: [], communities: [] };
let activeTab = 'users';

const searchInput  = document.getElementById('search-input');
const resultsEl    = document.getElementById('search-results');
const tabsEl       = document.getElementById('search-tabs');

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function verifiedBadge() {
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".85em" height=".85em" style="display:inline-block;vertical-align:middle;flex-shrink:0;" aria-label="Verified"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
}

function renderUsers(users) {
    if (!users.length) return emptyState('fa-user-slash', '{{ __("users.no_users_found") }}', '{{ __("users.try_different_search") }}');
    return users.map(u => `
        <a href="/users/${escapeHtml(u.username)}" class="user-card">
            <div class="user-avatar-wrap"><img src="${escapeHtml(u.avatar_url)}" alt="${escapeHtml(u.username)}" loading="lazy"></div>
            <div class="user-info">
                <div class="user-name">${escapeHtml(u.name)}${u.is_verified ? verifiedBadge() : ''}</div>
                <div class="user-handle">@${escapeHtml(u.username)}</div>
            </div>
        </a>`).join('');
}

function renderPosts(posts) {
    if (!posts.length) return emptyState('fa-file-alt', '{{ __("users.no_posts_found") }}', '{{ __("users.try_different_search") }}');
    return posts.map(p => `
        <a href="/posts/${escapeHtml(p.slug)}" class="post-card">
            <div class="post-card-meta">
                <div class="post-card-avatar"><img src="${escapeHtml(p.user.avatar_url)}" alt="${escapeHtml(p.user.username)}" loading="lazy"></div>
                <span class="post-card-author">${escapeHtml(p.user.name)}</span>
                <span class="post-card-time">${escapeHtml(p.created_at)}</span>
            </div>
            <div class="post-card-content">${escapeHtml(p.content)}</div>
        </a>`).join('');
}

function renderCommunities(groups) {
    if (!groups.length) return emptyState('fa-users-slash', '{{ __("users.no_communities_found") }}', '{{ __("users.try_different_search") }}');
    return groups.map(g => `
        <a href="/communities/${escapeHtml(g.slug)}" class="group-card">
            <div class="group-avatar">
                ${g.avatar_url
                    ? `<img src="${escapeHtml(g.avatar_url)}" alt="${escapeHtml(g.name)}" loading="lazy">`
                    : `<span class="group-avatar-placeholder">${escapeHtml(g.name.charAt(0).toUpperCase())}</span>`}
            </div>
            <div class="group-info">
                <div class="group-name">${escapeHtml(g.name)}</div>
                <div class="group-meta">${g.members_count.toLocaleString()} {{ __("users.members") }}</div>
                ${g.description ? `<div class="group-desc">${escapeHtml(g.description)}</div>` : ''}
            </div>
        </a>`).join('');
}

function emptyState(icon, title, sub) {
    return `<div class="empty-state"><i class="fas ${icon}"></i><h3>${title}</h3><p>${sub}</p></div>`;
}

function renderTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.search-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    if (tab === 'users')       resultsEl.innerHTML = renderUsers(currentData.users);
    else if (tab === 'posts')  resultsEl.innerHTML = renderPosts(currentData.posts);
    else                       resultsEl.innerHTML = renderCommunities(currentData.communities);
}

document.querySelectorAll('.search-tab').forEach(btn => {
    btn.addEventListener('click', () => renderTab(btn.dataset.tab));
});

function doSearch(q) {
    resultsEl.innerHTML = `<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>{{ __('users.searching') }}</p></div>`;

    fetch(`/api/search?q=${encodeURIComponent(q)}`, {
        credentials: 'include',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error();
        currentData = data;
        tabsEl.style.display = 'flex';

        // Update tab counters
        const counts = { users: data.users.length, posts: data.posts.length, communities: data.communities.length };
        document.getElementById('count-users').textContent = counts.users;
        document.getElementById('count-posts').textContent = counts.posts;
        document.getElementById('count-communities').textContent = counts.communities;

        // Auto-select tab with most results
        const best = Object.entries(counts).sort((a,b) => b[1]-a[1])[0][0];
        renderTab(best);
    })
    .catch(() => {
        resultsEl.innerHTML = emptyState('fa-exclamation-circle', '{{ __("users.search_failed") }}', '{{ __("users.please_try_again") }}');
    });
}

searchInput.addEventListener('input', e => {
    clearTimeout(searchTimeout);
    const q = e.target.value.trim();

    if (q.length < 2) {
        tabsEl.style.display = 'none';
        document.getElementById('count-users').textContent = '';
        document.getElementById('count-posts').textContent = '';
        document.getElementById('count-communities').textContent = '';
        resultsEl.innerHTML = `<div class="empty-state"><i class="fas fa-search"></i><h3>{{ __('users.search_for_users') }}</h3><p>{{ __('users.type_to_search') }}</p></div>`;
        return;
    }

    searchTimeout = setTimeout(() => doSearch(q), 300);
});

// Auto-search if query param is present
const urlQ = new URLSearchParams(location.search).get('q');
if (urlQ && urlQ.length >= 2) {
    searchInput.value = urlQ;
    doSearch(urlQ);
}

searchInput.focus();
</script>
@endsection
