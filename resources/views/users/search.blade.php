@extends('layouts.app')

@section('title', __('users.search'))

@section('content')
<style>
.search-container { max-width: 680px; margin: 0 auto; padding: 0 12px; }

.search-box {
    position: relative; margin-bottom: 32px;
}
.search-input {
    width: 100%; padding: 16px 20px 16px 56px; font-size: 16px;
    border: 1px solid var(--border); border-radius: var(--radius-lg);
    background: var(--surface); color: var(--text);
}
.search-input:focus {
    outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}
.search-icon {
    position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 18px;
}

.search-results { display: flex; flex-direction: column; gap: 12px; }
.user-card {
    display: flex; align-items: center; gap: 16px; padding: 16px 20px;
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
}
.user-avatar {
    width: 56px; height: 56px; border-radius: 50%; overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--secondary)); flex-shrink: 0;
}
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }
.user-avatar .placeholder {
    width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 700; color: white;
}
.user-info { flex: 1; }
.user-info a { text-decoration: none; }
.user-name { font-size: 16px; font-weight: 600; color: var(--text); }
.user-name:hover { color: var(--primary); }
.user-meta { font-size: 13px; color: var(--text-muted); }
.user-meta span { direction: ltr; }

.empty-state { text-align: center; padding: 60px 20px; }
.empty-state i { font-size: 64px; color: var(--text-muted); margin-bottom: 20px; opacity: 0.5; }

/* Mobile Responsive */
@media (max-width: 480px) {
    .search-container { padding: 0 8px; }
    .search-input { font-size: 15px; padding: 14px 16px 14px 50px; }
    .search-icon { left: 16px; font-size: 16px; }
    .user-card {
        display: grid !important;
        grid-template-columns: auto 1fr !important;
        grid-template-rows: auto auto !important;
        gap: 10px !important;
        padding: 12px !important;
    }
    .user-avatar {
        grid-row: 1 / 3 !important;
        grid-column: 1 !important;
        width: 48px !important;
        height: 48px !important;
    }
    .user-info {
        grid-row: 1 !important;
        grid-column: 2 !important;
    }
    .user-name { font-size: 15px !important; }
    .user-meta { font-size: 13px !important; }
}
</style>

<div class="search-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="search-input" class="search-input" placeholder="{{ __('users.search_users_placeholder') }}" autocomplete="off">
    </div>

    <div id="search-results" class="search-results">
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>{{ __('users.search_for_users') }}</h3>
            <p style="color: var(--text-muted);">{{ __('users.type_to_search') }}</p>
        </div>
    </div>
</div>

<script>
let searchTimeout;
const searchInput = document.getElementById('search-input');
const resultsContainer = document.getElementById('search-results');
const searchForUsersText = {!! json_encode(__('users.search_for_users')) !!};
const typeAtLeast2Text = {!! json_encode(__('users.type_at_least_2')) !!};
const searchingText = {!! json_encode(__('users.searching')) !!};
const noUsersFoundText = {!! json_encode(__('users.no_users_found')) !!};
const tryDifferentSearchText = {!! json_encode(__('users.try_different_search')) !!};
const searchFailedText = {!! json_encode(__('users.search_failed')) !!};
const pleaseTryAgainText = {!! json_encode(__('users.please_try_again')) !!};

searchInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 2) {
        resultsContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>${searchForUsersText}</h3>
                <p style="color: var(--text-muted);">${typeAtLeast2Text}</p>
            </div>
        `;
        return;
    }

    resultsContainer.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>' + searchingText + '</p></div>';

    searchTimeout = setTimeout(() => {
        fetch(`/api/search-users?q=${encodeURIComponent(query)}`, {
            credentials: 'include',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.users && data.users.length > 0) {
                resultsContainer.innerHTML = data.users.map(user => {
                    const vb = user.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".85em" height=".85em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
                    return `<div class="user-card">
                        <a href="/users/${user.username}" class="user-avatar">
                            <img src="${escapeHtml(user.avatar_url)}" alt="${escapeHtml(user.username)}">
                        </a>
                        <div class="user-info">
                            <a href="/users/${user.username}" style="display:inline-flex;align-items:center;gap:.2em;">
                                <div class="user-name">${escapeHtml(user.name)}</div>${vb}
                            </a>
                            <div class="user-meta"><span dir="ltr" style="display: inline-block;">@${escapeHtml(user.username)}</span></div>
                        </div>
                    </div>`;
                }).join('');
            } else {
                resultsContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>${noUsersFoundText}</h3>
                        <p style="color: var(--text-muted);">${tryDifferentSearchText}</p>
                    </div>
                `;
            }
        })
        .catch(() => {
            resultsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>${searchFailedText}</h3>
                    <p style="color: var(--text-muted);">${pleaseTryAgainText}</p>
                </div>
            `;
        });
    }, 300);
});

searchInput.focus();
</script>
@endsection
