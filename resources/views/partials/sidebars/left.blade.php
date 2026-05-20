@php
    $user = auth()->user();
    $trendingHashtags = \App\Models\Hashtag::popular(5)->get();
    $recentActivity = $user->notifications()->latest()->limit(5)->get();
@endphp

<div class="modern-sidebar-column">
    {{-- Profile Section --}}
    <div class="sidebar-profile">
        <a href="{{ route('users.show', $user) }}" class="sidebar-avatar">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}">
        </a>
        <div class="sidebar-user-info">
            <a href="{{ route('users.show', $user) }}" class="sidebar-name">{{ $user->name }}</a>
            <div class="sidebar-handle">{{ '@' . $user->username }}</div>
        </div>
    </div>

    @if($user->profile && $user->profile->bio)
        <div class="sidebar-bio">
            <p>{{ $user->profile->bio }}</p>
        </div>
    @endif

    {{-- Main Navigation --}}
    <nav class="sidebar-nav">
        <a href="{{ route('home') }}" class="sidebar-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>{{ __('navigation.home') }}</span>
        </a>
        
        <a href="{{ route('explore') }}" class="sidebar-nav-link {{ request()->routeIs('explore') ? 'active' : '' }}">
            <i class="fas fa-compass"></i>
            <span>{{ __('navigation.explore') }}</span>
        </a>
        
        <a href="{{ route('users.saved-posts') }}" class="sidebar-nav-link {{ request()->routeIs('users.saved-posts') ? 'active' : '' }}">
            <i class="fas fa-bookmark"></i>
            <span>{{ __('navigation.saved_posts') }}</span>
        </a>

        <a href="{{ route('reports.my-reports') }}" class="sidebar-nav-link {{ request()->routeIs('reports.my-reports') ? 'active' : '' }}">
            <i class="fas fa-flag"></i>
            <span>{{ __('messages.my_reports') ?? 'My Reports' }}</span>
        </a>

        <a href="{{ route('profile.edit', $user->username) }}" class="sidebar-nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <i class="fas fa-cog"></i>
            <span>{{ __('messages.settings') ?? 'Settings' }}</span>
        </a>
        
        <a href="{{ route('users.show', $user->username) }}" class="sidebar-nav-link {{ request()->routeIs('users.show', ['user' => $user->username]) ? 'active' : '' }}">
            <i class="fas fa-user"></i>
            <span>{{ __('navigation.profile') }}</span>
        </a>
    </nav>

    {{-- Trending Section Integrated --}}
    @if($trendingHashtags->count() > 0)
    <div class="sidebar-trending">
        <h3 class="sidebar-title">{{ __('hashtags.trending') }}</h3>
        <div class="trending-list">
            @foreach($trendingHashtags as $hashtag)
            <a href="{{ route('hashtags.show', $hashtag->slug) }}" class="sidebar-trending-item">
                <span class="hashtag">#{{ $hashtag->name }}</span>
                <span class="count">{{ $hashtag->usage_count }}</span>
            </a>
            @endforeach
        </div>
        <a href="{{ route('hashtags.index') }}" class="sidebar-more">{{ __('messages.view_all') }}</a>
    </div>
    @endif
</div>
