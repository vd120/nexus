@extends('layouts.app')

@section('title', __('users.explore_users'))

@section('content')
<div class="explore-page"
     data-search-error="{{ __('users.search_error') }}"
     data-searching="{{ __('users.searching') }}">
    <div class="page-header">
        <h1>{{ __('users.explore_users') }}</h1>
        <a href="{{ route('home') }}" class="back-link">
            <i class="fas fa-arrow-left"></i>
            <span>{{ __('users.back_to_feed') }}</span>
        </a>
    </div>


    <div class="search-section">
        <div class="search-container">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text"
                       id="user-search"
                       class="search-input"
                       placeholder="{{ __('users.search_users_placeholder') }}"
                       autocomplete="off">
                <button type="button" class="search-clear" id="search-clear" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-results" id="search-results" style="display: none;">
                <div class="search-loading" id="search-loading" style="display: none;">
                    @for($i=0;$i<4;$i++)
                    <div class="sk-sidebar-row" style="padding:10px 0;">
                        <div class="sk sk-avatar"></div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                            <div class="sk sk-line" style="width:55%;"></div>
                            <div class="sk sk-line" style="width:35%;"></div>
                        </div>
                    </div>
                    @endfor
                </div>
                <div class="search-empty" id="search-empty" style="display: none;">
                    <i class="fas fa-search"></i>
                    <span>{{ __('users.no_users_found') }}</span>
                </div>
                <div class="search-list" id="search-list"></div>
            </div>
        </div>
    </div>



    @if($users->count() > 0)
        <div class="users-list">
            @foreach($users as $user)
                <div class="user-card">
                    <div class="user-avatar-section">
                        <a href="{{ route('users.show', $user) }}" style="display:flex;flex-shrink:0;"><img src="{{ $user->avatar_url }}" alt="Avatar" class="user-avatar" style="pointer-events:none;"></a>
                    </div>

                    <div class="user-content">
                        <div class="user-header">
                            <h3 class="user-name">
                                <a href="{{ route('users.show', $user) }}" style="display:inline-flex;align-items:center;gap:.2em;">{{ $user->username }}<x-verified-badge :user="$user" size=".85em" /></a>
                                @if($user->is_suspended)
                                    <span class="suspension-badge">
                                        <i class="fas fa-exclamation-triangle"></i> {{ __('users.suspended') }}
                                    </span>
                                @endif
                                @if($user->profile && $user->profile->is_private)
                                    <span class="privacy-badge private">
                                        <i class="fas fa-lock"></i> {{ __('users.private') }}
                                    </span>
                                @endif
                                @if(auth()->user()->isBlocking($user))
                                    <span class="block-indicator blocked-by-you">
                                        <i class="fas fa-ban"></i> {{ __('users.blocked') }}
                                    </span>
                                @elseif($user->isBlocking(auth()->user()))
                                    <span class="block-indicator blocked-you">
                                        <i class="fas fa-user-slash"></i> {{ __('users.blocking_you') }}
                                    </span>
                                @endif
                            </h3>
                        </div>

                        @if($user->profile && $user->profile->bio)
                            <p class="user-bio">{{ $user->profile->bio }}</p>
                        @endif

                        <div class="user-stats">
                            <span class="stat-item"><strong data-user-followers="{{ $user->id }}">{{ $user->followers_count ?? 0 }}</strong> {{ __('users.followers') }}</span>
                            <span class="stat-item"><strong data-user-following="{{ $user->id }}">{{ $user->follows_count ?? 0 }}</strong> {{ __('users.following') }}</span>
                        </div>
                    </div>

                    <div class="user-card-actions">
                        @if(in_array($user->id, $blockedByCurrentUser))
                            <div class="action-buttons">
                                <button type="button" class="btn unblock-btn" data-username="{{ $user->username }}" onclick="exploreToggleBlock(this, '{{ $user->username }}')">{{ __('users.unblock') }}</button>
                            </div>
                        @elseif(in_array($user->id, $blockedCurrentUser))
                            <div class="cannot-interact">
                                <i class="fas fa-ban"></i>
                                <span>{{ __('users.blocked') }}</span>
                            </div>
                        @else
                            @php $isUserFollowing = in_array($user->id, $followingIds); @endphp
                            <div class="action-buttons">
                                <button type="button"
                                        class="btn follow-btn {{ $isUserFollowing ? 'following' : '' }}"
                                        data-username="{{ $user->username }}"
                                        onclick="exploreToggleFollow(this, '{{ $user->username }}')">
                                    {{ $isUserFollowing ? __('users.following') : __('users.follow') }}
                                </button>
                                <button type="button" class="btn block-btn" data-username="{{ $user->username }}" onclick="exploreToggleBlock(this, '{{ $user->username }}')">{{ __('users.block') }}</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($users->hasPages())
            <div style="text-align: center; margin-top: 20px;">
                {{ $users->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>{{ __('users.no_users_to_explore') }}</h3>
            <p>{{ __('users.check_back_later') }}</p>
        </div>
    @endif
</div>

<style>
.explore-page {
    max-width: 600px;
    margin: 0 auto;
    padding: 24px 16px;
}

.page-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.page-header h1 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 700;
    color: var(--twitter-dark);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--twitter-blue);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.back-link i {
    font-size: 12px;
}

/* ===== USER SEARCH STYLES ===== */
.search-section {
    margin-bottom: 24px;
}

.search-container {
    position: relative;
    max-width: 100%;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--twitter-gray);
    font-size: 16px;
    z-index: 2;
}

