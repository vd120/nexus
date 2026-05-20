@extends('layouts.app')

@section('title', __('messages.home'))

{{-- Override layout constraints for 3-column feed --}}
@section('main_class', 'full-width')
@section('content_class', 'wide-content')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/posts-index.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('css/chat-show.css') }}?v={{ time() }}">
<style>
    @media (max-width: 768px) {
        :is(.app-layout, .main-content) { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
    }
    
    /* Ensure sidebar partial matches the feed column */
    #feed-chat-sidebar {
        width: 100%;
        max-width: none;
    }
</style>
@endpush

@section('content')

<div class="feed-layout">
    {{-- Left Sidebar --}}
    <aside class="feed-sidebar left">
        @include('partials.sidebars.left')
    </aside>

    <div class="feed-container">
        @if(session('verified'))
            <script>showToast('{{ __('messages.email_verified_success_toast') }}', 'success');</script>
        @endif

        @auth
        {{-- Stories - Always show section --}}
        <div class="stories-section">
            <div class="stories-header">
                <h3>{{ __('messages.stories') }}</h3>
                <a href="{{ route('stories.index') }}" class="btn btn-ghost" style="padding: 6px 12px; font-size: 13px;">
                    <i class="fas fa-external-link-alt"></i> {{ __('messages.view_all_stories') }}
                </a>
            </div>
            <div class="stories-scroll" id="stories-scroll">
                @if($myStories->count() > 0)
                    @php
                    $latestMyStory = $myStories->sortByDesc('created_at')->first();
                    @endphp
                    <div class="story-item" onclick="viewStoryFromHome('{{ auth()->user()->username }}', '{{ $latestMyStory->slug }}')">
                        <div class="story-avatar-wrapper">
                            <div class="story-avatar">
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}">
                            </div>
                        </div>
                        <div class="story-name">{{ __('messages.your_story') }}</div>
                    </div>
                @else
                <div class="story-item create" onclick="window.location.href='{{ route('stories.create') }}'" style="position: relative;">
                    <div class="story-avatar-wrapper">
                        <div class="story-avatar">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                            <div class="add-icon"><i class="fas fa-plus"></i></div>
                        </div>
                    </div>
                    <div class="story-name">{{ __('messages.create_story') }}</div>
                </div>
                @endif

                @foreach($followedUsersWithStories as $user)
                    @php
                    $latestStory = $user->activeStories->sortByDesc('created_at')->first();
                    @endphp
                    @if($latestStory)
                    <div class="story-item" data-username="{{ $user->username }}" onclick="viewStoryFromHome('{{ $user->username }}', '{{ $latestStory->slug }}')">
                        <div class="story-avatar-wrapper">
                            <div class="story-avatar">
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}">
                            </div>
                        </div>
                        <div class="story-name">{{ $user->username }}</div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Create Post - Clean Professional Design --}}
        <div class="create-post">
            <div class="create-post-header">
                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="create-post-avatar">
                <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
                    <span class="create-post-author">{{ auth()->user()->username }}</span>
                    <button type="button" class="privacy-btn" id="privacy-btn" onclick="togglePrivacy()" style="padding: 3px 8px; font-size: 11px;">
                        <i class="fas fa-globe" id="privacy-icon"></i> <span id="privacy-text">{{ __('messages.public') }}</span>
                    </button>
                </div>
            </div>
            <textarea id="post-content" placeholder="{{ __('messages.whats_on_your_mind') }}" dir="auto" style="margin-top: 12px;"></textarea>
            <div id="hashtag-suggestions" class="hashtag-suggestions" style="display: none;"></div>
            <div class="post-actions">
                <div class="post-actions-left">
                    <label for="media" class="post-action-btn" style="cursor: pointer;">
                        <i class="fas fa-image" style="color: #45bd62;"></i> <span>{{ __('messages.media') }}</span>
                    </label>
                    <input type="file" id="media" accept="image/*,video/*" multiple style="display: none;" onchange="previewMedia(this)">
                </div>
                <button type="button" class="btn btn-primary" onclick="submitPost()">
                    {{ __('messages.post') }}
                </button>
            </div>
            <input type="hidden" id="is-private" value="0">
            <div id="media-preview-container" style="display: none; margin-top: 12px;">
                <div id="media-previews" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
            </div>
        </div>
        @endauth

        {{-- Posts Feed --}}
        <div class="posts-feed" id="posts-container">
            @forelse($posts as $post)
                @include('partials.post', ['post' => $post])
            @empty
                <div class="empty-state">
                    <i class="fas fa-newspaper"></i>
                    <h3>{{ __('messages.no_posts_yet') }}</h3>
                    <p>{{ __('messages.be_first_to_post') }}</p>
                </div>
            @endforelse

            {{-- Load More Button --}}
            @if($posts->hasMorePages())
            <div class="load-more-container" id="load-more-container">
                <button id="load-more-btn" class="btn btn-primary" onclick="loadMorePosts()">
                    <i class="fas fa-spinner fa-spin" id="load-more-spinner" style="display: none;"></i>
                    {{ __('messages.load_more') }}
                </button>
            </div>
            @endif

            {{-- Loading Indicator for Infinite Scroll --}}
            <div id="infinite-scroll-loader" style="display: none; text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
            </div>

            {{-- No More Posts Message --}}
            <div id="no-more-posts" style="display: none; text-align: center; padding: 30px; color: var(--text-muted);">
                <i class="fas fa-check-circle" style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>{{ __('messages.no_more_posts') }}</p>
            </div>
        </div>

        @guest
        <div class="guest-cta">
            <h3>{{ __('messages.join_community') }}</h3>
            <p>{{ __('messages.sign_up_to_post') }}</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="{{ route('register') }}" class="btn btn-primary">{{ __('messages.sign_up') }}</a>
                <a href="{{ route('login') }}" class="btn">{{ __('messages.sign_in') }}</a>
            </div>
        </div>
        @endguest
    </div>

    {{-- Right Sidebar --}}
    <aside class="feed-sidebar right">
        @include('partials.sidebars.right')
    </aside>
