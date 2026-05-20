@php
    $trendingHashtags = \App\Models\Hashtag::popular(5)->get();
    $followedUsers = auth()->user()->follows()->with('followed')->get()
        ->sortByDesc(fn($f) => $f->followed->is_online);
    $topCommunities = \App\Models\SocialGroup::withCount('members')->orderBy('members_count', 'desc')->limit(5)->get();
    $latestMessages = \App\Models\GlobalMessage::latest()->limit(4)->get()->reverse();
@endphp

<div class="modern-sidebar-column">
    {{-- Following Users (Horizontal) --}}
    <div class="sidebar-following">
        <h3 class="sidebar-title">{{ __('messages.following') ?? 'Following' }}</h3>
        <div class="following-grid" id="following-users-list">
            @foreach($followedUsers as $follow)
                @php $user = $follow->followed; @endphp
                <a href="{{ route('users.show', $user->username) }}" class="following-item" data-user-id="{{ $user->id }}">
                    <div class="avatar-container">
                        <div class="following-avatar-wrapper">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}">
                        </div>
                        <div class="online-dot {{ $user->is_online ? 'online' : '' }}"></div>
                    </div>
                    <span class="following-username">{{ $user->username }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Top Communities --}}
    <div class="sidebar-communities">
        <h3 class="sidebar-title">{{ __('messages.top_communities') }}</h3>
        <div class="communities-list">
            @foreach($topCommunities as $group)
            <a href="{{ route('communities.show', $group->slug) }}" class="sidebar-community-item">
                <span class="community-name">{{ $group->name }}</span>
                <span class="community-count">{{ $group->members_count }} {{ __('messages.members') }}</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Trending Hashtags --}}
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
    </div>
    @endif

    {{-- Global Chat Preview --}}
    <div class="sidebar-global-chat">
        <h3 class="sidebar-title">{{ __('navigation.global_chat') }}</h3>
        <div class="chat-preview-list">
            @foreach($latestMessages as $message)
            <div class="chat-preview-item">
                <span class="chat-user">{{ $message->user->username }}</span>
                <span class="chat-text">{{ \Illuminate\Support\Str::limit($message->content, 30) }}</span>
            </div>
            @endforeach
        </div>
        <a href="{{ route('global-chat.index') }}" class="sidebar-more">
            {{ __('messages.enter_global_chat') }}
        </a>
    </div>
</div>

<script>
    // Real-time online status update - wait until NexusSocket is defined
    function initNexusSocketListeners() {
        if (window.NexusSocket) {
            window.NexusSocket.on('user:online', (data) => {
                const el = document.querySelector(`.following-item[data-user-id="${data.userId}"] .online-dot`);
                if (el) el.classList.add('online');
            });
            window.NexusSocket.on('user:offline', (data) => {
                const el = document.querySelector(`.following-item[data-user-id="${data.userId}"] .online-dot`);
                if (el) el.classList.remove('online');
            });
        } else {
            setTimeout(initNexusSocketListeners, 500); 
        }
    }
    initNexusSocketListeners();
</script>