.search-input {
    width: 100%;
    padding: 14px 50px 14px 48px;
    border: 2px solid var(--border-color);
    border-radius: 24px;
    font-size: 16px;
    background: var(--input-bg);
    color: var(--twitter-dark);
    outline: none;
    font-family: inherit;
    box-shadow: var(--elev-1);
}

.search-input:focus {
    border-color: var(--brand-500);
    box-shadow: 0 0 0 3px var(--primary-glow);
}

.search-clear {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--twitter-gray);
    cursor: pointer;
    padding: 6px;
    border-radius: 50%;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
}

.search-results {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--elev-3);
    max-height: 320px;
    overflow-y: auto;
    z-index: 100;
    backdrop-filter: blur(8px);
}

.search-loading,
.search-empty {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    color: var(--twitter-gray);
    font-size: 14px;
}

.search-loading i {
    animation: spin 1s linear infinite;
}

.search-list {
    max-height: 280px;
    overflow-y: auto;
}

.search-user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color);
}

.search-user-item:last-child {
    border-bottom: none;
}

.search-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}

.search-user-avatar-placeholder {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--twitter-light);
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--twitter-gray);
    font-size: 12px;
    flex-shrink: 0;
}

.search-user-info {
    flex: 1;
    min-width: 0;
}

.search-user-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--twitter-dark);
    display: block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.search-user-bio {
    font-size: 12px;
    color: var(--twitter-gray);
    margin-top: 2px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.users-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.user-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: var(--card-bg);
    border-radius: 16px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.user-avatar-section {
    flex-shrink: 0;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-color);
}

.user-avatar-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--twitter-light);
    border: 2px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--twitter-gray);
    font-size: 18px;
}

.user-content {
    flex: 1;
    min-width: 0; /* Allows text truncation */
}

.user-name {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    max-width: 100%;
    overflow: hidden;
}

.user-name a {
    color: var(--twitter-dark);
    text-decoration: none;
    transition: color 0.2s ease;
    max-width: 200px; /* Limit username width */
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-block;
}

.user-name a:hover {
    color: var(--twitter-blue);
}

.privacy-badge.private {
    background: var(--danger);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    font-weight: 500;
}

.privacy-badge.verified {
    background: var(--brand-500);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    font-weight: 500;
}

.suspension-badge {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-weight: 500;
    text-transform: uppercase;
    box-shadow: 0 1px 3px rgba(255, 107, 107, 0.3);
    flex-shrink: 0;
    animation: pulse 2s infinite;
}

.block-indicator {
    background: var(--twitter-light);
    color: var(--twitter-gray);
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    font-weight: 500;
}

@keyframes pulse {
    0% {
        box-shadow: 0 1px 3px rgba(255, 107, 107, 0.3);
    }
    50% {
        box-shadow: 0 2px 4px rgba(255, 107, 107, 0.5);
    }
    100% {
        box-shadow: 0 1px 3px rgba(255, 107, 107, 0.3);
    }
}

.block-indicator {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-weight: 500;
}

.block-indicator.blocked-by-you {
    background: #dc3545;
    color: white;
}

.block-indicator.blocked-you {
    background: #ffc107;
    color: #212529;
}

