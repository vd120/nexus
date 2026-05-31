@php
    $user = auth()->user();
    $bio = optional($user->profile)->bio;
    $fullName = optional($user->profile)->full_name ?: $user->name;

    $routeUser = request()->route('user');
    $onOwnProfile = request()->routeIs('users.show') && $routeUser && (
        is_object($routeUser)
            ? ($routeUser->id ?? null) == $user->id
            : ($routeUser == $user->username || $routeUser == $user->id)
    );

    try {
        $myCommunities = $user->socialGroups()
            ->wherePivot('status', 'approved')
            ->orderBy('social_group_members.created_at', 'desc')
            ->limit(8)
            ->get();
    } catch (\Throwable $e) {
        $myCommunities = collect();
    }
@endphp

<aside class="feed-sidebar-left" id="feedSidebarLeft" aria-label="{{ __('navigation.profile') }}">
    <div class="fsl-inner">
        <div class="fsl-profile-card">
            <div class="fsl-profile">
                <a href="{{ route('users.show', $user) }}" class="fsl-avatar" title="{{ $user->username }}">
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}">
                </a>
                <div class="fsl-identity">
                    <a href="{{ route('users.show', $user) }}" class="fsl-fullname">{{ $fullName }}</a>
                    <span class="fsl-username">{{ '@' . $user->username }}</span>
                    @if($bio)
                        <p class="fsl-bio">{{ $bio }}</p>
                    @endif
                </div>
            </div>
        </div>

        <nav class="fsl-nav">
            <a href="{{ route('users.show', $user) }}"
               class="fsl-link {{ $onOwnProfile ? 'active' : '' }}"
               data-label="{{ __('navigation.profile') }}">
                <i class="fas fa-user"></i>
                <span>{{ __('navigation.profile') }}</span>
            </a>
            <a href="{{ route('profile.edit', $user->username) }}"
               class="fsl-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
               data-label="{{ __('messages.edit_profile') }}">
                <i class="fas fa-pen-to-square"></i>
                <span>{{ __('messages.edit_profile') }}</span>
            </a>
            <a href="{{ route('users.saved-posts') }}"
               class="fsl-link {{ request()->routeIs('users.saved-posts') ? 'active' : '' }}"
               data-label="{{ __('navigation.saved_posts') }}">
                <i class="fas fa-bookmark"></i>
                <span>{{ __('navigation.saved_posts') }}</span>
            </a>
            <a href="{{ route('pulse.index') }}"
               class="fsl-link {{ request()->routeIs('pulse.*') ? 'active' : '' }}"
               data-label="{{ __('messages.pulse') }}">
                <i class="fas fa-heart-pulse"></i>
                <span>{{ __('messages.pulse') }}</span>
            </a>
            <a href="{{ route('users.memories', $user) }}"
               class="fsl-link {{ request()->routeIs('users.memories') ? 'active' : '' }}"
               data-label="{{ __('messages.my_memories') }}">
                <i class="fas fa-book-open"></i>
                <span>{{ __('messages.my_memories') }}</span>
            </a>
            <a href="{{ route('explore') }}"
               class="fsl-link {{ request()->routeIs('explore') ? 'active' : '' }}"
               data-label="{{ __('users.explore_users') }}">
                <i class="fas fa-users"></i>
                <span>{{ __('users.explore_users') }}</span>
            </a>
            <a href="{{ route('hashtags.index') }}"
               class="fsl-link {{ request()->routeIs('hashtags.*') ? 'active' : '' }}"
               data-label="{{ __('hashtags.hashtags') }}">
                <i class="fas fa-hashtag"></i>
                <span>{{ __('hashtags.hashtags') }}</span>
            </a>
            <a href="{{ route('reports.my-reports') }}"
               class="fsl-link {{ request()->routeIs('reports.my-reports') ? 'active' : '' }}"
               data-label="{{ __('messages.my_reports') }}">
                <i class="fas fa-flag"></i>
                <span>{{ __('messages.my_reports') }}</span>
            </a>
            <a href="{{ route('notifications.index') }}"
               class="fsl-link {{ request()->routeIs('notifications.index') ? 'active' : '' }}"
               data-label="{{ __('navigation.notifications') }}">
                <i class="fas fa-bell"></i>
                <span>{{ __('navigation.notifications') }}</span>
            </a>
        </nav>

        @if($myCommunities->isNotEmpty())
        <div class="fsl-section">
            <div class="fsl-section-head">
                <h4 class="fsl-section-title">{{ __('messages.your_communities') ?? 'Your communities' }}</h4>
                <a href="{{ route('communities.index') }}" class="fsl-section-more">{{ __('messages.view_all') ?? 'All' }}</a>
            </div>
            <div class="fsl-shortcuts">
                @foreach($myCommunities as $g)
                    <a href="{{ route('communities.show', $g->slug) }}"
                       class="fsl-shortcut"
                       data-label="{{ $g->name }}"
                       title="{{ $g->name }}">
                        <span class="fsl-shortcut-avatar">
                            @if($g->avatar)
                                <img src="{{ $g->avatar_url }}" alt="{{ $g->name }}" loading="lazy">
                            @else
                                <i class="fas fa-users"></i>
                            @endif
                        </span>
                        <span class="fsl-shortcut-name">{{ $g->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <button type="button" class="fsl-toggle" id="fslToggle" aria-expanded="true" aria-controls="feedSidebarLeft">
            <i class="fas fa-chevron-left fsl-toggle-icon"></i>
            <span class="fsl-toggle-label">{{ __('messages.collapse') }}</span>
        </button>
    </div>
</aside>

<script>
(function () {
    const sidebar = document.getElementById('feedSidebarLeft');
    const toggle = document.getElementById('fslToggle');
    if (!sidebar || !toggle) return;

    const KEY = 'nexus_feed_sidebar_collapsed';
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
})();
</script>
