@if(!isset($post) || !$post)
    @php return; @endphp
@endif

@php
    $userReaction = auth()->check() ? $post->userReaction : null;
    $totalReactions = $post->reactions_count ?? $post->reactions->count();
    $groupedReactions = $post->getGroupedReactions();
@endphp

<div class="post-card {{ (isset($isPinned) && $isPinned) || $post->isPinned() ? 'pinned-post' : '' }} {{ isset($is_broadcast) && $is_broadcast ? 'viewer-context-needed' : '' }}" id="post-{{ $post->id }}" data-post-id="{{ $post->id }}" data-post-slug="{{ $post->slug }}" data-owner-id="{{ $post->user_id }}">

    @if(!$post->is_approved)
        <div class="pending-approval-badge" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; background: rgba(245, 158, 11, 0.08); border-bottom: 1px solid var(--border); font-size: 13px; color: #f59e0b; font-weight: 700;">
            <i class="fas fa-clock-rotate-left"></i>
            <span>{{ __('messages.pending_approval') }}</span>
            <span style="font-size: 11px; font-weight: 500; opacity: 0.8; margin-left: 4px;">{{ __('messages.only_visible_to_you_and_admins') }}</span>
        </div>
    @endif

    @if($post->social_group_id)
        @include('partials.group-post-header', [
            'post' => $post, 
            'group' => $post->socialGroup, 
            'hideGroupContext' => $hideGroupContext ?? false,
            'is_broadcast' => isset($is_broadcast) && $is_broadcast
        ])
    @else
        <div class="post-header">
            <div class="post-author">
                @if($post->is_anonymous)
                    <div class="author-avatar anonymous-avatar" style="width: 44px; height: 44px; background: #374151; color: #9ca3af; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; border-radius: 12px; flex-shrink: 0;">
                        <i class="fas fa-user-secret"></i>
                    </div>
                    <div class="author-info">
                        <div class="author-top-row">
                            <span class="author-name anonymous-name" style="font-weight: 700; color: var(--text-muted, #9ca3af); font-style: italic;">{{ __('messages.anonymous_participant') }}</span>
                            <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" style="margin-left: 10px; font-size: 13px; color: var(--primary); transform: rotate(45deg); opacity: 0.9; {{ $post->isPinned() ? '' : 'display: none;' }}" title="{{ __('users.pinned_to_profile') }}"></i>
                        </div>
                        <span class="post-time" data-timestamp="{{ $post->created_at->toIso8601String() }}">
                            {{ $post->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $post->created_at->diffForHumans(null, true, true) }}
                            <span class="privacy-badge"><i class="fas fa-user-secret"></i></span>
                        </span>
                    </div>
                @else
                    <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->username }}" class="author-avatar">
                    <div class="author-info">
                        <div class="author-top-row">
                            <a href="{{ route('users.show', $post->user) }}" class="author-name">{{ $post->user->username }}</a>
                            <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" style="margin-left: 10px; font-size: 13px; color: var(--primary); transform: rotate(45deg); opacity: 0.9; {{ $post->isPinned() ? '' : 'display: none;' }}" title="{{ __('users.pinned_to_profile') }}"></i>
                            @php 
                                $isBroadcast = isset($is_broadcast) && $is_broadcast;
                                $showFollow = $isBroadcast || (auth()->check() && auth()->id() !== $post->user->id);
                            @endphp
                            @if($showFollow)
                                @php $isFollowing = auth()->check() ? auth()->user()->isFollowing($post->user) : false; @endphp
                                <button type="button" class="quick-follow-btn {{ $isFollowing ? 'following' : '' }} {{ $isBroadcast ? 'context-not-owner' : '' }}"
                                        onclick="quickFollow('{{ $post->user->username }}', this)"
                                        data-following="{{ $isFollowing ? 'true' : 'false' }}"
                                        data-username="{{ $post->user->username }}"
                                        data-author-id="{{ $post->user_id }}"
                                        @if($isBroadcast) style="display: none;" @endif>
                                    <span>{{ $isFollowing ? __('messages.following') : __('messages.follow') }}</span>
                                </button>
                            @endif
                        </div>
                        <span class="post-time" data-timestamp="{{ $post->created_at->toIso8601String() }}">
                            {{ $post->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $post->created_at->diffForHumans(null, true, true) }}
                            @if($post->is_private)
                                <span class="privacy-badge"><i class="fas fa-lock"></i></span>
                            @else
                                <span class="privacy-badge public"><i class="fas fa-globe"></i></span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>
            <div class="post-header-actions">
                @auth
                <button type="button" class="post-menu-btn" onclick="togglePostMenu('{{ $post->id }}')" title="{{ __('messages.options') }}">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="post-menu-dropdown" id="post-menu-{{ $post->id }}" style="display: none;">
                    @php 
                        $isBroadcast = isset($is_broadcast) && $is_broadcast;
                        $isOwner = auth()->check() && $post->user_id === auth()->id();
                    @endphp

                    @if($isBroadcast || $isOwner)
                        <button type="button" id="pin-menu-item-{{ $post->id }}" class="menu-item {{ $isBroadcast ? 'context-owner' : '' }}" onclick="pinPost(event, {{ $post->id }})" style="{{ ($isBroadcast || !$post->isPinned()) ? '' : 'display: none;' }}">
                            <i class="fas fa-thumbtack"></i> {{ __('users.pin_post') }}
                        </button>
                        <button type="button" id="unpin-menu-item-{{ $post->id }}" class="menu-item {{ $isBroadcast ? 'context-owner' : '' }}" onclick="unpinPost(event, {{ $post->id }})" style="{{ ($isBroadcast || $post->isPinned()) ? '' : 'display: none;' }}">
                            <i class="fas fa-thumbtack" style="transform: rotate(45deg);"></i> {{ __('users.unpin_post') }}
                        </button>
                        <button type="button" class="menu-item {{ $isBroadcast ? 'context-owner' : '' }}" onclick="deletePost('{{ $post->slug }}', this)" @if($isBroadcast) style="display: none;" @endif>
                            <i class="fas fa-trash"></i> {{ __('messages.delete_post') }}
                        </button>
                    @endif

                    @if($isBroadcast || ($post->canDelete(auth()->user()) && !$isOwner))
                        @php
                            $isAdminDeletingOthers = auth()->check() && auth()->user()->is_admin && $post->user_id !== auth()->id();
                        @endphp
                        <button type="button" class="menu-item {{ $isBroadcast ? 'context-admin' : ($isAdminDeletingOthers ? 'admin-delete' : '') }}" 
                                onclick="deletePost('{{ $post->slug }}', this)"
                                data-is-admin-delete="{{ ($isBroadcast || $isAdminDeletingOthers) ? 'true' : 'false' }}"
                                @if($isBroadcast) style="display: none;" @endif>
                            <i class="fas fa-trash"></i> 
                            {{ ($isBroadcast || $isAdminDeletingOthers) ? __('messages.admin_delete_post') : __('messages.delete_post') }}
                        </button>
                    @endif

                    @if($isBroadcast || !$isOwner)
                        <button type="button" class="menu-item {{ $isBroadcast ? 'context-not-owner' : '' }}" onclick="openReportModal('{{ $post->slug }}', '{{ $post->id }}')" @if($isBroadcast) style="display: none;" @endif>
                            <i class="fas fa-flag"></i> {{ __('messages.report_post') }}
                        </button>
                    @endif
                </div>
                @else
                @if($post->canDelete(auth()->user()))
                <button type="button" class="delete-post-btn" onclick="deletePost('{{ $post->slug }}', this)" title="{{ __('messages.delete_post') }}">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
                @endauth
            </div>
        </div>
    @endif

    @if($post->content)
        <div class="post-content">
            @php
                $content = $post->content_html;
                $contentLength = strlen(strip_tags($post->content));
                $shouldTruncate = $contentLength > 300;
                $truncatedContent = $shouldTruncate ? substr(strip_tags($post->content), 0, 300) . '...' : $post->content;
                if ($shouldTruncate) {
                    $truncatedContent = app(\App\Services\HashtagService::class)->linkify(app(\App\Services\MentionService::class)->convertMentionsToLinks($truncatedContent));
                }
                // Detect Arabic text for immediate RTL direction
                $isArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', strip_tags($post->content));
            @endphp
            <p class="post-text {{ $shouldTruncate ? 'truncated' : '' }}"
               style="{{ $isArabic ? 'direction:rtl;text-align:right;' : '' }}"
               data-full-content="{{ htmlspecialchars($content, ENT_QUOTES, 'UTF-8') }}"
               data-truncated-content="{{ htmlspecialchars($truncatedContent, ENT_QUOTES, 'UTF-8') }}">
                {!! $shouldTruncate ? $truncatedContent : $content !!}
            </p>
            @if($shouldTruncate)
                <button type="button" class="show-more-btn" onclick="togglePostContent(this)">
                    <span class="show-more-text">{{ __('messages.show_more') }}</span>
                    <span class="show-less-text" style="display: none;">{{ __('messages.show_less') }}</span>
                </button>
            @endif
        </div>
    @endif

    @if($post->media && $post->media->count() > 0)
        @php
            $mediaCount = $post->media->count();
            $remainingCount = $mediaCount - 4;
            $mediaData = $post->media->map(function($m, $index) {
                return [
                    'index' => $index,
                    'type' => $m->media_type,
                    'src' => asset('storage/' . $m->media_path)
                ];
            });
        @endphp
        <div class="post-media fb-grid fb-grid-{{ $mediaCount > 4 ? 4 : $mediaCount }}" 
             data-post-id="{{ $post->id }}" data-post-slug="{{ $post->slug }}" 
             data-media-count="{{ $mediaCount }}"
             data-media-list="{{ json_encode($mediaData) }}">
            @foreach($post->media as $index => $media)
                @if($index < 4)
                    @if($media->media_type === 'image')
                        <div class="media-item {{ $index === 3 && $remainingCount > 0 ? 'has-more' : '' }}">
                            <img src="{{ asset('storage/' . $media->media_path) }}" alt="{{ __('messages.post_image') }}" loading="lazy" data-media-index="{{ $index }}">
                            @if($index === 3 && $remainingCount > 0)
                                <div class="more-overlay">
                                    <span class="more-count">+{{ $remainingCount }}</span>
                                </div>
                            @endif
                            <div class="media-click-catcher" onclick="openMediaModal('{{ $post->id }}', '{{ $index }}')" style="position: absolute; inset: 0; z-index: 20; cursor: pointer; background: rgba(0,0,0,0);"></div>
                        </div>
                    @elseif($media->media_type === 'video')
                        <div class="media-item video-indicator {{ $index === 3 && $remainingCount > 0 ? 'has-more' : '' }}">
                            <video preload="none" playsinline muted style="width: 100%; height: 100%; object-fit: cover;">
                                <source src="{{ asset('storage/' . $media->media_path) }}" type="video/mp4">
                            </video>
                            <div class="video-play-button">
                                <i class="fas fa-play"></i>
                            </div>
                            @if($index === 3 && $remainingCount > 0)
                                <div class="more-overlay">
                                    <span class="more-count">+{{ $remainingCount }}</span>
                                </div>
                            @endif
                            <div class="media-click-catcher" onclick="openMediaModal('{{ $post->id }}', '{{ $index }}')" style="position: absolute; inset: 0; z-index: 20; cursor: pointer; background: rgba(0,0,0,0);"></div>
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    @endif

    {{-- Reaction & Comments Summary Bar --}}
    <div class="post-engagement-bar">
        @if($totalReactions > 0)
            <span class="reaction-summary-bar" onclick="openPostReactorsModal('{{ $post->slug }}')" style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <span class="reaction-emojis-display" style="display: flex; gap: -4px; align-items: center;">
                    @foreach($groupedReactions as $reaction)
                        @php $imgPath = \App\Models\Post::REACTION_IMAGES[$reaction['reaction_type']] ?? null; @endphp
                        @if($imgPath)
                            <span class="reaction-emoji-count" data-reaction="{{ $reaction['reaction_type'] }}" style="background: var(--surface); border-radius: 50%; padding: 0; margin-right: -6px; border: 1px solid var(--border); width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; position: relative; z-index: {{ 10 - $loop->index }}; overflow: hidden;">
                                <img src="{{ $imgPath }}" alt="{{ $reaction['reaction_type'] }}" style="width: 100%; height: 100%; object-fit: contain;">
                            </span>
                        @else
                            <span class="reaction-emoji-count" data-reaction="{{ $reaction['reaction_type'] }}" style="background: var(--surface); border-radius: 50%; padding: 2px; margin-right: -6px; border: 1px solid var(--border); width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; position: relative; z-index: {{ 10 - $loop->index }};">
                                {{ $reaction['reaction_type'] }}
                            </span>
                        @endif
                    @endforeach
                </span>
                <span class="reaction-total-count">{{ $totalReactions }}</span>
            </span>
        @else
            <span></span>
        @endif
        <span class="engagement-comments">
            {{ number_format($post->comments_count ?? $post->comments->count()) }} {{ __('messages.comments') }}
        </span>
    </div>

    <div class="post-actions">
        @if(auth()->check())
            <div class="left-actions">
                <div class="react-btn-wrapper" style="position: relative;">
                    <button type="button" class="action-btn react-btn {{ $userReaction ? 'reacted' : '' }}"
                            data-current-reaction="{{ $userReaction ? $userReaction->reaction_type : '' }}"
                            onclick="togglePostReaction(this, '{{ $post->slug }}')"
                            title="{{ $userReaction ? \App\Models\Post::getReactionLabels()[$userReaction->reaction_type] : __('messages.react') }}">
                        @if($userReaction)
                            @php $userReactionImg = \App\Models\Post::REACTION_IMAGES[$userReaction->reaction_type] ?? null; @endphp
                            @if($userReactionImg)
                                <span class="react-emoji"><img src="{{ $userReactionImg }}" alt="{{ $userReaction->reaction_type }}" style="width: 24px; height: 24px; vertical-align: middle;"></span>
                            @else
                                <span class="react-emoji">{{ $userReaction->reaction_type }}</span>
                            @endif
                        @else
                            <i class="far fa-smile"></i>
                        @endif
                    </button>
                    {{-- Reaction Picker Popup --}}
                    <div class="reaction-picker" style="display: none;" data-post-slug="{{ $post->slug }}">
                        <div class="reaction-picker-popup">
                            <div class="reaction-picker-options">
                                @foreach(\App\Models\Post::REACTION_EMOJIS as $emoji)
                                    @php $imgPath = \App\Models\Post::REACTION_IMAGES[$emoji] ?? null; @endphp
                                    <button type="button" class="reaction-option"
                                            data-emoji="{{ $emoji }}"
                                            data-label="{{ \App\Models\Post::getReactionLabels()[$emoji] }}"
                                            onclick="selectPostReaction(this, '{{ $post->slug }}', '{{ $emoji }}')">
                                        @if($imgPath)
                                            <img src="{{ $imgPath }}" alt="{{ $emoji }}" class="reaction-emoji">
                                        @else
                                            <span class="reaction-emoji">{{ $emoji }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="action-btn comment-btn" onclick="focusCommentInput('{{ $post->slug }}')">
                    <i class="far fa-comment"></i>
                </button>
                <button type="button" class="action-btn share-btn" onclick="sharePost('{{ $post->slug }}', '{{ $post->user->username }}')">
                    <i class="far fa-paper-plane"></i>
                </button>
            </div>
            <button type="button" class="action-btn save-btn {{ (auth()->check() && $post->userSavedPost) ? 'saved' : '' }}" onclick="toggleSave('{{ $post->slug }}', this)">
                <i class="{{ (auth()->check() && $post->userSavedPost) ? 'fas' : 'far' }} fa-bookmark"></i>
            </button>
        @else
            <div class="left-actions">
                <button type="button" class="action-btn" onclick="showLoginModal('like', '{{ __('messages.like_posts_prompt') }}')" data-like-btn>
                    <i class="far fa-heart"></i>
                </button>
                <button type="button" class="action-btn" onclick="showLoginModal('comment', '{{ __('messages.comment_posts_prompt') }}')">
                    <i class="far fa-comment"></i>
                </button>
                <button type="button" class="action-btn" onclick="sharePost('{{ $post->slug }}', '{{ $post->user->username }}')">
                    <i class="far fa-paper-plane"></i>
                </button>
            </div>
            <button type="button" class="action-btn">
                <i class="far fa-bookmark"></i>
            </button>
        @endif
    </div>

    <div class="post-comments-section">
        <div class="comments-list" data-comments-list>
            @php
                $sortedComments = $post->comments->sortByDesc('created_at');
                $visibleComments = $sortedComments->take(2);
                $hasMore = $sortedComments->count() > 2;
            @endphp

            @foreach($visibleComments as $comment)
                @include('partials.comment', ['comment' => $comment])
            @endforeach

            @if($hasMore)
                <div class="show-more-comments">
                    <button type="button" onclick="toggleComments({{ $post->id }}, true)">
                        {{ __('messages.show_more_comments', ['count' => $sortedComments->count() - 2]) }}
                    </button>
                </div>
                <div class="hidden-comments" id="hidden-comments-{{ $post->id }}" style="display: none;">
                    @foreach($sortedComments->skip(2) as $comment)
                        @include('partials.comment', ['comment' => $comment])
                    @endforeach
                    <button type="button" class="hide-comments" onclick="toggleComments({{ $post->id }}, false)">
                        {{ __('messages.hide_comments') }}
                    </button>
                </div>
            @endif
        </div>

        @if(auth()->check())
            <div class="comment-form-wrapper">
                <div class="comment-form">
                    <textarea id="comment-content-{{ $post->slug }}" placeholder="{{ __('messages.write_a_comment') }}" maxlength="5000"></textarea>
                    <button type="button" onclick="submitComment('{{ $post->slug }}', {{ $post->id }})">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                @if($post->social_group_id)
                    @php
                        $isAnonDefault = \App\Models\SocialGroupMember::where('social_group_id', $post->social_group_id)
                            ->where('user_id', auth()->id())
                            ->where('status', 'approved')
                            ->value('is_anonymous_default') ?? false;
                    @endphp
                    <div class="comment-options">
                        <label class="anon-toggle">
                            <input type="checkbox" id="comment-anon-{{ $post->slug }}" {{ $isAnonDefault ? 'checked' : '' }}>
                            <span class="anon-label"><i class="fas fa-user-secret"></i> {{ __('messages.post_anonymously') }}</span>
                        </label>
                    </div>
                @endif
            </div>
        @else
            <div class="guest-message">
                <p><a href="{{ route('login') }}">{{ __('messages.login') }}</a> {{ __('messages.to_comment') }}</p>
            </div>
        @endif
    </div>

    {{-- Post Reactors Modal --}}
    <div class="reactors-modal" id="post-reactors-modal-{{ $post->slug }}">
        <div class="reactors-modal-overlay" onclick="closePostReactorsModal()"></div>
        <div class="reactors-modal-content">
            <div class="reactors-modal-header">
                <h3>{{ __('messages.reactions') }}</h3>
                <button type="button" class="reactors-modal-close" onclick="closePostReactorsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="reactors-modal-body" id="post-reactors-modal-body-{{ $post->slug }}">
                <div class="reactors-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
        </div>
    </div>
</div>
    <div id="post-translations" style="display:none;">{"follow":"{{ __('messages.follow') }}","following":"{{ __('messages.following') }}","delete_post_confirm":"{{ __('messages.delete_post_confirm') }}","post_deleted":"{{ __('messages.post_deleted') }}","failed_to_delete_post":"{{ __('messages.failed_to_delete_post') }}","delete_comment_confirm":"{{ __('messages.delete_comment_confirm') }}","anonymous_participant":"{{ __('messages.anonymous_participant') }}","just_now":"{{ __('messages.just_now') }}","reply":"{{ __('messages.reply') }}","no_posts_yet":"{{ __('messages.no_posts_yet') }}","be_first_to_post":"{{ __('messages.be_first_to_post') }}","write_a_reply":"{{ __('messages.write_a_reply') }}","write_a_comment":"{{ __('messages.write_a_comment') }}","cancel":"{{ __('messages.cancel') }}","please_write_comment":"{{ __('messages.please_write_comment') }}","please_write_reply":"{{ __('messages.please_write_reply') }}","failed_to_post_reply":"{{ __('messages.failed_to_post_reply') }}","post_pinned":"{{ __('messages.post_pinned') }}","post_unpinned":"{{ __('messages.post_unpinned') }}","failed_to_pin_post":"{{ __('messages.failed_to_pin_post') }}","failed_to_unpin_post":"{{ __('messages.failed_to_unpin_post') }}","done":"{{ __('messages.done') }}","reorder":"{{ __('messages.reorder') }}"}</div>

@pushonce('styles')
<link rel="stylesheet" href="{{ asset('css/partial-posts.css') }}">
@endpushonce

<style>
@keyframes floatUp {
    0% {
        opacity: 1;
        transform: translateY(0) scale(1) rotate(0deg);
    }
    50% {
        opacity: 1;
        transform: translateY(-60px) scale(1.3) rotate(10deg);
    }
    100% {
        opacity: 0;
        transform: translateY(-150px) scale(1.5) rotate(-5deg);
    }
}

.post-card .react-btn.reacted {
    background: transparent;
    color: var(--primary) !important;
    font-weight: 700;
}

.post-card .react-btn.reacted .react-emoji {
    animation: reactEmojiPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: inline-block;
    background: transparent !important;
}

@keyframes reactEmojiPop {
    0% { transform: scale(0); }
    70% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

.post-card .action-btn.react-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    font-size: 1rem;
    line-height: 1;
    border-radius: 999px;
    transition: all 0.2s;
}

.post-card .action-btn.react-btn:hover {
    background: rgba(255, 255, 255, 0.05);
}

[data-theme="light"] .post-card .action-btn.react-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.post-card .react-btn span.react-emoji {
    display: inline !important;
    font-size: 1.15rem;
    line-height: 1;
}

/* Reaction picker active indicator */
.reaction-option {
    position: relative;
    padding: 2px;
    min-width: 36px;
    height: 36px;
}

.reaction-option .reaction-emoji {
    width: 32px;
    height: 32px;
    object-fit: contain;
    display: block;
}

.reaction-option.active-reaction {
    background: rgba(94, 96, 206, 0.15);
    border: none;
    transform: translateY(-5px) scale(1.15);
    z-index: 10;
}

.reaction-option.active-reaction::before {
    content: '';
    position: absolute;
    inset: -2px;
    background: linear-gradient(135deg, var(--primary), var(--primary-glow));
    border-radius: 50%;
    z-index: -1;
    opacity: 0.8;
    filter: blur(4px);
    animation: activeGlowRotate 3s linear infinite;
}

@keyframes activeGlowRotate {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.1); opacity: 0.5; }
    100% { transform: scale(1); opacity: 0.8; }
}

