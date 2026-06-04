@php
    $user = auth()->user();

    $rsFollowing = ($followedUsers ?? collect())->map(function ($f) {
        $u = $f->followed ?? $f;
        return ($u && $u->id) ? $u : null;
    })->filter()->values();

    $rsOnlineCount = $rsFollowing->where('is_online', true)->count();

    $rsTopHashtags = ($topHashtags ?? collect())->take(4);
    if ($rsTopHashtags->isEmpty()) {
        try { $rsTopHashtags = \App\Models\Hashtag::popular(4)->get(); } catch (\Throwable $e) { $rsTopHashtags = collect(); }
    }

    try {
        $rsTopCommunities = \App\Models\SocialGroup::withCount('members')
            ->orderBy('members_count', 'desc')
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();
    } catch (\Throwable $e) { $rsTopCommunities = collect(); }

    try {
        $followingIds = $user->following()->pluck('followed_id');
        $rsSuggested = \App\Models\User::query()
            ->where('id', '!=', $user->id)
            ->whereNotIn('id', $followingIds)
            ->withCount('followers')
            ->with('profile')
            ->orderBy('followers_count', 'desc')
            ->orderBy('id', 'desc')
            ->limit(2)
            ->get();
    } catch (\Throwable $e) { $rsSuggested = collect(); }
@endphp