</div>

@include("partials.chat.mini-chat-container")

@auth
    {{-- Floating Action Button to toggle Chat Drawer --}}
    <button class="floating-chat-btn" id="chat-drawer-toggle" title="{{ __('chat.messages') }}">
        <i class="fa-regular fa-comment-dots"></i>
        @php
            $unreadMessagesCount = \App\Models\Message::where('sender_id', '!=', auth()->id())
                ->whereNull('read_at')
                ->whereHas('conversation', function($q) {
                    $q->where('user1_id', auth()->id())
                      ->orWhere('user2_id', auth()->id())
                      ->orWhereHas('group.members', function($q2) {
                          $q2->where('user_id', auth()->id());
                      });
                })
                ->count();
        @endphp
        <span class="btn-badge" id="chat-drawer-badge" style="{{ $unreadMessagesCount > 0 ? 'display: flex;' : 'display: none;' }}">
            {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
        </span>
    </button>

    {{-- Sliding Chat Drawer --}}
    <div class="chat-drawer" id="chat-drawer">
        <div class="chat-drawer-header">
            <button class="chat-drawer-close" id="chat-drawer-close" title="{{ __('messages.close') }}">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-drawer-body">
            <div class="drawer-loader" id="chat-drawer-loader">
                <i class="fas fa-circle-notch fa-spin"></i>
                <span>{{ __('notifications.loading') }}...</span>
            </div>
            <iframe class="chat-iframe" id="chat-drawer-iframe" data-src="{{ route('chat.index') }}"></iframe>
        </div>
    </div>
@endauth

<script>
// Stories Preview (Keep in-page as it's feed-specific)
function togglePrivacy() {
    const input = document.getElementById('is-private');
    const icon = document.getElementById('privacy-icon');
    const text = document.getElementById('privacy-text');
    
    if (input.value == '0') {
        input.value = '1';
        icon.className = 'fas fa-lock';
        text.innerText = '{{ __('messages.private') }}';
    } else {
        input.value = '0';
        icon.className = 'fas fa-globe';
        text.innerText = '{{ __('messages.public') }}';
    }
}

function viewStoryFromHome(username, slug) {
    window.location.href = `/stories/${username}/${slug}`;
}

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('chat-drawer-toggle');
    const closeBtn = document.getElementById('chat-drawer-close');
    const drawer = document.getElementById('chat-drawer');
    const iframe = document.getElementById('chat-drawer-iframe');
    const loader = document.getElementById('chat-drawer-loader');

    if (!toggleBtn || !drawer || !iframe) return;

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        drawer.classList.toggle('open');
        document.body.classList.toggle('chat-drawer-open', drawer.classList.contains('open'));
        
        // Lazy load iframe source on first open
        if (drawer.classList.contains('open') && !iframe.src) {
            const url = iframe.getAttribute('data-src');
            iframe.src = url;
            
            iframe.onload = () => {
                if (loader) loader.style.display = 'none';
                iframe.style.display = 'block';
            };
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            drawer.classList.remove('open');
            document.body.classList.remove('chat-drawer-open');
        });
    }

    // Close drawer when clicking outside
    document.addEventListener('click', (e) => {
        if (drawer.classList.contains('open') && 
            !drawer.contains(e.target) && 
            !toggleBtn.contains(e.target)) {
            drawer.classList.remove('open');
            document.body.classList.remove('chat-drawer-open');
        }
    });
});