.reaction-option.active-reaction::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 12px;
    height: 3px;
    background: var(--primary);
    border-radius: 10px;
    box-shadow: 0 0 10px var(--primary);
}

@media (max-width: 768px) {
    .reaction-option {
        padding: 2px;
        min-width: 30px;
        height: 30px;
    }

    .reaction-option .reaction-emoji {
        width: 26px;
        height: 26px;
    }
}

@media (max-width: 480px) {
    .reaction-option {
        padding: 1px;
        min-width: 26px;
        height: 26px;
    }

    .reaction-option .reaction-emoji {
        width: 22px;
        height: 22px;
    }
}

/* Reactors Modal */
.reactors-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 11000;
    align-items: center;
    justify-content: center;
}

.reactors-modal.active {
    display: flex;
}

.reactors-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.reactors-modal-content {
    position: relative;
    background: #1a1a1a;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    width: 95%;
    max-width: 360px;
    max-height: 70vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    overflow: hidden;
}

@keyframes modalPop {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.reactors-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.reactors-modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    background: linear-gradient(135deg, #fff 0%, #a1a1aa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.reactors-modal-close {
    background: rgba(255, 255, 255, 0.05);
    border: none;
    color: #a1a1aa;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.reactors-modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    transform: rotate(90deg);
}

.reactors-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    background: #1a1a1a;
}

