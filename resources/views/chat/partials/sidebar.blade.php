@php
    $userId = auth()->id();
    $groupIds = auth()->user()->groupMemberships()->pluck('group_id');
    
    $conversations = \App\Models\Conversation::where(function($q) use ($userId) {
            $q->where('user1_id', $userId)
              ->orWhere('user2_id', $userId);
        })
        ->orWhere(function($q) use ($groupIds) {
            $q->where('is_group', true)
              ->whereIn('group_id', $groupIds);
        })
        ->with(['user1', 'user2', 'latestMessage.sender', 'group.members.user', 'latestReaction.user', 'latestReaction.message'])
        ->orderBy('last_message_at', 'desc')
        ->limit(50)
        ->get();
@endphp

<aside class="chat-sidebar" id="chatSidebar">
    {{-- Header --}}
    <header class="sidebar-header">
        <div class="header-left">
            <div class="user-avatar-large">
                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->username }}">
            </div>
            <span class="username-text">{{ auth()->user()->username }}</span>
        </div>
        <div class="header-actions">
            <a href="{{ route('groups.create') }}" class="icon-btn" title="{{ __('chat.new_group') }}">
                <i class="fas fa-users"></i>
            </a>
            <button class="icon-btn" onclick="showUserSearch()" title="{{ __('chat.new_message') }}">
                <i class="fas fa-message"></i>
            </button>
        </div>
    </header>

    {{-- Search --}}
    <div class="search-bar">
        <div class="search-input-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="{{ __('chat.search_or_start_chat') }}" id="sidebarSearch" oninput="filterSidebarConversations(this.value)">
        </div>
    </div>

    {{-- Conversations List --}}
    <div class="conversations-list" id="sidebarConvList">
        @forelse($conversations as $conv)
            @php
                $latestMessage = $conv->latestMessage;
                $latestReaction = $conv->latestReaction;
                $isGroup = $conv->is_group;
                $displayName = $isGroup ? $conv->display_name : ($conv->other_user->username ?? 'User');
                $avatarUrl = $isGroup
                    ? ($conv->group && $conv->group->avatar ? asset('storage/' . $conv->group->avatar) : null)
                    : ($conv->other_user ? $conv->other_user->avatar_url : null);

                // Determine if reaction is the latest activity
                $showReactionPreview = false;
                if ($latestReaction && (!$latestMessage || $latestReaction->updated_at > $latestMessage->created_at)) {
                    // Only show reaction in sidebar if the reactor is NOT the message author
                    if ($latestReaction->message->sender_id !== $latestReaction->user_id) {
                        $showReactionPreview = true;
                    }
                }

                // Message preview logic
                $messagePreview = '';
                $messageIcon = '';

                if ($showReactionPreview) {
                    $reactor = $latestReaction->user;
                    $isOwnReaction = $reactor->id === auth()->id();
                    $name = $isOwnReaction ? __('chat.you') : ($reactor->username ?? 'User');
                    
                    $content = strip_tags($latestReaction->message->content);
                    if (str_starts_with($content, '{"__nexus_reply__":true')) {
                        $replyData = json_decode($content, true);
                        $content = $replyData['content'] ?? '';
                    }
                    $content = Str::limit(strip_tags($content), 15);
                    if (empty($content) && $latestReaction->message->type !== 'text') {
                        $content = '[' . __('chat.' . $latestReaction->message->type) . ']';
                    }

                    $messagePreview = ($isGroup || $isOwnReaction ? ($name . ' ') : '') . __('chat.reacted_on_message', [
                        'emoji' => $latestReaction->reaction_type,
                        'content' => $content
                    ]);
                } elseif ($latestMessage) {
                    $isOwn = $latestMessage->sender_id === auth()->id();
                    $content = strip_tags($latestMessage->content);
                    if (str_starts_with($content, '{"__nexus_reply__":true')) {
                        $replyData = json_decode($content, true);
                        $content = '↩ ' . ($replyData['content'] ?? '');
                    }

                    switch ($latestMessage->type) {
                        case 'image':
                            $messageIcon = '📷 ';
                            $messagePreview = $isOwn ? __('chat.you_sent_photo') : __('chat.sent_photo');
                            break;
                        case 'video':
                            $messageIcon = '🎥 ';
                            $messagePreview = $isOwn ? __('chat.you_sent_video') : __('chat.sent_video');
                            break;
                        case 'audio':
                            $messageIcon = '🎤 ';
                            $messagePreview = $isOwn ? __('chat.you_sent_audio') : __('chat.sent_audio');
                            break;
                        case 'document':
                            $messageIcon = '📎 ';
                            $messagePreview = $isOwn ? __('chat.you_sent_document') : __('chat.sent_document');
                            break;
                        case 'gif':
                            $messageIcon = 'GIF ';
                            $messagePreview = $isOwn ? __('chat.you_sent_gif') : __('chat.sent_gif');
                            break;
                        case 'sticker':
                            $messageIcon = '⭐ ';
                            $messagePreview = $isOwn ? __('chat.you_sent_sticker') : __('chat.sent_sticker');
                            break;
                        case 'story_reply':
                            $messageIcon = '📸 ';
                            $content = trim(str_replace('📸 Reply to your story:', '', $content));
                            $messagePreview = $isOwn ? __('chat.you_replied_to_story') : __('chat.replied_to_story');
                            if (!empty($content)) {
                                $messagePreview .= ': ' . Str::limit($content, 25);
                            }
                            break;
                        case 'group_invite':
                            $messageIcon = '👥 ';
                            $messagePreview = $isOwn ? __('chat.you_sent_group_invite') : __('chat.sent_group_invite');
                            break;
                        case 'voice':
                            $messageIcon = '🎤 ';
                            $messagePreview = $isOwn ? __('chat.you_sent_voice_message') : __('chat.sent_voice_message');
                            break;
                        case 'system':
                        case 'text':
                            if ($latestMessage->content === 'system_cleared') {
                                $messagePreview = $isOwn ? __('chat.you_cleared_the_chat') : __('chat.cleared_the_chat', ['user' => $latestMessage->sender->username ?? $latestMessage->sender->name]);
                            } else {
                                $messagePreview = $content;
                            }
                            break;
                        default:
                            $messagePreview = $content;
                            break;
                    }

                    // Add "You: " prefix for own messages in groups
                    if ($isGroup && $isOwn && !in_array($latestMessage->type, ['story_reply', 'system', 'image', 'video', 'audio', 'voice', 'document', 'gif', 'sticker', 'group_invite']) && $latestMessage->content !== 'system_cleared') {
                        $messagePreview = __('chat.you').': ' . $messagePreview;
                    }

                    // For group chats, prefix other participants' messages (except system messages)
                    if ($isGroup && !$isOwn && $latestMessage->sender && $latestMessage->type !== 'system') {
                        $messagePreview = $latestMessage->sender->username . ': ' . $messagePreview;
                    }
                }

                if (empty($messagePreview)) {
                    $messagePreview = __('chat.start_a_conversation');
                }
            @endphp
            <a href="{{ route('chat.show', $conv) }}" class="conversation-item {{ isset($conversation) && $conv->id === $conversation->id ? 'active' : '' }} {{ $conv->unread_count > 0 ? 'unread' : '' }}" data-name="{{ $displayName }}" data-user-id="{{ $isGroup ? '' : ($conv->other_user?->id ?? '') }}" data-conversation-slug="{{ $conv->slug }}" data-conversation-id="{{ $conv->id }}" data-is-group="{{ $isGroup ? 'true' : 'false' }}" data-latest-message-id="{{ $conv->latestMessage->id ?? '' }}">
                <div class="conv-avatar">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" loading="lazy" width="48" height="48">
                    @elseif($isGroup)
                        <div class="avatar-fallback group"><i class="fas fa-users"></i></div>
                    @else
                        <div class="avatar-fallback">{{ substr($displayName, 0, 1) }}</div>
                    @endif
                    @if(!$isGroup && $conv->other_user)
                        @php
                            // Strict check: Only show as online if the is_online flag is true.
                            // This flag is managed in real-time by the socket server.
                            $isOnline = (bool) $conv->other_user->is_online;
                        @endphp
                        <span class="online-indicator {{ $isOnline ? 'online' : 'offline' }}" data-user-id="{{ $conv->other_user->id }}"></span>
                    @endif
                </div>
                <div class="conv-content">
                    <div class="conv-header">
                        <div class="conv-title-container">
                            <span class="conv-title" dir="auto">
                                {{ $displayName }}
                            </span>
                            @if($conv->other_user)
                                {{-- Typing indicator moved to preview area --}}
                            @endif
                        </div>
                        <div class="conv-header-meta">
                            <span class="conv-time">@if($conv->last_message_at){{ \Carbon\Carbon::parse($conv->last_message_at)->format('h:i a') }}@endif</span>
                            @if($conv->isMutedBy(auth()->id()))
                                <i class="fas fa-bell-slash mute-indicator" title="{{ __('chat.notifications_muted') }}"></i>
                            @endif
                        </div>
                    </div>
                    <div class="conv-footer">
                        <div class="conv-preview-container">
                            <p class="conv-preview {{ $conv->unread_count > 0 ? 'unread-text' : '' }}">
                                <span class="preview-content-wrapper">
                                    @if(!$showReactionPreview && $latestMessage && $latestMessage->sender_id === auth()->id())
                                        <i class="fas {{ $latestMessage->read_at ? 'fa-check-double read' : ($latestMessage->delivered_at ? 'fa-check-double sent' : 'fa-check sent') }}"></i>
                                    @endif
                                    @if($latestMessage)
                                        <span class="preview-text" dir="auto">
                                            @if(in_array($latestMessage->type, ['text', 'story_reply']))
                                                {{ $messagePreview }}
                                            @else
                                                {{ $messageIcon }}{{ $messagePreview }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="preview-text">{{ __('chat.start_a_conversation') }}</span>
                                    @endif
                                </span>
                                <span class="typing-indicator-sidebar" style="display: none;">{{ __('chat.typing') }}</span>
                            </p>
                        </div>
                        <div class="conv-footer-meta">
                            @if($conv->unread_count > 0)
                                <span class="unread-pill">{{ $conv->unread_count > 99 ? '99+' : $conv->unread_count }}</span>
                            @endif
                            <div class="conv-item-actions">
                                <button class="conv-action-trigger" onclick="toggleConvMenu(event, '{{ $conv->id }}')">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="conv-dropdown" id="convDropdown-{{ $conv->id }}">
                                    @php $isMuted = $conv->isMutedBy(auth()->id()); @endphp
                                    <button class="menu-item" onclick="toggleMuteConversation(event, '{{ $conv->slug }}')">
                                        <i class="fas {{ $isMuted ? 'fa-bell' : 'fa-bell-slash' }}"></i>
                                        <span class="mute-text">{{ $isMuted ? __('chat.unmute_notifications') : __('chat.mute_notifications') }}</span>
                                    </button>
                                    <div class="menu-divider"></div>
                                    @if($isGroup)
                                        @if($conv->group && $conv->group->creator_id == auth()->id())
                                            <button class="menu-item danger" onclick="confirmDeleteGroupFromSidebar(event, '{{ $conv->id }}', '{{ route('groups.destroy', $conv->group) }}')">
                                                <i class="fas fa-trash-alt"></i> {{ __('chat.delete_group') }}
                                            </button>
                                        @else
                                            <button class="menu-item danger" onclick="confirmLeaveGroupFromSidebar(event, '{{ $conv->id }}', '{{ $conv->group ? route('groups.leave', $conv->group) : '' }}')">
                                                <i class="fas fa-sign-out-alt"></i> {{ __('chat.leave_group') }}
                                            </button>
                                        @endif
                                    @else
                                        <button class="menu-item danger" onclick="confirmDeleteConversation(event, '{{ $conv->id }}', '{{ route('chat.delete-conversation', $conv) }}')">
                                            <i class="fas fa-trash-alt"></i> {{ __('chat.delete_conversation') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-comments"></i></div>
                <h3>{{ __('chat.no_messages_yet') }}</h3>
                <p>{{ __('chat.start_new_conversation') }}</p>
            </div>
        @endforelse
    </div>
</aside>

<style>
    /* Search Modal Styles */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-container {
        background: var(--wa-panel, #202c33);
        width: 100%;
        max-width: 450px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    .modal-header {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        background: var(--wa-panel, #202c33);
        border-bottom: 1px solid var(--wa-border, #2f3b43);
    }
    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 500;
        color: var(--wa-text, #e9edef);
        flex: 1;
        text-align: center;
    }
    .modal-spacer { width: 38px; }
    .back-btn {
        background: none;
        border: none;
        color: var(--wa-text-muted, #8696a0);
        font-size: 18px;
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
    }
    .modal-body { padding: 16px; }
    .search-box { position: relative; margin-bottom: 16px; }
    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--wa-text-muted, #8696a0);
    }
    .search-field {
        width: 100%;
        padding: 12px 14px 12px 44px;
        background: var(--wa-bg, #111b21);
        border: none;
        border-radius: 8px;
        color: var(--wa-text, #e9edef);
        font-size: 14px;
        outline: none;
    }
    .search-field:focus { box-shadow: 0 0 0 2px var(--wa-accent, #00a884); }
    .search-results { max-height: 350px; overflow-y: auto; }
    .result-user {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        margin: 4px 0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .result-user:hover {
        background: var(--wa-panel-hover, #2a3942);
        transform: translateX(4px);
    }
    .result-user .conv-avatar {
        margin-right: 14px;
        width: 44px;
        height: 44px;
        position: relative;
    }
    .result-user img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    .result-user-info {
        display: flex;
        flex-direction: column;
        gap: 1px;
        flex: 1;
        min-width: 0;
    }
    .result-user-name {
        font-size: 15px;
        color: var(--wa-text, #e9edef);
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .result-user-fullname {
        font-size: 12px;
        color: var(--wa-text-muted, #8696a0);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Enhanced User Status Dot */
    .online-indicator {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        background-color: #8696a0; /* Offline color */
        border: 2.5px solid var(--wa-panel, #202c33);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        z-index: 10;
    }
    .result-user:hover .online-indicator {
        border-color: var(--wa-panel-hover, #2a3942);
    }
    .online-indicator.online {
        background-color: #25d366; /* Online color */
        box-shadow: 0 0 5px rgba(37, 211, 102, 0.4);
    }

    /* Conversation Actions Dropdown */
    .conv-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .conv-preview-container {
        flex: 1;
        min-width: 0;
    }
    .conv-footer-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: fit-content;
        position: relative;
    }
    .conv-item-actions {
        position: relative;
        display: flex;
        align-items: center;
        z-index: 1001;
    }
    .conv-action-trigger {
        background: none;
        border: none;
        color: var(--wa-text-muted);
        width: 28px;
        height: 28px;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s;
        opacity: 0;
    }
    .conversation-item:hover .conv-action-trigger {
        opacity: 1;
        pointer-events: auto;
    }
    /* Keep unread pill visible even on hover for mobile/touch consistency */
    .conversation-item:hover .unread-pill {
        display: flex;
    }
    .conv-action-trigger:hover {
        background: rgba(255, 255, 255, 0.1);
        color: var(--wa-accent);
    }
    .conv-dropdown {
        position: absolute;
        top: 100%;
        inset-inline-end: 0;
        background: var(--wa-panel);
        border: 1px solid var(--wa-border);
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        display: none;
        min-width: 180px;
        padding: 6px 0;
        animation: dropdownIn 0.2s ease-out;
    }
    @keyframes dropdownIn {
        from { opacity: 0; transform: translateY(-10px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .conv-dropdown.show {
        display: block;
    }
    .conv-dropdown.drop-up {
        top: auto;
        bottom: 100%;
        margin-bottom: 8px;
        animation: dropdownUp 0.2s ease-out;
    }
    @keyframes dropdownUp {
        from { opacity: 0; transform: translateY(10px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .conv-dropdown button.menu-item {
        width: 100%;
        padding: 10px 16px;
        border: none;
        background: none;
        color: var(--wa-text);
        text-align: left;
        cursor: pointer;
        font-size: 13px;
        font-weight: 400;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: background 0.2s;
    }
    .conv-dropdown button.menu-item:hover {
        background: var(--wa-panel-hover);
    }
    .conv-dropdown button.menu-item i {
        font-size: 14px;
        color: var(--wa-text-muted);
        width: 18px;
        text-align: center;
    }
    .conv-dropdown button.menu-item.danger {
        color: var(--wa-red);
    }
    .conv-dropdown button.menu-item.danger i {
        color: var(--wa-red);
    }
    .menu-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.08);
        margin: 6px 0;
    }

    @media (max-width: 900px), (pointer: coarse) {
        .conv-item-actions {
            opacity: 1;
        }
        .conv-action-trigger {
            opacity: 1;
            pointer-events: auto;
            font-size: 14px;
            width: 28px;
            height: 28px;
            padding: 0;
        }
        .conv-dropdown {
            min-width: 140px;
        }
        .conv-dropdown button {
            padding: 8px 12px;
            font-size: 12px;
        }

    }
    
    .mute-indicator {
        font-size: 12px;
        color: #aebac1; /* Brighter muted color for better visibility */
        display: inline-block;
    }

    @media (max-width: 900px), (pointer: coarse) {

        /* Mobile User Search Modal */
        .modal-overlay {
            align-items: flex-start;
        }
        .modal-container {
            max-width: 100%;
            height: 100dvh;
            border-radius: 0;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--wa-border, #2f3b43);
        }
        .modal-body {
            flex: 1;
            padding: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .search-results {
            max-height: none;
            flex: 1;
            overflow-y: auto;
        }
    }
</style>

{{-- Search Modal --}}
<div id="userSearchModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) hideUserSearch()">
    <div class="modal-container">
        <div class="modal-header">
            <button class="back-btn" onclick="hideUserSearch()"><i class="fas fa-arrow-left"></i></button>
            <h3>{{ __('chat.new_chat') }}</h3>
            <div class="modal-spacer"></div>
        </div>
        <div class="modal-body">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="userSearch" dir="auto" placeholder="{{ __('chat.search_contacts') }}" class="search-field">
            </div>
            <div id="userResults" class="search-results"></div>
        </div>
    </div>
</div>

<script>
    window.currentUserId = {{ auth()->id() }};
    window.allConversationIds = @json($conversations->pluck('id'));

    // Join all sidebar rooms to get typing indicators
    document.addEventListener('DOMContentLoaded', function() {
        // Immediate unread clearing for the active conversation
        const clearActiveUnread = () => {
            const activeId = window.activeConversationId;
            if (activeId) {
                const item = document.querySelector(`.conversation-item[data-conversation-id="${activeId}"]`);
                if (item) {
                    item.classList.remove('unread');
                    const pill = item.querySelector('.unread-pill');
                    if (pill) pill.remove();
                    const preview = item.querySelector('.conv-preview');
                    if (preview) preview.classList.remove('unread-text');

                }
            }
        };
        
        // Run immediately and again after a short delay to ensure DOM is ready
        clearActiveUnread();
        setTimeout(clearActiveUnread, 100);
        setTimeout(clearActiveUnread, 500);

        const joinAllRooms = () => {
            if (window.NexusSocket && window.NexusSocket.status === 'CONNECTED') {
                if (window.allConversationIds && window.allConversationIds.length > 0) {
                    window.allConversationIds.forEach(id => {
                        window.NexusSocket.joinConversation(id);
                    });

                }
            } else {
                setTimeout(joinAllRooms, 1000);
            }
        };
        joinAllRooms();
    });

    function showUserSearch() {
        const modal = document.getElementById('userSearchModal');
        if (modal) {
            modal.style.display = 'flex';
            const input = document.getElementById('userSearch');
            if (input) setTimeout(() => input.focus(), 100);
        }
    }

    function hideUserSearch() {
        const modal = document.getElementById('userSearchModal');
        if (modal) modal.style.display = 'none';
    }

    function filterSidebarConversations(query) {
        const items = document.querySelectorAll('#sidebarConvList .conversation-item');
        const q = query.toLowerCase();
        items.forEach(item => {
            const name = item.getAttribute('data-name')?.toLowerCase() || '';
            item.style.display = name.includes(q) ? 'flex' : 'none';
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function startChat(userId) { window.location.href = '/chat/start/' + userId; }
    function startChatWithUser(userId) { window.location.href = '/chat/start/' + userId; }

    // Shared Translation strings
    window.chatTranslations = Object.assign(window.chatTranslations || {}, {
        you: '{{ __('chat.you') }}',
        online: '{{ __('chat.online') }}',
        offline: '{{ __('chat.offline') }}',
        last_seen: '{{ __('messages.last_seen') }}',
        today: '{{ __('messages.today') }}',
        yesterday: '{{ __('messages.yesterday') }}',
        at: '{{ __('messages.at') }}',
        typing: '{{ __('chat.typing') }}',
        message_deleted: '{{ __('chat.message_deleted') }}',
        start_a_conversation: '{{ __('chat.start_a_conversation') }}',
        conversation_deleted: '{{ __('chat.conversation_deleted') }}',
        start_new_conversation: '{{ __('chat.start_new_conversation') }}',
        cleared_the_chat: '{{ __('chat.cleared_the_chat', ['user' => ':user']) }}',
        you_cleared_the_chat: '{{ __('chat.you_cleared_the_chat') }}',
        and: '{{ __('chat.and') }}',
        are_typing: '{{ __('chat.are_typing') }}',
        users_typing: '{{ __('chat.users_typing') }}',
        is_typing: '{{ __('chat.is_typing') }}',
        
        // Message Types - Own
        you_sent_photo: '{{ __('chat.you_sent_photo') }}',
        you_sent_video: '{{ __('chat.you_sent_video') }}',
        you_sent_audio: '{{ __('chat.you_sent_audio') }}',
        you_sent_document: '{{ __('chat.you_sent_document') }}',
        you_sent_gif: '{{ __('chat.you_sent_gif') }}',
        you_sent_sticker: '{{ __('chat.you_sent_sticker') }}',
        you_sent_voice_message: '{{ __('chat.you_sent_voice_message') }}',
        you_sent_group_invite: '{{ __('chat.you_sent_group_invite') }}',
        you_replied_to_story: '{{ __('chat.you_replied_to_story') }}',
        you_replied: '{{ __('chat.you_replied') }}',
        
        // Message Types - Others
        sent_photo: '{{ __('chat.sent_photo') }}',
        sent_video: '{{ __('chat.sent_video') }}',
        sent_audio: '{{ __('chat.sent_audio') }}',
        sent_document: '{{ __('chat.sent_document') }}',
        sent_gif: '{{ __('chat.sent_gif') }}',
        sent_sticker: '{{ __('chat.sent_sticker') }}',
        sent_voice_message: '{{ __('chat.sent_voice_message') }}',
        sent_group_invite: '{{ __('chat.sent_group_invite') }}',
        replied_to_story: '{{ __('chat.replied_to_story') }}',
        replied_to_you: '{{ __('chat.replied_to_you') }}',
    });

    // Update sidebar conversation item in real-time
    window.updateExistingConversationItem = function(data) {
        const sidebar = document.getElementById('sidebarConvList');
        if (!sidebar) return;

        // Try to find by ID first (most reliable)
        let item = sidebar.querySelector(`.conversation-item[data-conversation-id="${data.conversation_id}"]`);
        
        // Fallback to slug
        if (!item) {
            item = sidebar.querySelector(`.conversation-item[data-conversation-slug="${data.conversation_slug || data.slug}"]`);
        }

        if (!item) {
            // New conversation!
            addNewConversationItem(data);
            return;
        }

        // Update latest message ID for read/delivery receipt tracking
        if (data.id) {
            item.setAttribute('data-latest-message-id', data.id);
        }

        const isOwn = data.sender_id == window.currentUserId;
        const previewEl = item.querySelector('.conv-preview');
        
        if (previewEl) {
            let text = data.last_message || '';
            let iconHtml = '';

            // Handle checkmarks based on backend flag or fall back to isOwn
            const showCheckmarks = data.show_checkmarks !== undefined ? data.show_checkmarks : isOwn;
            if (showCheckmarks) {
                const cls = data.checkmark_class || 'fa-check sent';
                iconHtml = `<i class="fas ${cls}"></i> `;
            }

            const isGroup = !item.getAttribute('data-user-id');

            // Fallback for rebuilding text if last_message is missing (backwards compatibility)
            if (!text) {
                text = getMediaPreviewText(data, isOwn, isGroup);
            }

            // Update entire preview HTML
            previewEl.innerHTML = `
                <span class="preview-content-wrapper">
                    ${iconHtml}
                    <span class="preview-text">${escapeHtml(text)}</span>
                </span>
                <span class="typing-indicator-sidebar" style="display: none;">${window.chatTranslations.typing || 'typing...'}</span>
            `;
            
            // Handle unread styling (only if NOT muted)
            const isMuted = item.querySelector('.mute-indicator') !== null;
            if (!isOwn && String(window.activeConversationId) !== String(data.conversation_id) && !isMuted) {
                previewEl.classList.add('unread-text');
                item.classList.add('unread');
            } else {
                previewEl.classList.remove('unread-text');
                item.classList.remove('unread');
            }

            // Force hide typing indicator when a message arrives
            item.classList.remove('is-typing');
            const sidebarIndicator = item.querySelector('.typing-indicator-sidebar');
            if (sidebarIndicator) sidebarIndicator.style.setProperty('display', 'none', 'important');
            if (previewEl) previewEl.style.setProperty('display', 'block', 'important');
        }

        // Update read/delivered icons if available
        const iconContainer = item.querySelector('.preview-content-wrapper i');
        if (iconContainer && isOwn) {
            if (data.read_at) {
                iconContainer.className = 'fas fa-check-double read';
            } else if (data.delivered_at) {
                iconContainer.className = 'fas fa-check-double sent';
            } else {
                iconContainer.className = 'fas fa-check sent';
            }
        }

        // Update time
        const timeEl = item.querySelector('.conv-time');
        if (timeEl && (data.created_at || data.last_message_time)) {
            const date = new Date(data.created_at || data.last_message_time);
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'pm' : 'am';
            hours = hours % 12;
            hours = hours ? hours : 12;
            timeEl.textContent = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
        }

        // Update unread pill for others' messages
        const activeId = window.activeConversationId ? String(window.activeConversationId) : null;
        const currentMsgId = data.conversation_id ? String(data.conversation_id) : null;
        const isActiveChat = activeId && currentMsgId && activeId === currentMsgId;

        const isMuted = item.querySelector('.mute-indicator') !== null;
        if (!isOwn && !isActiveChat && !isMuted) {
            let pill = item.querySelector('.unread-pill');
            if (!pill) {
                const meta = item.querySelector('.conv-footer-meta');
                if (meta) {
                    pill = document.createElement('span');
                    pill.className = 'unread-pill';
                    const actions = meta.querySelector('.conv-item-actions');
                    if (actions) {
                        meta.insertBefore(pill, actions);
                    } else {
                        meta.appendChild(pill);
                    }
                }
            }
            if (pill) {
                let count = parseInt(pill.textContent) || 0;
                count++;
                pill.textContent = count > 99 ? '99+' : count;
            }
        } else {
            // Remove pill if it exists and it's active chat or own message
            const pill = item.querySelector('.unread-pill');
            if (pill) {
                pill.remove();
            }
            item.classList.remove('unread');
        }

        // Move to top unless no_reorder is set
        if (!data.no_reorder) {
            sidebar.prepend(item);
        }
    };

    window.addNewConversationItem = function(data) {
        const sidebar = document.getElementById('sidebarConvList');
        if (!sidebar) return;

        // Remove empty state if it exists
        const emptyState = sidebar.querySelector('.empty-state');
        if (emptyState) emptyState.remove();

        // Check if we have enough data to render (at least username/name and slug)
        // If it's a message event, we use sender info. If it's conversation:updated, we use display_name.
        const displayName = data.display_name || (data.sender ? data.sender.username : 'User');
        const avatarUrl = data.avatar_url || (data.sender ? data.sender.avatar_url : null);
        const slug = data.conversation_slug || data.slug;
        const convId = data.conversation_id;
        if (!slug || !convId) return;

        // Prevent duplicates
        if (sidebar.querySelector(`.conversation-item[data-conversation-id="${convId}"]`)) return;

        const isGroup = data.is_group || false;

        const time = new Date(data.created_at || data.last_message_time || Date.now());
        let hours = time.getHours();
        const minutes = String(time.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const timeStr = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;

        const isOwn = data.sender_id == window.currentUserId;
        let previewText = data.last_message || data.content || '';
        
        // Handle reply JSON using global sanitizer
        previewText = window.sanitizeMessage(previewText);
        const otherUserId = isOwn ? (data.recipient_id || '') : (data.sender_id || (data.sender ? data.sender.id : ''));
        
        // If we just received a message from them, they are definitely online
        const initialStatus = (!isOwn && !isGroup) ? 'online' : 'offline';

        const item = document.createElement('a');
        item.href = `/chat/${slug}`;
        item.className = `conversation-item unread`;
        item.setAttribute('data-conversation-id', convId);
        item.setAttribute('data-conversation-slug', slug);
        item.setAttribute('data-name', displayName);
        item.setAttribute('data-latest-message-id', data.id || data.message_id || '');
        
        item.innerHTML = `
            <div class="conv-avatar">
                ${avatarUrl 
                    ? `<img src="${avatarUrl}" alt="${displayName}" loading="lazy" width="48" height="48">`
                    : (isGroup 
                        ? `<div class="avatar-fallback group"><i class="fas fa-users"></i></div>`
                        : `<div class="avatar-fallback">${displayName.charAt(0).toUpperCase()}</div>`)
                }
                ${!isGroup ? `<span class="online-indicator ${initialStatus}" data-user-id="${otherUserId}"></span>` : ''}
            </div>
            <div class="conv-content">
                <div class="conv-header">
                    <div class="conv-title-container">
                        <span class="conv-title" dir="auto">${displayName}</span>
                    </div>
                    <div class="conv-header-meta">
                        <span class="conv-time">${timeStr}</span>
                    </div>
                </div>
                <div class="conv-footer">
                    <p class="conv-preview unread-text">
                        <span class="preview-content-wrapper">
                            ${isOwn ? '<i class="fas fa-check sent"></i> ' : ''}
                            <span class="preview-text" dir="auto">${escapeHtml(previewText)}</span>
                        </span>
                        <span class="typing-indicator-sidebar" style="display: none;">${window.chatTranslations.typing || 'typing...'}</span>
                    </p>
                    <div class="conv-footer-meta">
                        ${data.unread_count > 0 ? `<span class="unread-pill">${data.unread_count > 99 ? '99+' : data.unread_count}</span>` : ''}
                        <div class="conv-item-actions">
                            <button class="conv-action-trigger" onclick="toggleConvMenu(event, '${convId}')">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="conv-dropdown" id="convDropdown-${convId}">
                                <button class="menu-item" onclick="toggleMuteConversation(event, '${slug}')">
                                    <i class="fas ${data.is_muted ? 'fa-bell' : 'fa-bell-slash'}"></i>
                                    <span class="mute-text">${data.is_muted ? '{{ __('chat.unmute_notifications') }}' : '{{ __('chat.mute_notifications') }}'}</span>
                                </button>
                                <div class="menu-divider"></div>
                                <button class="menu-item danger" onclick="confirmDeleteConversation(event, '${convId}', '/chat/${convId}')">
                                    <i class="fas fa-trash-alt"></i> {{ __('chat.delete_conversation') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        sidebar.prepend(item);
        
        // If NexusSocket is available, join this new room
        if (window.NexusSocket && window.NexusSocket.joinConversation) {
            window.NexusSocket.joinConversation(convId);
        }
    };

    function getMediaPreviewText(data, isOwn, isGroup) {
        const type = data.type;
        let prefix = '';
        
        if (isGroup) {
            if (isOwn) {
                // Only add "You: " for text messages, as media messages already include "You sent..."
                if (type === 'text' && data.content !== 'system_cleared') {
                    prefix = (window.chatTranslations.you || 'You') + ': ';
                }
            } else if (data.sender && type !== 'system') {
                // For other users in groups, always add "Username: " prefix (except for system messages)
                prefix = (data.sender.username || data.sender.name || 'User') + ': ';
            }
        }
        switch(type) {
            case 'image': return '📷 ' + (isOwn ? window.chatTranslations.you_sent_photo : window.chatTranslations.sent_photo);
            case 'video': return '🎥 ' + (isOwn ? window.chatTranslations.you_sent_video : window.chatTranslations.sent_video);
            case 'audio': return '🎤 ' + (isOwn ? window.chatTranslations.you_sent_audio : window.chatTranslations.sent_audio);
            case 'document': return '📎 ' + (isOwn ? window.chatTranslations.you_sent_document : window.chatTranslations.sent_document);
            case 'gif': return 'GIF ' + (isOwn ? window.chatTranslations.you_sent_gif : window.chatTranslations.sent_gif);
            case 'sticker': return '⭐ ' + (isOwn ? window.chatTranslations.you_sent_sticker : window.chatTranslations.sent_sticker);
            case 'story_reply': 
                let replyContent = (data.content || '').replace('📸 Reply to your story:', '').trim();
                return '📸 ' + (isOwn ? window.chatTranslations.you_replied_to_story : window.chatTranslations.replied_to_story) + (replyContent ? ': ' + replyContent : '');
            case 'group_invite': return '👥 ' + (isOwn ? window.chatTranslations.you_sent_group_invite : window.chatTranslations.sent_group_invite);
            case 'voice': return '🎤 ' + (isOwn ? window.chatTranslations.you_sent_voice_message : window.chatTranslations.sent_voice_message);
            case 'system':
                if (data.content === 'system_cleared') {
                    return isOwn ? (window.chatTranslations.you_cleared_the_chat || 'You cleared the chat') : (window.chatTranslations.cleared_the_chat ? window.chatTranslations.cleared_the_chat.replace(':user', data.username || 'User') : 'Chat cleared');
                }
                return window.sanitizeMessage(data.content || '');
            case 'text':
                let textContent = data.content || '';
                if (textContent === 'system_cleared') {
                    return isOwn ? (window.chatTranslations.you_cleared_the_chat || 'You cleared the chat') : (window.chatTranslations.cleared_the_chat ? window.chatTranslations.cleared_the_chat.replace(':user', data.username || 'User') : 'Chat cleared');
                }
                return prefix + window.sanitizeMessage(textContent);
            default: return '';
        }
    }

    // User Search Logic
    document.addEventListener('DOMContentLoaded', function() {
        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.addEventListener('input', function() {
                const query = this.value.trim();
                const resultsDiv = document.getElementById('userResults');
                if (query.length < 2) { resultsDiv.innerHTML = ''; return; }

                fetch(`/api/search-users?q=${encodeURIComponent(query)}`, {
                    credentials: 'include',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        resultsDiv.innerHTML = data.users.map(u => `
                            <div class="result-user" onclick="startChat(${u.id})">
                                <div class="conv-avatar">
                                    <img src="${escapeHtml(u.avatar_url)}">
                                    <span class="online-indicator ${u.is_online ? 'online' : 'offline'}" data-user-id="${u.id}"></span>
                                </div>
                                <div class="result-user-info">
                                    <div class="result-user-name" dir="auto">${escapeHtml(u.username)}</div>
                                    ${u.name && u.name !== u.username ? `<div class="result-user-fullname" dir="auto">${escapeHtml(u.name)}</div>` : ''}
                                </div>
                            </div>
                        `).join('');
                    }
                });
            });
        }
    });
    window.updateSidebarReadReceipts = function(conversationId, isRead = false, isDelivered = false, readerId = null, data = null) {
        if (!conversationId) return;
        const item = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
        if (item) {
            // If the reader is me, clear the unread state
            const currentUserId = {{ auth()->id() }};
            if (isRead && readerId && parseInt(readerId) === currentUserId) {
                item.classList.remove('unread');
                const pill = item.querySelector('.unread-pill');
                if (pill) pill.remove();
                const preview = item.querySelector('.conv-preview');
                if (preview) preview.classList.remove('unread-text');
            }

            // Update receipts for messages I sent
            const latestMsgId = item.getAttribute('data-latest-message-id');
            const icon = item.querySelector('.preview-content-wrapper i');
            
            if (icon && latestMsgId) {
                if (isRead && data && data.read_messages) {
                    // Check if our latest message is in the read list and is now read by all
                    const msgInfo = data.read_messages.find(m => String(m.id) === String(latestMsgId));
                    if (msgInfo) {
                        if (msgInfo.is_all_read) {
                            icon.className = 'fas fa-check-double read';
                        } else {
                            icon.className = 'fas fa-check-double sent';
                        }
                    }
                } else if (isDelivered && data && String(data.message_id) === String(latestMsgId)) {
                    // Check if our latest message is now delivered to all
                    if (!icon.classList.contains('read')) {
                        if (data.is_all_delivered) {
                            icon.className = 'fas fa-check-double sent';
                        } else {
                            icon.className = 'fas fa-check sent';
                        }
                    }
                } else if (isRead && !data) {
                    // Fallback for 1-1 or old format
                    icon.className = 'fas fa-check-double read';
                }
            }
        }
    };

    function toggleConvMenu(event, id) {
        event.preventDefault();
        event.stopPropagation();
        
        const dropdown = document.getElementById('convDropdown-' + id);
        if (!dropdown) return;

        // Close all other dropdowns
        document.querySelectorAll('.conv-dropdown').forEach(d => {
            if (d !== dropdown) d.classList.remove('show');
        });
        
        dropdown.classList.toggle('show');

        if (dropdown.classList.contains('show')) {
            const rect = dropdown.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            
            // Smart positioning
            if (rect.bottom > viewportHeight - 20) {
                dropdown.classList.add('drop-up');
            } else {
                dropdown.classList.remove('drop-up');
            }

            // Horizontal overflow protection
            if (rect.left < 10) {
                dropdown.style.left = '0';
                dropdown.style.right = 'auto';
            } else if (rect.right > window.innerWidth - 10) {
                dropdown.style.right = '0';
                dropdown.style.left = 'auto';
            }
        }
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function() {
        document.querySelectorAll('.conv-dropdown').forEach(d => d.classList.remove('show'));
    });

    function confirmDeleteGroupFromSidebar(event, id, url) {
        event.preventDefault();
        event.stopPropagation();
        const item = event.target.closest('.conversation-item');
        if (confirm('{{ __('chat.delete_group_confirm') }}')) {
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (item) item.remove();
                    if (window.activeConversationId == id) {
                        window.location.href = '{{ route('chat.index') }}';
                    }
                }
            });
        }
    }

    function confirmLeaveGroupFromSidebar(event, id, url) {
        event.preventDefault();
        event.stopPropagation();
        const item = event.target.closest('.conversation-item');
        if (confirm('{{ __('chat.leave_group_confirm') }}')) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (item) item.remove();
                    if (window.activeConversationId == id) {
                        window.location.href = '{{ route('chat.index') }}';
                    }
                }
            });
        }
    }

    function confirmDeleteConversation(event, id, url) {
        event.preventDefault();
        event.stopPropagation();
        const item = event.target.closest('.conversation-item');
        
        if (confirm('{{ __('chat.confirm_delete_conversation') }}')) {
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (item) {
                        const sidebar = item.parentElement;
                        item.remove();
                        
                        // Show empty state if no conversations left
                        if (sidebar && sidebar.querySelectorAll('.conversation-item').length === 0) {
                            sidebar.innerHTML = `
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fas fa-comments"></i></div>
                                    <h3>{{ __('chat.no_messages_yet') }}</h3>
                                    <p>{{ __('chat.start_new_conversation') }}</p>
                                </div>
                            `;
                        }
                    }
                    
                    if (window.activeConversationId == id) {
                        window.location.href = '{{ route('chat.index') }}';
                    }
                }
            });
        }
    }

    window.toggleMuteConversation = function(event, slug) {
        event.preventDefault();
        event.stopPropagation();
        
        fetch(`/chat/${slug}/mute`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI for this conversation item
                const convItem = document.querySelector(`.conversation-item[data-conversation-slug="${slug}"]`);
                if (convItem) {
                    const dropdown = convItem.querySelector('.conv-dropdown');
                    const muteBtn = dropdown.querySelector('button[onclick*="toggleMuteConversation"]');
                    const muteIcon = muteBtn.querySelector('i');
                    const muteText = muteBtn.querySelector('.mute-text');
                    const headerMeta = convItem.querySelector('.conv-header-meta');
                    
                    if (data.is_muted) {
                        muteIcon.className = 'fas fa-bell';
                        muteText.textContent = '{{ __('chat.unmute_notifications') }}';
                        
                        // Add mute indicator to header if not already there
                        if (!headerMeta.querySelector('.mute-indicator')) {
                            const indicator = document.createElement('i');
                            indicator.className = 'fas fa-bell-slash mute-indicator';
                            indicator.title = '{{ __('chat.notifications_muted') }}';
                            headerMeta.appendChild(indicator);
                        }
                    } else {
                        muteIcon.className = 'fas fa-bell-slash';
                        muteText.textContent = '{{ __('chat.mute_notifications') }}';
                        
                        // Remove mute indicator
                        const indicator = headerMeta.querySelector('.mute-indicator');
                        if (indicator) indicator.remove();
                    }
                    
                    // Show success toast
                    if (window.showToast) window.showToast(data.message);
                }
                
                // Close the menu
                if (window.toggleConvMenu) {
                    const convId = convItem.getAttribute('data-conversation-id');
                    window.toggleConvMenu(event, convId);
                }
            }
        })
        .catch(error => {
            console.error('Error toggling mute:', error);
        });
    };
</script>
