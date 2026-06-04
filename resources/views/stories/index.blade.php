@extends('layouts.app')

@section('title', __('messages.stories'))

@push('styles')
@vite('resources/css/stories-index.css')
@endpush

@section('content')
@if(session('success'))
    <script>
        window.runOnPageLoad( function() {
            showToast('{{ session('success') }}', 'success');
        });
    </script>
@endif

<div class="stories-page">
    <div class="page-header">
        <h1>{{ __('messages.stories') }}</h1>
        <a href="{{ route('stories.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i>
            {{ __('messages.create_story') }}
        </a>
    </div>

    @if($myStories->count() > 0)
    <div class="story-section">
        <h3>{{ __('messages.your_stories') }}</h3>
        <div class="stories-grid">
            @php
            // Group stories by user and get the latest one
            $myStoriesGrouped = $myStories->groupBy('user_id');
            @endphp
            @foreach($myStoriesGrouped as $userId => $userStories)
            @php
            $latestStory = $userStories->sortByDesc('created_at')->first();
            @endphp
            <div class="story-card" onclick="viewStory('{{ $latestStory->user->username }}', '{{ $latestStory->slug }}')">
                <div class="story-preview">
                    @if($latestStory->media_type === 'text')
                        @php
                            $bgColor = $latestStory->metadata['bg_color'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        @endphp
                        <div class="story-text-preview" style="background: {{ $bgColor }}">
                            <i class="fas fa-font"></i>
                            <span class="story-text-snippet">{{ Str::limit($latestStory->content, 50) }}</span>
                        </div>
                    @elseif($latestStory->media_type === 'image')
                        <img src="{{ asset('storage/' . $latestStory->media_path) }}" alt="Story">
                    @else
                        <video muted preload="metadata">
                            <source src="{{ asset('storage/' . $latestStory->media_path) }}" type="video/mp4">
                        </video>
                    @endif
                </div>
                <div class="story-overlay">
                    <div class="story-avatar">
                        <img src="{{ $latestStory->user->avatar_url }}" alt="Avatar">
                    </div>
                    <div class="story-meta">
                        <span class="story-user" style="display:inline-flex;align-items:center;gap:.2em;">{{ $latestStory->user->username }}<x-verified-badge :user="$latestStory->user" size=".8em" /></span>
                        <span class="story-time">{{ $latestStory->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($followedUsersWithStories->count() > 0)
    <div class="story-section" id="friends-story-section">
        <h3>{{ __('messages.friends_stories') }}</h3>
        <div class="stories-grid" id="friends-stories-grid">
            @foreach($followedUsersWithStories as $user)
            @php
            $latestStory = $user->activeStories->sortByDesc('created_at')->first();
            @endphp
            @if($latestStory)
            <div class="story-card" data-username="{{ $user->username }}" onclick="viewStory('{{ $user->username }}', '{{ $latestStory->slug }}')">
                <div class="story-preview">
                    @if($latestStory->media_type === 'text')
                        @php
                            $bgColor = $latestStory->metadata['bg_color'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        @endphp
                        <div class="story-text-preview" style="background: {{ $bgColor }}">
                            <i class="fas fa-font"></i>
                            <span class="story-text-snippet">{{ Str::limit($latestStory->content, 50) }}</span>
                        </div>
                    @elseif($latestStory->media_type === 'image')
                        <img src="{{ asset('storage/' . $latestStory->media_path) }}" alt="Story">
                    @else
                        <video muted preload="metadata">
                            <source src="{{ asset('storage/' . $latestStory->media_path) }}" type="video/mp4">
                        </video>
                    @endif
                </div>
                <div class="story-overlay">
                    <div class="story-avatar">
                        <img src="{{ $user->avatar_url }}" alt="Avatar">
                    </div>
                    <div class="story-meta">
                        <span class="story-user" style="display:inline-flex;align-items:center;gap:.2em;">{{ $user->username }}<x-verified-badge :user="$user" size=".8em" /></span>
                        <span class="story-time">{{ $latestStory->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    @if($myStories->count() === 0 && $followedUsersWithStories->count() === 0)
    <div class="empty-state" id="stories-empty-state">
        <i class="fas fa-camera"></i>
        <h3>{{ __('messages.no_stories') }}</h3>
        <p>{{ __('messages.be_first_to_create') }}</p>
        <a href="{{ route('stories.create') }}" class="btn-primary">{{ __('messages.create_one') }}</a>
    </div>
    @endif
</div>



<script>
function viewStory(username, storySlug) {
    window.location.href = '/stories/' + username + '/' + storySlug;
}

// Real-time story addition for Stories Index page
function addStoryToSection(user) {
    // Remove empty state if it exists
    const emptyState = document.getElementById('stories-empty-state');
    if (emptyState) {
        emptyState.remove();
    }

    // Find or create the Friends' Stories section
    let friendsSection = document.getElementById('friends-story-section');
    if (!friendsSection) {
        friendsSection = document.createElement('div');
        friendsSection.className = 'story-section';
        friendsSection.id = 'friends-story-section';
        friendsSection.innerHTML = `
            <h3>{{ __('messages.friends_stories') }}</h3>
            <div class="stories-grid" id="friends-stories-grid"></div>
        `;
        const storiesPage = document.querySelector('.stories-page');
        if (storiesPage) {
            storiesPage.appendChild(friendsSection);
        }
    }

    const grid = document.getElementById('friends-stories-grid');
    if (!grid) return;

    // Check if the story card for this user already exists
    let card = grid.querySelector(`.story-card[data-username="${user.username}"]`);
    if (card) {
        // Update click handler to point to the newest story
        card.onclick = function() { viewStory(user.username, user.storySlug); };
        
        // Update the preview element inside the card
        const previewDiv = card.querySelector('.story-preview');
        if (previewDiv) {
            let previewHtml = '';
            if (user.mediaType === 'text') {
                previewHtml = `
                    <div class="story-text-preview" style="background: ${user.bgColor}">
                        <i class="fas fa-font"></i>
                        <span class="story-text-snippet">${user.content.substring(0, 50)}</span>
                    </div>
                `;
            } else if (user.mediaType === 'image') {
                previewHtml = `<img src="${user.mediaPath}" alt="Story">`;
            } else {
                previewHtml = `
                    <video muted preload="metadata">
                        <source src="${user.mediaPath}" type="video/mp4">
                    </video>
                `;
            }
            previewDiv.innerHTML = previewHtml;
        }

        // Update time stamp
        const timeSpan = card.querySelector('.story-time');
        if (timeSpan) {
            timeSpan.textContent = user.timeAgo || 'Just now';
        }

        // Bubbling: Bring card to front of friends stories grid
        if (grid.firstChild && grid.firstChild !== card) {
            grid.insertBefore(card, grid.firstChild);
        }
        return;
    }

    // Create a new story card
    card = document.createElement('div');
    card.className = 'story-card';
    card.setAttribute('data-username', user.username);
    card.onclick = function() { viewStory(user.username, user.storySlug); };

    let previewHtml = '';
    if (user.mediaType === 'text') {
        previewHtml = `
            <div class="story-text-preview" style="background: ${user.bgColor}">
                <i class="fas fa-font"></i>
                <span class="story-text-snippet">${user.content.substring(0, 50)}</span>
            </div>
        `;
    } else if (user.mediaType === 'image') {
        previewHtml = `<img src="${user.mediaPath}" alt="Story">`;
    } else {
        previewHtml = `
            <video muted preload="metadata">
                <source src="${user.mediaPath}" type="video/mp4">
            </video>
        `;
    }

    const avatarUrl = user.avatarUrl || '/images/default-avatar.svg';
    const hasAvatar = user.avatarUrl && user.avatarUrl !== '';

    card.innerHTML = `
        <div class="story-preview">
            ${previewHtml}
        </div>
        <div class="story-overlay">
            <div class="story-avatar">
                ${hasAvatar 
                    ? `<img src="${avatarUrl}" alt="Avatar">`
                    : `<div class="avatar-placeholder">${user.username.charAt(0).toUpperCase()}</div>`
                }
            </div>
            <div class="story-meta">
                <span class="story-user" style="display:inline-flex;align-items:center;gap:.2em;">${user.username}${user.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : ''}</span>
                <span class="story-time">${user.timeAgo || 'Just now'}</span>
            </div>
        </div>
    `;

    // Prepend to stories grid
    if (grid.firstChild) {
        grid.insertBefore(card, grid.firstChild);
    } else {
        grid.appendChild(card);
    }
}

// Real-time story deletion for Stories Index page
function removeStoryFromSection(username) {
    const card = document.querySelector(`.story-card[data-username="${username}"]`);
    if (card) {
        card.remove();
    }

    // If no stories are left in the friends section, remove the entire section
    const grid = document.getElementById('friends-stories-grid');
    if (grid && grid.children.length === 0) {
        const friendsSection = document.getElementById('friends-story-section');
        if (friendsSection) {
            friendsSection.remove();
        }
    }

    // If all story sections are empty, render the empty state
    const allStoryCards = document.querySelectorAll('.story-card');
    if (allStoryCards.length === 0) {
        let emptyState = document.getElementById('stories-empty-state');
        if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.id = 'stories-empty-state';
            emptyState.innerHTML = `
                <i class="fas fa-camera"></i>
                <h3>{{ __('messages.no_stories') }}</h3>
                <p>{{ __('messages.be_first_to_create') }}</p>
                <a href="{{ route('stories.create') }}" class="btn-primary">{{ __('messages.create_one') }}</a>
            `;
            const storiesPage = document.querySelector('.stories-page');
            if (storiesPage) {
                storiesPage.appendChild(emptyState);
            }
        }
    }
}

// Bind callbacks to window for global access from socket-manager
window.addStoryToSection = addStoryToSection;
window.removeStoryFromSection = removeStoryFromSection;

// Check for story deleted toast
window.runOnPageLoad( function() {
    if (localStorage.getItem('story_deleted') === 'true') {
        localStorage.removeItem('story_deleted');
        if (typeof showToast === 'function') {
            showToast(window.chatTranslations.story_deleted_toast || '{{ __('messages.story_deleted_success') }}', 'success');
        }
    }
});
</script>
@endsection