/* Tabs */
.reactors-tabs {
    display: flex;
    gap: 8px;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    overflow-x: auto;
    scrollbar-width: none;
}

.reactors-tabs::-webkit-scrollbar { display: none; }

.reactor-tab {
    background: transparent;
    border: none;
    color: #71717a;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.reactor-tab span {
    font-size: 0.75rem;
    opacity: 0.6;
}

.reactor-tab:hover {
    background: rgba(255, 255, 255, 0.03);
    color: #e4e4e7;
}

.reaction-summary-bar {
    background: rgba(255, 255, 255, 0.05);
    padding: 2px 8px;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(4px);
}

.reaction-summary-bar:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.reaction-emojis-display {
    display: flex;
    align-items: center;
}

.reaction-emoji-count {
    transition: transform 0.2s ease;
    cursor: pointer;
}

.reaction-emoji-count:hover {
    transform: scale(1.2) z-index: 20;
}

.reaction-total-count {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    margin-left: 3px;
}

.reactor-tab.active {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}

/* List */
.reactors-list {
    padding: 8px 12px;
}

.reactor-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-radius: 16px;
    transition: background 0.2s;
    margin-bottom: 4px;
}

.reactor-item:hover {
    background: rgba(255, 255, 255, 0.03);
}

.reactor-user-info {
    display: flex;
    align-items: center;
    gap: 14px;
}

