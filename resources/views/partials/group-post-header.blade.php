<div class="post-header">
    <div class="post-author" style="display: flex !important; align-items: flex-start !important;">
        @if($post->is_anonymous)
            <div class="author-avatar anonymous-avatar">
                <i class="fas fa-user-secret"></i>
            </div>
            <div class="author-info" style="display: flex; flex-direction: column; align-items: flex-start; margin-top: -3px;">
                <div class="author-top-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <span class="author-name anonymous-name">{{ __('messages.anonymous_participant') }}</span>
                    @if(isset($group) && $group && (!isset($hideGroupContext) || !$hideGroupContext))
                        <span class="header-in-word">in</span>
                        <a href="{{ route('communities.show', $group->slug) }}" class="group-context-name"><i class="fas fa-users"></i> {{ $group->name }}</a>
                    @endif
                    <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" style="margin-left: 6px; font-size: 13px; color: var(--primary); transform: rotate(45deg); opacity: 0.9; {{ $post->isPinned() ? '' : 'display: none;' }}" title="{{ __('users.pinned_to_profile') }}"></i>
                </div>
                <span class="post-time" data-timestamp="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $post->created_at->diffForHumans(null, true, true) }}</span>
            </div>
        @else
            <a href="{{ route('users.show', $post->user) }}" style="flex-shrink:0;display:flex;"><img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->username }}" class="author-avatar" style="pointer-events:none;"></a>
            <div class="author-info" style="display: flex; flex-direction: column; align-items: flex-start; margin-top: -3px;">
                <div class="author-top-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    {{-- Name + verified badge --}}
                    <a href="{{ route('users.show', $post->user) }}" class="author-name" style="display:inline-flex;align-items:center;gap:.2em;">{{ $post->user->name ?: $post->user->username }}<x-verified-badge :user="$post->user" size=".95em" /></a>

                    {{-- "in Community" --}}
                    @if(isset($group) && $group && (!isset($hideGroupContext) || !$hideGroupContext))
                        <span class="header-in-word">in</span>
                        <a href="{{ route('communities.show', $group->slug) }}" class="group-context-name"><i class="fas fa-users"></i> {{ $group->name }}</a>
                    @endif

                    {{-- Role badge after community --}}
                    @php $role = $post->author_role; @endphp
                    @if($role)
                        @if($role === 'admin')
                            <span class="role-badge-pill admin-pill" title="{{ __('messages.community_admin') }}">
                                <i class="fas fa-crown"></i>
                                <span>{{ __('messages.role_admin') }}</span>
                            </span>
                        @elseif($role === 'moderator')
                            <span class="role-badge-pill moderator-pill" title="{{ __('messages.community_moderator') }}">
                                <i class="fas fa-shield-alt"></i>
                                <span>{{ __('messages.role_moderator') }}</span>
                            </span>
                        @endif
                    @endif

                    <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" style="font-size: 13px; color: var(--primary); transform: rotate(45deg); opacity: 0.9; {{ $post->isPinned() ? '' : 'display: none;' }}" title="{{ __('users.pinned_to_profile') }}"></i>

                    @if($post->member && $post->member->badges->count() > 0)
                        <div class="author-badges">
                            @foreach($post->member->badges as $badge)
                                <span class="post-mini-badge" title="{{ $badge->name }}" style="color: {{ $badge->color }};">
                                    <i class="{{ $badge->icon }}"></i>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <span class="post-time" data-timestamp="{{ $post->created_at->toIso8601String() }}">
                    <a href="{{ route('users.show', $post->user) }}" class="author-handle">{{ '@' . $post->user->username }}</a>
                    <span class="time-sep" aria-hidden="true">·</span>
                    <span class="time-text">{{ $post->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $post->created_at->diffForHumans(null, true, true) }}</span>
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
        {{-- Standard actions like menu --}}
        @auth
        <button type="button" class="post-menu-btn" onclick="togglePostMenu('{{ $post->id }}')">
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
                @if($isOwner && !$isBroadcast)
                <button type="button" class="menu-item" onclick="window.location.href='{{ route('posts.analytics', $post) }}'">
                    <i class="fas fa-chart-bar"></i> {{ __('posts.post_analytics') }}
                </button>
                @endif
                <button type="button" class="menu-item {{ $isBroadcast ? 'context-owner' : '' }}" onclick="deletePost('{{ $post->slug }}', this)" @if($isBroadcast) style="display: none;" @endif>
                    <i class="fas fa-trash"></i> {{ __('messages.delete_post') }}
                </button>
            @endif

            @if($isBroadcast || ($post->canDelete(auth()->user()) && !$isOwner))
                <button type="button" class="menu-item {{ $isBroadcast ? 'context-admin' : '' }}" onclick="deletePost('{{ $post->slug }}', this)" @if($isBroadcast) style="display: none;" @endif>
                    <i class="fas fa-trash"></i> {{ __('messages.delete_post') }}
                </button>
            @endif

            @if($isBroadcast || !$isOwner)
                <button type="button" class="menu-item {{ $isBroadcast ? 'context-not-owner' : '' }}" onclick="openReportModal('{{ $post->slug }}', '{{ $post->id }}')" @if($isBroadcast) style="display: none;" @endif>
                    <i class="fas fa-flag"></i> {{ __('messages.report_post') }}
                </button>
            @endif
        </div>
        @endauth
    </div>
</div>

<style>
.anonymous-avatar {
    width: 34px;
    height: 34px;
    background: #374151;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    border-radius: 50%;
    flex-shrink: 0;
}
.header-in-word {
    font-size: 12px;
    color: rgba(255,255,255,0.3);
    font-weight: 400;
}
[data-theme="light"] .header-in-word {
    color: rgba(0,0,0,0.35);
}
.group-context-name {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 700;
    color: var(--primary);
    text-decoration: none;
    font-size: 13px;
}
.group-context-name i {
    font-size: 11px;
    opacity: 0.8;
}
.group-context-name:hover {
    text-decoration: underline;
}
.anonymous-name {
    font-weight: 700;
    color: var(--text-muted, #9ca3af);
    font-style: italic;
}
.author-badges {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-left: 4px;
}
.role-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-left: 4px;
}
.admin-pill {
    background: rgba(217, 119, 6, 0.1);
    color: #d97706;
    border: 1px solid rgba(217, 119, 6, 0.2);
}
.moderator-pill {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
    border: 1px solid rgba(37, 99, 235, 0.2);
}
.role-badge-pill i {
    font-size: 9px;
}
.post-mini-badge {
    font-size: 11px;
    opacity: 0.9;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<style>
    .comment-name-row { display: flex; align-items: center; gap: 6px; }
    .role-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        line-height: 1.2;
    }
    .role-badge-pill.mini { padding: 2px 6px; border-radius: 4px; font-size: 9px; }
    .admin-pill { background: rgba(217, 119, 6, 0.1); color: #d97706; border: 1px solid rgba(217, 119, 6, 0.2); }
    .moderator-pill { background: rgba(37, 99, 235, 0.1); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.2); }
</style>
