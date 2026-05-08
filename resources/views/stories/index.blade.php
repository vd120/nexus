@extends('layouts.app')

@section('title', __('messages.stories'))

@section('content')
@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                        <video muted>
                            <source src="{{ asset('storage/' . $latestStory->media_path) }}" type="video/mp4">
                        </video>
                    @endif
                </div>
                <div class="story-overlay">
                    <div class="story-avatar">
                        <img src="{{ $latestStory->user->avatar_url }}" alt="Avatar">
                    </div>
                    <div class="story-meta">
                        <span class="story-user">{{ $latestStory->user->username }}</span>
                        <span class="story-time">{{ $latestStory->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($followedUsersWithStories->count() > 0)
    <div class="story-section">
        <h3>{{ __('messages.friends_stories') }}</h3>
        <div class="stories-grid">
            @foreach($followedUsersWithStories as $user)
            @php
            $latestStory = $user->activeStories->sortByDesc('created_at')->first();
            @endphp
            @if($latestStory)
            <div class="story-card" onclick="viewStory('{{ $user->username }}', '{{ $latestStory->slug }}')">
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
                        <video muted>
                            <source src="{{ asset('storage/' . $latestStory->media_path) }}" type="video/mp4">
                        </video>
                    @endif
                </div>
                <div class="story-overlay">
                    <div class="story-avatar">
                        <img src="{{ $user->avatar_url }}" alt="Avatar">
                    </div>
                    <div class="story-meta">
                        <span class="story-user">{{ $user->username }}</span>
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
    <div class="empty-state">
        <i class="fas fa-camera"></i>
        <h3>{{ __('messages.no_stories') }}</h3>
        <p>{{ __('messages.be_first_to_create') }}</p>
        <a href="{{ route('stories.create') }}" class="btn-primary">{{ __('messages.create_one') }}</a>
    </div>
    @endif
</div>

<style>
.stories-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.page-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: var(--twitter-dark);
}

.story-section {
    margin-bottom: 40px;
}

.story-section h3 {
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--twitter-dark);
}

.stories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.story-card {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    aspect-ratio: 9/16;
}

/* Text story preview */
.story-text-preview {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    text-align: center;
}

.story-text-preview i {
    font-size: 48px;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 12px;
}

.story-text-snippet {
    color: white;
    font-size: 14px;
    line-height: 1.5;
    max-width: 100%;
    word-wrap: break-word;
}

.story-card {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.2s ease;
}

.story-card:hover {
    transform: scale(1.02);
}

.story-preview {
    width: 100%;
    height: 100%;
}

.story-preview img,
.story-preview video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.story-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(transparent 50%, rgba(0,0,0,0.8));
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.story-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 3px solid var(--twitter-blue);
    overflow: hidden;
}

.story-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--twitter-blue), #8B5CF6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 20px;
}

.story-meta {
    color: white;
}

.story-user {
    display: block;
    font-weight: 600;
    font-size: 14px;
}

.story-time {
    display: block;
    font-size: 12px;
    opacity: 0.8;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--twitter-gray);
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    display: block;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: var(--twitter-dark);
}

.btn-primary {
    background: var(--twitter-blue);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 24px;
    cursor: pointer;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #1991DB;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 20px !important;
    }
    
    .story-section h3 {
        font-size: 16px !important;
    }

    .stories-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 10px !important;
    }
    
    .btn-primary {
        padding: 6px 12px !important;
        font-size: 13px !important;
    }
}

@media (max-width: 640px) {
    .stories-page {
        padding: 10px 8px !important;
    }

    .page-header {
        margin-bottom: 15px !important;
    }

    .stories-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 8px !important;
    }
    
    .story-card {
        border-radius: 12px !important;
    }
    
    .story-avatar {
        width: 32px !important;
        height: 32px !important;
        border-width: 2px !important;
    }
    
    .story-user {
        font-size: 11px !important;
    }
    
    .story-time {
        font-size: 9px !important;
    }
    
    .story-overlay {
        padding: 6px !important;
    }
    
    .story-text-preview i {
        font-size: 24px !important;
    }
    
    .story-text-snippet {
        font-size: 10px !important;
    }
    
    .empty-state {
        padding: 20px 10px !important;
    }
    
    .empty-state i {
        font-size: 32px !important;
    }
}

@media (max-width: 400px) {
    .stories-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 6px !important;
    }
}
</style>

<script>
function viewStory(username, storySlug) {
    window.location.href = '/stories/' + username + '/' + storySlug;
}

// Check for story deleted toast
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('story_deleted') === 'true') {
        localStorage.removeItem('story_deleted');
        if (typeof showToast === 'function') {
            showToast(window.chatTranslations.story_deleted_toast || '{{ __('messages.story_deleted_success') }}', 'success');
        }
    }
});
</script>
@endsection
