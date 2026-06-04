@php
    $level = $level ?? 0;
    $maxLevel = 4;
    $isLiked = auth()->check() ? $comment->likedBy(auth()->user()) : false;
    $likesCount = $comment->likes->count();
    $repliesCount = $comment->replies ? $comment->replies->count() : 0;
@endphp

<div class="comment-item {{ $level > 0 ? 'nested' : '' }} level-{{ $level }}{{ $comment->is_anonymous ? ' is-anonymous' : '' }}" data-comment-id="{{ $comment->id }}" id="comment-{{ $comment->id }}">
    <div class="comment-header">
        <div class="comment-author">
            @if($comment->is_anonymous)
                <div class="comment-avatar-placeholder anonymous" aria-hidden="true"><i class="fas fa-user-secret"></i></div>
                <div class="comment-author-info">
                    <span class="comment-name anonymous-name">{{ __('messages.anonymous_participant') }}</span>
                    <span class="comment-time" data-timestamp="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $comment->created_at->diffForHumans(null, true, true) }}</span>
                </div>
            @else
                <a href="{{ route('users.show', $comment->user) }}" style="flex-shrink:0;display:flex;"><img src="{{ $comment->user->avatar_url }}" alt="" class="comment-avatar" style="pointer-events:none;"></a>
                <div class="comment-author-info">
                    <div class="comment-name-row">
                        <a href="{{ route('users.show', $comment->user) }}" class="comment-name" style="display:inline-flex;align-items:center;gap:.2em;">{{ $comment->user->username }}<x-verified-badge :user="$comment->user" size=".85em" /></a>

                        {{-- Commenter Role Badges --}}
                        @php $role = $comment->author_role; @endphp
                        @if($role === 'admin')
                            <span class="role-badge-pill admin-pill mini" title="{{ __('messages.community_admin') }}">
                                <i class="fas fa-crown" aria-hidden="true"></i>
                                <span>{{ __('messages.role_admin') }}</span>
                            </span>
                        @elseif($role === 'moderator')
                            <span class="role-badge-pill moderator-pill mini" title="{{ __('messages.community_moderator') }}">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                <span>{{ __('messages.role_moderator') }}</span>
                            </span>
                        @endif
                    </div>
                    <span class="comment-time" data-timestamp="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $comment->created_at->diffForHumans(null, true, true) }}</span>
                </div>
            @endif
        </div>
        @if(auth()->check() && $comment->user_id === auth()->id())
            <button type="button" class="delete-comment-btn" onclick="deleteComment({{ $comment->id }}, this)" title="{{ __('messages.delete_comment') }}" aria-label="{{ __('messages.delete_comment') }}">
                <i class="fas fa-trash-alt" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    <div class="comment-content">
        @php
            $isArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', strip_tags($comment->content));
        @endphp
        <p dir="auto" style="{{ $isArabic ? 'direction: rtl; text-align: right;' : '' }}">
            {!! app(\App\Services\MentionService::class)->convertMentionsToLinks($comment->content) !!}
        </p>
    </div>

    <div class="comment-actions-bar">
        @if(auth()->check())
            <button type="button"
                    class="comment-action-btn comment-like-btn {{ $isLiked ? 'liked' : '' }}"
                    onclick="likeComment({{ $comment->id }}, this)"
                    aria-label="{{ $isLiked ? (__('messages.unlike') ?? 'Unlike') : (__('messages.like') ?? 'Like') }}"
                    aria-pressed="{{ $isLiked ? 'true' : 'false' }}">
                <span class="like-burst-wrap" aria-hidden="true">
                    <i class="{{ $isLiked ? 'fas' : 'far' }} fa-heart like-heart"></i>
                </span>
                <span class="comment-likes-count">{{ $likesCount }}</span>
            </button>
            @if($level < $maxLevel)
                <button type="button" class="comment-action-btn comment-reply-btn" onclick="toggleReplyForm({{ $comment->id }})" aria-label="{{ __('messages.reply') }}" aria-expanded="false" aria-controls="reply-form-{{ $comment->id }}">
                    <i class="fas fa-reply" aria-hidden="true"></i>
                    <span>{{ __('messages.reply') }}</span>
                </button>
            @endif
        @else
            <button type="button" class="comment-action-btn" onclick="showLoginModal('like', '{{ __('messages.like_comments_prompt') }}')" aria-label="{{ __('messages.like') ?? 'Like' }}">
                <span class="like-burst-wrap" aria-hidden="true">
                    <i class="far fa-heart like-heart"></i>
                </span>
                <span class="comment-likes-count">{{ $likesCount }}</span>
            </button>
            @if($level < $maxLevel)
                <button type="button" class="comment-action-btn comment-reply-btn" onclick="showLoginModal('reply', '{{ __('messages.reply_comments_prompt') }}')" aria-label="{{ __('messages.reply') }}">
                    <i class="fas fa-reply" aria-hidden="true"></i>
                    <span>{{ __('messages.reply') }}</span>
                </button>
            @endif
        @endif
    </div>

    @if($level < $maxLevel)
        @php
            $parentAuthor = $comment->is_anonymous ? __('messages.anonymous_participant') : '@' . $comment->user->username;
            $isAnonDefault = false;
            if ($post->social_group_id && auth()->check()) {
                $isAnonDefault = (bool) \App\Models\SocialGroupMember::where('social_group_id', $post->social_group_id)
                    ->where('user_id', auth()->id())
                    ->where('status', 'approved')
                    ->value('is_anonymous_default');
            }
        @endphp
        <div class="reply-form reply-form--minimal" id="reply-form-{{ $comment->id }}" style="display: none;" data-parent-author="{{ $parentAuthor }}">
            <div class="reply-input-wrapper">
                @if(auth()->check())
                    <img src="{{ auth()->user()->avatar_url }}" alt="" class="reply-avatar" id="reply-avatar-{{ $comment->id }}">
                @endif
                <label for="reply-content-{{ $comment->id }}" class="visually-hidden">{{ __('messages.write_a_reply') }}</label>
                <textarea id="reply-content-{{ $comment->id }}" class="reply-textarea" placeholder="{{ __('messages.reply_to', ['user' => $parentAuthor]) ?? ('Reply to ' . $parentAuthor . '…') }}" dir="auto" maxlength="5000" rows="1" data-reply-submit-target="{{ $comment->id }}" data-reply-post-id="{{ $comment->post_id }}"></textarea>
                @if($post->social_group_id && auth()->check())
                    <label class="reply-anon-chip" title="{{ __('messages.post_anonymously') }}" aria-label="{{ __('messages.post_anonymously') }}">
                        <input type="checkbox" id="reply-anon-{{ $comment->id }}" {{ $isAnonDefault ? 'checked' : '' }} onchange="toggleReplyAnon({{ $comment->id }}, '{{ auth()->user()->avatar_url ?? '' }}')">
                        <i class="fas fa-user-secret" aria-hidden="true"></i>
                    </label>
                    @if($isAnonDefault)
                        <script>window.runOnPageLoad( function() { toggleReplyAnon({{ $comment->id }}, '{{ auth()->user()->avatar_url }}'); });</script>
                    @endif
                @endif
                <button type="button" class="reply-submit-btn" onclick="submitReply({{ $comment->id }}, {{ $comment->post_id }})" aria-label="{{ __('messages.submit') ?? 'Send' }}" disabled>
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                </button>
            </div>
            <div class="reply-hint" aria-hidden="true">
                <span>{{ __('messages.press_esc_to_cancel') ?? 'Esc to cancel' }} · {{ __('messages.cmd_enter_to_send') ?? '⌘ + Enter to send' }}</span>
            </div>
        </div>
    @endif

    @if($comment->replies && $repliesCount > 0)
        @php
            $hiddenReplies = $comment->replies->sortByDesc('created_at');
        @endphp

        <div class="replies-container">
            <div class="show-replies-always">
                <button type="button" class="show-replies-btn" onclick="toggleNestedReplies({{ $comment->id }}, true)" aria-expanded="false" aria-controls="hidden-replies-{{ $comment->id }}">
                    <i class="fas fa-chevron-down show-replies-chevron" aria-hidden="true"></i>
                    {{ $repliesCount == 1 ? __('messages.show_reply') : __('messages.show_replies', ['count' => $repliesCount]) }}
                </button>
            </div>
            <div class="hidden-replies" id="hidden-replies-{{ $comment->id }}" style="display: none;">
                @foreach($hiddenReplies as $reply)
                    @include('partials.comment-preview', ['comment' => $reply, 'level' => $level + 1])
                @endforeach
            </div>
        </div>
    @endif
</div>