.reactor-avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    object-fit: cover;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.reactor-avatar-placeholder {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.2rem;
}

.reactor-name {
    font-weight: 600;
    color: #e4e4e7;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.reactor-name:hover {
    color: #fff;
}

.reactor-emoji-badge {
    font-size: 1.2rem;
    background: rgba(255, 255, 255, 0.03);
    padding: 6px;
    border-radius: 12px;
    line-height: 1;
}

.reactors-empty {
    padding: 60px 20px;
    text-align: center;
    color: #52525b;
}

.reactors-empty i {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.3;
}

.reactors-empty p {
    font-weight: 500;
    margin: 0;
}

.reactors-loading {
    display: flex;
    justify-content: center;
    padding: 60px;
    color: #3b82f6;
    font-size: 2rem;
}

/* Light Theme Overrides */
[data-theme="light"] .reactors-modal-content {
    background: #ffffff;
    border-color: rgba(0, 0, 0, 0.1);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}

[data-theme="light"] .reactors-modal-header {
    background: rgba(0, 0, 0, 0.02);
    border-bottom-color: rgba(0, 0, 0, 0.08);
}

[data-theme="light"] .reactors-modal-header h3 {
    background: linear-gradient(135deg, #111b21 0%, #3b4a54 100%);
    -webkit-background-clip: text;
}

[data-theme="light"] .reactors-modal-close {
    background: rgba(0, 0, 0, 0.05);
    color: #54656f;
}

[data-theme="light"] .reactors-modal-close:hover {
    background: rgba(0, 0, 0, 0.1);
    color: #111b21;
}

[data-theme="light"] .reactors-modal-body {
    background: #ffffff;
}

[data-theme="light"] .reactors-tabs {
    border-bottom-color: rgba(0, 0, 0, 0.05);
}

[data-theme="light"] .reactor-tab {
    color: #54656f;
}

[data-theme="light"] .reactor-tab:hover {
    background: rgba(0, 0, 0, 0.03);
    color: #111b21;
}

[data-theme="light"] .reactor-tab.active {
    background: rgba(0, 0, 0, 0.08);
    color: #111b21;
}

[data-theme="light"] .reactor-item:hover {
    background: rgba(0, 0, 0, 0.03);
}

[data-theme="light"] .reactor-name {
    color: #111b21;
}

[data-theme="light"] .reactor-name:hover {
    color: #000;
}

[data-theme="light"] .reactor-emoji-badge {
    background: rgba(0, 0, 0, 0.03);
}

[data-theme="light"] .reactors-empty {
    color: #8696a0;
}
</style>


{{-- Style removed --}}

