@if(!isset($post) || !$post)
    @php return; @endphp
@endif

@php
    $userReaction = auth()->check() ? $post->userReaction : null;
    $totalReactions = $post->reactions_count ?? $post->reactions->count();
    $totalComments = $post->comments_count ?? $post->comments->count();
    $groupedReactions = $post->getGroupedReactions();
    $hasEngagement = $totalReactions > 0 || $totalComments > 0;
    $isPinnedPost = isset($isPinned) && $isPinned;
@endphp

<article class="post-card {{ $isPinnedPost ? 'pinned-post' : '' }} {{ $post->is_anonymous ? 'is-anonymous' : '' }} {{ isset($is_broadcast) && $is_broadcast ? 'viewer-context-needed' : '' }}"
         id="post-{{ $post->id }}"
         data-post-id="{{ $post->id }}"
         data-post-slug="{{ $post->slug }}"
         data-owner-id="{{ $post->user_id }}"
         aria-labelledby="post-author-{{ $post->id }}">

    @if(!$post->is_approved)
        <div class="pending-approval-badge">
            <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
            <span>{{ __('messages.pending_approval') }}</span>
            <span class="pending-approval-note">{{ __('messages.only_visible_to_you_and_admins') }}</span>
        </div>
    @endif

    @if($isPinnedPost)
        <div class="pinned-accent" aria-hidden="true"></div>
    @endif

    @if($post->social_group_id)
        @include('partials.group-post-header', [
            'post' => $post,
            'group' => $post->socialGroup,
            'hideGroupContext' => $hideGroupContext ?? false,
            'is_broadcast' => isset($is_broadcast) && $is_broadcast
        ])
    @else
        <header class="post-header">
            <div class="post-author">
                @if($post->is_anonymous)
                    <div class="author-avatar anonymous-avatar" aria-hidden="true">
                        <i class="fas fa-user-secret"></i>
                    </div>
                    <div class="author-info">
                        <div class="author-top-row">
                            <span class="author-name anonymous-name" id="post-author-{{ $post->id }}">{{ __('messages.anonymous_participant') }}</span>
                            @if($isPinnedPost)
                                <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" title="{{ __('users.pinned_to_profile') }}" aria-label="{{ __('users.pinned_to_profile') }}"></i>
                            @endif
                        </div>
                        <span class="post-time" data-timestamp="{{ $post->created_at->toIso8601String() }}">
                            <span class="time-text">{{ $post->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $post->created_at->diffForHumans(null, true, true) }}</span>
                            <span class="privacy-badge"><i class="fas fa-user-secret" aria-hidden="true"></i></span>
                        </span>
                    </div>
                @else
                    <a href="{{ route('users.show', $post->user) }}" class="author-avatar-link" style="flex-shrink:0;display:flex;"><img src="{{ $post->user->avatar_url }}" alt="" class="author-avatar" loading="lazy" decoding="async" style="pointer-events:none;"></a>
                    <div class="author-info">
                        <div class="author-top-row">
                            <a href="{{ route('users.show', $post->user) }}" class="author-name" id="post-author-{{ $post->id }}" style="display:inline-flex;align-items:center;gap:.2em;">{{ $post->user->name ?: $post->user->username }}<x-verified-badge :user="$post->user" size=".95em" /></a>
                            <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" title="{{ __('users.pinned_to_profile') }}" aria-label="{{ __('users.pinned_to_profile') }}" style="{{ $isPinnedPost ? '' : 'display: none;' }}"></i>
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
                            <a href="{{ route('users.show', $post->user) }}" class="author-handle">{{ '@' . $post->user->username }}</a>
                            <span class="time-sep" aria-hidden="true">·</span>
                            <span class="time-text">{{ $post->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $post->created_at->diffForHumans(null, true, true) }}</span>
                            @if($post->is_private)
                                <span class="privacy-badge" title="{{ __('messages.private') }}"><i class="fas fa-lock" aria-hidden="true"></i></span>
                            @else
                                <span class="privacy-badge public" title="{{ __('messages.public') }}"><i class="fas fa-globe" aria-hidden="true"></i></span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>
            <div class="post-header-actions">
                @auth
                <button type="button" class="post-menu-btn" onclick="togglePostMenu('{{ $post->id }}')" title="{{ __('messages.options') }}" aria-label="{{ __('messages.options') }}" aria-haspopup="menu">
                    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                </button>
                <div class="post-menu-dropdown" id="post-menu-{{ $post->id }}" role="menu" style="display: none;">
                    @php
                        $isBroadcast = isset($is_broadcast) && $is_broadcast;
                        $isOwner = auth()->check() && $post->user_id === auth()->id();
                    @endphp

                    @if($isBroadcast || $isOwner)
                        <button type="button" role="menuitem" id="pin-menu-item-{{ $post->id }}" class="menu-item {{ $isBroadcast ? 'context-owner' : '' }}" onclick="pinPost(event, {{ $post->id }})" style="{{ ($isBroadcast || !$post->isPinned()) ? '' : 'display: none;' }}">
                            <i class="fas fa-thumbtack" aria-hidden="true"></i> {{ __('users.pin_post') }}
                        </button>
                        <button type="button" role="menuitem" id="unpin-menu-item-{{ $post->id }}" class="menu-item menu-item-unpin {{ $isBroadcast ? 'context-owner' : '' }}" onclick="unpinPost(event, {{ $post->id }})" style="{{ ($isBroadcast || $post->isPinned()) ? '' : 'display: none;' }}">
                            <i class="fas fa-thumbtack" aria-hidden="true"></i> {{ __('users.unpin_post') }}
                        </button>
                        <button type="button" role="menuitem" class="menu-item {{ $isBroadcast ? 'context-owner' : '' }}" onclick="deletePost('{{ $post->slug }}', this)" @if($isBroadcast) style="display: none;" @endif>
                            <i class="fas fa-trash" aria-hidden="true"></i> {{ __('messages.delete_post') }}
                        </button>
                    @endif

                    @if($isBroadcast || ($post->canDelete(auth()->user()) && !$isOwner))
                        @php
                            $isAdminDeletingOthers = auth()->check() && auth()->user()->is_admin && $post->user_id !== auth()->id();
                        @endphp
                        <button type="button" role="menuitem" class="menu-item {{ $isBroadcast ? 'context-admin' : ($isAdminDeletingOthers ? 'admin-delete' : '') }}"
                                onclick="deletePost('{{ $post->slug }}', this)"
                                data-is-admin-delete="{{ ($isBroadcast || $isAdminDeletingOthers) ? 'true' : 'false' }}"
                                @if($isBroadcast) style="display: none;" @endif>
                            <i class="fas fa-trash" aria-hidden="true"></i>
                            {{ ($isBroadcast || $isAdminDeletingOthers) ? __('messages.admin_delete_post') : __('messages.delete_post') }}
                        </button>
                    @endif

                    @if($isBroadcast || !$isOwner)
                        <button type="button" role="menuitem" class="menu-item {{ $isBroadcast ? 'context-not-owner' : '' }}" onclick="openReportModal('{{ $post->slug }}', '{{ $post->id }}')" @if($isBroadcast) style="display: none;" @endif>
                            <i class="fas fa-flag" aria-hidden="true"></i> {{ __('messages.report_post') }}
                        </button>
                    @endif
                </div>
                @else
                @if($post->canDelete(auth()->user()))
                <button type="button" class="delete-post-btn" onclick="deletePost('{{ $post->slug }}', this)" title="{{ __('messages.delete_post') }}" aria-label="{{ __('messages.delete_post') }}">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
                @endif
                @endauth
            </div>
        </header>
    @endif

    @if($post->content)
        <div class="post-content">
            @php
                $stripped = strip_tags($post->content);
                $shouldTruncate = mb_strlen($stripped) > 300;
                $content = $post->content_html;
                $truncatedStripped = $shouldTruncate ? \Illuminate\Support\Str::limit($stripped, 300, '…') : $stripped;
                $truncatedContent = $shouldTruncate
                    ? app(\App\Services\HashtagService::class)->linkify(app(\App\Services\MentionService::class)->convertMentionsToLinks($truncatedStripped))
                    : $content;
                $isArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $stripped);
            @endphp
            <p class="post-text"
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

    @if($post->poll)
        @include('partials.poll', ['poll' => $post->poll->load('options'), 'post' => $post])
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
                            <img src="{{ asset('storage/' . $media->media_path) }}" alt="{{ __('messages.post_image') }}" loading="lazy" decoding="async" data-media-index="{{ $index }}">
                            @if($index === 3 && $remainingCount > 0)
                                <div class="more-overlay">
                                    <span class="more-count">+{{ $remainingCount }}</span>
                                </div>
                            @endif
                            <button type="button" class="media-click-catcher" onclick="openMediaModal('{{ $post->id }}', '{{ $index }}')" aria-label="{{ __('messages.view_media') ?? __('messages.post_image') }}"></button>
                        </div>
                    @elseif($media->media_type === 'video')
                        <div class="media-item video-indicator {{ $index === 3 && $remainingCount > 0 ? 'has-more' : '' }}">
                            <video preload="metadata"
                                   poster="{{ $media->media_thumbnail ? asset('storage/' . $media->media_thumbnail) : '' }}"
                                   playsinline muted>
                                <source src="{{ asset('storage/' . $media->media_path) }}" type="video/mp4">
                            </video>
                            <div class="video-play-button" aria-hidden="true">
                                <i class="fas fa-play"></i>
                            </div>
                            @if($index === 3 && $remainingCount > 0)
                                <div class="more-overlay">
                                    <span class="more-count">+{{ $remainingCount }}</span>
                                </div>
                            @endif
                            <button type="button" class="media-click-catcher" onclick="openMediaModal('{{ $post->id }}', '{{ $index }}')" aria-label="{{ __('messages.view_media') ?? __('messages.post_image') }}"></button>
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    @endif

    @if($hasEngagement)
        <div class="post-engagement-bar">
            @if($totalReactions > 0)
                <button type="button" class="reaction-summary-bar" onclick="openPostReactorsModal('{{ $post->slug }}')" aria-label="{{ __('messages.reactions') }}: {{ $totalReactions }}">
                    <span class="reaction-emojis-display">
                        @foreach($groupedReactions as $reaction)
                            @php $imgPath = \App\Models\Post::REACTION_IMAGES[$reaction['reaction_type']] ?? null; @endphp
                            <span class="reaction-emoji-count" data-reaction="{{ $reaction['reaction_type'] }}" data-count="{{ $reaction['count'] }}" style="z-index: {{ 10 - $loop->index }};">
                                @if($imgPath)
                                    <img src="{{ $imgPath }}" alt="{{ $reaction['reaction_type'] }}">
                                @else
                                    {{ $reaction['reaction_type'] }}
                                @endif
                            </span>
                        @endforeach
                    </span>
                    <span class="reaction-total-count">{{ $totalReactions }}</span>
                </button>
            @endif
            @if($totalComments > 0)
                <span class="engagement-comments">
                    {{ number_format($totalComments) }} {{ __('messages.comments') }}
                </span>
            @endif
        </div>
    @endif

    <div class="post-actions">
        @if(auth()->check())
            <div class="left-actions">
                <div class="react-btn-wrapper">
                    <button type="button"
                            class="action-btn react-btn {{ $userReaction ? 'reacted' : '' }}"
                            data-current-reaction="{{ $userReaction ? $userReaction->reaction_type : '' }}"
                            onclick="togglePostReaction(this, '{{ $post->slug }}')"
                            title="{{ $userReaction ? \App\Models\Post::getReactionLabels()[$userReaction->reaction_type] : __('messages.react') }}"
                            aria-label="{{ $userReaction ? \App\Models\Post::getReactionLabels()[$userReaction->reaction_type] : __('messages.react') }}"
                            aria-haspopup="menu">
                        @if($userReaction)
                            @php $userReactionImg = \App\Models\Post::REACTION_IMAGES[$userReaction->reaction_type] ?? null; @endphp
                            @if($userReactionImg)
                                <span class="react-emoji"><img src="{{ $userReactionImg }}" alt="{{ $userReaction->reaction_type }}"></span>
                            @else
                                <span class="react-emoji">{{ $userReaction->reaction_type }}</span>
                            @endif
                        @else
                            <i class="far fa-heart" aria-hidden="true"></i>
                        @endif
                    </button>
                    {{-- Reaction Picker Popup --}}
                    <div class="reaction-picker" style="display: none;" data-post-slug="{{ $post->slug }}" role="menu" aria-label="{{ __('messages.react') }}">
                        <div class="reaction-picker-popup">
                            <div class="reaction-picker-options">
                                @foreach(\App\Models\Post::POST_REACTION_EMOJIS as $emoji)
                                    @php $imgPath = \App\Models\Post::REACTION_IMAGES[$emoji] ?? null; @endphp
                                    <button type="button" class="reaction-option"
                                            role="menuitem"
                                            data-emoji="{{ $emoji }}"
                                            data-label="{{ \App\Models\Post::getReactionLabels()[$emoji] }}"
                                            aria-label="{{ \App\Models\Post::getReactionLabels()[$emoji] }}"
                                            onclick="selectPostReaction(this, '{{ $post->slug }}', '{{ $emoji }}')">
                                        @if($imgPath)
                                            <img src="{{ $imgPath }}" alt="" class="reaction-emoji">
                                        @else
                                            <span class="reaction-emoji">{{ $emoji }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="action-btn comment-btn" onclick="focusCommentInput('{{ $post->slug }}')" aria-label="{{ __('messages.write_a_comment') }}">
                    <i class="far fa-comment" aria-hidden="true"></i>
                </button>
                <button type="button" class="action-btn share-btn" onclick="sharePost('{{ $post->slug }}', '{{ $post->user->username }}')" aria-label="{{ __('messages.share_post') ?? __('messages.share') ?? 'Share' }}">
                    <i class="far fa-share-from-square" aria-hidden="true"></i>
                </button>
            </div>
            <button type="button"
                    class="action-btn save-btn {{ (auth()->check() && $post->userSavedPost) ? 'saved' : '' }}"
                    onclick="toggleSave('{{ $post->slug }}', this)"
                    aria-label="{{ (auth()->check() && $post->userSavedPost) ? (__('messages.unsave') ?? 'Unsave') : (__('messages.save') ?? 'Save') }}"
                    aria-pressed="{{ (auth()->check() && $post->userSavedPost) ? 'true' : 'false' }}">
                <i class="{{ (auth()->check() && $post->userSavedPost) ? 'fas' : 'far' }} fa-bookmark" aria-hidden="true"></i>
            </button>
        @else
            <div class="left-actions">
                <button type="button" class="action-btn" onclick="showLoginModal('like', '{{ __('messages.like_posts_prompt') }}')" data-like-btn aria-label="{{ __('messages.react') }}">
                    <i class="far fa-heart" aria-hidden="true"></i>
                </button>
                <button type="button" class="action-btn" onclick="showLoginModal('comment', '{{ __('messages.comment_posts_prompt') }}')" aria-label="{{ __('messages.write_a_comment') }}">
                    <i class="far fa-comment" aria-hidden="true"></i>
                </button>
                <button type="button" class="action-btn" onclick="sharePost('{{ $post->slug }}', '{{ $post->user->username }}')" aria-label="{{ __('messages.share_post') ?? 'Share' }}">
                    <i class="far fa-share-from-square" aria-hidden="true"></i>
                </button>
            </div>
            <button type="button" class="action-btn" aria-label="{{ __('messages.save') ?? 'Save' }}">
                <i class="far fa-bookmark" aria-hidden="true"></i>
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
                @include('partials.comment-preview', ['comment' => $comment])
            @endforeach

            @if($hasMore)
                <div class="show-more-comments">
                    <button type="button" onclick="toggleComments({{ $post->id }}, true)">
                        {{ __('messages.show_more_comments', ['count' => $sortedComments->count() - 2]) }}
                    </button>
                </div>
                <div class="hidden-comments" id="hidden-comments-{{ $post->id }}" style="display: none;">
                    @foreach($sortedComments->skip(2) as $comment)
                        @include('partials.comment-preview', ['comment' => $comment])
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
                    <label for="comment-content-{{ $post->slug }}" class="visually-hidden">{{ __('messages.write_a_comment') }}</label>
                    <textarea id="comment-content-{{ $post->slug }}" placeholder="{{ __('messages.write_a_comment') }}" maxlength="5000"></textarea>
                    <button type="button" onclick="submitComment('{{ $post->slug }}', {{ $post->id }})" aria-label="{{ __('messages.submit') ?? 'Send' }}">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
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
                            <span class="anon-label"><i class="fas fa-user-secret" aria-hidden="true"></i> {{ __('messages.post_anonymously') }}</span>
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
</article>

{{-- Post Reactors Modal moved outside to prevent containing block / blur issues --}}
<div class="reactors-modal" id="post-reactors-modal-{{ $post->slug }}" role="dialog" aria-modal="true" aria-label="{{ __('messages.reactions') }}">
    <div class="reactors-modal-overlay" onclick="closePostReactorsModal()"></div>
    <div class="reactors-modal-content">
        <div class="reactors-modal-header">
            <h3>{{ __('messages.reactions') }}</h3>
            <button type="button" class="reactors-modal-close" onclick="closePostReactorsModal()" aria-label="{{ __('messages.close') ?? 'Close' }}">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="reactors-modal-body" id="post-reactors-modal-body-{{ $post->slug }}">
            <div class="reactors-loading" aria-live="polite">
                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            </div>
        </div>
    </div>
</div>

<div id="post-translations" style="display:none;">{"follow":"{{ __('messages.follow') }}","following":"{{ __('messages.following') }}","delete_post_confirm":"{{ __('messages.delete_post_confirm') }}","post_deleted":"{{ __('messages.post_deleted') }}","failed_to_delete_post":"{{ __('messages.failed_to_delete_post') }}","delete_comment_confirm":"{{ __('messages.delete_comment_confirm') }}","anonymous_participant":"{{ __('messages.anonymous_participant') }}","just_now":"{{ __('messages.just_now') }}","reply":"{{ __('messages.reply') }}","no_posts_yet":"{{ __('messages.no_posts_yet') }}","be_first_to_post":"{{ __('messages.be_first_to_post') }}","write_a_reply":"{{ __('messages.write_a_reply') }}","reply_to":"{{ __('messages.reply_to', ['user' => ':user']) }}","write_a_comment":"{{ __('messages.write_a_comment') }}","cancel":"{{ __('messages.cancel') }}","please_write_comment":"{{ __('messages.please_write_comment') }}","please_write_reply":"{{ __('messages.please_write_reply') }}","failed_to_post_reply":"{{ __('messages.failed_to_post_reply') }}","post_pinned":"{{ __('messages.post_pinned') }}","post_unpinned":"{{ __('messages.post_unpinned') }}","failed_to_pin_post":"{{ __('messages.failed_to_pin_post') }}","failed_to_unpin_post":"{{ __('messages.failed_to_unpin_post') }}","done":"{{ __('messages.done') }}","reorder":"{{ __('messages.reorder') }}"}</div>