// Load More Posts & Infinite Scroll Logic
window.currentFeedPage = {{ $posts->currentPage() }};
window.isLoadingPosts = false;
window.hasMorePosts = {{ $posts->hasMorePages() ? 'true' : 'false' }};

window.loadMorePosts = async function() {
    if (window.isLoadingPosts || !window.hasMorePosts) return;

    window.isLoadingPosts = true;
    const btn = document.getElementById('load-more-btn');
    const spinner = document.getElementById('load-more-spinner');
    const infiniteLoader = document.getElementById('infinite-scroll-loader');
    
    if (btn) btn.disabled = true;
    if (spinner) spinner.style.display = 'inline-block';
    if (infiniteLoader && (!btn || btn.style.display === 'none')) {
        infiniteLoader.style.display = 'block';
    }

    try {
        const nextPage = window.currentFeedPage + 1;
        const response = await fetch(`/posts/load-more?page=${nextPage}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) throw new Error('Network error');
        
        const data = await response.json();
        
        if (data.success) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;
            
            const postsList = document.getElementById('posts-container');
            // Find Load More container to insert before it
            const loadMore = document.getElementById('load-more-container');
            
            Array.from(tempDiv.children).forEach(child => {
                if (loadMore) {
                    postsList.insertBefore(child, loadMore);
                } else {
                    postsList.appendChild(child);
                }
            });
            
            window.currentFeedPage = nextPage;
            window.hasMorePosts = data.has_more;

            if (!data.has_more) {
                if (loadMore) loadMore.style.display = 'none';
                const noMore = document.getElementById('no-more-posts');
                if (noMore) noMore.style.display = 'block';
            }
        }
    } catch (err) {
        if (typeof window.showToast === 'function') {
            window.showToast('Failed to load more posts', 'error');
        }
    } finally {
        window.isLoadingPosts = false;
        if (btn) btn.disabled = false;
        if (spinner) spinner.style.display = 'none';
        if (infiniteLoader) infiniteLoader.style.display = 'none';
    }
};

// Enable smooth infinite scroll
window.addEventListener('scroll', () => {
    if (!window.hasMorePosts || window.isLoadingPosts) return;
    
    // Load more when user is 1000px from the bottom
    if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 1000) {
        window.loadMorePosts();
    }
}, { passive: true });
</script>
@endsection
