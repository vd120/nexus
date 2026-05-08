@php
    $level = $level ?? 0;
    $maxLevel = 4;
@endphp

<div class="comment-item {{ $level > 0 ? 'nested' : '' }} level-{{ $level }}" data-comment-id="{{ $comment->id }}" id="comment-{{ $comment->id }}">
    <div class="comment-header">
        <div class="comment-author">
            @if($comment->is_anonymous)
                <div class="comment-avatar-placeholder"><i class="fas fa-user-secret"></i></div>
                <div class="comment-author-info">
                    <span class="comment-name">{{ __('messages.anonymous_participant') }}</span>
                    <span class="comment-time" data-timestamp="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $comment->created_at->diffForHumans(null, true, true) }}</span>
                </div>
            @else
                <img src="{{ $comment->user->avatar_url }}" alt="Avatar" class="comment-avatar">
                <div class="comment-author-info">
                    <div class="comment-name-row">
                        <a href="{{ route('users.show', $comment->user) }}" class="comment-name">{{ $comment->user->username }}</a>
                        
                        {{-- Commenter Role Badges --}}
                        @php $role = $comment->author_role; @endphp
                        @if($role)
                            @if($role === 'admin')
                                <span class="role-badge-pill admin-pill mini" title="{{ __('messages.community_admin') }}">
                                    <i class="fas fa-crown"></i>
                                    <span>{{ __('messages.role_admin') }}</span>
                                </span>
                            @elseif($role === 'moderator')
                                <span class="role-badge-pill moderator-pill mini" title="{{ __('messages.community_moderator') }}">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>{{ __('messages.role_moderator') }}</span>
                                </span>
                            @endif
                        @endif
                    </div>
                    <span class="comment-time" data-timestamp="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $comment->created_at->diffForHumans(null, true, true) }}</span>
                </div>
            @endif
        </div>
        @if(auth()->check() && $comment->user_id === auth()->id())
            <button type="button" class="delete-comment-btn" onclick="deleteComment({{ $comment->id }}, this)" title="{{ __('messages.delete_comment') }}">
                <i class="fas fa-trash-alt"></i>
            </button>
        @endif
    </div>

    <div class="comment-content">
        @php
            $isArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', strip_tags($comment->content));
        @endphp
        <p style="{{ $isArabic ? 'direction: rtl; text-align: right;' : '' }}">
            {!! app(\App\Services\MentionService::class)->convertMentionsToLinks($comment->content) !!}
        </p>
    </div>

    <div class="comment-actions-bar">
        @if(auth()->check())
            <button type="button" class="comment-action-btn {{ $comment->likedBy(auth()->user()) ? 'liked' : '' }}" onclick="likeComment({{ $comment->id }}, this)">
                <i class="fas fa-heart"></i>
                <span class="comment-likes-count">{{ $comment->likes->count() }}</span>
            </button>
            @if($level < $maxLevel)
                <button type="button" class="comment-action-btn" onclick="toggleReplyForm({{ $comment->id }})">
                    <i class="fas fa-reply"></i>
                    <span>{{ __('messages.reply') }}</span>
                </button>
            @endif
        @else
            <button type="button" class="comment-action-btn" onclick="showLoginModal('like', '{{ __('messages.like_comments_prompt') }}')">
                <i class="fas fa-heart"></i>
                <span class="comment-likes-count">{{ $comment->likes->count() }}</span>
            </button>
            @if($level < $maxLevel)
                <button type="button" class="comment-action-btn" onclick="showLoginModal('reply', '{{ __('messages.reply_comments_prompt') }}')">
                    <i class="fas fa-reply"></i>
                    <span>{{ __('messages.reply') }}</span>
                </button>
            @endif
        @endif
    </div>

    @if($level < $maxLevel)
        <div class="reply-form" id="reply-form-{{ $comment->id }}" style="display: none;">
            <div class="reply-input-wrapper">
                @if(auth()->check())
                    <img src="{{ auth()->user()->avatar_url }}" alt="Your avatar" class="reply-avatar" id="reply-avatar-{{ $comment->id }}">
                @endif
                <textarea id="reply-content-{{ $comment->id }}" placeholder="{{ __('messages.write_a_reply') }}" maxlength="5000"></textarea>
                <button type="button" onclick="submitReply({{ $comment->id }}, {{ $comment->post_id }})">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="reply-options">
                @if($post->social_group_id)
                    @php
                        $isAnonDefault = \App\Models\SocialGroupMember::where('social_group_id', $post->social_group_id)
                            ->where('user_id', auth()->id())
                            ->where('status', 'approved')
                            ->value('is_anonymous_default') ?? false;
                    @endphp
                    <label class="anon-toggle">
                        <input type="checkbox" id="reply-anon-{{ $comment->id }}" {{ $isAnonDefault ? 'checked' : '' }} onchange="toggleReplyAnon({{ $comment->id }}, '{{ auth()->user()->avatar_url ?? '' }}')">
                        <span class="anon-label"><i class="fas fa-user-secret"></i> {{ __('messages.post_anonymously') }}</span>
                    </label>
                    @if($isAnonDefault)
                        <script>document.addEventListener('DOMContentLoaded', function() { toggleReplyAnon({{ $comment->id }}, '{{ auth()->user()->avatar_url }}'); });</script>
                    @endif
                @endif
                <button type="button" class="cancel-reply" onclick="toggleReplyForm({{ $comment->id }})">{{ __('messages.cancel') }}</button>
            </div>
        </div>
    @endif

    @if($comment->replies && $comment->replies->count() > 0)
        @php
            // Hide all replies initially, show button to reveal
            // Sort by newest first
            $hiddenReplies = $comment->replies->sortByDesc('created_at');
            $hasReplies = $hiddenReplies->count() > 0;
        @endphp

        <div class="replies-container">
            @if($hasReplies)
                <div class="show-replies-always">
                    <button type="button" class="show-replies-btn" onclick="toggleNestedReplies({{ $comment->id }}, true)">
                        {{ $comment->replies->count() == 1 ? __('messages.show_reply') : __('messages.show_replies', ['count' => $comment->replies->count()]) }}
                    </button>
                </div>
                <div class="hidden-replies" id="hidden-replies-{{ $comment->id }}" style="display: none;">
                    @foreach($hiddenReplies as $reply)
                        @include('partials.comment', ['comment' => $reply, 'level' => $level + 1])
                    @endforeach
                </div>
            @endif
        </div>
@endif
</div>