<aside class="feed-sidebar-right" id="feedSidebarRight" aria-label="Discover">

    {{-- Collapse toggle only — no "Discover" title --}}
    <div class="fsr-header">
        <button type="button" class="fsr-toggle" id="fsrToggle" aria-expanded="true" aria-controls="feedSidebarRight" title="{{ __('messages.collapse') }}">
            <i class="fas fa-chevron-right fsr-toggle-icon"></i>
        </button>
    </div>

    <div class="fsr-stack">

        {{-- Pulse — daily community prompt --}}
        @include('partials.pulse-card', ['variant' => 'sidebar'])

        {{-- Memory Prompt --}}
        @include('partials.memory-prompt-card', ['variant' => 'sidebar'])

        {{-- Following / Online --}}
        <section class="fsr-card" data-fsr-section="following">
            <header class="fsr-card-head">
                <h3 class="fsr-card-title">{{ __('messages.following') }}</h3>
                <span class="fsr-card-badge {{ $rsOnlineCount > 0 ? 'is-active' : '' }}" title="{{ __('chat.online') }}">
                    <span class="fsr-pulse"></span>
                    {{ $rsOnlineCount }} {{ __('chat.online') }}
                </span>
            </header>
            @if($rsFollowing->isNotEmpty())
                <div class="fsr-following" id="fsr-following-list">
                    @foreach($rsFollowing as $u)
                    <a href="{{ route('users.show', $u->username) }}" class="fsr-follow-item {{ $u->is_online ? 'is-online' : '' }}" data-user-id="{{ $u->id }}" data-username="{{ $u->username }}" title="{{ $u->username }}">
                        <span class="fsr-follow-avatar-wrap">
                            <span class="fsr-follow-avatar">
                                <img src="{{ $u->avatar_url }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='/images/default-avatar.svg'">
                            </span>
                            <span class="fsr-online-dot {{ $u->is_online ? 'online' : '' }}" data-user-id="{{ $u->id }}"></span>
                        </span>
                        <span class="fsr-follow-name">{{ $u->username }}</span>
                    </a>
                    @endforeach
                </div>
            @else
                <div class="fsr-empty">
                    <p>{{ __('messages.not_following_anyone') }}</p>
                    <a href="{{ route('explore') }}" class="fsr-empty-cta">{{ __('messages.find_people') ?? 'Find people' }}</a>
                </div>
            @endif
        </section>

        {{-- Who to follow --}}
        <section class="fsr-card" data-fsr-section="suggest">
            <header class="fsr-card-head">
                <h3 class="fsr-card-title">{{ __('messages.who_to_follow') }}</h3>
                <a href="{{ route('explore') }}" class="fsr-card-more">{{ __('messages.view_all') }}</a>
            </header>
            @if($rsSuggested->isNotEmpty())
                <ul class="fsr-suggest-list">
                    @foreach($rsSuggested as $sug)
                    <li class="fsr-suggest-item" data-user-id="{{ $sug->id }}" data-followers-count="{{ $sug->followers_count ?? 0 }}">
                        <a href="{{ route('users.show', $sug->username) }}" class="fsr-suggest-link">
                            <span class="fsr-suggest-avatar">
                                <img src="{{ $sug->avatar_url }}" alt="{{ $sug->username }}" loading="lazy" onerror="this.onerror=null;this.src='/images/default-avatar.svg'">
                            </span>
                            <span class="fsr-suggest-info">
                                <span class="fsr-suggest-name">{{ optional($sug->profile)->full_name ?: $sug->name }}</span>
                                <span class="fsr-suggest-handle">{{ '@' . $sug->username }}</span>
                            </span>
                        </a>
                        <button type="button"
                                class="fsr-follow-btn"
                                data-username="{{ $sug->username }}"
                                aria-label="{{ __('messages.follow') }} {{ '@' . $sug->username }}">
                            {{ __('messages.follow') }}
                        </button>
                    </li>
                    @endforeach
                </ul>
            @else
                <div class="fsr-empty">
                    <p>{{ __('messages.no_suggestions') ?? "No new suggestions right now." }}</p>
                </div>
            @endif
        </section>

        {{-- Top Communities --}}
        <section class="fsr-card" data-fsr-section="communities">
            <header class="fsr-card-head">
                <h3 class="fsr-card-title">{{ __('messages.top_communities') }}</h3>
                <a href="{{ route('communities.index') }}" class="fsr-card-more">{{ __('messages.view_all') }}</a>
            </header>
            @if($rsTopCommunities->isNotEmpty())
                <ul class="fsr-list fsr-list-community" id="fsr-communities-list">
                    @foreach($rsTopCommunities as $group)
                    <li data-community-slug="{{ $group->slug }}" data-members-count="{{ $group->members_count }}">
                        <a href="{{ route('communities.show', $group->slug) }}" class="fsr-row fsr-row-community">
                            <span class="fsr-row-icon">
                                @if(!empty($group->avatar_url))
                                    <img src="{{ $group->avatar_url }}" alt="" loading="lazy">
                                @else
                                    <i class="fas fa-users"></i>
                                @endif
                            </span>
                            <span class="fsr-row-body">
                                <span class="fsr-row-main">{{ $group->name }}</span>
                                <span class="fsr-row-meta" data-fsr-members-count>
                                    <span class="fsr-members-num">{{ number_format($group->members_count) }}</span> {{ __('messages.members') }}
                                </span>
                            </span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            @else
                <div class="fsr-empty">
                    <p>{{ __('messages.no_communities_yet') ?? 'No communities yet.' }}</p>
                    <a href="{{ route('communities.index') }}" class="fsr-empty-cta">{{ __('messages.browse_communities') ?? 'Browse' }}</a>
                </div>
            @endif
        </section>

        {{-- Trending Hashtags --}}
        <section class="fsr-card" data-fsr-section="trending">
            <header class="fsr-card-head">
                <h3 class="fsr-card-title">{{ __('hashtags.trending') }}</h3>
                <a href="{{ route('hashtags.index') }}" class="fsr-card-more">{{ __('messages.view_all') }}</a>
            </header>
            @if($rsTopHashtags->isNotEmpty())
                <ul class="fsr-list fsr-list-trend" id="fsr-trending-list">
                    @foreach($rsTopHashtags as $i => $hashtag)
                    <li data-hashtag-slug="{{ $hashtag->slug }}" data-usage-count="{{ $hashtag->usage_count }}">
                        <a href="{{ route('hashtags.show', $hashtag->slug) }}" class="fsr-row fsr-row-trend" data-rank="{{ $i + 1 }}">
                            <span class="fsr-trend-rank tier-{{ min($i + 1, 4) }}">{{ $i === 0 ? '🔥' : $i + 1 }}</span>
                            <span class="fsr-row-body">
                                <span class="fsr-row-main">#{{ $hashtag->name }}</span>
                                <span class="fsr-row-meta">
                                    <span class="fsr-usage-num">{{ number_format($hashtag->usage_count) }}</span> {{ __('messages.posts') }}
                                </span>
                            </span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            @else
                <div class="fsr-empty">
                    <p>{{ __('messages.no_trends_yet') ?? 'No trends yet.' }}</p>
                </div>
            @endif
        </section>

        {{-- Global Chat Preview --}}
        <section class="fsr-card fsr-chat-card" data-fsr-section="chat">
            <header class="fsr-card-head">
                <h3 class="fsr-card-title">
                    {{ __('navigation.global_chat') }}
                    <span class="fsr-live-dot" aria-hidden="true"></span>
                </h3>
            </header>
            <div class="fsr-chat-list" id="global-chat-preview-list">
                <div class="fsr-empty small">
                    <p>{{ __('messages.no_messages') }}</p>
                </div>
            </div>
            <div class="fsr-chat-footer">
                <a href="{{ route('global-chat.index') }}" class="fsr-chat-open-btn">
                    {{ __('messages.open') }} {{ __('navigation.global_chat') }}
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </section>
    </div>
