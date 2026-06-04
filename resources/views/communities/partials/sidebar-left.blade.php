{{--
    Shared left sidebar partial for all community sub-pages
    (about, members, settings, show).
    Expects: $group, $userMember
--}}
<aside class="ch-sidebar-left" id="chSidebarLeft">
    <div class="csl-inner">

        <button type="button" class="csl-toggle" id="cslToggle"
                aria-expanded="true" aria-label="{{ __('messages.collapse') }}">
            <i class="fas fa-chevron-left csl-toggle-icon"></i>
        </button>

        {{-- Community identity --}}
        <div class="csl-identity">
            <div class="csl-comm-avatar">
                <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}">
            </div>
            <div class="csl-comm-info">
                <div class="csl-comm-name">{{ $group->name }}</div>
                <div class="csl-comm-meta">
                    <i class="fas fa-{{ $group->privacy_level === 'public' ? 'globe' : 'lock' }}" style="font-size:10px;margin-right:3px;"></i>
                    {{ ucfirst($group->privacy_level) }}
                </div>
            </div>
        </div>

        {{-- Stats grid --}}
        <div class="csl-stats">
            <div class="csl-stat">
                <strong data-community-members-count="{{ $group->slug }}">{{ number_format($group->members_count) }}</strong>
                <span>{{ __('messages.members_label') }}</span>
            </div>
            <div class="csl-stat">
                <strong>{{ number_format($group->posts()->count()) }}</strong>
                <span>{{ __('messages.posts') }}</span>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="csl-nav">
            <a href="{{ route('communities.show', $group->slug) }}"
               class="csl-link {{ request()->routeIs('communities.show') ? 'active' : '' }}"
               data-label="{{ __('messages.discussion') }}">
                <i class="fas fa-comment-alt"></i>
                <span>{{ __('messages.discussion') }}</span>
            </a>
            <a href="{{ route('communities.about', $group->slug) }}"
               class="csl-link {{ request()->routeIs('communities.about') ? 'active' : '' }}"
               data-label="{{ __('messages.about') }}">
                <i class="fas fa-info-circle"></i>
                <span>{{ __('messages.about') }}</span>
            </a>
            <a href="{{ route('communities.members', $group->slug) }}"
               class="csl-link {{ request()->routeIs('communities.members') ? 'active' : '' }}"
               data-label="{{ __('messages.group_members') }}">
                <i class="fas fa-users"></i>
                <span>{{ __('messages.group_members') }}</span>
            </a>
            @if($userMember && $userMember->status === 'approved')
            <a href="{{ route('communities.settings', $group->slug) }}"
               class="csl-link {{ request()->routeIs('communities.settings') ? 'active' : '' }}"
               data-label="{{ __('messages.settings') }}">
                <i class="fas fa-sliders-h"></i>
                <span>{{ __('messages.settings') }}</span>
            </a>
            @endif
            @if($group->isAdmin(auth()->user()))
            <a href="{{ route('communities.admin.index', $group->slug) }}"
               class="csl-link csl-link--admin"
               data-label="{{ __('messages.admin_tools') }}">
                <i class="fas fa-shield-alt"></i>
                <span>{{ __('messages.admin_tools') }}</span>
            </a>
            @endif
        </nav>

        {{-- Rules --}}
        @if($group->rules->count() > 0)
        <div class="csl-section">
            <div class="csl-section-label">{{ __('messages.rules') }}</div>
            <div class="csl-rules">
                @foreach($group->rules->take(5) as $i => $rule)
                <div class="csl-rule">
                    <span class="csl-rule-num">{{ $i + 1 }}</span>
                    <span class="csl-rule-text">{{ $rule->title }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</aside>

<script>
(function () {
    const sidebar = document.getElementById('chSidebarLeft');
    const toggle  = document.getElementById('cslToggle');
    if (!sidebar || !toggle) return;
    const KEY = 'nexus_ch_sidebar_left_collapsed';
    const apply = (c) => {
        sidebar.classList.toggle('collapsed', c);
        toggle.setAttribute('aria-expanded', c ? 'false' : 'true');
    };
    apply(localStorage.getItem(KEY) === '1');
    toggle.addEventListener('click', () => {
        const next = !sidebar.classList.contains('collapsed');
        apply(next);
        localStorage.setItem(KEY, next ? '1' : '0');
    });
})();
</script>
