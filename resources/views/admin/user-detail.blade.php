@extends('layouts.app')

@section('title', __('admin.user_profile') . ' - Admin Panel')

@section('content')
<div class="admin-page">
    {{-- Header --}}
    <div class="admin-header">
        <div class="header-left">
            <a href="{{ route('admin.users') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ __('admin.user_profile') }}</h1>
                <p>{{ __('admin.user_profile_subtitle') }}</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.users.edit', $user) }}" class="action-btn edit">
                <i class="fas fa-edit"></i> {{ __('admin.edit_user') }}
            </a>
        </div>
    </div>

    {{-- User Card --}}
    <div class="user-card">
        <div class="user-card-header">
            <div class="user-avatar-large">
                <img src="{{ $user->avatar_url }}" alt="">
            </div>
            @if($user->is_suspended)
            <div class="suspended-badge" style="right: 130px;"><i class="fas fa-ban"></i> {{ __('admin.suspended') }}</div>
            @endif
            @if($user->is_admin)
            <div class="admin-badge"><i class="fas fa-crown"></i> {{ __('admin.admin_badge') }}</div>
            @endif
        </div>

        <div class="user-card-body">
            <h2>{{ $user->username }}</h2>
            @if($user->name)
            <p class="user-fullname" style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">{{ $user->name }}</p>
            @endif
            <div class="user-status">
                @if($user->profile && $user->profile->is_private)
                <span class="status-badge private"><i class="fas fa-lock"></i> {{ __('admin.private_badge') }}</span>
                @else
                <span class="status-badge public"><i class="fas fa-globe"></i> {{ __('admin.public_badge') }}</span>
                @endif
            </div>

            <div class="user-meta">
                <div class="meta-item">
                    <i class="fas fa-user"></i>
                    <span>{{ $user->name }}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-at"></i>
                    <span>{{ $user->username }}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-envelope"></i>
                    <span>{{ $user->email }}</span>
                    @if($user->hasVerifiedEmail())
                    <span class="verification-badge verified"><i class="fas fa-check-circle"></i> {{ __('admin.verified') }}</span>
                    @else
                    <span class="verification-badge unverified"><i class="fas fa-exclamation-circle"></i> {{ __('admin.unverified') }}</span>
                    @endif
                </div>
                @if($user->profile && $user->profile->location)
                <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ $user->profile->location }}</span>
                </div>
                @endif
                @if($user->profile && $user->profile->website)
                <div class="meta-item">
                    <i class="fas fa-link"></i>
                    <a href="{{ $user->profile->website }}" target="_blank">{{ $user->profile->website }}</a>
                </div>
                @endif
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>{{ __('admin.joined_label') }} {{ $user->created_at->format('M j, Y') }}</span>
                </div>
            </div>

            @if($user->profile && $user->profile->bio)
            <div class="user-bio">
                <p>{{ $user->profile->bio }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-icon-wrap posts">
                <i class="fas fa-pen-square"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $user->posts->count() }}</span>
                <span class="stat-label">{{ __('admin.posts_count') }}</span>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon-wrap followers">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $user->followers->count() }}</span>
                <span class="stat-label">{{ __('admin.followers') }}</span>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon-wrap following">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $user->follows->count() }}</span>
                <span class="stat-label">{{ __('admin.following') }}</span>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon-wrap stories">
                <i class="fas fa-circle-notch"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ $user->stories->count() }}</span>
                <span class="stat-label">{{ __('admin.stories_count') }}</span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="content-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="posts">{{ __('admin.posts') }}</button>
            <button class="tab-btn" data-tab="comments">{{ __('admin.comments_tab') }}</button>
            <button class="tab-btn" data-tab="stories">{{ __('admin.stories') }}</button>
        </div>

        <div id="posts-tab" class="tab-content active">
            @if($user->posts->count() > 0)
            <div class="items-list">
                @foreach($user->posts->take(10) as $post)
                <div class="item-card">
                    <div class="item-content">
                        <p>{{ Str::limit($post->content ?? __('admin.media_post'), 150) }}</p>
                    </div>
                    <div class="item-meta">
                        <span><i class="fas fa-heart"></i> {{ $post->likes->count() }}</span>
                        <span><i class="fas fa-comment"></i> {{ $post->comments->count() }}</span>
                        <span>{{ $post->created_at->diffForHumans() }}</span>
                        <a href="/posts/{{ $post->slug }}" target="_blank" class="view-link">{{ __('admin.view_post') }} <i class="fas fa-external-link-alt"></i></a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-pen-square"></i>
                <p>{{ __('admin.no_posts_yet') }}</p>
            </div>
            @endif
        </div>

        <div id="comments-tab" class="tab-content">
            @if($user->comments->count() > 0)
            <div class="items-list">
                @foreach($user->comments->take(10) as $comment)
                <div class="item-card">
                    <div class="item-content comment">
                        <p>{!! app(\App\Services\MentionService::class)->convertMentionsToLinks($comment->content) !!}</p>
                    </div>
                    <div class="item-meta">
                        <span><i class="fas fa-heart"></i> {{ $comment->likes->count() }}</span>
                        <span>{{ __('admin.on_post') }} {{ $comment->post->user->name }}{{ __('admin.post_owner') }}</span>
                        <span>{{ $comment->created_at->diffForHumans() }}</span>
                        <a href="/posts/{{ $comment->post->slug }}" target="_blank" class="view-link">{{ __('admin.view_post') }} <i class="fas fa-external-link-alt"></i></a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <p>{{ __('admin.no_comments_yet') }}</p>
            </div>
            @endif
        </div>

        <div id="stories-tab" class="tab-content">
            @if($user->stories->count() > 0)
            <div class="stories-grid">
                @foreach($user->stories->take(12) as $story)
                <div class="story-thumb">
                    @if($story->media_type === 'image')
                        <img src="{{ asset('storage/' . $story->media_path) }}" alt="">
                    @else
                        <video muted>
                            <source src="{{ asset('storage/' . $story->media_path) }}" type="video/mp4">
                        </video>
                    @endif
                    <div class="story-overlay">
                        <span><i class="fas fa-eye"></i> {{ $story->storyViews->count() }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-circle-notch"></i>
                <p>{{ __('admin.no_stories_yet') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin-user-detail.css') }}">

<script>
window.runOnPageLoad( function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Remove active from all buttons and contents
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });
});
</script>
@endsection