</aside>

<script>
(function () {
    const sidebar = document.getElementById('feedSidebarRight');
    const toggle = document.getElementById('fsrToggle');
    if (!sidebar || !toggle) return;

    const KEY = 'nexus_feed_sidebar_right_collapsed';
    const apply = (collapsed) => {
        sidebar.classList.toggle('collapsed', collapsed);
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    apply(localStorage.getItem(KEY) === '1');

    toggle.addEventListener('click', () => {
        const next = !sidebar.classList.contains('collapsed');
        apply(next);
        localStorage.setItem(KEY, next ? '1' : '0');
    });

    sidebar.querySelectorAll('.fsr-rail-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (sidebar.classList.contains('collapsed')) {
                apply(false);
                localStorage.setItem(KEY, '0');
            }
        });
    });

    // ---------- Online indicators ----------
    const onlineBadge = sidebar.querySelector('.fsr-card-badge');

    function updateOnlineBadge() {
        const total = sidebar.querySelectorAll('.fsr-online-dot.online').length;
        if (onlineBadge) {
            // Remove all existing text nodes, then append a fresh one after the pulse span.
            // The Blade template renders whitespace text nodes before AND after the pulse span,
            // so we must clear them all to avoid leaving stale label text in place.
            Array.from(onlineBadge.childNodes)
                .filter(n => n.nodeType === Node.TEXT_NODE)
                .forEach(n => n.remove());
            const label = ' ' + total + ' ' + (window.chatTranslations?.online || 'online');
            onlineBadge.appendChild(document.createTextNode(label));
            onlineBadge.classList.toggle('is-active', total > 0);
        }
    }

    // Stable comparator for the Following list: online users always come first,
    // then ties broken by username for deterministic order across reorders.
    function sortFollowingList() {
        const list = document.getElementById('fsr-following-list');
        if (!list) return;
        const items = Array.from(list.querySelectorAll('.fsr-follow-item'));
        if (items.length < 2) return;
        items.sort((a, b) => {
            const aOn = a.classList.contains('is-online') ? 0 : 1;
            const bOn = b.classList.contains('is-online') ? 0 : 1;
            if (aOn !== bOn) return aOn - bOn;
            const an = (a.getAttribute('data-username') || '').toLowerCase();
            const bn = (b.getAttribute('data-username') || '').toLowerCase();
            return an.localeCompare(bn);
        });
        // Re-append in the new order. appendChild on an existing node moves it,
        // so we don't need to remove first.
        items.forEach(el => list.appendChild(el));
    }

    const setStatus = (userId, online) => {
        sidebar.querySelectorAll(`.fsr-online-dot[data-user-id="${userId}"]`).forEach(el => {
            el.classList.toggle('online', !!online);
        });
        // Mirror the class on the parent <a> so CSS / sort can target it.
        sidebar.querySelectorAll(`.fsr-follow-item[data-user-id="${userId}"]`).forEach(el => {
            el.classList.toggle('is-online', !!online);
        });
        updateOnlineBadge();
        sortFollowingList();
    };

    // Run an initial sort so server-rendered list also bubbles online users up.
    sortFollowingList();

    function fetchInitialOnlineState() {
        const ids = Array.from(sidebar.querySelectorAll('.fsr-follow-item[data-user-id]'))
            .map(el => el.getAttribute('data-user-id'))
            .filter(Boolean);
        if (!ids.length) return;
        fetch('/users/online-status/bulk?ids[]=' + ids.join('&ids[]='), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data || !data.statuses) return;
            Object.entries(data.statuses).forEach(([uid, isOnline]) => setStatus(uid, isOnline));
            updateOnlineBadge();
        })
        .catch(() => {});
    }

    function bindPresence() {
        if (!window.NexusSocket || !window.NexusSocket.socket) {
            return setTimeout(bindPresence, 400);
        }
        const sock = window.NexusSocket.socket;
        sock.on('user:online',  d => d && d.userId && setStatus(d.userId, true));
        sock.on('user:offline', d => d && d.userId && setStatus(d.userId, false));
        // Bulk sync fires on socket connect; we may have missed it, so we also do
        // the HTTP fallback above. But still listen in case the server re-emits.
        sock.on('users:online', d => {
            const onlineIds = new Set((d && d.userIds ? d.userIds : []).map(id => String(id)));
            sidebar.querySelectorAll('.fsr-online-dot[data-user-id]').forEach(el => {
                const uid = el.getAttribute('data-user-id');
                if (!uid) return;
                el.classList.toggle('online', onlineIds.has(String(uid)));
            });
            updateOnlineBadge();
        });
    }

    fetchInitialOnlineState();
    bindPresence();

    // ---------- Realtime Following list ----------
    // Wrap fetch to detect /users/{username}/follow POST responses. Add or remove
    // the user from #fsr-following-list without a page reload.
    const FOLLOW_URL_RE = /\/users\/([^\/?#]+)\/follow(?:[?#].*)?$/;
    const origFetch = window.fetch.bind(window);
    window.fetch = function (input, init) {
        const promise = origFetch(input, init);
        try {
            const url = typeof input === 'string' ? input : (input && input.url) || '';
            const method = ((init && init.method) || (typeof input === 'object' && input.method) || 'GET').toUpperCase();
            const m = FOLLOW_URL_RE.exec(url);
            if (m && method === 'POST') {
                const username = decodeURIComponent(m[1]);
                promise.then(res => res && res.clone ? res.clone().json().catch(() => null) : null)
                    .then(data => {
                        if (!data || data.success !== true) return;
                        handleFollowChange(username, data);
                    });
            }
        } catch (e) { /* swallow */ }
        return promise;
    };

    // ---------- Inline "Follow" buttons in Who-to-follow ----------
    sidebar.addEventListener('click', function (e) {
        const btn = e.target.closest('.fsr-follow-btn');
        if (!btn || btn.disabled) return;
        e.preventDefault();
        const username = btn.getAttribute('data-username');
        if (!username) return;

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';

        btn.disabled = true;
        btn.classList.add('is-loading');

        fetch(`/users/${encodeURIComponent(username)}/follow`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            btn.classList.remove('is-loading');
            if (!data || data.success !== true) {
                btn.disabled = false;
                return;
            }
            // Remove the suggestion row on successful follow so the slot frees up.
            // The fetch-wrapper above also handles list refresh on its own.
            if (data.is_following) {
                if (window.NexusSoul) window.NexusSoul.feedback.follow();
                const item = btn.closest('.fsr-suggest-item');
                if (item) {
                    item.classList.add('is-followed');
                    setTimeout(() => item.remove(), 280);
                }
            } else {
                btn.disabled = false;
            }
        })
        .catch(() => {
            btn.classList.remove('is-loading');
            btn.disabled = false;
        });
    });

    function handleFollowChange(username, data) {
        const list = document.getElementById('fsr-following-list');
        const userId = data.user_id;
        if (!userId) return;

        // Find the section that wraps the Following grid so we can flip to "empty" if needed.
        const followingCard = list ? list.closest('.fsr-card') : null;

        if (data.is_following) {
            // ADD if not already there
            if (list) {
                if (list.querySelector(`.fsr-follow-item[data-user-id="${userId}"]`)) return;
                const item = document.createElement('a');
                item.href = '/users/' + encodeURIComponent(username);
                item.className = 'fsr-follow-item';
                item.setAttribute('data-user-id', String(userId));
                item.setAttribute('data-username', username);
                item.setAttribute('title', username);
                const avatar = (data.user && data.user.avatar_url) ? data.user.avatar_url : '/images/default-avatar.svg';
                item.innerHTML =
                    '<span class="fsr-follow-avatar-wrap">' +
                        '<span class="fsr-follow-avatar">' +
                            '<img src="' + avatar + '" alt="" loading="lazy" onerror="this.onerror=null;this.src=\'/images/default-avatar.svg\'">' +
                        '</span>' +
                        '<span class="fsr-online-dot" data-user-id="' + userId + '"></span>' +
                    '</span>' +
                    '<span class="fsr-follow-name">' + username + '</span>';
                list.appendChild(item);
                // Sort and sync online state for the new entry
                sortFollowingList();
                fetch('/users/online-status/bulk?ids[]=' + userId, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(r => r.ok ? r.json() : null)
                    .then(d => { if (d && d.statuses) setStatus(userId, !!d.statuses[String(userId)]); })
                    .catch(() => {});
            } else if (followingCard) {
                // Card showed the empty-state. Replace it with a fresh grid.
                const emptyState = followingCard.querySelector('.fsr-empty');
                if (emptyState) emptyState.remove();
                const grid = document.createElement('div');
                grid.className = 'fsr-following';
                grid.id = 'fsr-following-list';
                followingCard.appendChild(grid);
                handleFollowChange(username, data);
            }
        } else {
            // REMOVE
            if (!list) return;
            const existing = list.querySelector(`.fsr-follow-item[data-user-id="${userId}"]`);
            if (existing) existing.remove();
        }
    }

    // ============================================================
    // ============   Who to follow — auto refill   ===============
    // ============================================================
    const SUGGEST_LIMIT = 2;
    const recentlyShownUserIds = new Set();
    Array.from(sidebar.querySelectorAll('.fsr-suggest-item[data-user-id]'))
        .forEach(li => recentlyShownUserIds.add(li.getAttribute('data-user-id')));

    function suggestionCardEl() {
        return sidebar.querySelector('[data-fsr-section="suggest"]');
    }

    function buildSuggestItemHTML(user) {
        const display = (user.name || user.username || '').replace(/</g, '&lt;');
        const handle = '@' + (user.username || '').replace(/</g, '&lt;');
        const followLabel = (window.chatTranslations?.follow) || 'Follow';
        return ''
            + '<a href="/users/' + encodeURIComponent(user.username) + '" class="fsr-suggest-link">'
                + '<span class="fsr-suggest-avatar">'
                    + '<img src="' + (user.avatar_url || '/images/default-avatar.svg') + '" alt="' + (user.username || '') + '" loading="lazy" onerror="this.onerror=null;this.src=\'/images/default-avatar.svg\'">'
                + '</span>'
                + '<span class="fsr-suggest-info">'
                    + '<span class="fsr-suggest-name">' + display + '</span>'
                    + '<span class="fsr-suggest-handle">' + handle + '</span>'
                + '</span>'
            + '</a>'
            + '<button type="button" class="fsr-follow-btn" data-username="' + (user.username || '') + '" aria-label="' + followLabel + ' ' + handle + '">'
                + '<i class="fas fa-plus fsr-fb-icon"></i>'
                + '<span class="fsr-fb-label">' + followLabel + '</span>'
            + '</button>';
    }

    function ensureSuggestList() {
        const card = suggestionCardEl();
        if (!card) return null;
        let list = card.querySelector('.fsr-suggest-list');
        if (!list) {
            // Empty state → swap to a real list so we can prepend cards into it
            const empty = card.querySelector('.fsr-empty');
            if (empty) empty.remove();
            list = document.createElement('ul');
            list.className = 'fsr-suggest-list';
            card.appendChild(list);
        }
        return list;
    }

    function insertSuggestionSorted(list, user) {
        const li = document.createElement('li');
        li.className = 'fsr-suggest-item is-entering';
        li.setAttribute('data-user-id', String(user.id));
        li.setAttribute('data-followers-count', String(user.followers_count || 0));
        li.innerHTML = buildSuggestItemHTML(user);
        const count = user.followers_count || 0;
        let inserted = false;
        for (const existing of Array.from(list.children)) {
            const ec = parseInt(existing.getAttribute('data-followers-count') || '0', 10);
            if (count > ec) {
                list.insertBefore(li, existing);
                inserted = true;
                break;
            }
        }
        if (!inserted) list.appendChild(li);
        requestAnimationFrame(() => {
            requestAnimationFrame(() => li.classList.remove('is-entering'));
        });
        recentlyShownUserIds.add(String(user.id));
    }

    function refillSuggestions() {
        const list = sidebar.querySelector('.fsr-suggest-list');
        const current = list ? list.querySelectorAll('.fsr-suggest-item').length : 0;
        const need = SUGGEST_LIMIT - current;
        if (need <= 0) return;
        const excludeIds = Array.from(recentlyShownUserIds).filter(Boolean);
        const params = new URLSearchParams();
        params.set('limit', String(need));
        excludeIds.forEach(id => params.append('exclude[]', id));
        fetch('/api/sidebar/suggestions?' + params.toString(), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data || !data.success || !Array.isArray(data.data)) return;
            if (data.data.length === 0) return;
            const targetList = ensureSuggestList();
            if (!targetList) return;
            data.data.forEach(u => insertSuggestionSorted(targetList, u));
        })
        .catch(() => {});
    }

    // ============================================================
    // ===========   Top Communities — re-sort + refetch   ========
    // ============================================================
    const COMMUNITIES_LIMIT = 3;

    function sortCommunitiesList() {
        const list = document.getElementById('fsr-communities-list');
        if (!list) return;
        const items = Array.from(list.children);
        if (items.length < 2) return;
        items.sort((a, b) => {
            const ac = parseInt(a.getAttribute('data-members-count') || '0', 10);
            const bc = parseInt(b.getAttribute('data-members-count') || '0', 10);
            return bc - ac;
        });
        items.forEach(el => list.appendChild(el));
    }

    function buildCommunityItemHTML(community) {
        const name = (community.name || '').replace(/</g, '&lt;');
        const slug = community.slug || '';
        const count = community.members_count || 0;
        const membersLabel = (window.chatTranslations?.members) || 'members';
        const iconHTML = community.avatar_url
            ? '<img src="' + community.avatar_url + '" alt="" loading="lazy">'
            : '<i class="fas fa-users"></i>';
        return ''
            + '<a href="/communities/' + encodeURIComponent(slug) + '" class="fsr-row fsr-row-community">'
                + '<span class="fsr-row-icon">' + iconHTML + '</span>'
                + '<span class="fsr-row-body">'
                    + '<span class="fsr-row-main">' + name + '</span>'
                    + '<span class="fsr-row-meta" data-fsr-members-count>'
                        + '<i class="fas fa-user"></i> '
                        + '<span class="fsr-members-num">' + Number(count).toLocaleString() + '</span> ' + membersLabel
                    + '</span>'
                + '</span>'
                + '<span class="fsr-row-cta" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>'
            + '</a>';
    }

    function refreshTopCommunities() {
        fetch('/api/sidebar/top-communities?limit=' + COMMUNITIES_LIMIT, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data || !data.success || !Array.isArray(data.data)) return;
            const card = sidebar.querySelector('[data-fsr-section="communities"]');
            if (!card) return;
            let list = card.querySelector('#fsr-communities-list');
            if (data.data.length === 0) {
                if (list) list.remove();
                return;
            }
            if (!list) {
                const empty = card.querySelector('.fsr-empty');
                if (empty) empty.remove();
                list = document.createElement('ul');
                list.id = 'fsr-communities-list';
                list.className = 'fsr-list fsr-list-community';
                card.appendChild(list);
            }
            // Reconcile by slug — keep existing nodes if still present, replace text.
            const wantedSlugs = data.data.map(c => c.slug);
            // Remove items that fell off the top
            Array.from(list.children).forEach(li => {
                if (wantedSlugs.indexOf(li.getAttribute('data-community-slug')) === -1) {
                    li.remove();
                }
            });
            // Add/update each wanted community
            data.data.forEach(c => {
                let li = list.querySelector('li[data-community-slug="' + (c.slug || '').replace(/"/g, '\\"') + '"]');
                if (!li) {
                    li = document.createElement('li');
                    li.setAttribute('data-community-slug', c.slug || '');
                    list.appendChild(li);
                }
                li.setAttribute('data-members-count', String(c.members_count || 0));
                li.innerHTML = buildCommunityItemHTML(c);
            });
            sortCommunitiesList();
        })
        .catch(() => {});
    }

    // Intercept the existing community:member_count socket event by polling for
    // mismatches. The socket-manager already updates the count text, but we
    // also need to update data-members-count and re-sort.
    function watchCommunityCountChanges() {
        if (!window.NexusSocket || !window.NexusSocket.socket) {
            return setTimeout(watchCommunityCountChanges, 400);
        }
        window.NexusSocket.socket.on('community:member_count', (d) => {
            if (!d || !d.slug || d.members_count === undefined) return;
            const list = document.getElementById('fsr-communities-list');
            const item = list ? list.querySelector('li[data-community-slug="' + d.slug.replace(/"/g, '\\"') + '"]') : null;
            if (item) {
                item.setAttribute('data-members-count', String(d.members_count));
                const num = item.querySelector('.fsr-members-num');
                if (num) num.textContent = Number(d.members_count).toLocaleString();
                sortCommunitiesList();
            } else {
                // A community we don't display changed — its growth might've put
                // it into the top N. Refetch to check.
                refreshTopCommunities();
            }
        });
    }
    watchCommunityCountChanges();

    // ============================================================
    // =============   Trending Hashtags — poll   =================
    // ============================================================
    const TRENDING_LIMIT = 4;
    const TRENDING_POLL_MS = 60000;

    function buildTrendingItemHTML(tag, rank) {
        const tier = Math.min(rank + 1, 4);
        const rankHTML = rank === 0
            ? '<i class="fas fa-fire"></i>'
            : String(rank + 1);
        const postsLabel = (window.chatTranslations?.posts) || 'posts';
        return ''
            + '<a href="/hashtags/' + encodeURIComponent(tag.slug || '') + '" class="fsr-row fsr-row-trend" data-rank="' + (rank + 1) + '">'
                + '<span class="fsr-trend-rank tier-' + tier + '">' + rankHTML + '</span>'
                + '<span class="fsr-row-body">'
                    + '<span class="fsr-row-main">#' + (tag.name || '').replace(/</g, '&lt;') + '</span>'
                    + '<span class="fsr-row-meta">'
                        + '<i class="fas fa-arrow-trend-up"></i> '
                        + '<span class="fsr-usage-num">' + Number(tag.usage_count || 0).toLocaleString() + '</span> ' + postsLabel
                    + '</span>'
                + '</span>'
            + '</a>';
    }

    function refreshTrendingHashtags() {
        fetch('/api/sidebar/trending-hashtags?limit=' + TRENDING_LIMIT, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (!data || !data.success || !Array.isArray(data.data)) return;
            const card = sidebar.querySelector('[data-fsr-section="trending"]');
            if (!card) return;
            let list = card.querySelector('#fsr-trending-list');
            if (data.data.length === 0) {
                if (list) list.remove();
                return;
            }
            if (!list) {
                const empty = card.querySelector('.fsr-empty');
                if (empty) empty.remove();
                list = document.createElement('ul');
                list.id = 'fsr-trending-list';
                list.className = 'fsr-list fsr-list-trend';
                card.appendChild(list);
            }
            // Check if order/content differs before rebuilding
            const currentSlugs = Array.from(list.querySelectorAll('li[data-hashtag-slug]'))
                .map(li => li.getAttribute('data-hashtag-slug'));
            const wantedSlugs = data.data.map(t => t.slug);
            const same = currentSlugs.length === wantedSlugs.length &&
                currentSlugs.every((s, i) => s === wantedSlugs[i]);
            if (same) {
                // Just update counts in-place
                data.data.forEach((t) => {
                    const li = list.querySelector('li[data-hashtag-slug="' + (t.slug || '').replace(/"/g, '\\"') + '"]');
                    if (!li) return;
                    li.setAttribute('data-usage-count', String(t.usage_count || 0));
                    const num = li.querySelector('.fsr-usage-num');
                    if (num) num.textContent = Number(t.usage_count || 0).toLocaleString();
                });
                return;
            }
            // Rebuild fully — order changed
            list.innerHTML = '';
            data.data.forEach((t, i) => {
                const li = document.createElement('li');
                li.setAttribute('data-hashtag-slug', t.slug || '');
                li.setAttribute('data-usage-count', String(t.usage_count || 0));
                li.innerHTML = buildTrendingItemHTML(t, i);
                list.appendChild(li);
            });
        })
        .catch(() => {});
    }

    // Poll trending while the tab is visible; pause when hidden to save bandwidth.
    let trendingTimer = null;
    function startTrendingPoll() {
        stopTrendingPoll();
        trendingTimer = setInterval(refreshTrendingHashtags, TRENDING_POLL_MS);
    }
    function stopTrendingPoll() {
        if (trendingTimer) { clearInterval(trendingTimer); trendingTimer = null; }
    }
    if (typeof document.hidden !== 'undefined') {
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopTrendingPoll();
            else { refreshTrendingHashtags(); startTrendingPoll(); }
        });
    }
    startTrendingPoll();

    // Refresh trending when a new post lands — it likely changed counts.
    function watchPostsForTrending() {
        if (!window.NexusSocket || !window.NexusSocket.socket) {
            return setTimeout(watchPostsForTrending, 400);
        }
        // Debounce: a burst of posts shouldn't trigger N fetches.
        let pending = false;
        window.NexusSocket.socket.on('post:new', () => {
            if (pending) return;
            pending = true;
            setTimeout(() => { pending = false; refreshTrendingHashtags(); }, 1500);
        });
    }
    watchPostsForTrending();

    // ============================================================
    // ====   Hook into the follow click to also refill suggest   ==
    // ============================================================
    // The earlier click handler removes the followed item after a brief
    // animation; once it's gone, we fetch one fresh suggestion. Tap into
    // the same handler by listening for transitionend / animationend on the
    // dying suggestion row, then refilling once.
    sidebar.addEventListener('animationend', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('fsr-suggest-item') && e.target.classList.contains('is-followed')) {
            refillSuggestions();
        }
    });
    // Fallback: also refill on a short timer after any successful follow.
    // animationend may not fire if no CSS animation is defined for is-followed.
    sidebar.addEventListener('click', function (e) {
        const btn = e.target.closest('.fsr-follow-btn');
        if (!btn) return;
        // 320ms = removal delay (280) + small buffer
        setTimeout(refillSuggestions, 350);
    });
})();
</script>
