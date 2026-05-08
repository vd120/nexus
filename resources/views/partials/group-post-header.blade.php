<div class="post-header">
    <div class="post-author" style="display: flex !important; align-items: flex-start !important;">
        @if($post->is_anonymous)
            <div class="author-avatar anonymous-avatar">
                <i class="fas fa-user-secret"></i>
            </div>
            <div class="author-info" style="display: flex; flex-direction: column; align-items: flex-start; margin-top: -3px;">
                <div class="author-top-row" style="display: flex; align-items: center; gap: 6px;">
                    @if(isset($group) && $group && (!isset($hideGroupContext) || !$hideGroupContext))
                         <a href="{{ route('communities.show', $group->slug) }}" class="group-context-name">{{ $group->name }}</a>
                         <span class="header-separator">•</span>
                    @endif
                    <span class="author-name anonymous-name">{{ __('messages.anonymous_participant') }}</span>
                    <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" style="margin-left: 6px; font-size: 13px; color: var(--primary); transform: rotate(45deg); opacity: 0.9; {{ $post->isPinned() ? '' : 'display: none;' }}" title="{{ __('users.pinned_to_profile') }}"></i>
                </div>
                <span class="post-time" data-timestamp="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffInMinutes() < 1 ? __('messages.just_now') : $post->created_at->diffForHumans(null, true, true) }}</span>
            </div>
        @else
            <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->username }}" class="author-avatar">
            <div class="author-info" style="display: flex; flex-direction: column; align-items: flex-start; margin-top: -3px;">
                <div class="author-top-row" style="display: flex; align-items: center; gap: 6px;">
                    @if(isset($group) && $group && (!isset($hideGroupContext) || !$hideGroupContext))
                         <a href="{{ route('communities.show', $group->slug) }}" class="group-context-name">{{ $group->name }}</a>
                         <span class="header-separator">•</span>
                    @endif
                    <a href="{{ route('users.show', $post->user) }}" class="author-name">{{ $post->user->username }}</a>
                    <i class="fas fa-thumbtack pinned-icon-simple" id="pinned-icon-{{ $post->id }}" style="margin-left: 6px; font-size: 13px; color: var(--primary); transform: rotate(45deg); opacity: 0.9; {{ $post->isPinned() ? '' : 'display: none;' }}" title="{{ __('users.pinned_to_profile') }}"></i>
                    
                    {{-- Role Badges (Facebook-style) --}}
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
.header-separator {
    font-size: 0.9rem;
    color: var(--text-muted, #8e8e8e);
    margin: 0 4px;
    opacity: 0.7;
}
.group-context-name {
    font-weight: 600;
    color: var(--primary, #3b82f6);
    text-decoration: none;
    font-size: 0.9rem;
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