.user-bio {
    margin: 4px 0 8px 0;
    font-size: 14px;
    color: var(--twitter-gray);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.user-stats {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: var(--twitter-gray);
}

.stat-item strong {
    color: var(--twitter-dark);
}

.user-card-actions {
    flex-shrink: 0;
    min-width: 120px;
}

.follow-btn,
.unblock-btn,
.block-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.follow-btn:not(.following) {
    background: var(--primary, #1da1f2);
    color: white;
    border: 1px solid var(--primary, #1da1f2);
}

.follow-btn.following {
    background: transparent;
    color: var(--primary, #1da1f2);
    border: 1px solid var(--primary, #1da1f2);
}

.follow-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.follow-btn:active {
    transform: translateY(0);
}

.unblock-btn {
    background: #ffc107;
    color: #212529;
    width: 100%;
}

.block-btn {
    background: #dc3545;
    color: white;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 100%;
}

.action-buttons .follow-btn,
.action-buttons .block-btn {
    width: 100%;
}

.cannot-interact {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--twitter-gray);
    text-align: center;
    padding: 8px;
}

.cannot-interact i {
    font-size: 16px;
    opacity: 0.7;
}

.empty-state {
    text-align: center;
    padding: 48px 16px;
    color: var(--twitter-gray);
}

.empty-icon {
    margin-bottom: 16px;
}

.empty-icon i {
    font-size: 48px;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 8px 0;
    color: var(--twitter-dark);
    font-size: 18px;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

/* Mobile Responsive */
@media (max-width: 480px) {
    .explore-page {
        padding: 12px;
    }

    .page-header {
        margin-bottom: 16px;
        padding-bottom: 12px;
    }

    .page-header h1 {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 700;
        color: var(--twitter-dark);
    }

    .user-card {
        display: grid !important;
        grid-template-columns: auto 1fr !important;
        grid-template-rows: auto auto !important;
        gap: 10px !important;
        padding: 12px !important;
    }

    .user-avatar-section {
        grid-row: 1 / 3 !important;
        grid-column: 1 !important;
    }

    .user-avatar, .user-avatar-placeholder {
        width: 48px !important;
        height: 48px !important;
    }

    .user-content {
        grid-row: 1 !important;
        grid-column: 2 !important;
        min-width: 0 !important;
    }

    .user-card-actions {
        grid-row: 2 !important;
        grid-column: 2 !important;
        min-width: unset !important;
        margin-top: 0 !important;
    }

    .action-buttons {
        flex-direction: row !important;
        gap: 8px !important;
    }

    .action-buttons .follow-btn,
    .action-buttons .block-btn,
    .action-buttons .unblock-btn {
        flex: 1 !important;
        width: auto !important;
        max-width: none !important;
    }

    .user-name {
        font-size: 15px !important;
    }

    .user-name a {
        max-width: none !important;
    }

    .user-bio {
        font-size: 13px !important;
    }

    .user-stats {
        font-size: 12px !important;
    }

    .follow-btn,
    .unblock-btn,
    .block-btn {
        padding: 8px 16px !important;
        font-size: 13px !important;
    }

    .cannot-interact {
        flex-direction: row !important;
        justify-content: center !important;
    }
}
</style>

<script>
function exploreToggleFollow(btn, username) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch(`/users/${username}/follow`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const isFollowing = data.is_following;
            btn.innerHTML = isFollowing ? '{{ __('users.following') }}' : '{{ __('users.follow') }}';
            btn.classList.toggle('following', isFollowing);
            btn.disabled = false;
            
            // Update follower counter if it exists
            const followerStat = document.querySelector(`[data-user-followers="${data.user_id}"]`);
            if (followerStat && data.followers_count !== undefined) {
                followerStat.textContent = data.followers_count;
            }
            
            showToast(data.message, 'success');
        } else {
            showToast(data.message || '{{ __('users.error_following') }}', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function exploreToggleBlock(btn, username) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch(`/users/${username}/block`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const isBlocking = data.blocking;
            btn.innerHTML = isBlocking ? '{{ __('users.unblock') }}' : '{{ __('users.block') }}';
            btn.classList.toggle('danger', isBlocking);
            btn.disabled = false;
            showToast(data.message, 'success');
            // Update UI instead of removing the card
            const card = btn.closest('.user-card');
            
            // Update follower counter
            const followerStat = document.querySelector(`[data-user-followers="${data.user_id}"]`);
            if (followerStat && data.followers_count !== undefined) {
                followerStat.textContent = data.followers_count;
            }

            if (isBlocking) {
                btn.className = 'btn unblock-btn';
                const followBtn = card.querySelector('.follow-btn');
                if (followBtn) followBtn.style.display = 'none';
                
                const nameHeader = card.querySelector('.user-name');
                if (nameHeader && !nameHeader.querySelector('.blocked-by-you')) {
                    nameHeader.insertAdjacentHTML('beforeend', '<span class="block-indicator blocked-by-you"><i class="fas fa-ban"></i> {{ __('users.blocked') }}</span>');
                }
            } else {
                btn.className = 'btn block-btn';
                const followBtn = card.querySelector('.follow-btn');
                if (followBtn) {
                    followBtn.style.display = 'flex';
                    followBtn.innerHTML = '{{ __('users.follow') }}';
                    followBtn.classList.remove('following');
                }
                
                const indicator = card.querySelector('.blocked-by-you');
                if (indicator) indicator.remove();
            }
        } else {
            showToast(data.message || '{{ __('users.error_blocking') }}', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

window.runOnPageLoad( function() {
    const explorePage = document.querySelector('.explore-page');
    const searchErrorText = explorePage?.getAttribute('data-search-error') || 'Search error';
    const searchingText = explorePage?.getAttribute('data-searching') || 'Searching...';

    const searchInput = document.getElementById('user-search');
    const searchResults = document.getElementById('search-results');
    const searchList = document.getElementById('search-list');
    const searchLoading = document.getElementById('search-loading');
    const searchEmpty = document.getElementById('search-empty');
    const searchClear = document.getElementById('search-clear');

    let searchTimeout = null;
    let currentSearchRequest = null;

    // Show/hide clear button based on input
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();

        if (query.length > 0) {
            searchClear.style.display = 'flex';
            searchResults.style.display = 'block';

            // Debounce search
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            searchClear.style.display = 'none';
            searchResults.style.display = 'none';
        }
    });

    // Clear search
    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        searchClear.style.display = 'none';
        searchResults.style.display = 'none';
        searchInput.focus();
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Search function
    function performSearch(query) {
        // Cancel previous request if still pending
        if (currentSearchRequest) {
            currentSearchRequest.abort();
        }

        // Show loading
        searchLoading.style.display = 'flex';
        searchEmpty.style.display = 'none';
        searchList.innerHTML = '';

        // Create new AbortController
        const controller = new AbortController();
        currentSearchRequest = controller;

        // Make API request
        fetch(`/api/search-users?q=${encodeURIComponent(query)}`, {
            credentials: 'include',
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            signal: controller.signal
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            searchLoading.style.display = 'none';

            if (data.success && data.users && data.users.length > 0) {
                displaySearchResults(data.users);
            } else {
                searchEmpty.style.display = 'flex';
            }
        })
        .catch(error => {
            if (error.name === 'AbortError') {
                return; // Request was cancelled
            }

            console.error('Search error:', error);
            searchLoading.style.display = 'none';
            searchEmpty.style.display = 'flex';
            searchEmpty.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>${searchErrorText}</span>`;
        });
    }

    // Display search results
    function displaySearchResults(users) {
        searchList.innerHTML = '';

        users.forEach(user => {
            const userItem = document.createElement('div');
            userItem.className = 'search-user-item';
            userItem.onclick = () => {
                window.location.href = `/users/${encodeURIComponent(user.username)}`;
            };

            const avatarHtml = `<img src="${user.avatar_url}" alt="Avatar" class="search-user-avatar">`;

            const bioHtml = user.profile && user.profile.bio
                ? `<span class="search-user-bio">${user.profile.bio || ''}</span>`
                : '';

            const expVb = user.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".85em" height=".85em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
            userItem.innerHTML = `
                ${avatarHtml}
                <div class="search-user-info">
                    <span class="search-user-name" style="display:inline-flex;align-items:center;gap:.15em;">${escapeHtml(user.username)}${expVb}</span>
                    ${bioHtml}
                </div>
            `;

            searchList.appendChild(userItem);
        });
    }

    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.style.display = 'none';
            searchInput.blur();
        }
    });
});
</script>
@endsection
