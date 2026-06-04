@extends('layouts.app')

@php
$isGroup = $conversation->is_group;
$chatTitle = $isGroup
    ? ($conversation->display_name ?? 'Group Chat')
    : (($conversation->other_user->username ?? 'Chat'));
@endphp

@section('title', $chatTitle)

@section('content')
<script>
    window.activeConversationId = {{ $conversation->id }};
    window.activeConversationSlug = '{{ $conversation->slug }}';
    window.isGroupChat = {{ $isGroup ? 'true' : 'false' }};
</script>

<style>
/* Hide layout mobile nav on chat page */
.mobile-nav, .mobile-bottom-nav { display: none !important; }

/* Override layout constraints for full width chat */
.app-layout, .main-content {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
}
.chat-page {
    height: calc(100vh - 64px);
    height: calc(100dvh - 64px);
    overflow: hidden;
    display: flex;
    position: relative;
    background: var(--wa-bg);
}

.chat-date-divider.unread-divider {
    margin: 32px 0 16px 0;
}
.chat-date-divider.unread-divider span {
    color: var(--primary);
    border-color: var(--primary);
    background: var(--wa-panel);
}
</style>
<div class="chat-page">
    <div class="chat-layout">
        @include('chat.partials.sidebar')

        {{-- Main Chat Area --}}
        <main class="chat-main">
            {{-- Scroll to Bottom Button --}}
            <button id="scrollToBottomBtn" title="{{ __('chat.scroll_to_bottom') }}">
                <i class="fas fa-chevron-down"></i>
                <span class="new-msg-badge"></span>
            </button>

            <header class="chat-header">
                <button class="back-btn-mobile" onclick="window.location.href='{{ route('chat.index') }}'">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-user-info">
                    @if($conversation->is_group)
                        <a href="{{ route('groups.show', $conversation->slug) }}" class="chat-avatar-link">
                            <div class="chat-avatar">
                                @if($conversation->group && $conversation->group->avatar)
                                    <img src="{{ asset('storage/' . $conversation->group->avatar) }}" alt="{{ __('chat.group') }}">
                                @else
                                    <div class="avatar-fallback"><i class="fas fa-users"></i></div>
                                @endif
                            </div>
                        </a>
                        <a href="{{ route('groups.show', $conversation->slug) }}" class="chat-details-link">
                            <div class="chat-details">
                                <h3>{{ $conversation->group->name ?? $conversation->display_name ?? __('chat.group') }}</h3>
                                <span class="status">{{ __('chat.member_count', ['count' => $conversation->group->members->count() ?? 0]) }}</span>
                            </div>
                        </a>
                    @else
                        <div class="chat-avatar">
                            @if($conversation->other_user)
                                <a href="{{ route('users.show', $conversation->other_user) }}" style="display:flex;flex-shrink:0;"><img src="{{ $conversation->other_user->avatar_url }}" alt="Avatar" style="pointer-events:none;"></a>
                            @else
                                <div class="avatar-fallback">{{ substr('U', 0, 1) }}</div>
                            @endif
                        </div>
                        <div class="chat-details">
                            <a href="{{ route('users.show', $conversation->other_user) }}" style="text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:.25em;"><h3 style="margin:0;">{{ $conversation->other_user->username ?? __('chat.user') }}</h3>@if($conversation->other_user)<x-verified-badge :user="$conversation->other_user" size=".95em" />@endif</a>
@php
    $otherUserShowsOnline = $conversation->other_user?->profile?->show_online_status ?? true;
    $isUserOnline = $conversation->other_user && (bool)$conversation->other_user->is_online && $otherUserShowsOnline;
@endphp
<span class="status {{ $isUserOnline ? 'online' : 'offline' }}" id="chat-user-status" data-user-id="{{ $conversation->other_user->id ?? '' }}">
    <span class="status-dot"></span>
    <span class="status-text">
        @if($isUserOnline)
            {{ __('chat.online') }}
        @elseif($conversation->other_user && $conversation->other_user->last_active)
            {{ __('messages.last_seen') }} 
            @if($conversation->other_user->last_active->isToday())
                {{ __('messages.today') }} {{ __('messages.at') }} {{ $conversation->other_user->last_active->format('h:i a') }}
            @elseif($conversation->other_user->last_active->isYesterday())
                {{ __('messages.yesterday') }} {{ __('messages.at') }} {{ $conversation->other_user->last_active->format('h:i a') }}
            @else
                {{ $conversation->other_user->last_active->format('d/m/Y h:i a') }}
            @endif
        @else
            {{ __('chat.offline') }}
        @endif
    </span>
</span>
                        </div>
                    @endif
                </div>
                <div class="chat-actions">
                    @if($conversation->is_group)
                        <a href="{{ route('groups.show', $conversation->slug) }}" class="action-btn" title="{{ __('chat.group') }}"><i class="fas fa-info-circle"></i></a>
                    @else
                        <button class="action-btn" onclick="clearChat()" title="{{ __('chat.clear_chat') }}"><i class="fas fa-trash"></i></button>
                    @endif
                </div>
            </header>

            <div class="chat-main-content">
                <div class="chat-messages" id="chatMessages">
                @php 
                    $lastDate = null; 
                    $unreadDividerShown = false;
                @endphp
                @forelse($messages as $message)
                    @php
                        $groupedReactions = $message->getGroupedReactions();
                        $totalReactionsCount = 0;
                        foreach($groupedReactions as $r) {
                            $totalReactionsCount += $r['count'];
                        }
                        $hasReactions = $totalReactionsCount > 0;
                    @endphp

                    @php
                        $msgDate = $message->created_at->format('Y-m-d');
                        $showDate = ($lastDate !== $msgDate);
                        $lastDate = $msgDate;
                    @endphp

                    @if(!$unreadDividerShown && !$message->is_mine && !$message->isReadByUser(auth()->id()) && $message->type !== 'system' && $message->content !== 'system_cleared')
                        <div class="chat-date-divider unread-divider">
                            <span dir="auto">{{ __('chat.unread_messages') }}</span>
                        </div>
                        @php $unreadDividerShown = true; @endphp
                    @endif
                    @if($showDate)
                        <div class="chat-date-divider" data-date="{{ $msgDate }}">
                            <span dir="auto">
                                @if($message->created_at->isToday())
                                    {{ __('messages.today') }}
                                @elseif($message->created_at->isYesterday())
                                    {{ __('messages.yesterday') }}
                                @else
                                    {{ $message->created_at->format('d/m/Y') }}
                                @endif
                            </span>
                        </div>
                    @endif
                    @if($message->type === 'system' || ($message->type === 'text' && $message->content === 'system_cleared'))
                        <div class="system-message">
                            <span class="system-text" dir="auto">
                                @if($message->content === 'system_cleared')
                                    {{ $message->is_mine ? __('chat.you_cleared_the_chat') : __('chat.cleared_the_chat', ['user' => $message->sender->username ?? 'User']) }}
                                @else
                                    {{ $message->content }}
                                @endif
                            </span>
                            <span class="system-time">{{ $message->created_at->format('h:i a') }}</span>
                        </div>
                    @elseif($message->type === 'group_invite')
                        @php $inviteData = json_decode($message->media_path, true); @endphp
                        <div class="message {{ $message->is_mine ? 'own' : 'other' }} group-invite" data-message-id="{{ $message->id }}" data-sender-name="{{ $message->is_mine ? __('chat.you') : ($message->sender->username ?? 'User') }}">
                            @if(!$message->is_mine && $message->sender && $isGroup)
                                <div class="message-avatar">
                                    <a href="{{ route('users.show', $message->sender) }}" style="display:flex;flex-shrink:0;"><img src="{{ $message->sender->avatar_url }}" alt="{{ $message->sender->username }}" style="pointer-events:none;"></a>
                                </div>
                            @endif
                            <div class="message-bubble {{ $hasReactions ? 'has-reactions' : '' }}">

                                @if(!$message->is_mine && $message->sender && $isGroup)
                                    <a href="{{ route('users.show', $message->sender) }}" class="sender-name" style="text-decoration:none;display:inline-flex;align-items:center;gap:.2em;">{{ $message->sender->username ?? 'User' }}<x-verified-badge :user="$message->sender" size=".8em" /></a>
                                @endif
                                <div class="invite-card">
                                    <div class="invite-icon"><i class="fas fa-users"></i></div>
                                    <div class="invite-content">
                                        <div class="invite-title">{{ $inviteData['group_name'] ?? __('chat.group') }}</div>
                                        <div class="invite-text">{{ $message->sender->username ?? __('chat.someone') }} {{ __('chat.invited_you_to_join') }}</div>
                                    </div>
                                    @if(!$message->is_mine && ($inviteData['invite_link'] ?? null))
                                        <button class="accept-btn" onclick="acceptGroupInvite('{{ $inviteData['invite_link'] }}')"><i class="fas fa-check"></i> {{ __('chat.join') }}</button>
                                    @endif
                                </div>
                                <span class="message-time">
                                    {{ $message->created_at->format('h:i a') }}
                                    @if($message->is_mine)
                                        @if($message->read_at)
                                            <i class="fas fa-check-double read" title="{{ __('chat.seen') }}"></i>
                                        @elseif($message->delivered_at)
                                            <i class="fas fa-check-double sent" title="{{ __('chat.delivered') }}"></i>
                                        @else
                                            <i class="fas fa-check" title="{{ __('chat.sent') }}"></i>
                                        @endif
                                    @endif
                                </span>
                            </div>
                            <div class="msg-side-actions">
                                <button class="side-action-btn react" onclick="openReactionPicker(event, '{{ $message->id }}')" title="{{ __('chat.react') }}">
                                    <i class="far fa-smile"></i>
                                </button>
                                <button class="side-action-btn reply" onclick="initiateReply('{{ $message->id }}')" title="{{ __('chat.reply') }}">
                                    <i class="fas fa-reply"></i>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="message {{ $message->is_mine ? 'own' : 'other' }} {{ $message->trashed() ? 'deleted' : '' }}" data-message-id="{{ $message->id }}" data-sender-name="{{ $message->is_mine ? __('chat.you') : ($message->sender->username ?? 'User') }}">
                            @php
                                // Handle multiple media files (stored as JSON)
                                $mediaItems = null;
                                if ($message->media_path && str_starts_with($message->media_path, '[')) {
                                    $mediaItems = json_decode($message->media_path, true);
                                }
                            @endphp
                            @if(!$message->is_mine && $message->sender && $isGroup)
                                <div class="message-avatar">
                                    <a href="{{ route('users.show', $message->sender) }}" style="display:flex;flex-shrink:0;"><img src="{{ $message->sender->avatar_url }}" alt="{{ $message->sender->username }}" style="pointer-events:none;"></a>
                                </div>
                            @endif
                            <div class="message-bubble {{ $hasReactions ? 'has-reactions' : '' }}">

                                @if(!$message->is_mine && $message->sender && $isGroup)
                                    <a href="{{ route('users.show', $message->sender) }}" class="sender-name" dir="auto" style="text-decoration:none;display:inline-flex;align-items:center;gap:.2em;">{{ $message->sender->username ?? 'User' }}<x-verified-badge :user="$message->sender" size=".8em" /></a>
                                @endif
                                <div class="message-content {{ ($mediaItems || $message->type === 'image' || $message->type === 'video') ? 'has-media' : '' }} {{ ($message->content && $message->content !== '' && $message->type !== 'group_invite' && $message->content !== 'system_cleared') ? 'has-text' : '' }}">
                                    @if($message->trashed())
                                        <div class="deleted-msg-wrapper">
                                            <i class="fas fa-ban"></i>
                                            <em class="deleted-text">{{ __('chat.message_deleted') ?? 'This message was deleted' }}</em>
                                        </div>
                                    @else
                                        @if($mediaItems && is_array($mediaItems))
                                            {{-- Multiple media files - WhatsApp-style grid with max 4 items --}}
                                            <div class="message-media-album" data-message-id="{{ $message->id }}">
                                                {{-- Store all media paths in a script tag --}}
                                                <script type="application/json" class="media-data">
                                                    @json($mediaItems)
                                                </script>
                                                @php
                                            $displayCount = min(count($mediaItems), 4);
                                            $remainingCount = count($mediaItems) - $displayCount;
                                        @endphp

                                        @if($displayCount === 1)
                                            {{-- Single image - full width --}}
                                            <div class="media-grid-single">
                                                @php $media = $mediaItems[0]; @endphp
                                                @if($media['type'] === 'image' && !empty($media['path']))
                                                    <div class="media-item">
                                                        <img src="{{ asset('storage/' . $media['path']) }}"
                                                             alt="Image"
                                                             onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, 0)">
                                                    </div>
                                                @elseif($media['type'] === 'video' && !empty($media['path']))
                                                    <div class="media-item video" onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, 0)">
                                                        <video src="{{ asset('storage/' . $media['path']) }}"></video>
                                                        <div class="media-overlay">
                                                            <i class="fas fa-play"></i>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($displayCount === 2)
                                            {{-- Two images - side by side --}}
                                            <div class="media-grid-two">
                                                @foreach(array_slice($mediaItems, 0, 2) as $index => $media)
                                                    @if($media['type'] === 'image')
                                                        <div class="media-item">
                                                            <img src="{{ asset('storage/' . $media['path']) }}"
                                                                 alt="Image"
                                                                 onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, {{ $index }})">
                                                        </div>
                                                    @elseif($media['type'] === 'video')
                                                        <div class="media-item video">
                                                            <video src="{{ asset('storage/' . $media['path']) }}"></video>
                                                            <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, {{ $index }})">
                                                                <i class="fas fa-play"></i>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- 3 or 4 images - WhatsApp grid --}}
                                            <div class="media-grid-{{ $displayCount }}">
                                                @foreach(array_slice($mediaItems, 0, $displayCount) as $index => $media)
                                                    @if($media['type'] === 'image')
                                                        <div class="media-item {{ $media['type'] }}">
                                                            <img src="{{ asset('storage/' . $media['path']) }}"
                                                                 alt="Image"
                                                                 onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, {{ $index }})">
                                                            @if($index === 3 && $remainingCount > 0)
                                                                <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, 4)">
                                                                    <span class="overlay-text">+{{ $remainingCount }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @elseif($media['type'] === 'video')
                                                        <div class="media-item video">
                                                            <video src="{{ asset('storage/' . $media['path']) }}"></video>
                                                            <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, {{ $index }})">
                                                                <i class="fas fa-play"></i>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                            </div>
                                        @elseif($message->type === 'image' && $message->media_path)
                                            <div class="message-media">
                                                <img src="{{ asset('storage/' . $message->media_path) }}" alt="Image" onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, 0)">
                                            </div>
                                        @elseif($message->type === 'video' && $message->media_path)
                                            <div class="message-media" onclick="openMediaViewerFromAlbum(this, {{ $message->id }}, 0)">
                                                <video src="{{ asset('storage/' . $message->media_path) }}"></video>
                                                <div class="media-overlay">
                                                    <i class="fas fa-play"></i>
                                                </div>
                                            </div>
                                        @elseif($message->type === 'voice' && $message->media_path)
                                        <div class="voice-message" data-audio-url="{{ asset('storage/' . $message->media_path) }}" data-duration="{{ $message->duration ?? 0 }}">
                                            <button class="voice-play-btn" onclick="toggleVoiceMessage(this)">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <div class="voice-info">
                                                <div class="voice-progress-container" onclick="seekVoice(event, this)">
                                                    <div class="voice-progress-bar"></div>
                                                </div>
                                                <div class="voice-meta">
                                                    <span class="voice-label">{{ __('chat.voice_message') }}</span>
                                                    <span class="voice-duration">0:00 / {{ floor(($message->duration ?? 0) / 60) }}:{{ str_pad(($message->duration ?? 0) % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                            </div>
                                            <button class="voice-speed-btn" onclick="toggleVoiceSpeed(this)">1x</button>
                                        </div>
                                        @endif
                                        @if($message->content && $message->content !== '' && $message->type !== 'group_invite' && $message->content !== 'system_cleared')
                                            @php
                                                $isReply = str_starts_with($message->content, '{"__nexus_reply__":true');
                                                $replyData = $isReply ? json_decode($message->content, true) : null;
                                                $displayContent = $isReply ? $replyData['content'] : $message->content;
                                                
                                                // Existing story reply detection
                                                $isStoryReply = !$isReply && str_starts_with($message->content, '📸 Reply to your story:');
                                                $storyReplyContent = $isStoryReply ? trim(str_replace('📸 Reply to your story:', '', $message->content)) : null;
                                            @endphp
                                            
                                            @php
                                                if (!function_exists('chatLinkify')) {
                                                    function chatLinkify(string $text): string {
                                                        return preg_replace_callback(
                                                            '/(https?:\/\/[^\s<>"\']{4,})/i',
                                                            fn($m) => '<a href="' . e($m[1]) . '" target="_blank" rel="noopener noreferrer" class="chat-link">' . e($m[1]) . '</a>',
                                                            e($text)
                                                        );
                                                    }
                                                }
                                            @endphp
                                            @if($isReply)
                                                <div class="replied-message-box" onclick="scrollToMessage(event, '{{ $replyData['reply_to']['id'] }}')">
                                                    <span class="replied-user">{{ $replyData['reply_to']['username'] ?? $replyData['reply_to']['sender_name'] ?? $replyData['reply_to']['user'] ?? 'User' }}</span>
                                                    <span class="replied-content">{{ $replyData['reply_to']['content'] ?? '' }}</span>
                                                </div>
                                                <span class="text">{!! chatLinkify($displayContent) !!}</span>
                                            @elseif($isStoryReply)
                                                <div class="story-reply-message">
                                                    <div class="story-reply-header">
                                                        <span class="story-reply-label">{{ __('chat.story_reply') }}</span>
                                                    </div>
                                                    <div class="story-reply-content">{{ $storyReplyContent }}</div>
                                                </div>
                                            @else
                                                <span class="text" dir="auto">{!! chatLinkify($message->content) !!}</span>
                                            @endif
                                        @endif
                                        @if($message->link_preview && ($message->link_preview['title'] ?? $message->link_preview['image'] ?? null))
                                            @php $lp = $message->link_preview; @endphp
                                            <a href="{{ e($lp['url'] ?? '#') }}" target="_blank" rel="noopener noreferrer" class="lp-card">
                                                @if(!empty($lp['image']))
                                                    <div class="lp-img"><img src="{{ e($lp['image']) }}" alt="" loading="lazy" onerror="this.parentElement.style.display='none'"></div>
                                                @endif
                                                <div class="lp-body">
                                                    @if(!empty($lp['domain']))<div class="lp-domain">{{ $lp['domain'] }}</div>@endif
                                                    @if(!empty($lp['title']))<div class="lp-title">{{ $lp['title'] }}</div>@endif
                                                    @if(!empty($lp['description']))<div class="lp-desc">{{ Str::limit($lp['description'], 120) }}</div>@endif
                                                </div>
                                            </a>
                                        @endif
                                    @endif
                                    <span class="message-time">
                                        {{ $message->created_at->format('h:i a') }}
                                        @if($message->is_mine)
                                            @if($message->read_at)
                                                <i class="fas fa-check-double read" title="{{ __('chat.seen') }}"></i>
                                            @elseif($message->delivered_at)
                                                <i class="fas fa-check-double sent" title="{{ __('chat.delivered') }}"></i>
                                            @else
                                                <i class="fas fa-check" title="{{ __('chat.sent') }}"></i>
                                            @endif
                                        @endif
                                    </span>
                                        @if(!$message->trashed())
                                             <div class="msg-item-actions">
                                                 <button class="msg-action-trigger" onclick="toggleMsgMenu(event, '{{ $message->id }}')">
                                                      <i class="fas fa-chevron-down"></i>
                                                 </button>
                                                 <div class="msg-dropdown" id="msgDropdown-{{ $message->id }}">
                                                     <button class="menu-item" onclick="initiateReply('{{ $message->id }}')">
                                                         <i class="fas fa-reply"></i> {{ __('chat.reply') }}
                                                     </button>
                                                     @if($message->is_mine)
                                                         <button class="menu-item danger" onclick="deleteMessage({{ $message->id }})">
                                                             <i class="fas fa-trash-alt"></i> {{ __('chat.delete_message') }}
                                                         </button>
                                                         <button class="menu-item info" onclick="showMessageInfo('{{ $message->id }}')">
                                                             <i class="fas fa-info-circle"></i> {{ __('chat.message_info') }}
                                                         </button>
                                                     @endif
                                                 </div>
                                             </div>
                                         @endif
                                    </div>
                                        @php
                                            $myReaction = null;
                                            $hasMine = false;
                                            foreach($groupedReactions as $r) {
                                                if (collect($r['users'])->contains('id', auth()->id())) {
                                                    $hasMine = true;
                                                    $myReaction = $r['reaction_type'];
                                                }
                                            }
                                        @endphp

                                        @if(!$message->trashed())
                                            <div class="message-reactions-bar" 
                                                 data-message-id="{{ $message->id }}" 
                                                 data-my-reaction="{{ $myReaction }}"
                                                 style="{{ !$hasReactions ? 'display:none;' : '' }}">
                                                @if($hasReactions)
    
                                                    <div class="reaction-group-pill {{ $hasMine ? 'has-mine' : '' }}" 
                                                         onclick="showMessageReactors('{{ $message->id }}')">
                                                        <div class="reaction-emoji-stack">
                                                            @foreach($groupedReactions as $r)
                                                                <span class="stack-emoji">{{ $r['reaction_type'] }}</span>
                                                            @endforeach
                                                        </div>
                                                        <span class="reaction-total-count">{{ $totalReactionsCount }}</span>
    
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                            </div>
                            @if(!$message->trashed())
                            <div class="msg-side-actions">
                                <button class="side-action-btn react" onclick="openReactionPicker(event, '{{ $message->id }}')" title="{{ __('chat.react') }}">
                                    <i class="far fa-smile"></i>
                                </button>
                                <button class="side-action-btn reply" onclick="initiateReply('{{ $message->id }}')" title="{{ __('chat.reply') }}">
                                    <i class="fas fa-reply"></i>
                                </button>
                            </div>
                            @endif
                        </div>
@endif
                @empty
                    <div class="no-messages">
                        <i class="fas fa-comments"></i>
                        <p>{{ __('chat.no_messages_in_chat', ['user' => $conversation->other_user->username ?? __('chat.user')]) }}</p>
                    </div>
                @endforelse
            </div>
            </div>

            <div class="chat-input-area">
                <div class="typing-indicator" id="typingIndicator" style="display: none;">
                    <span class="typing-dots">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </span>
                    <span class="typing-text">
                        <span>{{ __('chat.typing') }}</span>
                    </span>
                </div>
                <form id="messageForm" onsubmit="sendMessage(event)">
                <div id="replyPreview" class="reply-preview-container" style="display: none;">
                    <div class="reply-preview-content">
                        <div class="reply-preview-border"></div>
                        <div class="reply-preview-details" onclick="if(replyingTo) scrollToMessage(event, replyingTo.id)" style="cursor: pointer;">
                            <div class="reply-preview-user" id="replyPreviewUser"></div>
                            <div class="reply-preview-text" id="replyPreviewText"></div>
                        </div>
                        <button type="button" class="reply-preview-close" onclick="cancelReply()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                {{-- Link preview card --}}
                <div id="link-preview-card" style="display:none; margin:.5rem .75rem; background:var(--input-bg,#12121f); border:1px solid var(--border-color,#2a2a3e); border-radius:8px; overflow:hidden; position:relative;">
                    <button type="button" onclick="dismissLinkPreview()" style="position:absolute;top:.375rem;right:.375rem;background:none;border:none;cursor:pointer;opacity:.5;font-size:.875rem;z-index:1;" aria-label="Dismiss preview">&times;</button>
                    <div style="display:flex; gap:.75rem; padding:.75rem;">
                        <img id="lp-image" src="" alt="" style="width:72px; height:72px; object-fit:cover; border-radius:6px; flex-shrink:0; display:none;">
                        <div style="min-width:0; flex:1;">
                            <div id="lp-domain" style="font-size:.7rem; opacity:.5; margin-bottom:.2rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
                            <div id="lp-title" style="font-size:.875rem; font-weight:600; line-height:1.3; margin-bottom:.25rem; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;"></div>
                            <div id="lp-desc" style="font-size:.78rem; opacity:.6; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;"></div>
                        </div>
                    </div>
                </div>

                <div id="mediaPreview" class="media-preview" style="display: none;">
                        <div class="preview-carousel">
                            <button type="button" class="carousel-arrow left" onclick="movePreview(-1)" title="{{ __('chat.previous') }}">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="preview-slides" id="previewSlides">
                                <!-- Slides will be added here -->
                            </div>
                            <button type="button" class="carousel-arrow right" onclick="movePreview(1)" title="{{ __('chat.next') }}">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="preview-indicators" id="previewIndicators">
                            <!-- Dots will be added here -->
                        </div>
                        <div class="preview-info">
                            <span id="previewCount">1 / 1</span>
                            <button type="button" class="clear-all" onclick="clearMediaPreview()" title="{{ __('chat.remove_all') }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="input-row">
                        <label for="mediaInput" class="attach-btn" title="{{ __('chat.attach') }}"><i class="fas fa-paperclip"></i></label>
                        <button type="button" id="voiceRecordBtn" class="voice-record-btn" title="{{ __('chat.record_voice') }}" onclick="toggleVoiceRecording()"><i class="fas fa-microphone"></i></button>
                        <input type="file" id="mediaInput" accept="image/*,video/*" multiple onchange="handleMediaSelect(event)" style="display: none;">
                        <input type="text" id="messageInput" dir="auto" placeholder="{{ __('chat.type_a_message') }}" maxlength="1000" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                        <button type="submit" id="sendButton" class="send-btn" title="{{ __('chat.send') }}"><i class="fas fa-paper-plane"></i></button>
                    </div>
                    
                    {{-- Voice Recording Overlay --}}
                    <div id="voiceRecordingOverlay" class="voice-recording-overlay" style="display: none;">
                        <div class="recording-content">
                            <div class="recording-timer" id="recordingTimer">00:00</div>
                            <div class="recording-waveform" id="recordingWaveform"></div>
                            <div class="recording-controls">
                                <button class="recording-btn cancel" onclick="cancelVoiceRecording()" title="{{ __('chat.cancel') }}"><i class="fas fa-times"></i></button>
                                <button class="recording-btn" id="recordToggleBtn" onclick="toggleVoiceRecord()" title="{{ __('chat.start_recording') }}"><i class="fas fa-microphone"></i></button>
                                <button class="recording-btn send" id="sendVoiceBtn" title="{{ __('chat.send') }}" disabled onclick="console.log('🎤 Send clicked!'); sendVoiceMessage();"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>


    {{-- User Search Modal --}}


    {{-- Delete Message Modal --}}
    <div id="deleteMessageModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this)closeDeleteModal()">
        <div class="modal-box delete-modal">
            <div class="modal-header">
                <h3>{{ __('chat.delete_message') }}</h3>
                <button class="close-btn" onclick="closeDeleteModal()" title="{{ __('chat.close') }}"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p class="delete-description">{{ __('chat.delete_message_desc') }}</p>
                <button class="delete-option" onclick="confirmDelete('everyone')">
                    <div class="delete-option-icon"><i class="fas fa-users"></i></div>
                    <div class="delete-option-content">
                        <div class="delete-option-title">{{ __('chat.delete_for_everyone') }}</div>
                        <div class="delete-option-desc">{{ __('chat.delete_for_everyone_desc') }}</div>
                    </div>
                </button>
                <button class="delete-option" onclick="confirmDelete('me')">
                    <div class="delete-option-icon"><i class="fas fa-user"></i></div>
                    <div class="delete-option-content">
                        <div class="delete-option-title">{{ __('chat.delete_for_me') }}</div>
                        <div class="delete-option-desc">{{ __('chat.delete_for_me_desc') }}</div>
                    </div>
                </button>
            </div>
        </div>
        {{-- Reactors modal moved to global layout --}}
    </div>

    {{-- Message Info Modal --}}
    <div id="messageInfoModal" class="modal-overlay" style="display: none;" onclick="if(event.target===this)closeMessageInfoModal()">
        <div class="modal-box message-info-modal">
            <div class="modal-header">
                <div class="spacer"></div>
                <h3>{{ __('chat.message_info') }}</h3>
                <button class="close-btn" onclick="closeMessageInfoModal()" title="{{ __('chat.close') }}"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="info-section">
                    <div class="info-section-title"><i class="fas fa-check-double read"></i> {{ __('chat.read_by') }}</div>
                    <div id="messageInfoReadList" class="info-user-list"></div>
                </div>
                <div class="info-section mt-3">
                    <div class="info-section-title"><i class="fas fa-check-double sent"></i> {{ __('chat.delivered_to') }}</div>
                    <div id="messageInfoDeliveredList" class="info-user-list"></div>
                </div>
                <div class="info-section mt-3">
                    <div class="info-section-title"><i class="fas fa-check"></i> {{ __('chat.remaining') }}</div>
                    <div id="messageInfoRemainingList" class="info-user-list"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Enhanced Media Viewer --}}
<div id="mediaViewer" class="nexus-gallery" aria-hidden="true">
    <div class="gallery-overlay"></div>
    
    <div class="gallery-header">
        <div class="gallery-info"></div>
        <div class="gallery-actions">
            <button id="galleryClose" class="gallery-btn" title="{{ __('chat.close') }}">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="gallery-main">
        <button id="galleryPrev" class="gallery-nav-btn left" title="{{ __('chat.previous') }}">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="gallery-content">
            <img id="galleryImage" src="" alt="{{ __('chat.gallery_image') }}" style="display: none;">
            <video id="galleryVideo" src="" controls style="display: none;"></video>
        </div>

        <button id="galleryNext" class="gallery-nav-btn right" title="{{ __('chat.next') }}">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <div class="gallery-footer">
        <span id="galleryCounter" class="gallery-counter">0 / 0</span>
    </div>
</div>

<style>
/* Hide main layout mobile nav on chat pages */
.chat-page ~ .mobile-nav,
.chat-page ~ .mobile-bottom-nav,
body:has(.chat-page) .mobile-nav,
body:has(.chat-page) .mobile-bottom-nav {
    display: none !important;
}

/* Use layout CSS variables for theme support */
:root {
    --wa-bg: var(--bg, #111b21);
    --wa-panel: var(--surface, #202c33);
    --wa-panel-hover: var(--surface-hover, #2a3942);
    --wa-border: var(--border, #2f3b43);
    --wa-text: var(--text, #e9edef);
    --wa-text-muted: var(--text-muted, #8696a0);
    --wa-accent: var(--primary, #00a884);
    --wa-blue: var(--primary, #53bdeb);
    --wa-green: var(--success, #25d366);
    --wa-red: var(--danger, #f15c6d);
    --wa-message-out: #005c4b; /* Deep Forest Green */
    --wa-message-in: #202c33;  /* Dark Slate */
    --wa-message-out-text: #e9edef;
    --wa-message-in-text: #e9edef;
    --wa-icon-muted: #8696a0;
}

[data-theme="light"] {
    --wa-message-out: #d9f2d9; /* Soft Mid-Light Green */
    --wa-message-in: #ffffff;  /* Pure White */
    --wa-message-out-text: #111b21;
    --wa-message-in-text: #111b21;
    --wa-icon-muted: #667781;
}

.chat-page {
    height: calc(100vh - 64px);
    height: calc(100dvh - 64px);
    background: var(--wa-bg);
    overflow: hidden;
    position: relative;
    display: flex; /* Flex-row by default for sidebar + main */
}

/* Consolidating mobile styles here to prevent conflicts */
@media (max-width: 900px) {
    .chat-page {
        position: fixed !important;
        top: 68px !important; /* Standard header height */
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        height: calc(100% - 68px) !important;
        width: 100% !important;
        z-index: 10 !important;
        flex-direction: column;
    }

    @media (max-width: 480px) {
        .chat-page {
            top: 48px !important;
            height: calc(100% - 48px) !important;
        }
    }

    .chat-layout {
        height: 100%;
        width: 100%;
    }

    .chat-main {
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
    }

    .chat-header {
        position: relative !important;
        top: 0 !important;
        height: 60px;
        flex-shrink: 0;
        z-index: 10;
        background: var(--wa-panel) !important;
        border-bottom: 1px solid var(--wa-border);
    }

    .chat-main-content {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 10px !important;
        display: flex;
        flex-direction: column;
    }

    .message {
        max-width: calc(100% - 35px) !important;
    }

    .chat-input-area {
        position: relative !important;
        bottom: 0 !important;
        padding: 10px;
        flex-shrink: 0;
        background: var(--wa-panel);
    }
}

/* Layout overrides handled at top of file */

.chat-layout {
    display: flex;
    height: 100%;
    width: 100%;
    max-width: 100%;
    margin: 0;
}

/* Sidebar - Fixed width on left */
.chat-sidebar {
    width: 100%;
    max-width: none;
    min-width: 320px;
    background: var(--wa-panel);
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--wa-border);
}

/* Desktop - make sidebar wider */
@media (min-width: 900px) {
    .chat-sidebar {
        max-width: 450px;
    }
}

@media (min-width: 1200px) {
    .chat-sidebar {
        max-width: 500px;
    }
}

@media (min-width: 1400px) {
    .chat-sidebar {
        max-width: 550px;
    }
}

/* Header */
.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--wa-panel);
    border-bottom: 1px solid var(--wa-border);
}

.header-left { display: flex; align-items: center; gap: 10px; }

.user-avatar-large {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, var(--wa-accent), var(--wa-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    font-weight: 600;
    flex-shrink: 0;
}

.user-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.username-text {
    font-size: 14px;
    font-weight: 600;
    color: var(--wa-text);
}

.header-actions {
    display: flex;
    gap: 8px;
}

.icon-btn {
    width: 38px;
    height: 38px;
    border: none;
    background: transparent;
    color: var(--wa-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
    font-size: 16px;
}

.icon-btn:hover {
    background: var(--wa-panel-hover);
    color: var(--wa-text);
}

/* Search */
.search-bar {
    padding: 8px 12px;
    background: var(--wa-panel);
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input-wrapper i {
    position: absolute;
    left: 14px;
    color: var(--wa-text-muted);
    font-size: 14px;
}

.search-input-wrapper input {
    width: 100%;
    padding: 10px 14px 10px 44px;
    background: var(--wa-bg);
    border: none;
    border-radius: 8px;
    color: var(--wa-text);
    font-size: 14px;
    outline: none;
}

.search-input-wrapper input:focus {
    box-shadow: 0 0 0 2px var(--wa-accent);
}

/* Conversations List */
.conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid var(--wa-border);
    text-decoration: none;
}

.conversation-item:hover {
    background: var(--wa-panel-hover);
}

.conversation-item.active {
    background: var(--wa-panel-hover);
}

.conversation-item.unread {
    background: rgba(0, 168, 132, 0.08);
}

.conv-avatar {
    margin-right: 14px;
    flex-shrink: 0;
    position: relative;
}

.conv-avatar .avatar-fallback,
.conv-avatar img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-fallback {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    border-radius: 50%;
}



.avatar-fallback.group {
    background: linear-gradient(135deg, var(--wa-accent), var(--wa-blue));
}

.conv-content {
    flex: 1;
    min-width: 0;
}

.conv-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.conv-title-container {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 0;
}

.conv-title {
    font-size: 15px;
    font-weight: 500;
    color: var(--wa-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}



.conv-time {
    font-size: 12px;
    color: var(--wa-text-muted);
    flex-shrink: 0;
}

.conv-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conv-preview {
    margin: 0;
    font-size: 13px;
    color: var(--wa-text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 260px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 0;
}

.conv-preview.unread-text {
    color: var(--wa-text);
    font-weight: 500;
}

.conv-preview i.read-status {
    font-size: 14px;
    flex-shrink: 0;
}

.conv-preview i.read-status.read,
.conv-preview i.read {
    color: #53bdeb;
}

.conv-preview i.read-status.sent,
.conv-preview i.sent {
    color: #8696a3;
}

.empty-preview {
    font-style: italic;
    opacity: 0.7;
}

.unread-pill {
    background: var(--wa-accent);
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
    flex-shrink: 0;
    white-space: nowrap;
}

/* Empty State */
.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-icon {
    font-size: 64px;
    color: var(--wa-text-muted);
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-state h3 {
    margin: 0 0 8px;
    font-size: 18px;
    color: var(--wa-text);
}

.empty-state p {
    margin: 0 0 24px;
    color: var(--wa-text-muted);
    font-size: 14px;
}

/* Static Header - Fixed on mobile, static on desktop */
.chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: var(--wa-panel);
    border-bottom: 1px solid var(--wa-border);
    height: 64px;
    flex-shrink: 0;
}

/* Mobile - fixed header */
@media (max-width: 900px) {
    .chat-header {
        position: relative;
        top: 0;
        z-index: 10;
        height: 56px;
        padding: 10px 14px;
        flex-shrink: 0;
        background: var(--wa-panel);
    }
}

.back-btn-mobile {
    background: none;
    border: none;
    color: var(--wa-text-muted);
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    display: none;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
    flex-shrink: 0;
}

.back-btn-mobile:hover { color: var(--wa-text); }

/* Chat user info and actions */
.chat-header .chat-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    overflow: hidden;
    padding-top: 10px;
}

.chat-header .chat-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

/* Static Input Area - Fixed on mobile, static on desktop */
.chat-input-area {
    padding: 12px 16px;
    background: var(--wa-panel);
    border-top: 1px solid var(--wa-border);
    flex-shrink: 0;
}

/* Mobile - stacked input */
@media (max-width: 900px) {
    .chat-input-area {
        position: relative;
        bottom: 0;
        z-index: 10;
        padding: 10px 14px;
        background: var(--wa-panel);
        border-top: 1px solid var(--wa-border);
        flex-shrink: 0;
    }
}

/* Messages area - with padding to account for fixed header */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Main content wrapper */
.chat-main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Desktop - add padding for project header */
@media (min-width: 901px) {
    .chat-main-content {
        padding-top: 15px;
    }
}

/* Mobile - stacked layout */
@media (max-width: 900px) {
    .chat-main {
        height: 100% !important;
        display: flex;
        flex-direction: column;
        flex: 1;
        width: 100%;
        overflow: hidden;
    }
    
    .chat-main-content {
        padding: 0 !important;
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        height: auto;
    }

    .chat-messages {
        padding: 15px 12px !important;
        flex: 1;
        overflow-y: auto;
    }
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--wa-panel);
    border-bottom: 1px solid var(--wa-border);
}

.sidebar-user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.user-avatar-sm {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, var(--wa-accent), var(--wa-blue));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 600;
    flex-shrink: 0;
}

.user-avatar-sm img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sidebar-username {
    font-size: 14px;
    font-weight: 600;
    color: var(--wa-text);
    font-family: 'Inter', 'Cairo', sans-serif;
}

.back-btn {
    background: none;
    border: none;
    color: var(--wa-text-muted);
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
}

.back-btn:hover { color: var(--wa-text); }

.sidebar-actions {
    margin-left: auto;
    display: flex;
    gap: 6px;
}

.icon-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    color: var(--wa-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 15px;
}

.icon-btn:hover { background: var(--wa-panel-hover); }

.sidebar-search {
    padding: 10px 12px;
    border-bottom: 1px solid var(--wa-border);
}

.search-wrapper {
    position: relative;
}

.search-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--wa-text-muted);
    font-size: 13px;
}

.search-wrapper input {
    width: 100%;
    padding: 9px 12px 9px 38px;
    background: var(--wa-bg);
    border: none;
    border-radius: 8px;
    color: var(--wa-text);
    font-size: 14px;
    outline: none;
}

.search-wrapper input:focus { box-shadow: 0 0 0 2px var(--wa-accent); }

.conv-list {
    flex: 1;
    overflow-y: auto;
}

.conv-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
    border-bottom: 1px solid var(--wa-border);
    text-decoration: none;
    transition: background 0.2s;
}

.conv-item:hover { background: var(--wa-panel-hover); }
.conv-item.active { background: var(--wa-panel-hover); }
.conv-item.unread { background: rgba(0, 168, 132, 0.08); }

.conv-avatar {
    margin-right: 12px;
    flex-shrink: 0;
}

.conv-avatar img,
.conv-avatar .avatar-fallback {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-fallback {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    border-radius: 50%;
}

.conv-body { flex: 1; min-width: 0; }

.conv-top {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
}

.conv-name {
    font-size: 15px;
    font-weight: 500;
    color: var(--wa-text);
}

.conv-time {
    font-size: 12px;
    color: var(--wa-text-muted);
}

.conv-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conv-preview {
    font-size: 13px;
    color: var(--wa-text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.conv-preview i { font-size: 10px; color: var(--wa-blue); }
.conv-preview em { font-style: italic; opacity: 0.7; }

.badge {
    background: var(--wa-accent);
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 10px;
}

.empty-sidebar {
    padding: 50px 20px;
    text-align: center;
    color: var(--wa-text-muted);
}

.empty-sidebar i { font-size: 48px; margin-bottom: 12px; opacity: 0.3; }
.empty-sidebar p { margin: 0; }

/* Main Chat */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: var(--wa-bg);
    position: relative;
    overflow: hidden;
}

.chat-main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Group chat clickable links */
.chat-avatar-link,
.chat-details-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: opacity 0.2s;
}

.chat-avatar-link:hover,
.chat-details-link:hover {
    opacity: 0.8;
}

.chat-avatar-link:hover .chat-avatar,
.chat-details-link:hover h3 {
    opacity: 0.8;
}

.chat-avatar img, .chat-avatar .avatar-fallback {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.chat-details h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 500;
    color: var(--wa-text);
    font-family: 'Inter', 'Cairo', sans-serif;
    transition: color 0.2s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

.chat-details-link:hover h3 {
    color: var(--wa-accent);
}

.status {
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--wa-text-muted);
}

.status.online {
    color: var(--wa-green);
}

.status .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}

.status.online .status-dot {
    box-shadow: none;
    animation: none;
}

/* Online indicator for chat list */
.status-text {
    font-weight: 500;
}

.chat-actions {
    display: flex;
    gap: 8px;
}

/* Messages */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 10px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    overscroll-behavior-y: contain;
    -webkit-overflow-scrolling: touch;
}

/* Scrollbar styling */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}
.chat-messages::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}
.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.action-btn {
    width: 38px;
    height: 38px;
    border: none;
    background: transparent;
    color: var(--wa-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 16px;
}

.action-btn:hover { background: var(--wa-panel-hover); color: var(--wa-text); }

.message {
    display: flex;
    gap: 8px;
    width: 100%;
    max-width: 100%;
    position: relative;
    z-index: 1;
    overflow: visible !important;
    transition: background 0.3s ease;
    margin-bottom: 12px;
}

.message:hover {
    z-index: 10 !important;
}

@media (min-width: 901px) {
    .message {
        width: 100%;
        max-width: 100%;
        position: relative;
        z-index: 1;
        transition: z-index 0s, background 0.3s ease;
    }
    .message-bubble {
        max-width: 85%;
    }

    .message:hover {
        z-index: 10 !important;
    }
}

/* @keyframes msgIn {
    from { opacity: 0; }
    to { opacity: 1; }
} */

.message.own {
    align-self: flex-end;
    flex-direction: row-reverse;
    justify-self: flex-end;
}

.message.other {
    align-self: flex-start;
    justify-self: flex-start;
}

.message.other + .message.other {
    margin-top: 6px;
}

.message-avatar {
    flex-shrink: 0;
}

.message-avatar img, .message-avatar .avatar-fallback {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    margin-top: 2px;
}

.message-bubble {
    display: flex;
    flex-direction: column;
    max-width: 100%;
    align-items: flex-end;
    word-break: break-word;
    position: relative;
    overflow: visible !important;
}

.message.own .message-bubble {
    align-items: flex-end;
}

.message.other .message-bubble {
    align-items: flex-start;
}

.sender-name {
    font-size: 12px;
    font-weight: 600;
    color: #53bdeb;
    margin-bottom: 3px;
    padding: 0 12px;
    font-family: 'Inter', 'Cairo', sans-serif;
}

.message-content {
    padding: 8px 12px 10px 12px;
    padding-inline-end: 34px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.4;
    position: relative;
    min-width: 60px;
    max-width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.15);
    transition: none;
}

.message-content[dir="rtl"] {
    text-align: right;
    align-items: flex-end;
}

.message.own .message-content {
    background: var(--wa-message-out);
    color: var(--wa-message-out-text);
    border-top-right-radius: 0;
}

.message.own .message-content::before {
    content: "";
    position: absolute;
    top: 0;
    right: -8px;
    width: 12px;
    height: 14px;
    background: var(--wa-message-out);
    clip-path: polygon(0 0, 0 100%, 100% 0);
}

.message.other .message-content {
    background: var(--wa-message-in);
    color: var(--wa-message-in-text);
    border-top-left-radius: 0;
}

.message.other .message-content::before {
    content: "";
    position: absolute;
    top: 0;
    left: -8px;
    width: 12px;
    height: 14px;
    background: var(--wa-message-in);
    clip-path: polygon(0 0, 100% 0, 100% 100%);
}

/* Inline links inside messages */
.chat-link {
    color: #60a5fa;
    text-decoration: underline;
    text-underline-offset: 2px;
    word-break: break-all;
}
.chat-link:hover { color: #93c5fd; }

/* Link preview card */
.lp-card {
    display: flex;
    flex-direction: column;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.04);
    text-decoration: none;
    color: inherit;
    margin-top: 6px;
    max-width: 280px;
    transition: background 0.15s ease;
}
.lp-card:hover { background: rgba(255,255,255,0.07); }
.lp-img { width: 100%; max-height: 140px; overflow: hidden; }
.lp-img img { width: 100%; height: 140px; object-fit: cover; display: block; }
.lp-body { padding: 8px 10px; display: flex; flex-direction: column; gap: 3px; }
.lp-domain { font-size: 10px; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.04em; }
.lp-title { font-size: 12.5px; font-weight: 600; color: rgba(255,255,255,0.9); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.lp-desc { font-size: 11.5px; color: rgba(255,255,255,0.5); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
[data-theme="light"] .lp-card { border-color: rgba(0,0,0,0.08); background: rgba(0,0,0,0.03); }
[data-theme="light"] .lp-card:hover { background: rgba(0,0,0,0.06); }
[data-theme="light"] .lp-domain { color: rgba(0,0,0,0.4); }
[data-theme="light"] .lp-title { color: rgba(0,0,0,0.85); }
[data-theme="light"] .lp-desc { color: rgba(0,0,0,0.5); }
[data-theme="light"] .chat-link { color: #2563eb; }
[data-theme="light"] .chat-link:hover { color: #1d4ed8; }

.message-content .text {
    word-wrap: break-word;
    display: block;
    color: inherit;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Noto Sans Arabic', 'Cairo', 'Tahoma', 'Arial', sans-serif;
    unicode-bidi: embed;
    line-height: 1.6;
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: antialiased;
}

/* Arabic and RTL text support - Enhanced for enhanced readability */
.message-content .text:lang(ar),
.message-content .text[dir="rtl"],
.message-content .text[lang="ar"] {
    direction: rtl;
    text-align: right;
    font-family: 'Cairo', 'Noto Sans Arabic', 'Segoe UI', Tahoma, sans-serif;
    font-size: 15.5px; /* Slightly larger for better Arabic legibility */
    line-height: 1.8; /* Increased line-height for Arabic script ascenders/descenders */
    letter-spacing: 0;
}

.message-content .deleted-msg-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 2px;
    color: #9ca3af;
}

.message-content .deleted-msg-wrapper i {
    font-size: 14px;
    color: #ef4444;
    opacity: 0.8;
}

.message-content .deleted-text {
    font-style: italic;
    font-size: 13.5px;
    font-weight: 400;
    opacity: 0.9;
}

.message.deleted .message-content {
    background: rgba(239, 68, 68, 0.05) !important;
    border: 1px solid rgba(239, 68, 68, 0.2) !important;
    opacity: 0.9;
}

[data-theme="dark"] .message.deleted .message-content {
    background: rgba(239, 68, 68, 0.08) !important;
    border: 1px solid rgba(239, 68, 68, 0.15) !important;
}

.message.deleted .message-time {
    position: relative !important;
    bottom: auto !important;
    right: auto !important;
    margin-top: 8px !important;
    align-self: flex-end;
    background: transparent !important;
    padding: 0 !important;
    backdrop-filter: none !important;
    color: #9ca3af !important;
    opacity: 0.7;
    z-index: 1;
}

.message.deleted .text {
    color: #4b5563;
    font-style: italic;
}

.message.deleted .message-time {
    color: #9ca3af;
}

/* Story Reply Message Style */
.story-reply-message {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.story-reply-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--wa-text-muted);
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.story-reply-header i {
    font-size: 11px;
    color: var(--wa-accent);
}

.story-reply-label {
    font-weight: 500;
    color: #53bdeb;
}

.story-reply-content {
    font-size: 14px;
    line-height: 1.4;
    color: #e9edef;
}

.message.own .story-reply-header {
    border-bottom-color: rgba(0, 0, 0, 0.1);
}

/* Media album grid for multiple files - WhatsApp style */
.message-media-album {
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 8px;
    max-width: 320px;
}

/* Single image - full width */
.media-grid-single {
    width: 100%;
    max-width: 100%;
    border-radius: 8px;
    overflow: hidden;
}

.media-grid-single img,
.media-grid-single video {
    width: 100%;
    height: auto;
    max-height: 250px;
    object-fit: contain;
    border-radius: 8px;
    cursor: pointer;
    display: block;
}

/* Two images - side by side */
.media-grid-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px;
    max-width: 320px;
    width: fit-content;
}

.media-grid-two .media-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
}

.media-grid-two .media-item img,
.media-grid-two .media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    display: block;
}

/* Three images - WhatsApp triangle layout */
.media-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px;
    max-width: 320px;
    width: fit-content;
}

.media-grid-3 .media-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
}

.media-grid-3 .media-item:first-child {
    grid-row: 1 / 3;
    grid-column: 1;
}

.media-grid-3 .media-item:first-child img,
.media-grid-3 .media-item:first-child video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.media-grid-3 .media-item:nth-child(2),
.media-grid-3 .media-item:nth-child(3) {
    grid-column: 2;
}

.media-grid-3 .media-item:nth-child(2) img,
.media-grid-3 .media-item:nth-child(2) video,
.media-grid-3 .media-item:nth-child(3) img,
.media-grid-3 .media-item:nth-child(3) video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Four images - WhatsApp grid */
.media-grid-4 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px;
    max-width: min(100%, 320px);
    width: fit-content;
}

.media-grid-4 .media-item {
    position: relative;
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
}

.media-grid-4 .media-item img,
.media-grid-4 .media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    display: block;
}

/* Media item container */
.media-item {
    position: relative;
    overflow: hidden;
}

.media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

/* Overlay for videos and +N counter */
.media-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.media-overlay:hover {
    background: rgba(0, 0, 0, 0.5);
}

.media-overlay i {
    color: white;
    font-size: 32px;
}

.media-overlay .overlay-text {
    color: white;
    font-size: 24px;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}

/* Video play icon */
.media-item.video .media-overlay i {
    background: rgba(0, 0, 0, 0.6);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.message-media {
    margin-bottom: 6px;
    border-radius: 8px;
    overflow: hidden;
    max-width: 100%;
    position: relative;
    cursor: pointer;
}

.message-media img, .message-media video {
    max-width: 100%;
    width: auto;
    max-height: 250px;
    display: block;
    cursor: pointer;
}

/* Voice Message Styles */
.voice-message {
    min-width: 260px;
    max-width: 100%;
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    margin: 4px 0;
    width: fit-content;
}

.message.own .voice-message {
    background: rgba(0, 0, 0, 0.2);
}

.message.other .voice-message {
    background: rgba(255, 255, 255, 0.1);
}

/* Clean Voice Message Design */
.voice-message {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    min-width: 260px;
    max-width: 100%;
    width: fit-content;
    position: relative;
    user-select: none;
}

.message.own .voice-message {
    background: rgba(0, 0, 0, 0.15);
}

.voice-play-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--wa-accent);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s;
    font-size: 14px;
    box-shadow: 0 2px 8px rgba(0, 168, 132, 0.3);
}

.voice-play-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 168, 132, 0.4);
}

.voice-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.voice-progress-container {
    height: 16px;
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    position: relative;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 0 4px;
}

.voice-progress-bar {
    height: 4px;
    width: 0%;
    background: var(--wa-accent);
    border-radius: 2px;
    position: relative;
    transition: width 0.1s linear;
}

.voice-progress-bar::after {
    content: '';
    position: absolute;
    right: -4px;
    top: 50%;
    transform: translateY(-50%);
    width: 10px;
    height: 100%;
    background: white;
    border-radius: 50%;
    box-shadow: 0 0 6px rgba(0,0,0,0.3);
    opacity: 0;
    transition: opacity 0.2s;
}

.voice-progress-container:hover .voice-progress-bar::after {
    opacity: 1;
}

.voice-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.voice-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--wa-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.voice-duration {
    font-size: 11px;
    color: var(--wa-text-muted);
    font-family: monospace;
}

.voice-speed-btn {
    padding: 4px 8px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--wa-text);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    flex-shrink: 0;
}

.voice-speed-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}


.voice-message-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 200px;
}

.voice-play-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--wa-accent);
    border: none;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s;
    font-size: 14px;
}

.voice-play-btn:hover {
    transform: scale(1.08);
    background: var(--wa-accent-hover, #1a73e8);
}

.voice-play-btn:active {
    transform: scale(0.95);
}

.voice-play-btn.playing {
    background: rgba(255, 255, 255, 0.3);
    animation: pulse-playing 1.5s infinite;
}

@keyframes pulse-playing {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(255, 255, 255, 0); }
}

.voice-waveform {
    flex: 1;
    height: 40px;
    background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.2) 100%);
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
    min-width: 120px;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}

.voice-waveform canvas {
    width: 100% !important;
    height: 100% !important;
    display: block;
}

/* Make waveform cursor more visible */
.voice-waveform wave {
    cursor: pointer;
}

/* Add hover effect */
.voice-waveform:hover {
    background: linear-gradient(180deg, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.25) 100%);
    border-color: rgba(255, 255, 255, 0.15);
    box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.4), 0 0 10px rgba(74, 222, 128, 0.2);
}

/* Playing state glow */
.voice-message:has(.voice-play-btn.playing) .voice-waveform {
    border-color: rgba(74, 222, 128, 0.3);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3), 0 0 15px rgba(74, 222, 128, 0.3);
}

/* Waveform cursor styling */
.voice-waveform wave[part="progress"] {
    background: #22c55e !important;
}

.voice-duration {
    font-size: 11px;
    color: var(--wa-text-muted);
    min-width: 35px;
    text-align: center;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
}

.voice-speed-btn {
    padding: 3px 8px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--wa-text-muted);
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.voice-speed-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: var(--wa-text);
    border-color: rgba(255, 255, 255, 0.2);
}

.voice-speed-btn:active {
    transform: scale(0.95);
}

/* Voice Recording Overlay */
.voice-recording-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}

.recording-content {
    text-align: center;
    padding: 24px;
    position: relative;
    z-index: 100;
    pointer-events: none; /* Allow clicks to pass through to children */
}

.recording-content > * {
    pointer-events: auto; /* Re-enable clicks on direct children */
}

.recording-timer {
    font-size: 48px;
    font-weight: 700;
    color: var(--wa-text);
    margin-bottom: 24px;
    font-family: monospace;
}

.recording-timer.recording {
    color: #ef4445;
    /* Removed pulse animation - keep timer static */
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.recording-waveform {
    width: 300px;
    height: 80px;
    margin: 0 auto 24px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    pointer-events: none; /* Allow clicks to pass through */
}

.recording-waveform canvas {
    width: 100% !important;
    height: 100% !important;
}

.recording-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    position: relative;
    z-index: 100;
    pointer-events: none; /* Allow container to not block, children will override */
}

.recording-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s;
    position: relative;
    z-index: 110;
    pointer-events: auto; /* Enable clicks on buttons */
}

.recording-btn.cancel {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4445;
}

.recording-btn.cancel:hover {
    background: rgba(239, 68, 68, 0.3);
}

.recording-btn:not(.cancel):not(.send) {
    background: var(--wa-accent);
    color: white;
}

.recording-btn:not(.cancel):not(.send):hover {
    transform: scale(1.1);
}

.recording-btn:not(.cancel):not(.send).recording {
    background: #ef4445;
    animation: pulse-btn 1.5s infinite;
}

@keyframes pulse-btn {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    50% { box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
}

.recording-btn.send {
    background: var(--wa-accent) !important;
    color: white !important;
    position: relative;
    z-index: 10;
    pointer-events: auto !important;
}

.recording-btn.send:hover:not(:disabled) {
    transform: scale(1.1);
}

.recording-btn.send:disabled {
    opacity: 0.5 !important;
    cursor: not-allowed;
    pointer-events: none !important;
}

.recording-btn.send:not(:disabled) {
    cursor: pointer !important;
    pointer-events: auto !important;
    opacity: 1 !important;
    background: var(--wa-accent) !important;
}

/* Voice record button in input area */
.voice-record-btn {
    background: none;
    border: none;
    color: var(--wa-text-muted);
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.voice-record-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--wa-accent);
}

.voice-record-btn.recording {
    color: #ef4445;
    animation: pulse-icon 1s infinite;
}

@keyframes pulse-icon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.message-media img, .message-media video {
    max-width: 100%;
    width: auto;
    max-height: 250px;
    display: block;
    cursor: pointer;
}

.message-time {
    font-size: 10px;
    color: var(--wa-text-muted);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 3px;
    margin-top: -4px;
    margin-bottom: -2px;
    align-self: flex-end;
    pointer-events: auto;
    user-select: none;
}

.message-time i.read {
    color: #53bdeb;
}

.message-time i.sent, .message-time i.delivered {
    color: var(--wa-icon-muted);
}

.message.own .message-time {
    justify-content: flex-end;
}

.message.other .message-time {
    justify-content: flex-end;
}

.message.own .message-time i {
    font-size: 10px;
    flex-shrink: 0;
}

/* Message Actions Dropdown - Clean Background-free Design */
.msg-item-actions {
    position: absolute;
    top: 6px;
    inset-inline-end: 6px;
    z-index: 100;
    opacity: 0;
    transition: opacity 0.2s ease;
    padding: 0;
}

.message:hover .msg-item-actions {
    opacity: 1;
}

.msg-action-trigger {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.9);
    background: transparent;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4)); /* Visibility on media */
}

[data-theme="light"] .msg-action-trigger {
    color: #111b21 !important; /* Specific black color for light theme */
    filter: none;
}

.msg-action-trigger:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
}

[data-theme="light"] .msg-action-trigger:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #000000;
}

.msg-dropdown {
    position: absolute;
    top: 28px;
    background-color: #202c33 !important; /* Force solid dark */
    border: 1px solid #2f3b43;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8);
    min-width: 170px;
    display: none;
    padding: 6px 0;
    z-index: 99999; /* Higher than headers/inputs */
    opacity: 1 !important; /* No transparency */
}

/* WhatsApp-Style Media Bubble Overrides */
.message-content.has-media {
    padding: 3px !important;
    overflow: hidden;
    max-width: 350px !important;
    width: fit-content;
}

.message-content.has-media .message-media-album,
.message-content.has-media .message-media {
    margin: 0 !important;
    border-radius: 8px 8px 4px 4px;
}

.message-content.has-media .text {
    padding: 8px 12px 12px 12px !important;
    margin: 0 !important;
    display: block;
    width: 100%;
}

.message-content.has-media .message-time {
    position: absolute;
    bottom: 8px;
    right: 10px;
    background: rgba(0, 0, 0, 0.4);
    padding: 2px 6px;
    border-radius: 10px;
    color: white !important;
    backdrop-filter: blur(4px);
    z-index: 15;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
}

.message-content.has-media .message-time i {
    color: rgba(255, 255, 255, 0.7);
    font-size: 9px;
}

.message-content.has-media .message-time i.read {
    color: #53bdeb !important;
}

.message-content.has-media .message-time i.sent {
    color: rgba(255, 255, 255, 0.9) !important;
}

.message-content.has-media.has-text .message-time {
    position: static !important;
    background: transparent !important;
    padding: 0 10px 8px 10px !important;
    margin-top: -6px !important;
    color: var(--wa-text-muted) !important;
    backdrop-filter: none !important;
    align-self: flex-end;
    width: 100%;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .message-content.has-media {
        max-width: 100% !important;
    }
}

.message.other .msg-dropdown {
    inset-inline-start: 4px;
    inset-inline-end: auto;
}

.message.own .msg-dropdown {
    inset-inline-start: auto;
    inset-inline-end: 4px;
}

[data-theme="light"] .msg-dropdown {
    background-color: #ffffff !important; /* Force solid white */
    border-color: #e9edef;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.msg-dropdown.show {
    display: block !important;
}

.msg-dropdown.drop-up {
    top: auto !important;
    bottom: 32px !important;
    animation: msgDropdownUp 0.2s ease-out !important;
}

@keyframes msgDropdownUp {
    from { opacity: 0; transform: translateY(10px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 600px) {
    .msg-dropdown {
        min-width: 140px;
        max-width: 85vw;
    }
}

.msg-dropdown button.menu-item {
    width: 100%;
    padding: 10px 16px;
    background: transparent;
    border: none;
    color: var(--wa-text);
    text-align: left;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: background 0.2s;
}

.msg-dropdown button.menu-item:hover {
    background: var(--wa-panel-hover);
}

.msg-dropdown button.menu-item.danger {
    color: #ff5c5c;
}

.msg-dropdown button.menu-item.info {
    color: #53bdeb;
}

.msg-dropdown button.menu-item i {
    font-size: 16px;
    width: 20px;
    text-align: center;
    opacity: 0.7;
}

@media (max-width: 900px), (pointer: coarse) {
    .msg-item-actions {
        opacity: 0.8;
        background: none;
        width: 24px;
        height: 24px;
    }
    .msg-action-trigger {
        width: 24px;
        height: 24px;
        font-size: 12px;
        background: transparent;
        color: rgba(255, 255, 255, 0.8);
        filter: drop-shadow(0 1px 1px rgba(0,0,0,0.5));
    }
}

/* Overridden by premium styles above */

/* ═══════════════════════════════════════════════
   MESSAGE REACTIONS
   ═══════════════════════════════════════════════ */

/* Reaction badges bar - Floating and positioned as requested */
.message-reactions-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    z-index: 10;
    position: absolute;
    bottom: -12px;
    right: 12px;
    pointer-events: auto;
}

/* My messages (right side) -> reactions on BOTTOM LEFT */
.message.own .message-reactions-bar {
    right: auto;
    left: 12px;
}

/* Other users (left side) -> reactions on BOTTOM RIGHT */
.message.other .message-reactions-bar {
    right: 12px;
}

.reaction-group-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    background: #1e262c;
    border: 1px solid rgba(255, 255, 255, 0.1);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-size: 13px;
    user-select: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.4);
}

.reaction-group-pill:hover {
    background: #2a333d;
    transform: scale(1.1);
}

.reaction-group-pill.has-mine {
    background: #004a3a;
    border-color: #00a884;
}

.reaction-emoji-stack {
    display: flex;
    align-items: center;
}

.reaction-emoji-stack .stack-emoji {
    font-size: 14px;
    line-height: 1;
    margin-inline-start: -2px;
}

.reaction-emoji-stack .stack-emoji:first-child {
    margin-inline-start: 0;
}

.reaction-total-count {
    font-size: 11px;
    font-weight: 700;
    color: #e9edef;
    margin-inline-start: 2px;
}

.reaction-group-pill.has-mine .reaction-total-count {
    color: #00a884;
}

[data-theme="light"] .reaction-group-pill {
    background: #ffffff;
    border-color: rgba(0, 0, 0, 0.08);
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

[data-theme="light"] .reaction-group-pill .reaction-total-count {
    color: #54656f;
}

[data-theme="light"] .reaction-group-pill:hover {
    background: #f0f2f5;
}

[data-theme="light"] .reaction-group-pill.has-mine {
    background: #e7fce3;
    border-color: #00a884;
}

/* Message Reactions positioning - ensure it stays floating without shrinking parent bubble */
.message-bubble {
    transition: none;
    margin-bottom: 4px;
}

.message-bubble.has-reactions .message-content {
    padding-bottom: 10px !important; /* Keep consistent with base padding */
}

/* Reactors modal CSS moved to global app-layout.css */

/* Quick React Button - Next to bubble */
.quick-react-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: #8696a0;
    cursor: pointer;
    transition: all 0.2s ease;
    opacity: 0; /* Hidden by default, shown on message hover */
    margin-top: auto;
    margin-bottom: 8px;
    flex-shrink: 0;
}

.message:hover .quick-react-btn {
    opacity: 1;
}

.quick-react-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #e9edef;
    transform: scale(1.1);
}

.message.own .quick-react-btn {
    margin-inline-end: 4px;
}

.message.other .quick-react-btn {
    margin-inline-start: 4px;
}

[data-theme="light"] .quick-react-btn:hover {
    background: #f0f2f5;
    color: #54656f;
}

@media (max-width: 768px) {
    .quick-react-btn {
        opacity: 0.5; /* Always visible but subtle on mobile */
        width: 28px;
        height: 28px;
        transition: opacity 0.2s ease;
    }
    .message:active .quick-react-btn,
    .message:hover .quick-react-btn {
        opacity: 1;
    }
}

/* Floating Reaction Picker */
.reaction-picker-overlay {
    position: fixed;
    inset: 0;
    z-index: 9998;
    background: transparent;
}

.msg-reaction-picker {
    position: fixed;
    z-index: 9999;
    background: rgba(22, 22, 22, 0.85);
    backdrop-filter: blur(25px) saturate(180%);
    -webkit-backdrop-filter: blur(25px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    padding: 10px;
    display: flex;
    gap: 6px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.4), 
                0 0 0 1px rgba(255, 255, 255, 0.05);
    animation: reactionPickerIn 0.25s cubic-bezier(0.18, 0.89, 0.32, 1.28);
}


@keyframes reactionPickerIn {
    from { 
        opacity: 0; 
        transform: scale(0.9) translateY(10px); 
    }
    to { 
        opacity: 1; 
        transform: scale(1) translateY(0); 
    }
}


.emoji-list {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-around;
    gap: 4px;
    padding: 2px;
}

.emoji-list span {
    font-size: 18px;
    cursor: pointer;
    padding: 4px;
    border-radius: 8px;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
}

.emoji-list span:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.25);
}

.emoji-list span.active-pick {
    background: #6366f1 !important;
    color: white;
    transform: scale(1.15);
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.5);
}


[data-theme="light"] .msg-reaction-picker {
    background: #ffffff;
    border-color: rgba(0, 0, 0, 0.1);
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
}

[data-theme="light"] .msg-reaction-picker .emoji-list span:hover {
    background: #f0f2f5;
}

@media (max-width: 768px) {
    .msg-reaction-picker {
        padding: 5px 8px;
        gap: 0px;
        border-radius: 30px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        white-space: nowrap;
        width: max-content;
        max-width: calc(100vw - 20px);
        overflow: visible;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .msg-reaction-picker button {
        font-size: 22px;
        width: 38px;
        height: 38px;
        flex-shrink: 0;
    }

    .msg-reaction-picker button:hover {
        transform: scale(1.1);
        background: transparent;
    }

    .message-reactions-bar {
        margin-top: -10px;
        padding: 0 4px;
        z-index: 10;
    }

    .reaction-badge {
        padding: 1px 6px;
        font-size: 11px;
    }

    .reaction-badge .reaction-emoji {
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .msg-reaction-picker {
        padding: 4px 6px;
        border-radius: 25px;
    }
    .msg-reaction-picker button {
        font-size: 20px;
        width: 34px;
        height: 34px;
    }
    .message-reactions-bar {
        margin-top: -8px;
    }
}

@media (max-width: 320px) {
    .msg-reaction-picker button {
        font-size: 18px;
        width: 30px;
        height: 30px;
    }
}

/* Group Invite */
.invite-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(0, 0, 0, 0.2);
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 6px;
}

.invite-icon {
    width: 42px;
    height: 42px;
    background: var(--wa-accent);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.invite-content { flex: 1; }
.invite-title { 
    font-weight: 600; 
    margin-bottom: 3px;
    color: #e9edef;
}
.invite-text { 
    font-size: 12px; 
    opacity: 0.8;
    color: #e9edef;
}

.accept-btn {
    background: var(--wa-accent);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

.accept-btn:hover { background: var(--wa-green); }
.accept-btn.joined { background: var(--wa-text-muted); }

/* System Message */
.system-message {
    align-self: center;
    background: var(--wa-panel);
    padding: 8px 20px;
    border-radius: 12px;
    text-align: center;
    margin: 16px auto;
    border: 1px solid var(--wa-border);
    width: fit-content;
    max-width: 85%;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}


.system-text { 
    font-size: 12px; 
    color: var(--wa-text-muted); 
    font-family: 'Inter', 'Cairo', sans-serif;
}
.system-text[dir="rtl"] {
    font-size: 13.5px;
    line-height: 1.6;
}
.system-time { font-size: 10px; color: var(--wa-text-muted); opacity: 0.7; display: block; margin-top: 3px; }

/* No Messages */
.no-messages {
    align-self: center;
    text-align: center;
    color: var(--wa-text-muted);
    margin: auto 0;
}

.no-messages i { font-size: 56px; margin-bottom: 16px; opacity: 0.2; }
.no-messages p { margin: 0; font-size: 15px; }

/* Input Area - styles only (position is fixed in earlier CSS) */
#messageForm { display: flex; flex-direction: column; gap: 8px; }

/* Date Dividers */
.chat-date-divider {
    display: flex;
    justify-content: center;
    margin: 24px 0;
    position: relative;
    z-index: 5;
}

.chat-date-divider span {
    background: var(--wa-panel);
    color: var(--wa-text-muted);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    border: 1px solid var(--wa-border);
    font-family: 'Inter', 'Cairo', sans-serif;
}

.chat-date-divider span[dir="rtl"] {
    font-size: 13.5px;
    line-height: 1.6;
    padding-bottom: 8px;
}

/* Typing Indicator */
.typing-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    margin-bottom: 4px;
    color: #25d366;
    font-size: 13px;
    font-weight: 500;
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.typing-indicator-sidebar {
    display: none;
    color: #25d366 !important;
    font-size: 13px;
    font-style: italic;
    font-weight: 600;
    animation: typing-fade 1.5s infinite;
}

.conversation-item.is-typing .preview-content-wrapper {
    display: none !important;
}

.conversation-item.is-typing .typing-indicator-sidebar {
    display: block !important;
}

@keyframes typing-fade {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}

.typing-indicator.hiding {
    opacity: 0;
    transform: translateY(5px);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.typing-dots {
    display: flex;
    gap: 3px;
    align-items: center;
}

.typing-dots .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #25d366;
    animation: typingBounce 1.4s infinite ease-in-out both;
}

.typing-dots .dot:nth-child(1) {
    animation-delay: -0.32s;
}

.typing-dots .dot:nth-child(2) {
    animation-delay: -0.16s;
}

@keyframes typingBounce {
    0%, 80%, 100% {
        transform: scale(0.6);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.media-preview {
    background: var(--wa-bg);
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 8px;
}

/* Carousel Preview */
.preview-carousel {
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.carousel-arrow {
    background: var(--wa-panel);
    border: 1px solid var(--wa-border);
    color: var(--wa-text);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
}

.carousel-arrow:hover {
    background: var(--wa-accent);
    border-color: var(--wa-accent);
}

.carousel-arrow:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.preview-slides {
    flex: 1;
    overflow: hidden;
    position: relative;
    height: clamp(150px, 25vh, 200px);
}

.preview-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-slide.active {
    opacity: 1;
    z-index: 1;
}

.preview-slide img,
.preview-slide video {
    max-width: 100%;
    max-height: 100%;
    border-radius: 8px;
    object-fit: contain;
}

.preview-slide .slide-number {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.preview-slide .remove-slide {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(255, 59, 48, 0.9);
    color: white;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.2s;
}

.preview-slide .remove-slide:hover {
    background: rgba(255, 59, 48, 1);
    transform: scale(1.1);
}

.preview-indicators {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 10px;
}

.preview-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--wa-border);
    cursor: pointer;
    transition: all 0.2s;
}

.preview-indicator.active {
    background: var(--wa-accent);
    width: 24px;
    border-radius: 4px;
}

.preview-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--wa-border);
}

.preview-info #previewCount {
    font-size: 12px;
    color: var(--wa-text-muted);
}

.clear-all {
    background: transparent;
    border: none;
    color: var(--wa-red);
    cursor: pointer;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}

.clear-all:hover {
    background: rgba(255, 59, 48, 0.1);
    border-radius: 6px;
}

.input-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.attach-btn, .emoji-btn {
    background: transparent;
    border: none;
    color: var(--wa-text-muted);
    cursor: pointer;
    font-size: 18px;
    padding: 8px;
}

.attach-btn:hover, .emoji-btn:hover { color: var(--wa-text); }

#messageInput {
    flex: 1;
    padding: 12px 16px;
    background: var(--wa-bg);
    border: none;
    border-radius: 24px;
    color: var(--wa-text);
    font-size: 14px;
    outline: none;
}

#messageInput {
    flex: 1;
    padding: 12px 16px;
    background: var(--wa-bg);
    border: none;
    border-radius: 20px;
    color: var(--wa-text);
    font-size: 15px;
    outline: none;
}

#messageInput:focus {
    box-shadow: none;
}

.send-btn {
    width: 42px;
    height: 42px;
    border: none;
    background: var(--wa-accent);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

.send-btn:active,
.send-btn:focus {
    background: var(--wa-accent) !important;
    outline: none !important;
    -webkit-tap-highlight-color: transparent !important;
}

/* Enhanced Nexus Gallery */
.nexus-gallery {
    position: fixed;
    inset: 0;
    z-index: 2000000;
    display: none;
    flex-direction: column;
    background: rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(10px);
    user-select: none;
}

.nexus-gallery[aria-hidden="false"] {
    display: flex;
}

.gallery-overlay {
    position: absolute;
    inset: 0;
    z-index: -1;
}

.gallery-header {
    height: 70px;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 100;
    background: linear-gradient(to bottom, rgba(0,0,0,0.5), transparent);
}

.gallery-counter {
    color: white;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    letter-spacing: 1px;
    background: rgba(0, 0, 0, 0.6);
    padding: 8px 16px;
    border-radius: 20px;
    backdrop-filter: blur(5px);
}

.gallery-footer {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    background: linear-gradient(to top, rgba(0,0,0,0.5), transparent);
}

.gallery-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.gallery-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

.gallery-main {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.gallery-content {
    max-width: 100%;
    max-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

#galleryImage, #galleryVideo {
    max-width: 95vw;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    transition: transform 0.3s ease;
}

.gallery-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    z-index: 1000;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(5px);
}

.gallery-nav-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-50%) scale(1.1);
    border-color: rgba(255, 255, 255, 0.3);
}

.gallery-nav-btn.left { left: 40px; }
.gallery-nav-btn.right { right: 40px; }

@media (max-width: 768px) {
    .nexus-gallery {
        z-index: 2147483647 !important;
    }
    
    #galleryClose {
        position: fixed !important;
        top: calc(env(safe-area-inset-top, 0px) + 20px) !important;
        right: 20px !important;
        width: 50px !important;
        height: 50px !important;
        background: rgba(0, 0, 0, 0.7) !important;
        border: 2px solid #fff !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 2147483647 !important;
        opacity: 1 !important;
        visibility: visible !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.5) !important;
    }

    #galleryClose i {
        font-size: 24px !important;
        color: #fff !important;
    }

    .gallery-header { 
        height: auto; 
        padding: calc(env(safe-area-inset-top, 10px) + 15px) 20px 15px 20px;
        background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent) !important;
        pointer-events: none;
    }
    .gallery-header > * { pointer-events: auto; }
}

#scrollToBottomBtn {
    position: absolute;
    bottom: 85px;
    right: 20px;
    width: 42px;
    height: 42px;
    background: var(--wa-panel);
    color: var(--wa-text-muted);
    border: 1px solid var(--wa-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 9000;
    transition: all 0.2s ease;
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

#scrollToBottomBtn i {
    font-size: 16px;
}

#scrollToBottomBtn.visible,
#scrollToBottomBtn.has-new-msg {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

#scrollToBottomBtn:hover {
    background: var(--wa-panel-hover);
    color: var(--wa-accent);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
}

#scrollToBottomBtn:active {
    transform: translateY(0) scale(0.95);
}

#scrollToBottomBtn .new-msg-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: var(--wa-accent);
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid var(--wa-panel);
    display: none;
}

#scrollToBottomBtn.has-new-msg .new-msg-badge {
    display: block;
}

@media (max-width: 900px) {
    #scrollToBottomBtn {
        position: fixed;
        bottom: 100px;
        right: 20px;
        width: 45px;
        height: 45px;
        z-index: 1000;
    }
}



/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99998;
    align-items: center;
    justify-content: center;
}

.modal-box {
    background: var(--surface);
    border: 1px solid var(--border);
    width: 100%;
    max-width: 420px;
    border-radius: var(--radius-2xl);
    overflow: hidden;
    box-shadow: var(--elev-3);
}

.message-info-modal {
    max-width: 400px;
}
.info-section {
    margin-bottom: 20px;
}
.message-info-modal {
    max-width: 420px;
    background: var(--wa-bg) !important;
    border-radius: 12px;
}
.info-section {
    margin-bottom: 24px;
}
.info-section:last-child {
    margin-bottom: 0;
}
.info-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--wa-accent);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.info-section-title i.read {
    color: var(--wa-blue);
}
.info-section-title i.sent {
    color: var(--wa-icon-muted);
}
.info-user-list {
    display: flex;
    flex-direction: column;
    max-height: 280px;
    overflow-y: auto;
}
.info-user-list::-webkit-scrollbar {
    width: 4px;
}
.info-user-list::-webkit-scrollbar-thumb {
    background: var(--wa-border);
    border-radius: 10px;
}
.info-user-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 8px;
    border-bottom: 1px solid var(--wa-border);
    transition: background 0.15s;
}
.info-user-item:last-child {
    border-bottom: none;
}
.info-user-item:hover {
    background: rgba(255, 255, 255, 0.04);
}
.info-user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
}
.info-user-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.info-user-name {
    font-size: 15px;
    color: var(--wa-text);
    font-weight: 500;
}
.info-user-time {
    font-size: 12px;
    color: var(--wa-text-muted);
}
.info-empty {
    font-size: 13px;
    color: var(--wa-text-muted);
    padding: 15px 10px;
    text-align: center;
}
.info-loading {
    display: flex;
    justify-content: center;
    padding: 30px 0;
    color: var(--wa-accent);
    font-size: 24px;
}

.modal-header {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--wa-border);
}

.modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 500;
    color: var(--wa-text);
    flex: 1;
    text-align: center;
}

.modal-header .close-btn {
    background: none;
    border: none;
    color: var(--wa-text-muted);
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
    width: 36px;
    height: 36px;
    margin-right: -8px; /* Offset padding for alignment */
}

.modal-header .close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--wa-text);
}

.spacer { width: 36px; }

.modal-body { padding: 16px; }

.search-box {
    position: relative;
    margin-bottom: 16px;
}

.search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--wa-text-muted);
}

.search-input {
    width: 100%;
    padding: 11px 14px 11px 42px;
    background: var(--wa-bg);
    border: none;
    border-radius: 8px;
    color: var(--wa-text);
    font-size: 14px;
    outline: none;
}

.search-input:focus { box-shadow: 0 0 0 2px var(--wa-accent); }

/* Delete Message Modal */
.delete-modal .modal-header h3 {
    text-align: left;
}

.delete-description {
    color: var(--wa-text-muted);
    font-size: 14px;
    margin: 0 0 16px 0;
}

.delete-option {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    width: 100%;
    padding: 16px;
    margin-bottom: 12px;
    background: #1c272d; /* Solid Dark Option */
    border: 1px solid var(--wa-border);
    border-radius: 10px;
    cursor: pointer;
    transition: 0.2s;
    text-align: left;
}

[data-theme="light"] .delete-option {
    background: #f8f9fa; /* Solid Light Option */
    border-color: #e9edef;
}

.delete-option:hover {
    background: var(--wa-border);
    border-color: var(--wa-text-muted);
}

.delete-option:last-child {
    margin-bottom: 0;
}

.delete-option-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--wa-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.delete-option:nth-child(2) .delete-option-icon {
    color: var(--wa-red);
    background: rgba(241, 92, 109, 0.1);
}

.delete-option:nth-child(3) .delete-option-icon {
    color: var(--wa-text-muted);
    background: rgba(134, 150, 160, 0.1);
}

.delete-option-content {
    flex: 1;
    min-width: 0;
}

.delete-option-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--wa-text);
    margin-bottom: 4px;
}

.delete-option-desc {
    font-size: 13px;
    color: var(--wa-text-muted);
    line-height: 1.4;
}

@media (max-width: 600px) {
    .delete-modal {
        width: 92% !important;
        max-width: 340px !important;
    }
    .delete-option {
        padding: 12px !important;
        gap: 12px !important;
        margin-bottom: 8px !important;
    }
    .delete-option-icon {
        width: 32px !important;
        height: 32px !important;
        font-size: 15px !important;
    }
    .delete-option-title {
        font-size: 14px !important;
        margin-bottom: 2px !important;
    }
    .delete-option-desc {
        font-size: 11px !important;
        line-height: 1.3 !important;
    }
    .delete-description {
        font-size: 13px !important;
        margin-bottom: 12px !important;
    }
    .modal-header h3 {
        font-size: 16px !important;
    }
}

.results-list { max-height: 320px; overflow-y: auto; }

.result-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
}

.result-item:hover { background: var(--wa-panel-hover); }

.result-item img, .result-item .avatar-fallback {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 12px;
}

.result-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
    min-width: 0;
}

.result-name { 
    font-size: 14px; 
    color: var(--wa-text); 
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.result-fullname {
    font-size: 12px;
    color: var(--wa-text-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Responsive */
@media (max-width: 900px) {
    /* Show back button on mobile */
    .back-btn-mobile {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: var(--wa-text);
        font-size: 18px;
        width: 32px;
        height: 32px;
    }

    .chat-sidebar {
        position: fixed;
        left: 0;
        top: 64px;
        bottom: 0;
        width: 100%;
        max-width: none;
        z-index: 9999;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        margin-top: 0;
        border-right: 1px solid var(--wa-border);
        background: var(--wa-bg);
    }

    .chat-sidebar.active { transform: translateX(0); }

    /* Hide main website mobile nav on chat pages */
    .mobile-nav, .app-layout ~ .mobile-nav {
        display: none !important;
    }
    
    /* Fix input row on mobile */
    .input-row {
        gap: 8px;
        min-width: 0;
    }
    
    /* Fix message input on mobile */
    #messageInput {
        min-width: 0;
        flex: 1;
        padding: 10px 14px;
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    /* Fix send button on mobile */
    .send-btn {
        width: 40px;
        height: 40px;
        min-width: 40px;
        flex-shrink: 0;
    }
    
    /* Fix attach and voice buttons on mobile */
    .attach-btn, .voice-record-btn {
        flex-shrink: 0;
        width: 40px;
        min-width: 40px;
    }
    
    /* Voice recording overlay on mobile */
    .voice-recording-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
    }
    
    .recording-timer {
        font-size: 36px;
    }
    
    .recording-waveform {
        width: 250px;
        height: 60px;
    }
    
    .recording-btn {
        width: 48px;
        height: 48px;
    }
    
    /* Voice messages on mobile */
    .voice-message {
        min-width: 160px;
        max-width: 100%;
        padding: 4px 8px;
    }

    .voice-message-controls {
        gap: 4px;
        min-width: 120px;
    }

    .voice-play-btn {
        width: 26px;
        height: 26px;
        font-size: 9px;
        flex-shrink: 0;
    }

    .voice-waveform {
        height: 24px;
        min-width: 60px;
        flex: 1;
        border-radius: 12px;
    }

    .voice-duration {
        font-size: 8px;
        min-width: 24px;
        flex-shrink: 0;
    }

    .voice-speed-btn {
        padding: 2px 4px;
        font-size: 7px;
        flex-shrink: 0;
    }

    /* Clean voice messages on mobile */
    .voice-message {
        padding: 8px 10px;
        min-width: 180px;
        gap: 8px;
        border-radius: 18px;
    }

    .voice-play-btn {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }

    .voice-label {
        font-size: 11px;
    }

    .voice-duration {
        font-size: 10px;
    }
    
    /* Voice recording overlay on small screens */
    .recording-waveform {
        width: 200px;
        height: 50px;
    }

    .recording-btn {
        width: 44px;
        height: 44px;
    }
}

@media (max-width: 600px) {
    /* Smaller voice messages on very small screens */
    .voice-message {
        min-width: 150px;
        padding: 3px 6px;
    }

    .voice-message-controls {
        gap: 3px;
        min-width: 110px;
    }

    .voice-play-btn {
        width: 24px;
        height: 24px;
        font-size: 9px;
    }

    .voice-waveform {
        height: 22px;
        min-width: 50px;
    }

    .voice-duration {
        font-size: 7px;
        min-width: 22px;
    }

    .voice-speed-btn {
        display: none; /* Hide speed control on very small screens */
    }

    /* Clean voice messages on very small screens */
    .voice-message {
        padding: 6px 8px;
        min-width: 160px;
        gap: 6px;
        border-radius: 16px;
    }

    .voice-play-btn {
        width: 26px;
        height: 26px;
        font-size: 11px;
    }

    .voice-label {
        font-size: 10px;
    }

    .voice-duration {
        font-size: 9px;
    }

    /* Voice recording overlay on very small screens */
    .recording-waveform {
        width: 180px;
        height: 45px;
    }

    .recording-btn {
        width: 40px;
        height: 40px;
    }
}

/* Unified small screen tweaks */
@media (max-width: 600px) {
    .message-content {
        max-width: 90%;
        font-size: 14px;
    }
    .input-row {
        gap: 4px;
    }
    .text {
        font-size: 13.5px;
    }
    .text[dir="rtl"] {
        font-size: 15px; /* Larger Arabic on mobile */
        line-height: 1.7;
    }
}
    
    /* Smaller buttons on small screens */
    .send-btn {
        width: 38px;
        height: 38px;
        min-width: 38px;
    }
    
    .attach-btn, .voice-record-btn {
        width: 38px;
        min-width: 38px;
        padding: 6px;
        position: relative;
        z-index: 5;
    }
    
    /* Voice messages on small screens */
    .voice-message {
        min-width: 160px;
        padding: 4px 8px;
    }

    .voice-message-controls {
        gap: 4px;
        min-width: 120px;
    }

    .voice-play-btn {
        width: 26px;
        height: 26px;
        font-size: 9px;
    }

    .voice-waveform {
        height: 24px;
        min-width: 60px;
    }

    .voice-duration {
        font-size: 8px;
        min-width: 24px;
    }

    .voice-speed-btn {
        display: none; /* Hide speed control on very small screens */
    }

    /* Clean voice messages on small screens */
    .voice-message {
        padding: 8px 10px;
        min-width: 170px;
        gap: 8px;
        border-radius: 18px;
    }

    .voice-play-btn {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .voice-label {
        font-size: 11px;
    }

    .voice-duration {
        font-size: 10px;
    }

    /* Mobile message sizing */
    .message {
        width: 100%;
        max-width: 100%;
    }
    .message-bubble {
        max-width: 88%;
    }
    
    /* Ensure messages with short text don't stretch */
    .message-content {
        max-width: 100%;
        padding: 8px 10px 18px 10px; /* Increased bottom padding for reactions */
        padding-inline-end: 28px; /* Maintain space for chevron on mobile */
    }
    
    /* Sender name smaller on mobile */
    .sender-name {
        font-size: 11px;
        padding: 0 10px;
    }
    
    /* Better media sizing on mobile */
    .message-media img,
    .message-media video,
    .media-grid-single img,
    .media-grid-single video {
        max-height: 180px;
        max-width: 100%;
    }

    .media-grid-two,
    .media-grid-3,
    .media-grid-4 {
        max-width: 280px;
        width: fit-content;
    }

    .media-grid-two .media-item,
    .media-grid-3 .media-item,
    .media-grid-4 .media-item {
        width: 100%;
        aspect-ratio: 1;
        overflow: hidden;
    }

    .media-grid-two .media-item img,
    .media-grid-two .media-item video,
    .media-grid-3 .media-item img,
    .media-grid-3 .media-item video,
    .media-grid-4 .media-item img,
    .media-grid-4 .media-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    /* Compact time and status on mobile */
    .message-time {
        font-size: 10px;
        margin-top: 2px;
    }
    
    .message.own .message-time i {
        font-size: 9px;
    }
    
    /* Reaction bar mobile adjustments */
    .message-reactions-bar {
        bottom: -12px !important;
        right: 4px !important;
    }

    .message.own .message-reactions-bar {
        right: auto !important;
        left: 4px !important;
    }

    /* Delete button on mobile */
    .delete-btn {
        padding: 4px 8px;
        font-size: 11px;
        margin-top: 2px;
    }

    /* Hide main website mobile nav on chat pages */
    .mobile-nav {
        display: none !important;
    }
}

/* Scrollbar styling */
.chat-messages::-webkit-scrollbar { width: 6px; }
.chat-messages::-webkit-scrollbar-track { background: transparent; }
.chat-messages::-webkit-scrollbar-thumb { background: var(--wa-border); border-radius: 3px; }

.conv-list::-webkit-scrollbar { width: 6px; }
.conv-list::-webkit-scrollbar-track { background: transparent; }
.conv-list::-webkit-scrollbar-thumb { background: var(--wa-border); border-radius: 3px; }

/* Tablet view optimization */
@media (min-width: 601px) and (max-width: 900px) {
    .message {
        max-width: 70%;
    }
    
    .message-media img,
    .message-media video,
    .media-grid-single img {
        max-height: 220px;
    }
}

/* Reply Preview & Bubbles */
.reply-preview-container {
    padding: 10px 14px;
    background: var(--wa-panel);
    border-bottom: 1px solid var(--wa-border);
    animation: slideUp 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
}

@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.reply-preview-content {
    display: flex;
    align-items: center;
    background: rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(8px);
    border-radius: 10px;
    padding: 10px 14px;
    position: relative;
    gap: 14px;
    transition: all 0.2s ease;
}

[data-theme="light"] .reply-preview-content {
    background: rgba(0, 0, 0, 0.04);
}

.reply-preview-content:hover {
    background: rgba(0, 0, 0, 0.12);
}

.reply-preview-border {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: var(--wa-accent);
    border-radius: 5px 0 0 5px;
    box-shadow: 2px 0 8px rgba(0, 168, 132, 0.3);
}

.reply-preview-details {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.reply-preview-user {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--wa-accent);
    letter-spacing: 0.2px;
}

.reply-preview-text {
    font-size: 13px;
    color: var(--wa-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}

.reply-preview-close {
    background: var(--wa-panel);
    border: 1px solid var(--wa-border);
    color: var(--wa-text-muted);
    cursor: pointer;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.reply-preview-close:hover {
    color: var(--wa-red);
    transform: rotate(90deg);
    background: rgba(241, 92, 109, 0.1);
}

/* Replied Message Box (Synchronized with Global Chat) */
.replied-message-box {
    background: rgba(0, 0, 0, 0.05);
    border-inline-start: 3px solid var(--wa-accent);
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 8px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 2px;
    max-width: 100%;
    overflow: hidden;
}

[data-theme="dark"] .replied-message-box {
    background: rgba(255, 255, 255, 0.08);
}

.message.own .replied-message-box {
    background: rgba(0, 0, 0, 0.12);
    border-inline-start-color: rgba(255, 255, 255, 0.6);
}

.replied-user {
    color: var(--wa-accent);
    font-weight: 700;
    font-size: 11px;
    display: block;
    margin-bottom: 2px;
    letter-spacing: 0.5px;
}

.message.own .replied-user {
    color: #ffffff;
}

.replied-content {
    font-size: 12px;
    color: var(--wa-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    opacity: 0.9;
}

/* Full-Width Row Highlight (Synchronized with Global Chat) */
.message.highlight-msg {
    animation: fullRowHighlight 2s ease !important;
}

@keyframes fullRowHighlight {
    0% { background: #dcf8c6; }
    30% { background: #dcf8c6; }
    100% { background: transparent; }
}

[data-theme="dark"] .message.highlight-msg {
    animation: fullRowHighlightDark 2s ease !important;
}

@keyframes fullRowHighlightDark {
    0% { background: rgba(99, 102, 241, 0.25); }
    30% { background: rgba(99, 102, 241, 0.25); }
    100% { background: transparent; }
}

/* Floating Side Actions */
.msg-side-actions {
    display: flex;
    flex-direction: column;
    gap: 0px; /* Extremely tight grouping */
    opacity: 0;
    transition: all 0.2s ease;
    align-items: center;
    justify-content: center;
    align-self: center;
    flex-shrink: 0;
    z-index: 5;
}

.message:hover .msg-side-actions {
    opacity: 1;
}

.message.own .msg-side-actions {
    margin-inline-end: 6px;
}

.message:not(.own) .msg-side-actions {
    margin-inline-start: 6px;
}

.side-action-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: transparent;
    border: none;
    color: var(--wa-icon-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-size: 15px;
}

.side-action-btn:hover {
    background: rgba(0, 168, 132, 0.1);
    color: var(--wa-accent);
    transform: scale(1.2);
}

[data-theme="light"] .side-action-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

@media (max-width: 900px) {
    .msg-side-actions {
        opacity: 0.8 !important;
        margin-inline-start: 2px;
    }
    .message.own .msg-side-actions {
        margin-inline-end: 2px;
    }
    .side-action-btn {
        width: 26px;
        height: 26px;
        font-size: 13px;
    }
}




</style>



<script>
// Sidebar toggle for mobile
function toggleSidebar() {
    document.getElementById('chatSidebar').classList.toggle('active');
}

// Filter sidebar conversations
function filterSidebarConversations(q) {
    const items = document.querySelectorAll('#sidebarConvList .conversation-item');
    const query = q.toLowerCase();
    items.forEach(item => {
        const name = item.getAttribute('data-name')?.toLowerCase() || '';
        item.style.display = name.includes(query) ? 'flex' : 'none';
    });
}

// User search modal
function showUserSearch() {
    document.getElementById('userSearchModal').style.display = 'flex';
    setTimeout(() => document.getElementById('userSearch').focus(), 100);
}

function hideUserSearch() {
    document.getElementById('userSearchModal').style.display = 'none';
}

// User search
document.getElementById('userSearch')?.addEventListener('input', function() {
    const query = this.value.trim();
    const results = document.getElementById('userResults');
    if (query.length < 2) { results.innerHTML = ''; return; }

    fetch(`/api/search-users?q=${encodeURIComponent(query)}`, {
        credentials: 'include',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.users.length) {
            results.innerHTML = data.users.map(u => {
                const vb = u.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
                return `<div class="result-item" onclick="startChat(${u.id})">
                    <img src="${escapeHtml(u.avatar_url)}">
                    <div class="result-info">
                        <div class="result-name" style="display:inline-flex;align-items:center;gap:.15em;">${escapeHtml(u.username)}${vb}</div>
                        ${u.username ? `<div class="result-fullname">@${escapeHtml(u.username)}</div>` : ''}
                    </div>
                </div>`;
            }).join('');
        }
    });
});

function escapeHtml(t) {
    const d = document.createElement('div');
    d.textContent = t || '';
    return d.innerHTML;
}

function linkifyText(text) {
    if (!text) return '';
    const escaped = escapeHtml(text);
    return escaped.replace(
        /(https?:\/\/[^\s<>"']{4,})/gi,
        '<a href="$1" target="_blank" rel="noopener noreferrer" class="chat-link">$1</a>'
    );
}

function startChat(id) { window.location.href = '/chat/start/' + id; }

// Message sending queue to prevent race conditions when sending fast
let messageSendQueue = [];
let isSendingMessage = false;
let lastSentMessageId = 0;

// Process message queue sequentially
let replyingTo = null;

function initiateReply(messageId) {
    const messageEl = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (!messageEl) return;

    const senderName = messageEl.getAttribute('data-sender-name') || 
                       (messageEl.classList.contains('own') ? window.chatTranslations.you : 'User');
    
    let content = '';
    const textEl = messageEl.querySelector('.text');
    if (textEl) {
        content = textEl.textContent;
    } else if (messageEl.querySelector('.message-media')) {
        content = '[Media]';
    } else if (messageEl.querySelector('.voice-player')) {
        content = '[Voice Message]';
    }

    replyingTo = {
        id: messageId,
        user: senderName,
        content: content
    };

    const preview = document.getElementById('replyPreview');
    const previewUser = document.getElementById('replyPreviewUser');
    const previewText = document.getElementById('replyPreviewText');

    previewUser.textContent = senderName;
    previewText.textContent = content;
    preview.style.display = 'block';

    const input = document.getElementById('messageInput');
    input.focus();
    
    // Scroll preview into view if needed
    preview.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function cancelReply() {
    replyingTo = null;
    document.getElementById('replyPreview').style.display = 'none';
}

function scrollToMessage(event, messageId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (!messageId) return;
    
    const messageEl = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (messageEl) {
        // Small timeout to ensure click handling is complete and prevent "rescroll"
        setTimeout(() => {
            messageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remove and re-add for consecutive clicks
            messageEl.classList.remove('highlight-msg');
            void messageEl.offsetWidth; // Force reflow
            messageEl.classList.add('highlight-msg');
            
            setTimeout(() => messageEl.classList.remove('highlight-msg'), 2000);
        }, 50);
    } else {
        if (typeof showToast === 'function') {
            showToast('{{ __("chat.message_not_found") }}', 'info');
        }
    }
}

function processMessageQueue() {
    if (isSendingMessage || messageSendQueue.length === 0) return;

    const messageData = messageSendQueue.shift();
    isSendingMessage = true;

    // Check if this is a media message
    if (messageData.isMedia) {
        processMediaMessage(messageData);
        return;
    }

    const body = { content: messageData.content };
    if (replyingTo) {
        body.reply_to_id = replyingTo.id;
        cancelReply();
    }
    if (messageData.link_preview) {
        body.link_preview = messageData.link_preview;
    }

    fetch(`{{ route('chat.store', $conversation) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const message = data.message;

            addMessage(message);
            if (window.updateExistingConversationItem) {
                window.updateExistingConversationItem(message);
            }

            // Sync with RealTime state to prevent polling from missing messages
            lastSentMessageId = data.message.id;
            if (window.RealTime && window.RealTime.updateLastMessageId) {
                window.RealTime.updateLastMessageId(data.message.id);
            }

            if (window.sendTypingStatus) window.sendTypingStatus(false);
            isTyping = false;
            if (typingTimeout) clearTimeout(typingTimeout);
            messageData.resolve(data);
        } else {
            messageData.reject(new Error(data.error || 'Failed to send message'));
        }
    })
    .catch(err => {
        console.error('Send message error:', err);
        messageData.reject(err);
    })
    .finally(() => {
        isSendingMessage = false;
        messageData.input.disabled = false;
        messageData.sendButton.disabled = false;
        messageData.input.value = '';
        // Process next message in queue if any
        if (messageSendQueue.length > 0) {
            setTimeout(processMessageQueue, 50); // Small delay between sends
        }
    });
}

// Send message with queue to prevent race conditions
function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    const hasMedia = selectedFiles.length > 0;

    if (!content && !hasMedia) return;

    const sendButton = document.getElementById('sendButton');

    // Disable input but don't block - queue will handle ordering
    input.disabled = true;
    sendButton.disabled = true;

    // If has media, send as FormData (also queued)
    if (hasMedia) {
        sendMediaMessage(content, null);
        return;
    }

    // Create a promise for this message
    return new Promise((resolve, reject) => {
        // If a preview fetch is in-flight, wait for it before sending (max 3s)
        const pendingFetch = lpFetchPromise;
        const doQueue = (resolvedLpData) => {
            dismissLinkPreview();
            lpDismissed = false;
            lpFetchPromise = null;
            messageSendQueue.push({
                content: content,
                link_preview: resolvedLpData,
                input: input,
                sendButton: sendButton,
                resolve: resolve,
                reject: reject
            });
            processMessageQueue();
        };

        if (!lpData && pendingFetch) {
            // Wait for the fetch but cap at 3s so slow sites don't block sending
            const timeout = new Promise(r => setTimeout(() => r(null), 3000));
            Promise.race([pendingFetch, timeout]).then(data => doQueue(data || lpData));
        } else {
            doQueue(lpData);
        }
    });
}

// Send media message (supports multiple files in one message) with queue
function sendMediaMessage(content, mediaFile) {
    const input = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');

    // Disable input
    input.disabled = true;
    sendButton.disabled = true;

    // Create a promise for this media message
    return new Promise((resolve, reject) => {
        // Add to queue
        messageSendQueue.push({
            content: content,
            input: input,
            sendButton: sendButton,
            resolve: resolve,
            reject: reject,
            isMedia: true
        });

        // Process queue
        processMessageQueue();
    });
}

// Process media message from queue
function processMediaMessage(messageData) {
    const formData = new FormData();
    if (messageData.content) {
        formData.append('content', messageData.content);
    }
    
    if (replyingTo) {
        formData.append('reply_to_id', replyingTo.id);
        cancelReply();
    }

    // Append ALL selected files
    selectedFiles.forEach((file) => {
        formData.append('media[]', file);
    });

    fetch(`{{ route('chat.store', $conversation) }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.message) {
            const message = data.message;

            // Add the message with all media
            addMessage(message);
            if (window.updateExistingConversationItem) {
                window.updateExistingConversationItem(message);
            }

            // Sync with RealTime state
            lastSentMessageId = data.message.id;
            if (window.RealTime && window.RealTime.updateLastMessageId) {
                window.RealTime.updateLastMessageId(data.message.id);
            }

            messageData.input.value = '';
            clearMediaPreview();
            if (window.sendTypingStatus) window.sendTypingStatus(false);
            isTyping = false;
            if (typingTimeout) clearTimeout(typingTimeout);
            messageData.resolve(data);
        } else {
            alert(data.error || window.chatTranslations.failed_to_send_media);
            messageData.reject(new Error(data.error || 'Failed to send media'));
        }
    })
    .catch(err => {
        console.error('Error sending media:', err);
        alert(window.chatTranslations.error_sending_media);
        messageData.reject(err);
    })
    .finally(() => {
        isSendingMessage = false;
        messageData.input.disabled = false;
        messageData.sendButton.disabled = false;
        // Process next message in queue if any
        if (messageSendQueue.length > 0) {
            setTimeout(processMessageQueue, 50);
        }
    });
}

// Keep track of the last message date for dividers
window.lastMessageDate = '{{ $lastDate ?? '' }}';

// Add message to chat - make it globally accessible
window.addMessage = function(msg) {
    try {
        const container = document.getElementById('chatMessages');
        if (!container) {
            console.error('addMessage: chatMessages container not found');
            return;
        }

        // Capture scroll state before any DOM changes
        const threshold = 150;
        const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < threshold;
    
        const noMsg = container.querySelector('.no-messages');
        if (noMsg) noMsg.remove();

    const isOwn = msg.sender_id == {{ auth()->id() }};
    
    // Date Divider Logic - Use local date string to match server's date format
    const fullDate = new Date(msg.created_at);
    const dateStr = fullDate.getFullYear() + '-' + String(fullDate.getMonth() + 1).padStart(2, '0') + '-' + String(fullDate.getDate()).padStart(2, '0');
    
    // Check if divider for this date already exists in the DOM
    const existingDivider = container.querySelector(`.chat-date-divider[data-date="${dateStr}"]`);
    
    if (!existingDivider) {
        const divider = document.createElement('div');
        divider.className = 'chat-date-divider';
        divider.dataset.date = dateStr;
        
        let displayDate = dateStr;
        const now = new Date();
        const today = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
        
        const yesterdayDate = new Date(now);
        yesterdayDate.setDate(now.getDate() - 1);
        const yesterday = yesterdayDate.getFullYear() + '-' + String(yesterdayDate.getMonth() + 1).padStart(2, '0') + '-' + String(yesterdayDate.getDate()).padStart(2, '0');
        
        if (dateStr === today) displayDate = window.chatTranslations.today || 'Today';
        else if (dateStr === yesterday) displayDate = window.chatTranslations.yesterday || 'Yesterday';
        else displayDate = fullDate.toLocaleDateString();
        
        divider.innerHTML = `<span>${displayDate}</span>`;
        container.appendChild(divider);
        window.lastMessageDate = dateStr;
    }

    const div = document.createElement('div');
    const senderName = isOwn ? (window.chatTranslations.you || 'You') : (msg.sender?.username || 'User');
    div.className = `message ${isOwn ? 'own' : 'other'}`;
    div.dataset.messageId = msg.id;
    div.dataset.senderName = senderName;

    // Format time to 12-hour format (e.g., "02:30 pm")
    const date = new Date(msg.created_at);
    let hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'pm' : 'am';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const time = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;

    // Build message HTML to match Blade template exactly
    let avatarHtml = '';
    let senderNameHtml = '';
    let contentHtml = '';
    
    // Avatar for other users
    if (!isOwn && msg.sender && window.isGroupChat) {
        const username = msg.sender.username || 'U';
        const avatar = `<a href="/users/${escapeHtml(username)}" style="display:flex;flex-shrink:0;"><img src="${escapeHtml(msg.sender.avatar_url)}" alt="${escapeHtml(username)}" style="pointer-events:none;"></a>`;
        avatarHtml = `<div class="message-avatar">${avatar}</div>`;
    }

    // Sender name for other users
    if (!isOwn && msg.sender && window.isGroupChat) {
        const sndVerified = msg.sender.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
        senderNameHtml = `<a href="/users/${escapeHtml(msg.sender.username || '')}" class="sender-name" style="text-decoration:none;display:inline-flex;align-items:center;gap:.15em;">${escapeHtml(msg.sender.username || 'User')}${sndVerified}</a>`;
    }

    // Handle system messages
    if (msg.type === 'system' || msg.type === 'system_cleared') {
        const isClear = msg.type === 'system_cleared' || msg.content === 'system_cleared';
        const clearText = isClear 
            ? (isOwn ? (window.chatTranslations.you_cleared_the_chat || 'You cleared the chat') : (window.chatTranslations.cleared_the_chat || 'Cleared the chat').replace(':user', msg.username || msg.sender?.username || 'User'))
            : msg.content;
        
        div.className = 'system-message';
        div.innerHTML = `
            <span class="system-text" dir="auto">${escapeHtml(clearText)}</span>
            <span class="system-time">${time}</span>
        `;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        void div.offsetWidth;
        return;
    }

    // Handle group invite messages
    if (msg.type === 'group_invite' && msg.media_path) {
        try {
            const inviteData = typeof msg.media_path === 'string' ? JSON.parse(msg.media_path) : msg.media_path;
            div.className = `message ${isOwn ? 'own' : 'other'} group-invite`;
            div.setAttribute('data-sender-name', senderName);
            div.innerHTML = `
                ${!isOwn && msg.sender ? avatarHtml : ''}
                <div class="message-bubble">
                    ${!isOwn && msg.sender ? senderNameHtml : ''}
                    <div class="invite-card">
                        <div class="invite-icon"><i class="fas fa-users"></i></div>
                        <div class="invite-content">
                            <div class="invite-title">${escapeHtml(inviteData.group_name || window.chatTranslations.group)}</div>
                            <div class="invite-text">${escapeHtml(msg.sender?.username || 'Someone')} ${escapeHtml(window.chatTranslations.invited_you_to_join)}</div>
                        </div>
                        ${!isOwn && inviteData.invite_link ? `<button class="accept-btn" onclick="acceptGroupInvite('${escapeHtml(inviteData.invite_link)}')"><i class="fas fa-check"></i> ${escapeHtml(window.chatTranslations.join)}</button>` : ''}
                    </div>
                    <span class="message-time">${time}${isOwn ? '<i class="fas fa-check" title="' + window.chatTranslations.sent + '"></i>' : ''}</span>
                </div>
                    <div class="msg-side-actions">
                        <button class="side-action-btn react" onclick="openReactionPicker(event, '${msg.id}')" title="${window.chatTranslations.react || 'React'}">
                            <i class="far fa-smile"></i>
                        </button>
                        <button class="side-action-btn reply" onclick="initiateReply('${msg.id}')" title="${window.chatTranslations.reply || 'Reply'}">
                            <i class="fas fa-reply"></i>
                        </button>
                    </div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            void div.offsetWidth;
            return;
        } catch (e) {
            console.error('Error parsing group invite:', e);
        }
    }

    // Handle multiple media files (JSON)
    if (msg.media_path && msg.media_path.startsWith('[')) {
        try {
            const mediaItems = JSON.parse(msg.media_path);
            if (Array.isArray(mediaItems) && mediaItems.length > 0) {
                const displayCount = Math.min(mediaItems.length, 4);
                const remainingCount = mediaItems.length - displayCount;

                contentHtml += `<div class="message-media-album">
                    <script type="application/json" class="media-data">${msg.media_path}<\/script>`;

                if (displayCount === 1) {
                    const media = mediaItems[0];
                    if (media.type === 'image') {
                        contentHtml += `<div class="media-grid-single">
                            <div class="media-item">
                                <img src="/storage/${escapeHtml(media.path)}" onclick="openMediaViewerFromAlbum(this, ${msg.id}, 0)">
                            </div>
                        </div>`;
                    } else if (media.type === 'video') {
                        contentHtml += `<div class="media-grid-single">
                            <div class="media-item video">
                                <video src="/storage/${escapeHtml(media.path)}"></video>
                                <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, ${msg.id}, 0)">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                        </div>`;
                    }
                } else if (displayCount === 2) {
                    contentHtml += `<div class="media-grid-two">`;
                    mediaItems.slice(0, 2).forEach((media, index) => {
                        if (media.type === 'image') {
                            contentHtml += `<div class="media-item">
                                <img src="/storage/${escapeHtml(media.path)}" onclick="openMediaViewerFromAlbum(this, ${msg.id}, ${index})">
                            </div>`;
                        } else if (media.type === 'video') {
                            contentHtml += `<div class="media-item video">
                                <video src="/storage/${escapeHtml(media.path)}"></video>
                                <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, ${msg.id}, ${index})">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>`;
                        }
                    });
                    contentHtml += `</div>`;
                } else {
                    contentHtml += `<div class="media-grid-${displayCount}">`;
                    mediaItems.slice(0, displayCount).forEach((media, index) => {
                        if (media.type === 'image') {
                            contentHtml += `<div class="media-item">
                                <img src="/storage/${escapeHtml(media.path)}" onclick="openMediaViewerFromAlbum(this, ${msg.id}, ${index})">`;
                            if (index === 3 && remainingCount > 0) {
                                contentHtml += `<div class="media-overlay" onclick="openMediaViewerFromAlbum(this, ${msg.id}, 4)">
                                    <span class="overlay-text">+${remainingCount}</span>
                                </div>`;
                            }
                            contentHtml += `</div>`;
                        } else if (media.type === 'video') {
                            contentHtml += `<div class="media-item video">
                                <video src="/storage/${escapeHtml(media.path)}"></video>
                                <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, ${msg.id}, ${index})">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>`;
                        }
                    });
                    contentHtml += `</div>`;
                }
                contentHtml += `</div>`; // Close message-media-album
            }
        } catch (e) {
            console.error('Error parsing media_path:', e);
        }
    } else if (msg.type === 'image' && msg.media_path) {
        contentHtml += `<div class="message-media"><img src="/storage/${escapeHtml(msg.media_path)}" alt="Image" onclick="openMediaViewerFromAlbum(this, ${msg.id}, 0)"></div>`;
    } else if (msg.type === 'video' && msg.media_path) {
        contentHtml += `<div class="message-media" onclick="openMediaViewerFromAlbum(this, ${msg.id}, 0)">
            <video src="/storage/${escapeHtml(msg.media_path)}"></video>
            <div class="media-overlay">
                <i class="fas fa-play"></i>
            </div>
        </div>`;
    } else if (msg.type === 'voice' && msg.media_path) {
        const duration = msg.duration || 0;
        const totalMin = Math.floor(duration / 60);
        const totalSec = String(duration % 60).padStart(2, '0');
        
        contentHtml += `<div class="voice-message" data-audio-url="/storage/${escapeHtml(msg.media_path)}" data-duration="${duration}">
            <button class="voice-play-btn" onclick="toggleVoiceMessage(this)"><i class="fas fa-play"></i></button>
            <div class="voice-info">
                <div class="voice-progress-container" onclick="seekVoice(event, this)">
                    <div class="voice-progress-bar"></div>
                </div>
                <div class="voice-meta">
                    <span class="voice-label">${window.chatTranslations.voice_message || 'Voice Message'}</span>
                    <span class="voice-duration">0:00 / ${totalMin}:${totalSec}</span>
                </div>
            </div>
            <button class="voice-speed-btn" onclick="toggleVoiceSpeed(this)" title="${escapeHtml(window.chatTranslations.playback_speed)}">1x</button>
        </div>`;
    }

    // Text content with story reply and general reply detection
    if (msg.content && msg.content.trim()) {
        const isReply = msg.content.startsWith('{"__nexus_reply__":true');
        if (isReply) {
            try {
                const replyData = JSON.parse(msg.content);
                contentHtml += `
                    <div class="replied-message-box" onclick="scrollToMessage(event, '${replyData.reply_to.id}')">
                        <span class="replied-user">${escapeHtml(replyData.reply_to.username || replyData.reply_to.sender_name || replyData.reply_to.user || 'User')}</span>
                        <span class="replied-content">${escapeHtml(replyData.reply_to.content || '')}</span>
                    </div>
                    <span class="text" dir="auto">${linkifyText(replyData.content)}</span>
                `;
            } catch (e) {
                console.error('Error parsing reply JSON:', e);
                contentHtml += `<span class="text">${escapeHtml(msg.content)}</span>`;
            }
        } else {
            const isStoryReply = msg.content && msg.content.startsWith('📸 Reply to your story:');
            if (isStoryReply) {
                const storyReplyContent = msg.content.replace('📸 Reply to your story:', '').trim();
                contentHtml += `<div class="story-reply-message">
                    <div class="story-reply-header">
                        <span class="story-reply-label">${escapeHtml(window.chatTranslations.story_reply)}</span>
                    </div>
                    <div class="story-reply-content">${escapeHtml(storyReplyContent)}</div>
                </div>`;
            } else {
                contentHtml += `<span class="text" dir="auto">${linkifyText(msg.content)}</span>`;
            }
        }
    }

    // Link preview card
    if (msg.link_preview && (msg.link_preview.title || msg.link_preview.image)) {
        const lp = msg.link_preview;
        const lpDomain = escapeHtml(lp.domain || '');
        const lpTitle = escapeHtml(lp.title || '');
        const lpDesc = lp.description ? `<div class="lp-desc">${escapeHtml(lp.description)}</div>` : '';
        const lpImg = lp.image ? `<div class="lp-img"><img src="${escapeHtml(lp.image)}" alt="" loading="lazy" onerror="this.parentElement.style.display='none'"></div>` : '';
        contentHtml += `<a href="${escapeHtml(lp.url)}" target="_blank" rel="noopener noreferrer" class="lp-card">
            ${lpImg}
            <div class="lp-body">
                <div class="lp-domain">${lpDomain}</div>
                <div class="lp-title">${lpTitle}</div>
                ${lpDesc}
            </div>
        </a>`;
    }

    // Time and Status
    let statusIcon = '';
    if (isOwn) {
        statusIcon = msg.read_at ? 'fa-check-double read' : (msg.delivered_at ? 'fa-check-double sent' : 'fa-check');
        const statusTitle = msg.read_at ? window.chatTranslations.read : (msg.delivered_at ? window.chatTranslations.delivered : window.chatTranslations.sent);
        statusIcon = `<i class="fas ${statusIcon}" title="${escapeHtml(statusTitle)}"></i>`;
    }

    const timeHtml = `
        <span class="message-time">
            ${time}
            ${statusIcon || ''}
        </span>
    `;

    const actionsHtml = (msg.type !== 'system' && msg.type !== 'system_cleared') ? `
        <div class="msg-item-actions">
            <button class="msg-action-trigger" onclick="toggleMsgMenu(event, '${msg.id}')">
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="msg-dropdown" id="msgDropdown-${msg.id}">
                <button class="menu-item" onclick="initiateReply('${msg.id}')">
                    <i class="fas fa-reply"></i> ${window.chatTranslations.reply || 'Reply'}
                </button>
                ${isOwn ? `
                <button class="menu-item danger" onclick="deleteMessage(${msg.id})">
                    <i class="fas fa-trash-alt"></i> ${window.chatTranslations.delete_message || 'Delete'}
                </button>
                <button class="menu-item info" onclick="showMessageInfo('${msg.id}')">
                    <i class="fas fa-info-circle"></i> ${window.chatTranslations.message_info || 'Message Info'}
                </button>
                ` : ''}
            </div>
        </div>
    ` : '';

    const reactionsBarHtml = `<div class="message-reactions-bar" data-message-id="${msg.id}" style="display:none;"></div>`;

    div.innerHTML = `
        ${avatarHtml}
        <div class="message-bubble ${msg.reactions && msg.reactions.length > 0 ? 'has-reactions' : ''}">
            ${senderNameHtml}
            <div class="message-content ${ (msg.media_path || msg.type === 'image' || msg.type === 'video') ? 'has-media' : '' } ${ (msg.content && msg.content.trim()) ? 'has-text' : '' }">
                ${contentHtml}${timeHtml}
                ${actionsHtml}
            </div>
            ${reactionsBarHtml}
        </div>

        <div class="msg-side-actions">
            <button class="side-action-btn react" onclick="openReactionPicker(event, '${msg.id}')" title="${window.chatTranslations.react || 'React'}">
                <i class="far fa-smile"></i>
            </button>
            <button class="side-action-btn reply" onclick="initiateReply('${msg.id}')" title="${window.chatTranslations.reply || 'Reply'}">
                <i class="fas fa-reply"></i>
            </button>
        </div>
    `;

    container.appendChild(div);
    
    const scrollBtn = document.getElementById('scrollToBottomBtn');

    if (isOwn || isAtBottom) {
        if (window.scrollToBottom) {
            window.scrollToBottom(isOwn ? 'smooth' : 'auto');
        } else {
            container.scrollTop = container.scrollHeight;
        }
    } else {
        // User is scrolled up and received a message from someone else
        if (scrollBtn) {
            scrollBtn.classList.add('visible', 'has-new-msg');
            const badge = scrollBtn.querySelector('.new-msg-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent || '0');
                badge.textContent = currentCount + 1;
                badge.style.display = 'flex';
            }
        }
    }

    // Handle image/media loading to maintain scroll if we were at the bottom
    const media = div.querySelectorAll('img, video');
    media.forEach(m => {
        m.addEventListener('load', () => {
            if (container.scrollHeight - container.scrollTop - container.clientHeight < threshold) {
                container.scrollTop = container.scrollHeight;
            }
        });
    });

    // Apply RTL direction if message contains Arabic text
    applyRTLIfArabic(div);

    // Trigger reflow to ensure animation plays
    void div.offsetWidth;
    } catch (err) {
        console.error('Error in addMessage:', err, msg);
    }
}

// Media handling - support multiple files with carousel preview
let selectedFiles = [];
let currentPreviewIndex = 0;

function handleMediaSelect(e) {
    const files = Array.from(e.target.files);
    if (!files.length) return;
    
    // Add to selected files
    selectedFiles = [...selectedFiles, ...files];
    
    // Show carousel preview
    showCarouselPreview();
}

function showCarouselPreview() {
    const preview = document.getElementById('mediaPreview');
    const slidesContainer = document.getElementById('previewSlides');
    const indicatorsContainer = document.getElementById('previewIndicators');
    const countEl = document.getElementById('previewCount');
    
    if (!selectedFiles.length) {
        preview.style.display = 'none';
        return;
    }
    
    preview.style.display = 'block';
    slidesContainer.innerHTML = '';
    indicatorsContainer.innerHTML = '';
    
    // Create slides
    selectedFiles.forEach((file, index) => {
        const slide = document.createElement('div');
        slide.className = `preview-slide ${index === currentPreviewIndex ? 'active' : ''}`;
        
        const slideNumber = document.createElement('div');
        slideNumber.className = 'slide-number';
        slideNumber.textContent = `${index + 1} / ${selectedFiles.length}`;
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-slide';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.onclick = () => removePreview(index);
        
        slide.appendChild(slideNumber);
        slide.appendChild(removeBtn);
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = document.createElement('img');
                img.src = ev.target.result;
                slide.appendChild(img);
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                const video = document.createElement('video');
                video.src = ev.target.result;
                video.controls = false;
                slide.appendChild(video);
            };
            reader.readAsDataURL(file);
        }
        
        slidesContainer.appendChild(slide);
        
        // Create indicator
        const indicator = document.createElement('div');
        indicator.className = `preview-indicator ${index === currentPreviewIndex ? 'active' : ''}`;
        indicator.onclick = () => goToPreview(index);
        indicatorsContainer.appendChild(indicator);
    });
    
    // Update count
    countEl.textContent = `${currentPreviewIndex + 1} / ${selectedFiles.length}`;
    
    // Update arrow states
    updateArrowStates();
}

function movePreview(direction) {
    if (!selectedFiles.length) return;
    
    currentPreviewIndex += direction;
    
    // Wrap around
    if (currentPreviewIndex < 0) {
        currentPreviewIndex = selectedFiles.length - 1;
    } else if (currentPreviewIndex >= selectedFiles.length) {
        currentPreviewIndex = 0;
    }
    
    updatePreviewDisplay();
}

function goToPreview(index) {
    currentPreviewIndex = index;
    updatePreviewDisplay();
}

function updatePreviewDisplay() {
    const slides = document.querySelectorAll('.preview-slide');
    const indicators = document.querySelectorAll('.preview-indicator');
    const countEl = document.getElementById('previewCount');
    
    slides.forEach((slide, index) => {
        slide.classList.toggle('active', index === currentPreviewIndex);
    });
    
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentPreviewIndex);
    });
    
    countEl.textContent = `${currentPreviewIndex + 1} / ${selectedFiles.length}`;
    
    updateArrowStates();
}

function updateArrowStates() {
    const arrows = document.querySelectorAll('.carousel-arrow');
    if (selectedFiles.length <= 1) {
        arrows.forEach(arrow => arrow.disabled = true);
    } else {
        arrows.forEach(arrow => arrow.disabled = false);
    }
}

function removePreview(index) {
    selectedFiles.splice(index, 1);
    
    // Adjust current index
    if (currentPreviewIndex >= selectedFiles.length) {
        currentPreviewIndex = Math.max(0, selectedFiles.length - 1);
    }
    
    if (!selectedFiles.length) {
        clearMediaPreview();
    } else {
        showCarouselPreview();
    }
    
    if (!selectedFiles.length) {
        document.getElementById('mediaInput').value = '';
    }
}

function clearMediaPreview() {
    selectedFiles = [];
    currentPreviewIndex = 0;
    document.getElementById('mediaPreview').style.display = 'none';
    document.getElementById('previewSlides').innerHTML = '';
    document.getElementById('previewIndicators').innerHTML = '';
    document.getElementById('mediaInput').value = '';
}

// Media viewer with album navigation
// Enhanced Nexus Gallery Manager
const NexusGallery = {
    items: [],
    currentIndex: 0,
    isOpen: false,

    init() {
        this.viewer = document.getElementById('mediaViewer');
        this.imgEl = document.getElementById('galleryImage');
        this.vidEl = document.getElementById('galleryVideo');
        this.counterEl = document.getElementById('galleryCounter');
        
        // Event Listeners
        document.getElementById('galleryClose').addEventListener('click', () => this.close());
        document.getElementById('galleryPrev').addEventListener('click', (e) => { e.stopPropagation(); this.navigate(-1); });
        document.getElementById('galleryNext').addEventListener('click', (e) => { e.stopPropagation(); this.navigate(1); });
        this.viewer.querySelector('.gallery-overlay').addEventListener('click', () => this.close());
        
        document.addEventListener('keydown', (e) => {
            if (!this.isOpen) return;
            if (e.key === 'Escape') this.close();
            if (e.key === 'ArrowLeft') this.navigate(-1);
            if (e.key === 'ArrowRight') this.navigate(1);
        });


    },

    cleanPath(path) {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        const sPath = path.startsWith('/') ? path : '/' + path;
        return sPath.startsWith('/storage/') ? sPath : '/storage' + sPath;
    },

    build(targetMessageId) {
        this.items = [];
        const mediaMap = new Map();

        const msg = document.querySelector(`.message[data-message-id="${targetMessageId}"]`);
        if (!msg) return mediaMap;

        const album = msg.querySelector('.message-media-album');
        if (album) {
            const scriptTag = album.querySelector('script.media-data');
            if (scriptTag) {
                try {
                    const items = JSON.parse(scriptTag.textContent.trim());
                    items.forEach((item, index) => {
                        const galleryIdx = this.items.length;
                        this.items.push({ src: this.cleanPath(item.path), type: item.type });
                        mediaMap.set(`${targetMessageId}_${index}`, galleryIdx);
                    });
                } catch (e) {
                    console.error('Failed to parse album:', e);
                }
            }
        } else {
            const singleImg = msg.querySelector('.message-media img');
            const singleVid = msg.querySelector('.message-media video');
            if (singleImg || singleVid) {
                this.items.push({
                    src: singleImg ? singleImg.src : (singleVid ? singleVid.src : ''),
                    type: singleImg ? 'image' : 'video'
                });
                mediaMap.set(`${targetMessageId}_0`, 0);
            }
        }
        return mediaMap;
    },

    open(messageId, index = 0) {
        const mediaMap = this.build(messageId);
        const galleryIdx = mediaMap.get(`${messageId}_${index}`);
        
        if (galleryIdx === undefined) {
            // Fallback for dynamic elements that might not be in DOM yet
            this.currentIndex = 0;
        } else {
            this.currentIndex = galleryIdx;
        }

        this.isOpen = true;
        this.viewer.setAttribute('aria-hidden', 'false');
        this.show();
    },

    show() {
        const item = this.items[this.currentIndex];
        if (!item) return;

        if (item.type === 'video') {
            this.imgEl.style.display = 'none';
            this.vidEl.style.display = 'block';
            this.vidEl.src = item.src;
            this.vidEl.play().catch(() => {});
        } else {
            this.vidEl.pause();
            this.vidEl.style.display = 'none';
            this.imgEl.style.display = 'block';
            this.imgEl.src = item.src;
        }

        this.counterEl.textContent = `${this.currentIndex + 1} / ${this.items.length}`;
    },

    navigate(dir) {
        if (this.items.length <= 1) return;
        this.currentIndex = (this.currentIndex + dir + this.items.length) % this.items.length;
        this.show();
    },

    close() {
        this.isOpen = false;
        this.viewer.setAttribute('aria-hidden', 'true');
        this.vidEl.pause();
        this.vidEl.src = '';
        this.imgEl.src = '';
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => NexusGallery.init());

// Global function for Blade/Dynamic handlers
window.openMediaViewerFromAlbum = function(el, messageId, index = 0) {
    NexusGallery.open(messageId, index);
};



// Clear chat
function clearChat() {
    if (confirm('{{ __('chat.confirm_delete') }}')) {
        fetch(`{{ route('chat.clear', $conversation) }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Chat clear request successful');
                // Immediate local feedback
                if (window.handleChatCleared) {
                    window.handleChatCleared({
                        conversation_id: {{ $conversation->id }},
                        username: '{{ auth()->user()->username }}',
                        user_id: {{ auth()->id() }},
                        created_at: new Date().toISOString()
                    });
                }
            }
        });
    }
}

// Accept group invite
function acceptGroupInvite(inviteLink) {
    if (!inviteLink) return;
    
    // Make POST request to accept invite
    fetch('/messaging-groups/accept-invite/' + encodeURIComponent(inviteLink), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to group chat
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                // Update invite button state without reload
                const inviteBtns = document.querySelectorAll('.join-group-btn');
                inviteBtns.forEach(btn => {
                    if (btn.getAttribute('onclick')?.includes(inviteLink)) {
                        btn.innerHTML = `<i class="fas fa-check"></i> ${window.chatTranslations.joined || 'Joined'}`;
                        btn.disabled = true;
                        btn.classList.remove('primary');
                    }
                });
                showToast(window.chatTranslations.joined_group || 'Joined group!', 'success');
            }
        } else {
            // Show error message
            alert(data.message || (window.chatTranslations.failed_to_join_group || 'Failed to join group'));
        }
    })
    .catch(error => {
        console.error('Error accepting invite:', error);
        alert(window.chatTranslations.failed_to_join_group || 'Failed to join group');
    });
}

// Message actions dropdown toggle
window.toggleMsgMenu = function(event, id) {
    event.preventDefault();
    event.stopPropagation();
    
    const dropdown = document.getElementById('msgDropdown-' + id);
    if (!dropdown) return;

    // Close all other message dropdowns
    document.querySelectorAll('.msg-dropdown').forEach(d => {
        if (d !== dropdown) d.classList.remove('show');
    });
    
    dropdown.classList.toggle('show');

    if (dropdown.classList.contains('show')) {
        // Reset styles first
        dropdown.style.left = '';
        dropdown.style.right = '';
        
        const rect = dropdown.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        const inputArea = document.querySelector('.chat-input-area');
        const inputHeight = inputArea ? inputArea.offsetHeight + 20 : 100;
        const headerHeight = 80;
        
        // Vertical positioning
        if (rect.bottom > viewportHeight - inputHeight) {
            dropdown.classList.add('drop-up');
        } else if (rect.top < headerHeight) {
            dropdown.classList.remove('drop-up');
        }

        // Horizontal overflow protection
        const currentRect = dropdown.getBoundingClientRect();
        if (currentRect.right > viewportWidth - 10) {
            dropdown.style.right = '8px';
            dropdown.style.left = 'auto';
        } else if (currentRect.left < 10) {
            dropdown.style.left = '8px';
            dropdown.style.right = 'auto';
        }
    }
};

// Close message dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.msg-item-actions')) {
        document.querySelectorAll('.msg-dropdown').forEach(d => d.classList.remove('show'));
    }
});

// Delete message - show modal
let messageToDeleteId = null;
window.currentlyInspectedMessageId = null;

function deleteMessage(id) {
    messageToDeleteId = id;
    document.getElementById('deleteMessageModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteMessageModal').style.display = 'none';
    messageToDeleteId = null;
}

function showMessageInfo(id) {
    window.currentlyInspectedMessageId = id;
    const modal = document.getElementById('messageInfoModal');
    modal.style.display = 'flex';
    
    fetchMessageInfo(id);
}

function fetchMessageInfo(id) {
    // Only fetch if this is still the inspected message
    if (window.currentlyInspectedMessageId !== id) return;

    // Show loading if first fetch
    const readList = document.getElementById('messageInfoReadList');
    if (!readList.querySelector('.info-user-item') && !readList.querySelector('.info-empty')) {
        readList.innerHTML = '<div class="info-loading"><i class="fas fa-circle-notch fa-spin"></i></div>';
        document.getElementById('messageInfoDeliveredList').innerHTML = '<div class="info-loading"><i class="fas fa-circle-notch fa-spin"></i></div>';
        document.getElementById('messageInfoRemainingList').innerHTML = '<div class="info-loading"><i class="fas fa-circle-notch fa-spin"></i></div>';
    }

    fetch(`/chat/message/${id}/info`)
        .then(res => res.json())
        .then(data => {
            if(data.success && window.currentlyInspectedMessageId === id) {
                renderMessageInfoUsers('messageInfoReadList', data.info.read, true);
                renderMessageInfoUsers('messageInfoDeliveredList', data.info.delivered, true);
                renderMessageInfoUsers('messageInfoRemainingList', data.info.remaining, false);
            } else if (!data.success) {
                console.error(data.error || 'Failed to load info');
                if (!readList.querySelector('.info-user-item')) {
                    closeMessageInfoModal();
                }
            }
        })
        .catch(err => {
            console.error(err);
        });
}

window.refreshMessageInfoIfOpen = function(messageId, conversationId) {
    if (window.currentlyInspectedMessageId && (String(window.currentlyInspectedMessageId) === String(messageId) || !messageId)) {
        // If messageId is null, it's a general conversation read event, we might need to refresh anyway
        // or check if the inspected message belongs to the conversation
        fetchMessageInfo(window.currentlyInspectedMessageId);
    }
};

function closeMessageInfoModal() {
    document.getElementById('messageInfoModal').style.display = 'none';
    window.currentlyInspectedMessageId = null;
}

function renderMessageInfoUsers(containerId, users, showTime) {
    const container = document.getElementById(containerId);
    if (!users || users.length === 0) {
        container.innerHTML = `<div class="info-empty">-</div>`;
        return;
    }
    
    let html = '';
    users.forEach(u => {
        let timeStr = '';
        if (showTime && u.time) {
            const date = new Date(u.time);
            timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        html += `
            <div class="info-user-item">
                <a href="/users/${escapeHtml(u.username)}" style="display:flex;flex-shrink:0;"><img src="${u.avatar_url}" alt="${u.name}" class="info-user-avatar" style="pointer-events:none;"></a>
                <div class="info-user-details">
                    <a href="/users/${escapeHtml(u.username)}" class="info-user-name" style="text-decoration:none;">${escapeHtml(u.username)}</a>
                    ${showTime ? `<span class="info-user-time">${timeStr}</span>` : ''}
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function confirmDelete(type) {
    if (!messageToDeleteId) return;

    // capture the id now so closing the modal (which nulls the variable)
    // doesn't wipe it out before the fetch callback uses it.
    const id = messageToDeleteId;

    closeDeleteModal();

    fetch(`/chat/message/${id}?type=${type}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            handleDeleteMessage(data.deleted_message_id, data.delete_type, data.deleted_for);
        }
    })
    .catch(err => console.error('Delete failed:', err));
}

// Handle message deletion UI update
window.handleDeleteMessage = function(messageId, deleteType, deletedFor) {
    const msgEl = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (!msgEl) return;

    if (deleteType === 'everyone') {
        // Show "message deleted" for everyone
        msgEl.classList.add('deleted');
        const content = msgEl.querySelector('.message-content');
        if (content) {
            content.classList.remove('has-media', 'has-text');
            const timeEl = msgEl.querySelector('.message-time');
            const timeHtml = timeEl ? timeEl.outerHTML : '';
            
            content.innerHTML = `
                <div class="deleted-msg-wrapper">
                    <i class="fas fa-ban"></i>
                    <em class="deleted-text">${window.chatTranslations.message_deleted}</em>
                </div>
                ${timeHtml}
            `;
        }
        // Remove delete button / actions
        const actions = msgEl.querySelector('.msg-item-actions');
        if (actions) actions.remove();
        
        const sideActions = msgEl.querySelector('.msg-side-actions');
        if (sideActions) sideActions.remove();

        const reactions = msgEl.querySelector('.message-reactions-bar');
        if (reactions) reactions.remove();
    } else {
        // Delete for me only - hide the message
        msgEl.style.display = 'none';
    }
};

window.updateReadReceiptsUI = function(readMessageIds, readerId, data) {
    if (!readMessageIds || !Array.isArray(readMessageIds)) return;

    // If current user is the reader, remove the unread divider
    if (readerId == window.currentUserId) {
        const unreadDivider = document.querySelector('.unread-divider');
        if (unreadDivider) {
            unreadDivider.remove();
        }
    }

    // New format supports per-message 'all_read' status
    if (data && data.read_messages) {
        data.read_messages.forEach(msgInfo => {
            const msgEl = document.querySelector('.message[data-message-id="' + msgInfo.id + '"]');
            if (msgEl) {
                const checkIcon = msgEl.querySelector('.message-time i[class*="fa-check"]');
                if (checkIcon) {
                    if (msgInfo.is_all_read) {
                        checkIcon.className = 'fas fa-check-double read';
                    } else {
                        // If not read by all, it should at least be delivered to some (double check grey)
                        checkIcon.className = 'fas fa-check-double sent';
                    }
                }
            }
        });
    } else {
        // Fallback for old format or 1-1 chats where read always means read by all
        readMessageIds.forEach(id => {
            const msgEl = document.querySelector('.message[data-message-id="' + id + '"]');
            if (msgEl) {
                const checkIcon = msgEl.querySelector('.message-time i[class*="fa-check"]');
                if (checkIcon) {
                    checkIcon.className = 'fas fa-check-double read';
                }
            }
        });
    }
};

window.updateDeliveredReceiptsUI = function(messageId, data) {
    if (!messageId) return;
    const msgEl = document.querySelector('.message[data-message-id="' + messageId + '"]');
    if (msgEl) {
        const checkIcon = msgEl.querySelector('.message-time i[class*="fa-check"]');
        if (checkIcon && !checkIcon.classList.contains('read')) {
            // Only show double grey check if all participants received it
            // or if it's a 1-1 chat (which defaults to true in data)
            if (data && data.is_all_delivered) {
                checkIcon.className = 'fas fa-check-double sent';
            } else {
                // Stay as single check if not delivered to all
                checkIcon.className = 'fas fa-check sent';
            }
        }
    }
};

// Mark message as deleted in the UI (for realtime.js)
function markMessageAsDeleted(id) {
    const el = document.querySelector(`[data-message-id="${id}"]`);
    if (el) {
        const contentEl = el.querySelector('.message-content');
        if (contentEl) {
            contentEl.classList.remove('has-media', 'has-text');
            const timeEl = el.querySelector('.message-time');
            const timeHtml = timeEl ? timeEl.outerHTML : '';
            
            contentEl.innerHTML = `
                <div class="deleted-msg-wrapper">
                    <i class="fas fa-ban"></i>
                    <em class="deleted-text">${window.chatTranslations.message_deleted}</em>
                </div>
                ${timeHtml}
            `;
            el.classList.add('deleted');
        }
        // Remove interactive elements
        const actions = el.querySelector('.msg-item-actions');
        if (actions) actions.remove();
        
        const sideActions = el.querySelector('.msg-side-actions');
        if (sideActions) sideActions.remove();

        const reactions = el.querySelector('.message-reactions-bar');
        if (reactions) reactions.remove();
    }
}

// Group invite handling delegated to `public/js/realtime.js` (window.acceptGroupInvite)

// Auto scroll to bottom on load and initialize
document.addEventListener('DOMContentLoaded', () => {
    window.conversationIsGroup = {{ $conversation->is_group ? 'true' : 'false' }};

    @if(!$conversation->is_group && $conversation->other_user)
        window.currentChatUserId = {{ $conversation->other_user->id }};
    @endif

    // Force scroll to bottom immediately and again after layout settles
    if (window.history.scrollRestoration) {
        window.history.scrollRestoration = 'manual';
    }

    const container = document.getElementById('chatMessages');
    if (container) {
        window.scrollToBottom = (behavior = 'auto') => {
            if (behavior === 'smooth') {
                container.scrollTo({ top: container.scrollHeight + 1000, behavior: 'smooth' });
            } else {
                container.scrollTop = container.scrollHeight + 1000;
            }
            
            // Re-check after a short delay for late layout shifts (like media loading or font rendering)
            setTimeout(() => {
                container.scrollTop = container.scrollHeight + 1000;
                if (container.style.visibility === 'hidden') {
                    container.style.visibility = 'visible';
                }
            }, 150);
        };

        // Scroll immediately
        window.scrollToBottom('auto');

        // Scroll after a frame
        requestAnimationFrame(() => window.scrollToBottom('auto'));

        // Scroll after all images are loaded
        const images = container.querySelectorAll('img');
        if (images.length > 0) {
            let loaded = 0;
            images.forEach(img => {
                if (img.complete) loaded++;
                else {
                    img.addEventListener('load', () => {
                        loaded++;
                        if (loaded === images.length) window.scrollToBottom();
                    });
                    img.addEventListener('error', () => {
                        loaded++;
                        if (loaded === images.length) window.scrollToBottom();
                    });
                }
            });
            if (loaded === images.length) window.scrollToBottom();
        }

        // Final safety scroll - wait for load to prevent "Forced Layout" warning
        window.addEventListener('load', () => {
            requestAnimationFrame(() => {
                setTimeout(() => window.scrollToBottom('auto'), 100);
            });
        });

        // Handle viewport changes (keyboard on mobile)
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => {
                const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 250;
                if (isAtBottom) {
                    window.scrollToBottom('auto');
                }
            });
        }
    }

    window.markMessagesAsRead = function() {
        if (!window.activeConversationSlug) return;
        
        // Don't mark as read if the page is hidden (tab not active)
        if (document.visibilityState !== 'visible') return;
        
        
        // Mark messages as read (server now also handles notifications)
        fetch(`/chat/${window.activeConversationSlug}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(response => {
            if (response.status === 404) {
                // Conversation was likely deleted, ignore silently
                return;
            }
        }).catch(err => {
            // Silence "NetworkError" which happens during page navigation/tunnel blips
            if (err.name !== 'TypeError' || !err.message.includes('fetch')) {
                console.error('Mark messages as read failed:', err);
            }
        });
    };

    // Initial mark as read
    window.markMessagesAsRead();

    // Mark as read when window gains focus
    window.addEventListener('focus', () => {
        window.markMessagesAsRead();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            window.markMessagesAsRead();
        }
    });
    setInterval(() => { if (document.hasFocus()) window.markMessagesAsRead(); }, 30000);

    // Online status is now handled automatically via WebSocket disconnect events
    // in the socket-server, so no legacy beacon/polling is required here.

    
});

// Translation strings for JavaScript
window.chatTranslations = Object.assign(window.chatTranslations || {}, {
    and: '{{ __('chat.and') }}',
    are_typing: '{{ __('chat.are_typing') }}',
    users_typing: '{{ __('chat.users_typing') }}',
    is_typing: '{{ __('chat.is_typing') }}',
    story_reply: '{{ __('chat.story_reply') }}',
    failed_to_send_media: '{{ __('chat.failed_to_send_media') }}',
    error_sending_media: '{{ __('chat.error_sending_media') }}',
    group: '{{ __('chat.group') }}',
    invited_you_to_join: '{{ __('chat.invited_you_to_join') }}',
    join: '{{ __('chat.join') }}',
    sent: '{{ __('chat.sent') }}',
    delivered: '{{ __('chat.delivered') }}',
    read: '{{ __('chat.read') }}',
    message_info: '{{ __('chat.message_info') }}',
    read_by: '{{ __('chat.read_by') }}',
    delivered_to: '{{ __('chat.delivered_to') }}',
    remaining: '{{ __('chat.remaining') }}',
    cleared_the_chat: '{{ __('chat.cleared_the_chat', ['user' => ':user']) }}',
    you_cleared_the_chat: '{{ __('chat.you_cleared_the_chat') }}',
    last_seen: '{{ __('messages.last_seen') }}',
    today: '{{ __('messages.today') }}',
    yesterday: '{{ __('messages.yesterday') }}',
    at: '{{ __('messages.at') }}',
    online: '{{ __('chat.online') }}',
    playback_speed: '{{ __('messages.playback_speed') }}',
    joined: '{{ __('messages.joined') }}',
    joined_group: '{{ __('messages.joined_group') }}',
    failed_to_join_group: '{{ __('messages.failed_to_join_group') }}',
    offline: '{{ __('chat.offline') }}',
    react: '{{ __('chat.react') }}',
    reactions: '{{ __('chat.reactions') }}',
});

// Sidebar conversation updates are now handled by the sidebar partial scripts

// Typing indicator - sending only (receiving handled by realtime.js for both DM and group)
let typingTimeout;
let isTyping = false;

// Link preview state — declared at outer scope so sendMessage() can access them
let lpDebounce = null, lpDismissed = false, lpData = null, lpFetchPromise = null;
const lpCache = {}; // client-side URL cache so repeated URLs are instant
let lpCurrentUrl = null;

function dismissLinkPreview() {
    const lpCard = document.getElementById('link-preview-card');
    if (lpCard) lpCard.style.display = 'none';
    lpDismissed = true;
    lpData = null;
    lpCurrentUrl = null;
    lpFetchPromise = null;
}

document.addEventListener('DOMContentLoaded', function() {
    // Link preview
    const lpCard = document.getElementById('link-preview-card');

    function showLinkPreview(data) {
        lpData = data;
        document.getElementById('lp-domain').textContent = data.domain || '';
        document.getElementById('lp-title').textContent = data.title || data.url;
        document.getElementById('lp-desc').textContent = data.description || '';
        const img = document.getElementById('lp-image');
        if (data.image) { img.src = data.image; img.style.display = ''; }
        else img.style.display = 'none';
        if (lpCard) lpCard.style.display = 'block';
    }

    const URL_PATTERN = /https?:\/\/[^\s<>'"]{4,}/gi;

    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            // Link preview detection
            const val = this.value;
            const matches = val.match(URL_PATTERN);
            if (!matches || lpDismissed) {
                if (!matches && lpCard) { lpCard.style.display = 'none'; lpData = null; lpDismissed = false; lpCurrentUrl = null; lpFetchPromise = null; }
            } else {
                const url = matches[0];
                // Skip if same URL already loaded
                if (url === lpCurrentUrl) return;
                lpCurrentUrl = url;
                clearTimeout(lpDebounce);
                lpFetchPromise = null;

                // Instant hit from client-side cache
                if (lpCache[url]) {
                    showLinkPreview(lpCache[url]);
                    return;
                }

                const doFetch = () => {
                    lpFetchPromise = fetch('{{ route("link-preview") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ url }),
                    })
                    .then(async res => {
                        if (res.ok) {
                            const data = await res.json();
                            lpCache[url] = data; // cache for instant re-use
                            if (lpCurrentUrl === url) showLinkPreview(data);
                            return data;
                        }
                        return null;
                    })
                    .catch(() => null);
                };

                // Fire immediately if URL was just pasted (input event with large delta),
                // otherwise use short debounce so mid-word typing doesn't trigger
                const isPaste = (this.value.length - (this._prevLen || 0)) > 5;
                this._prevLen = this.value.length;
                if (isPaste) doFetch();
                else lpDebounce = setTimeout(doFetch, 250);
            }

            if (!isTyping) {
                isTyping = true;
                sendTypingStatus(true);
            }

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                isTyping = false;
                sendTypingStatus(false);
            }, 2000);
        });
    }
});

function sendTypingStatus(isTyping) {
    if (window.NexusSocket) {
        window.NexusSocket.sendTyping({{ $conversation->id }}, isTyping);
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (NexusGallery.isOpen) NexusGallery.close();
        if (typeof hideUserSearch === 'function') hideUserSearch();
    }
});

// Auto-detect Arabic text in dynamically loaded messages
function applyRTLIfArabic(element) {
    const arabicPattern = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/;
    const textElements = element.querySelectorAll('.text');
    textElements.forEach(el => {
        const text = el.textContent || el.innerText || '';
        if (arabicPattern.test(text)) {
            el.setAttribute('dir', 'rtl');
            el.style.direction = 'rtl';
            el.style.textAlign = 'right';
            el.style.display = 'block';
            el.style.width = '100%';
        }
    });
}

// Join real-time conversation room when socket is ready
document.addEventListener('DOMContentLoaded', () => {
    const joinRoom = () => {
        if (window.NexusSocket && window.NexusSocket.status === 'CONNECTED') {
            window.NexusSocket.joinConversation({{ $conversation->id }});

        } else {
            // Retry if not yet connected
            setTimeout(joinRoom, 500);
        }
    };
    // Scroll to Bottom Button Logic
    const scrollBtn = document.getElementById('scrollToBottomBtn');
    const container = document.getElementById('chatMessages');
    if (scrollBtn && container) {
        // Click to scroll
        scrollBtn.addEventListener('click', () => {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
            scrollBtn.classList.remove('visible', 'has-new-msg');
        });

        // Show/hide button based on scroll position
        container.addEventListener('scroll', () => {
            const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
            if (isAtBottom) {
                scrollBtn.classList.remove('visible', 'has-new-msg');
                const badge = scrollBtn.querySelector('.new-msg-badge');
                if (badge) {
                    badge.textContent = '';
                    badge.style.display = 'none';
                }
            } else if (container.scrollHeight > container.clientHeight + 100) {
                scrollBtn.classList.add('visible');
            }
        }, { passive: true });
    }

    // Handle real-time chat cleared event
    window.handleChatCleared = (data) => {
        // Only clear if it's the current conversation
        if (data.conversation_id && String(data.conversation_id) !== String({{ $conversation->id }})) {
            return;
        }
        window.lastMessageDate = '';
        const container = document.getElementById('chatMessages');
        if (container) {
            // Clear all current messages
            container.innerHTML = '';
            
            // Add TODAY divider
            const now = new Date();
            const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            
            const divider = document.createElement('div');
            divider.className = 'chat-date-divider';
            divider.dataset.date = todayStr;
            divider.innerHTML = `<span>${window.chatTranslations.today || 'Today'}</span>`;
            container.appendChild(divider);
            window.lastMessageDate = todayStr;
            
            // Add the system message about clearing
            if (window.addMessage) {
                window.addMessage({
                    id: data.message_id,
                    conversation_id: data.conversation_id,
                    sender_id: data.user_id,
                    content: 'system_cleared',
                    type: 'system_cleared',
                    created_at: data.created_at || new Date().toISOString(),
                    username: data.username
                });
            } else {
                const isOwn = data.user_id == {{ auth()->id() }};
                const clearText = isOwn 
                    ? (window.chatTranslations.you_cleared_the_chat || 'You cleared the chat')
                    : (window.chatTranslations.cleared_the_chat || 'Cleared the chat').replace(':user', data.username || 'User');
                const systemDiv = document.createElement('div');
                systemDiv.className = 'system-message';
                systemDiv.innerHTML = `
                    <span class="system-text">${escapeHtml(clearText)}</span>
                    <span class="system-time">${data.time || ''}</span>
                `;
                container.appendChild(systemDiv);
            }
            
            // Update Sidebar Preview
            const sidebarItem = document.querySelector(`.conversation-item[data-conversation-id="${data.conversation_id}"]`);
            if (sidebarItem) {
                const previewTextEl = sidebarItem.querySelector('.preview-text');
                if (previewTextEl) {
                    const isOwn = data.user_id == {{ auth()->id() }};
                    const clearMsg = isOwn 
                        ? (window.chatTranslations.you_cleared_the_chat || 'You cleared the chat')
                        : (window.chatTranslations.cleared_the_chat || 'Cleared the chat').replace(':user', data.username || 'User');
                    previewTextEl.textContent = clearMsg;
                    
                    // Remove unread state
                    sidebarItem.classList.remove('unread');
                    const pill = sidebarItem.querySelector('.unread-pill');
                    if (pill) pill.remove();
                    const previewContainer = sidebarItem.querySelector('.conv-preview');
                    if (previewContainer) previewContainer.classList.remove('unread-text');
                }
                
                // Remove checkmarks
                const checkIcon = sidebarItem.querySelector('.preview-content-wrapper i');
                if (checkIcon) checkIcon.remove();

                // Move to top
                const sidebar = document.getElementById('sidebarConvList');
                if (sidebar) sidebar.prepend(sidebarItem);
            }

            // Reset scroll
            requestAnimationFrame(() => {
                container.scrollTop = 0;
            });
        }
    };

    const registerSocketListeners = () => {
        if (window.NexusSocket) {
            window.NexusSocket.on('chat:cleared', window.handleChatCleared);
        } else {
            setTimeout(registerSocketListeners, 100);
        }
    };

    registerSocketListeners();
    joinRoom();
});

// Leave room on unload
window.addEventListener('beforeunload', () => {
    if (window.NexusSocket) {
        window.NexusSocket.leaveConversation({{ $conversation->id }});
    }
});

// ============================================
// Voice Message Recording and Playback
// ============================================

// Import WaveSurfer dynamically
let WaveSurfer = null;
let waveSurferLoading = false;

const loadWaveSurfer = async () => {
    if (WaveSurfer) {
        return WaveSurfer;
    }
    
    if (waveSurferLoading) {
        // Wait for existing load
        while (waveSurferLoading) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        return WaveSurfer;
    }
    
    waveSurferLoading = true;
    try {
        const module = await import('https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.esm.js');
        WaveSurfer = module.default;
        console.log('WaveSurfer loaded successfully');
    } catch (error) {
        console.error('Failed to load WaveSurfer:', error);
    } finally {
        waveSurferLoading = false;
    }
    return WaveSurfer;
};

// Voice recording state
let voiceRecordingState = {
    isRecording: false,
    isPaused: false,
    mediaRecorder: null,
    audioChunks: [],
    startTime: null,
    pausedTime: 0,
    totalPausedDuration: 0,
    timerInterval: null,
    waveform: null,
    audioBlob: null,
};

// Initialize recording waveform
async function initRecordingWaveform() {
    // Clear any existing waveform
    const container = document.getElementById('recordingWaveform');
    if (container) {
        container.innerHTML = '';
    }
    
    // We'll use canvas-based visualization during recording
    // No need to initialize WaveSurfer for recording
    console.log('Recording waveform initialized (canvas-based)');
}

// Toggle voice recording overlay
function toggleVoiceRecording() {
    const overlay = document.getElementById('voiceRecordingOverlay');
    console.log('toggleVoiceRecording called, current display:', overlay.style.display);
    
    if (overlay.style.display !== 'flex') {
        overlay.style.display = 'flex';
        initRecordingWaveform();
        resetRecordingState();
    } else {
        overlay.style.display = 'none';
        cancelVoiceRecording();
    }
}

// Check microphone permissions
async function checkMicrophonePermission() {
    try {
        // Check if browser supports permissions API
        if (!navigator.permissions) {
            console.log('Permissions API not supported, will request access directly');
            return true;
        }
        
        const result = await navigator.permissions.query({ name: 'microphone' });
        console.log('Microphone permission state:', result.state);
        
        if (result.state === 'denied') {
            alert('Microphone permission was denied. Please enable it in your browser settings:\n\n' +
                  'Chrome/Edge: Click the lock icon → Microphone → Allow\n' +
                  'Firefox: Click the lock icon → Permissions → Microphone → Allow\n' +
                  'Safari: Settings → Websites → Microphone → Allow\n\n' +
                  'Then refresh the page.');
            return false;
        }
        
        return true;
    } catch (error) {
        console.error('Error checking microphone permission:', error);
        // If we can't check, still try to request access
        return true;
    }
}

function resetRecordingState() {
    const existingWaveform = voiceRecordingState.waveform;
    
    voiceRecordingState = {
        isRecording: false,
        isPaused: false,
        mediaRecorder: null,
        audioChunks: [],
        startTime: null,
        pausedTime: 0,
        totalPausedDuration: 0,
        timerInterval: null,
        waveform: existingWaveform,
        audioBlob: null,
    };

    document.getElementById('recordingTimer').textContent = '00:00';
    document.getElementById('recordingTimer').classList.remove('recording');
    document.getElementById('recordToggleBtn').innerHTML = '<i class="fas fa-microphone"></i>';
    document.getElementById('recordToggleBtn').classList.remove('recording');
    document.getElementById('recordToggleBtn').title = '{{ __('chat.start_recording') }}';
    
    const sendBtn = document.getElementById('sendVoiceBtn');
    sendBtn.disabled = true;
    sendBtn.setAttribute('disabled', 'disabled');
}

// Start/stop recording
async function toggleVoiceRecord() {
    console.log('Toggle voice record clicked, state:', {
        isRecording: voiceRecordingState.isRecording,
        isPaused: voiceRecordingState.isPaused,
        recorderState: voiceRecordingState.mediaRecorder?.state
    });
    
    if (!voiceRecordingState.isRecording && !voiceRecordingState.isPaused) {
        await startRecording();
    } else if (voiceRecordingState.isRecording) {
        // Stop recording (not pause)
        stopRecording();
    } else if (voiceRecordingState.isPaused) {
        resumeRecording();
    } else {
        // Recorder is inactive, close overlay
        console.log('Recorder inactive, closing overlay');
        document.getElementById('voiceRecordingOverlay').style.display = 'none';
        resetRecordingState();
    }
}

async function startRecording() {
    try {
        // Check if browser supports getUserMedia
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Your browser does not support audio recording. Please use a modern browser like Chrome, Firefox, or Edge.');
        }
        
        // Check if page is served over HTTPS or localhost
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            console.warn('Warning: Microphone access requires HTTPS in production. Current protocol:', window.location.protocol);
        }
        
        console.log('Requesting microphone access...');
        
        // Get available audio devices to select proper microphone
        let audioConstraints = {
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                sampleRate: 44100,
                autoGainControl: true
            }
        };
        
        // Try to find a real microphone (not monitor/output devices)
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const audioInputs = devices.filter(device => device.kind === 'audioinput');
            console.log('Available microphones:', audioInputs.map(d => ({ label: d.label || '(hidden by browser)', id: d.deviceId })));
            
            if (audioInputs.length > 0) {
                // Find a microphone that's not a monitor device
                const realMic = audioInputs.find(input => 
                    !input.label.toLowerCase().includes('monitor') && 
                    !input.label.toLowerCase().includes('output') &&
                    !input.label.toLowerCase().includes('dummy') &&
                    input.label.trim() !== ''
                );
                
                if (realMic && realMic.label) {
                    console.log('Selected microphone:', realMic.label);
                    audioConstraints.audio = {
                        deviceId: { ideal: realMic.deviceId },
                        echoCancellation: true,
                        noiseSuppression: true,
                        sampleRate: 44100,
                        autoGainControl: true
                    };
                } else {
                    // Use first available microphone with ideal (not exact) constraint
                    console.log('Using first available microphone (label hidden by browser)');
                    audioConstraints.audio.deviceId = { ideal: audioInputs[0].deviceId };
                }
            }
        } catch (err) {
            console.warn('Could not enumerate devices, using default microphone:', err);
        }
        
        const stream = await navigator.mediaDevices.getUserMedia(audioConstraints);

        console.log('Microphone access granted!');
        console.log('Audio tracks:', stream.getAudioTracks().map(t => ({ label: t.label, enabled: t.enabled, muted: t.muted })));

        voiceRecordingState.mediaRecorder = new MediaRecorder(stream, {
            mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 
                      MediaRecorder.isTypeSupported('audio/ogg') ? 'audio/ogg' : 
                      MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : ''
        });
        
        console.log('Using MIME type:', voiceRecordingState.mediaRecorder.mimeType);
        
        voiceRecordingState.audioChunks = [];

        voiceRecordingState.mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                voiceRecordingState.audioChunks.push(event.data);
                console.log('Audio chunk received, size:', event.data.size);
            }
        };

        // Store mimeType before onstop fires
        const recordedMimeType = voiceRecordingState.mediaRecorder.mimeType || 'audio/webm';
        console.log('Using MIME type:', recordedMimeType);

        voiceRecordingState.mediaRecorder.onstop = () => {
            console.log('MediaRecorder stopped event fired');
            console.log('Audio chunks collected:', voiceRecordingState.audioChunks.length);

            if (voiceRecordingState.audioChunks.length > 0) {
                // Use the actual recorded MIME type
                voiceRecordingState.audioBlob = new Blob(voiceRecordingState.audioChunks, { type: recordedMimeType });
                
                console.log('✅ Recording created, blob size:', voiceRecordingState.audioBlob.size, 'bytes');
                console.log('✅ Blob type:', voiceRecordingState.audioBlob.type);
                
                // Enable send button
                enableSendButton();
            } else {
                console.error('❌ No audio chunks collected!');
                alert('No audio was recorded. Please try again and make sure to speak into the microphone.');
            }
        };

        voiceRecordingState.mediaRecorder.onerror = (event) => {
            console.error('MediaRecorder error:', event.error);
            alert('Recording error: ' + event.error.name + ' - ' + event.error.message);
        };

        voiceRecordingState.mediaRecorder.start(100);
        voiceRecordingState.isRecording = true;
        voiceRecordingState.isPaused = false;
        voiceRecordingState.startTime = Date.now();

        // Update UI
        document.getElementById('recordingTimer').classList.add('recording');
        document.getElementById('recordToggleBtn').innerHTML = '<i class="fas fa-pause"></i>';
        document.getElementById('recordToggleBtn').classList.add('recording');
        document.getElementById('recordToggleBtn').title = '{{ __('chat.stop_recording') }}';

        // Start timer
        clearInterval(voiceRecordingState.timerInterval);
        voiceRecordingState.timerInterval = setInterval(updateRecordingTimer, 1000);

        // Connect to waveform (visual feedback) - start canvas visualization
        const analyser = createAudioAnalyser(stream);
        updateRecordingWaveform(analyser);
        
        console.log('Recording started with canvas waveform');

    } catch (error) {
        console.error('Error accessing microphone:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        
        let errorMessage = '{{ __('chat.microphone_access_denied') }}\n\n';
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            errorMessage += 'Permission was denied. To enable microphone access:\n';
            errorMessage += '1. Click the lock icon in your browser address bar\n';
            errorMessage += '2. Find "Microphone" or "Permissions"\n';
            errorMessage += '3. Set to "Allow"\n';
            errorMessage += '4. Refresh the page and try again';
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            errorMessage += 'No microphone was found. Please connect a microphone and try again.';
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            errorMessage += 'Your microphone is being used by another application. Please close other apps and try again.';
        } else if (error.name === 'OverconstrainedError') {
            errorMessage += 'Your microphone does not support the required audio settings.\n\n';
            errorMessage += 'Tip: Try selecting a different microphone in your browser settings.';
        } else if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
            errorMessage += '\n\n⚠️ IMPORTANT: Microphone access requires HTTPS.\n';
            errorMessage += 'You are currently using: ' + window.location.protocol + '\n';
            errorMessage += 'Please use HTTPS or localhost for microphone access.';
        }
        
        alert(errorMessage);
        cancelVoiceRecording();
    }
}

function createAudioAnalyser(stream) {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const source = audioContext.createMediaStreamSource(stream);
    const analyser = audioContext.createAnalyser();
    analyser.fftSize = 256;
    source.connect(analyser);
    return analyser;
}

function updateRecordingWaveform(analyser) {
    if (!voiceRecordingState.isRecording || voiceRecordingState.isPaused) return;
    
    const dataArray = new Uint8Array(analyser.frequencyBinCount);
    analyser.getByteFrequencyData(dataArray);
    
    // Always use canvas-based visualization (more reliable)
    drawWaveformOnCanvas(dataArray);
    
    requestAnimationFrame(() => updateRecordingWaveform(analyser));
}

function drawWaveformOnCanvas(dataArray) {
    const container = document.querySelector('#recordingWaveform');
    if (!container) return;

    let canvas = container.querySelector('canvas');
    if (!canvas) {
        canvas = document.createElement('canvas');
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        container.innerHTML = '';
        container.appendChild(canvas);
    }

    const ctx = canvas.getContext('2d');
    const width = canvas.width = container.offsetWidth || 300;
    const height = canvas.height = container.offsetHeight || 80;

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#4ade80';

    const barWidth = (width / dataArray.length) * 2.5;
    let x = 0;

    for (let i = 0; i < dataArray.length; i++) {
        const barHeight = (dataArray[i] / 255) * height;
        ctx.fillRect(x, (height - barHeight) / 2, barWidth - 1, barHeight);
        x += barWidth + 1;
    }
}

function pauseRecording() {
    if (voiceRecordingState.mediaRecorder && voiceRecordingState.isRecording) {
        voiceRecordingState.pausedTime = Date.now();
        voiceRecordingState.mediaRecorder.pause();
        voiceRecordingState.isPaused = true;
        voiceRecordingState.isRecording = false;

        document.getElementById('recordToggleBtn').innerHTML = '<i class="fas fa-microphone"></i>';
        document.getElementById('recordToggleBtn').classList.remove('recording');
        document.getElementById('recordToggleBtn').title = '{{ __('chat.start_recording') }}';
        clearInterval(voiceRecordingState.timerInterval);
    }
}

function resumeRecording() {
    if (voiceRecordingState.mediaRecorder && voiceRecordingState.isPaused) {
        voiceRecordingState.mediaRecorder.resume();
        voiceRecordingState.isPaused = false;
        voiceRecordingState.isRecording = true;
        voiceRecordingState.totalPausedDuration += Date.now() - voiceRecordingState.pausedTime;

        document.getElementById('recordToggleBtn').innerHTML = '<i class="fas fa-pause"></i>';
        document.getElementById('recordToggleBtn').classList.add('recording');
        document.getElementById('recordToggleBtn').title = '{{ __('chat.stop_recording') }}';
        voiceRecordingState.timerInterval = setInterval(updateRecordingTimer, 1000);
    }
}

function stopRecording() {
    if (voiceRecordingState.mediaRecorder && voiceRecordingState.mediaRecorder.state !== 'inactive') {
        voiceRecordingState.mediaRecorder.stop();
        if (voiceRecordingState.mediaRecorder.stream) {
            voiceRecordingState.mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }

        voiceRecordingState.isRecording = false;
        voiceRecordingState.isPaused = false;

        document.getElementById('recordToggleBtn').innerHTML = '<i class="fas fa-microphone"></i>';
        document.getElementById('recordToggleBtn').classList.remove('recording');
        document.getElementById('recordToggleBtn').title = '{{ __('chat.start_recording') }}';
        clearInterval(voiceRecordingState.timerInterval);
    }
}

function updateRecordingTimer() {
    if (!voiceRecordingState.startTime) return;
    
    // Calculate elapsed time excluding pause duration
    const now = Date.now();
    const totalElapsed = now - voiceRecordingState.startTime;
    const elapsed = Math.floor((totalElapsed - voiceRecordingState.totalPausedDuration) / 1000);
    
    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
    const seconds = (elapsed % 60).toString().padStart(2, '0');
    document.getElementById('recordingTimer').textContent = `${minutes}:${seconds}`;
    
    // Max 5 minutes
    if (elapsed >= 300) {
        pauseRecording();
    }
}

function cancelVoiceRecording() {
    console.log('Canceling voice recording');
    
    if (voiceRecordingState.mediaRecorder) {
        if (voiceRecordingState.isRecording || voiceRecordingState.isPaused) {
            voiceRecordingState.mediaRecorder.stop();
        }
        // Stop all audio tracks
        if (voiceRecordingState.mediaRecorder.stream) {
            voiceRecordingState.mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }
    }

    clearInterval(voiceRecordingState.timerInterval);

    if (voiceRecordingState.waveform) {
        try {
            voiceRecordingState.waveform.destroy();
        } catch (e) {
            console.error('Error destroying waveform:', e);
        }
        voiceRecordingState.waveform = null;
    }

    document.getElementById('voiceRecordingOverlay').style.display = 'none';
    resetRecordingState();
}

// Send voice message
async function sendVoiceMessage() {
    const sendBtn = document.getElementById('sendVoiceBtn');
    if (!sendBtn || sendBtn.disabled) return;

    if (!voiceRecordingState.audioBlob) {
        alert('No recording found. Please try again.');
        return;
    }
    
    // Validate duration (minimum 1 second)
    const elapsed = voiceRecordingState.startTime ? Math.floor((Date.now() - voiceRecordingState.startTime - voiceRecordingState.totalPausedDuration) / 1000) : 0;
    if (elapsed < 1) {
        alert('Recording is too short. Please record at least 1 second.');
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        return;
    }

    // Disable send button
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const formData = new FormData();
    const filename = 'voice-message-' + Date.now() + '.webm';
    formData.append('voice_message', voiceRecordingState.audioBlob, filename);
    formData.append('duration', elapsed > 0 ? elapsed : 1);
    formData.append('waveform_peaks', JSON.stringify(generateWaveformPeaks()));
    
    // Add reply support
    if (replyingTo && replyingTo.id) {
        formData.append('reply_to_id', replyingTo.id);
    }

    try {
        const response = await fetch(`{{ route('chat.store', $conversation) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (data.message) {
                appendVoiceMessage(data.message);
            }
            cancelVoiceRecording();
        } else {
            let errorMsg = 'Failed to send voice message';
            if (data.message) errorMsg += ': ' + data.message;
            alert(errorMsg);
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        }
    } catch (error) {
        alert('Network error. Please check your connection and try again.');
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    }
}

// Enable send button function - replaces button element entirely
function enableSendButton() {
    const sendBtn = document.getElementById('sendVoiceBtn');
    if (!sendBtn) {
        console.error('❌ Send button not found');
        return;
    }
    
    // Create a new button to replace the disabled one
    const newSendBtn = sendBtn.cloneNode(true);
    newSendBtn.disabled = false;
    newSendBtn.removeAttribute('disabled');
    newSendBtn.style.setProperty('pointer-events', 'auto', 'important');
    newSendBtn.style.setProperty('cursor', 'pointer', 'important');
    newSendBtn.style.setProperty('opacity', '1', 'important');
    newSendBtn.style.setProperty('background', 'var(--wa-accent)', 'important');
    
    // Add click handler
    newSendBtn.onclick = function() {
        console.log('🎤 Send clicked!');
        sendVoiceMessage();
    };
    
    // Replace the old button with the new one
    sendBtn.parentNode.replaceChild(newSendBtn, sendBtn);
    
    console.log('✅ Send button enabled and replaced');
    console.log('✅ New button disabled:', newSendBtn.disabled);
    console.log('✅ New button has disabled attr:', newSendBtn.hasAttribute('disabled'));
}

function generateWaveformPeaks() {
    // Generate realistic-looking waveform peaks
    // Create a pattern that rises and falls like actual audio
    const peaks = [];
    const numPeaks = 50;
    
    // Generate peaks with varying amplitudes
    for (let i = 0; i < numPeaks; i++) {
        // Create a wave-like pattern with some randomness
        const baseAmplitude = Math.sin(i / numPeaks * Math.PI); // Bell curve
        const randomness = Math.random() * 0.5 + 0.5; // 0.5 to 1.0
        const peak = baseAmplitude * randomness;
        peaks.push(Math.max(0.1, peak)); // Ensure minimum visibility
    }
    
    return peaks;
}

function appendVoiceMessage(message) {
    const messagesContainer = document.getElementById('chatMessages');
    const isOwn = message.sender_id === {{ auth()->id() }};
    const date = new Date(message.created_at);
    let hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'pm' : 'am';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const time = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
    const senderName = isOwn ? (window.chatTranslations.you || 'You') : (message.sender?.username || 'User');
    const audioUrl = '/storage/' + message.media_path;

    const duration = message.duration || 0;
    const totalMin = Math.floor(duration / 60);
    const totalSec = String(duration % 60).padStart(2, '0');

    const messageHtml = `
        <div class="message ${isOwn ? 'own' : 'other'}" data-message-id="${message.id}" data-sender-name="${escapeHtml(senderName)}">
            ${!isOwn ? `<div class="message-avatar"><a href="/users/${escapeHtml(message.sender.username)}" style="display:flex;flex-shrink:0;"><img src="${escapeHtml(message.sender.avatar_url)}" alt="${escapeHtml(message.sender.username)}" style="pointer-events:none;"></a></div>` : ''}
            <div class="message-bubble">
                ${(!isOwn && window.isGroupChat) ? `<a href="/users/${escapeHtml(message.sender.username)}" class="sender-name" style="text-decoration:none;display:inline-flex;align-items:center;gap:.15em;">${escapeHtml(message.sender.username)}${message.sender.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : ''}</a>` : ''}
                <div class="message-content">
                    <div class="voice-message" data-audio-url="${audioUrl}" data-duration="${duration}">
                        <button class="voice-play-btn" onclick="toggleVoiceMessage(this)">
                            <i class="fas fa-play"></i>
                        </button>
                        <div class="voice-info">
                            <div class="voice-progress-container" onclick="seekVoice(event, this)">
                                <div class="voice-progress-bar"></div>
                            </div>
                            <div class="voice-meta">
                                <span class="voice-label">${window.chatTranslations.voice_message || 'Voice Message'}</span>
                                <span class="voice-duration">0:00 / ${totalMin}:${totalSec}</span>
                            </div>
                        </div>
                        <button class="voice-speed-btn" onclick="toggleVoiceSpeed(this)">1x</button>
                    </div>
                    <span class="message-time">
                        ${time}
                        ${isOwn ? '<i class="fas fa-check" title="' + (window.chatTranslations.sent || 'Sent') + '"></i>' : ''}
                    </span>
                    <div class="msg-item-actions">
                        <button class="msg-action-trigger" onclick="toggleMsgMenu(event, '${message.id}')">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="msg-dropdown" id="msgDropdown-${message.id}">
                            <button class="menu-item" onclick="initiateReply('${message.id}')">
                                <i class="fas fa-reply"></i> ${window.chatTranslations.reply || 'Reply'}
                            </button>
                            ${isOwn ? `
                            <button class="menu-item danger" onclick="deleteMessage(${message.id})">
                                <i class="fas fa-trash-alt"></i> ${window.chatTranslations.delete_message || 'Delete'}
                            </button>` : ''}
                        </div>
                    </div>
                </div>
                <div class="message-reactions-bar" data-message-id="${message.id}" style="display:none;"></div>
            </div>
            <button class="quick-react-btn" onclick="openReactionPicker(event, '${message.id}')" title="${window.chatTranslations.react || 'React'}">
                <i class="far fa-smile"></i>
            </button>
        </div>
    `;

    messagesContainer.insertAdjacentHTML('beforeend', messageHtml);

    // Smart Scroll Logic for Voice Messages
    const isAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 150;
    const scrollBtn = document.getElementById('scrollToBottomBtn');
    
    if (isAtBottom || isOwn) {
        if (window.scrollToBottom) window.scrollToBottom(isOwn ? 'smooth' : 'auto');
        else messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } else if (scrollBtn) {
        scrollBtn.classList.add('visible', 'has-new-msg');
    }
}

// Clean Voice Message Playback
let currentAudio = null;
let currentBtn = null;
let animationFrame = null;

async function toggleVoiceMessage(btn) {
    const voiceMessage = btn.closest('.voice-message');
    if (!voiceMessage) return;

    const audioUrl = voiceMessage.dataset.audioUrl;
    if (!audioUrl) return;

    const progressBar = voiceMessage.querySelector('.voice-progress-bar');
    const timeDisplay = voiceMessage.querySelector('.voice-duration');
    const totalDuration = parseFloat(voiceMessage.dataset.duration) || 0;

    // If clicking the same message
    if (currentBtn === btn) {
        if (currentAudio && currentAudio.paused) {
            await currentAudio.play();
            btn.innerHTML = '<i class="fas fa-pause"></i>';
        } else if (currentAudio) {
            currentAudio.pause();
            btn.innerHTML = '<i class="fas fa-play"></i>';
        }
        return;
    }

    // Stop previous audio and reset its UI
    if (currentAudio) {
        currentAudio.pause();
        if (currentBtn) {
            currentBtn.innerHTML = '<i class="fas fa-play"></i>';
            const oldContainer = currentBtn.closest('.voice-message');
            if (oldContainer) {
                const oldBar = oldContainer.querySelector('.voice-progress-bar');
                const oldTime = oldContainer.querySelector('.voice-duration');
                const oldDur = parseFloat(oldContainer.dataset.duration) || 0;
                if (oldBar) oldBar.style.width = '0%';
                if (oldTime) {
                    const m = Math.floor(oldDur / 60);
                    const s = String(Math.floor(oldDur % 60)).padStart(2, '0');
                    oldTime.textContent = `0:00 / ${m}:${s}`;
                }
            }
        }
    }

    // Create new audio
    currentAudio = new Audio(audioUrl);
    currentBtn = btn;

    const speedBtn = voiceMessage.querySelector('.voice-speed-btn');
    if (speedBtn) currentAudio.playbackRate = parseFloat(speedBtn.textContent) || 1;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    currentAudio.ontimeupdate = () => {
        if (!currentAudio) return;
        const progress = (currentAudio.currentTime / currentAudio.duration) * 100;
        if (progressBar) progressBar.style.width = `${progress}%`;
        
        if (timeDisplay) {
            const curMin = Math.floor(currentAudio.currentTime / 60);
            const curSec = String(Math.floor(currentAudio.currentTime % 60)).padStart(2, '0');
            const totMin = Math.floor(currentAudio.duration / 60) || Math.floor(totalDuration / 60);
            const totSec = String(Math.floor(currentAudio.duration % 60) || Math.floor(totalDuration % 60)).padStart(2, '0');
            timeDisplay.textContent = `${curMin}:${curSec} / ${totMin}:${totSec}`;
        }
    };

    currentAudio.onplay = () => {
        btn.innerHTML = '<i class="fas fa-pause"></i>';
    };

    currentAudio.onpause = () => {
        btn.innerHTML = '<i class="fas fa-play"></i>';
    };

    currentAudio.onended = () => {
        btn.innerHTML = '<i class="fas fa-play"></i>';
        if (progressBar) progressBar.style.width = '0%';
        if (timeDisplay) {
            const m = Math.floor(totalDuration / 60);
            const s = String(Math.floor(totalDuration % 60)).padStart(2, '0');
            timeDisplay.textContent = `0:00 / ${m}:${s}`;
        }
        currentAudio = null;
        currentBtn = null;
    };

    currentAudio.onerror = () => {
        btn.innerHTML = '<i class="fas fa-play"></i>';
        currentAudio = null;
        currentBtn = null;
    };

    try {
        await currentAudio.play();
    } catch (e) {
        btn.innerHTML = '<i class="fas fa-play"></i>';
        currentAudio = null;
        currentBtn = null;
    }
}

function seekVoice(event, container) {
    if (!currentAudio || currentBtn.closest('.voice-message') !== container.closest('.voice-message')) return;
    
    const rect = container.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const width = rect.width;
    const percentage = x / width;
    
    currentAudio.currentTime = percentage * currentAudio.duration;
}

// Draw waveform with progress
function drawWaveform(container, progress, duration) {
    container.innerHTML = '';
    const canvas = document.createElement('canvas');
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    container.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    const width = canvas.width = container.offsetWidth || 120;
    const height = canvas.height = container.offsetHeight || 40;
    const playWidth = width * progress;

    // Background
    ctx.fillStyle = 'rgba(0, 0, 0, 0.2)';
    ctx.fillRect(0, 0, width, height);

    // Played portion (solid green)
    ctx.fillStyle = '#22c55e';
    ctx.fillRect(0, 0, playWidth, height);

    // Unplayed bars (dim green)
    ctx.fillStyle = 'rgba(74, 222, 128, 0.3)';
    const barCount = Math.floor(width / 5);
    for (let i = 0; i < barCount; i++) {
        const x = i * 5;
        if (x > playWidth) {
            const barHeight = Math.random() * (height * 0.6) + (height * 0.2);
            ctx.fillRect(x, (height - barHeight) / 2, 2, barHeight);
        }
    }

    // Cursor line
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(playWidth, 0);
    ctx.lineTo(playWidth, height);
    ctx.stroke();
}

// Animate waveform
function animateWaveform(container) {
    const canvas = container.querySelector('canvas');
    if (!canvas || !currentAudio) return;

    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const duration = currentAudio.duration;

    function draw() {
        if (currentAudio.paused || currentAudio.ended) return;

        const progress = currentAudio.currentTime / duration;
        const playWidth = width * progress;

        ctx.clearRect(0, 0, width, height);

        // Background
        ctx.fillStyle = 'rgba(0, 0, 0, 0.2)';
        ctx.fillRect(0, 0, width, height);

        // Played (solid)
        ctx.fillStyle = '#22c55e';
        ctx.fillRect(0, 0, playWidth, height);

        // Cursor
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(playWidth, 0);
        ctx.lineTo(playWidth, height);
        ctx.stroke();

        animationFrame = requestAnimationFrame(draw);
    }
    draw();
}

// Toggle playback speed
function toggleVoiceSpeed(btn) {
    const speeds = ['1x', '1.25x', '1.5x', '2x'];
    const currentSpeed = parseFloat(btn.textContent);
    const currentIndex = speeds.indexOf(btn.textContent);
    const nextIndex = (currentIndex + 1) % speeds.length;
    const nextSpeed = speeds[nextIndex];

    btn.textContent = nextSpeed;
    console.log('Playback speed changed to:', nextSpeed);

    if (currentAudio) {
        currentAudio.playbackRate = parseFloat(nextSpeed);
    }
}

window.currentUserId = {{ auth()->id() }};

/* ═══════════════════════════════════════════════
   MESSAGE REACTIONS ENGINE
   ═══════════════════════════════════════════════ */

const REACTION_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '😡'];

/**
 * Open the floating reaction picker near a message bubble
 */
window.openReactionPicker = function(event, messageId) {
    event.preventDefault();
    event.stopPropagation();

    // Close any open dropdowns / existing picker
    document.querySelectorAll('.msg-dropdown').forEach(d => d.classList.remove('show'));
    closeReactionPicker();

    const messageEl = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (!messageEl) return;

    const bubble = messageEl.querySelector('.message-bubble');
    const rect = bubble.getBoundingClientRect();
    
    // Find my current reaction for this message
    const bar = document.querySelector(`.message-reactions-bar[data-message-id="${messageId}"]`);
    const myCurrentEmoji = bar ? bar.getAttribute('data-my-reaction') : null;

    // Build picker HTML with indicator for current reaction
    const pickerHtml = `
        <div class="emoji-list">
            ${REACTION_EMOJIS.map(emoji => {
                const isActive = myCurrentEmoji === emoji;
                return `<span onclick="toggleMessageReaction('${messageId}', '${emoji}')" 
                                class="${isActive ? 'active-pick' : ''}" 
                                title="${emoji}">${emoji}</span>`;
            }).join('')}
        </div>
    `;

    // Create overlay to close on outside click
    const overlay = document.createElement('div');
    overlay.className = 'reaction-picker-overlay';
    overlay.onclick = closeReactionPicker;
    document.body.appendChild(overlay);

    // Create picker element
    const picker = document.createElement('div');
    picker.className = 'msg-reaction-picker';
    picker.id = 'activeReactionPicker';
    picker.innerHTML = pickerHtml;
    document.body.appendChild(picker);

    // Position calculation
    const padding = 15;
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    // Get picker size after it's in the DOM
    // Use offsetWidth/offsetHeight as they ignore CSS transforms (scale)
    const pWidth = picker.offsetWidth;
    const pHeight = picker.offsetHeight;
    
    // Try to position centered above the button that was clicked
    const btn = event.currentTarget;
    const btnRect = btn.getBoundingClientRect();
    
    let top = btnRect.top - pHeight - 12;
    let left = btnRect.left - (pWidth / 2) + (btnRect.width / 2);

    // Viewport boundaries check to prevent going off-screen
    if (left < 15) left = 15;
    if (left + pWidth > viewportWidth - 15) {
        left = viewportWidth - pWidth - 15;
    }
    
    // If not enough space above, position below the button
    if (top < padding) {
        top = btnRect.bottom + 12;
    }

    
    // Vertical clamping
    if (top + pHeight > viewportHeight - padding) {
        top = viewportHeight - pHeight - padding;
    }
    if (top < padding) top = padding;

    // Small screen centering if too wide
    if (viewportWidth < 400 && pWidth > viewportWidth - 30) {
        left = (viewportWidth - pWidth) / 2;
    }


    picker.style.top = top + 'px';
    picker.style.left = left + 'px';
    
    // Add active class for animation
    setTimeout(() => picker.classList.add('active'), 10);
};

function closeReactionPicker() {
    const picker = document.getElementById('activeReactionPicker');
    if (picker) picker.remove();
    const overlay = document.querySelector('.reaction-picker-overlay');
    if (overlay) overlay.remove();
}

/**
 * Toggle/Switch/Remove a reaction
 */
window.toggleMessageReaction = function(messageId, emoji) {
    closeReactionPicker();

    fetch(`/chat/message/${messageId}/react`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ reaction_type: emoji }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderReactionsBar(messageId, data.reactions);
        }
    })
    .catch(err => console.error('Reaction error:', err));
};

/**
 * Render reactions as a single grouped pill
 */
function renderReactionsBar(messageId, reactions) {
    const bar = document.querySelector(`.message-reactions-bar[data-message-id="${messageId}"]`);
    if (!bar) return;

    if (!reactions || reactions.length === 0) {
        bar.innerHTML = '';
        bar.style.display = 'none';
        bar.removeAttribute('data-my-reaction');
        const bubble = bar.closest('.message-bubble');
        if (bubble) bubble.classList.remove('has-reactions');
        return;

    }

    const currentUserId = window.currentUserId;
    let totalCount = 0;
    let hasMine = false;
    let myEmoji = null;
    let uniqueEmojis = [];

    reactions.forEach(r => {
        totalCount += r.count;
        uniqueEmojis.push(r.reaction_type);
        if (r.users.some(u => u.id === currentUserId)) {
            hasMine = true;
            myEmoji = r.reaction_type;
        }
    });

    // Store my reaction on the bar for the picker to find
    if (myEmoji) bar.setAttribute('data-my-reaction', myEmoji);
    else bar.removeAttribute('data-my-reaction');

    bar.innerHTML = `
        <div class="reaction-group-pill ${hasMine ? 'has-mine' : ''}" 
             onclick="showMessageReactors('${messageId}')">
            <div class="reaction-emoji-stack">
                ${uniqueEmojis.map(e => {
                    return `<span class="stack-emoji">${e}</span>`;
                }).join('')}
            </div>
            <span class="reaction-total-count">${totalCount}</span>
        </div>
    `;

    bar.style.display = 'flex';
    
    // Add has-reactions class to bubble for proper spacing
    const bubble = bar.closest('.message-bubble');
    if (bubble) {
        bubble.classList.add('has-reactions');
    }
}


/**
 * Reactors Modal Logic
 */
window.showMessageReactors = function(messageId) {
    const modal = document.getElementById('reactorsModalOverlay');
    const list = document.getElementById('reactorsList');
    
    if (!modal || !list) {
        console.error('Reactors modal elements not found in DOM');
        return;
    }

    list.innerHTML = '<div style="padding: 20px; text-align: center; color: #8696a0;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    modal.style.display = 'flex';

    console.log('Fetching reactors for message:', messageId);

    fetch(`/chat/message/${messageId}/reactions`)
    .then(r => {
        if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
        return r.json();
    })
    .then(data => {
        if (!data.success || !data.reactions || !Array.isArray(data.reactions) || data.reactions.length === 0) {
            list.innerHTML = '<div style="padding: 20px; text-align: center; color: #8696a0;">No reactions found.</div>';
            return;
        }

        let html = '';
        data.reactions.forEach((group) => {
            if (!group.users || !Array.isArray(group.users)) return;
            
            group.users.forEach((user) => {
                const isMe = String(user.id) === String(window.currentUserId);
                const avatar = user.avatar_url || '/images/default-avatar.svg';
                const userVerifiedBadge = user.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
                const usernameLabel = isMe ? `<span style="color: var(--wa-accent);">(${window.chatTranslations?.you || 'You'})</span> ${user.username || 'User'}` : ((user.username || 'User') + userVerifiedBadge);
                const emoji = group.reaction_type || '❓';
                
                const clickAttr = isMe ? `onclick="toggleMessageReaction('${messageId}', '${emoji}'); closeReactorsModal();"` : '';
                const removeLabel = isMe ? `<div style="font-size: 11px; color: var(--wa-red); opacity: 0.8; margin-top: 2px;">${window.chatTranslations?.click_to_remove || 'Click to remove'}</div>` : '';
                const wrapStart = !isMe ? `<a href="/users/${encodeURIComponent(user.username || '')}" style="text-decoration:none;color:inherit;display:contents;">` : '';
                const wrapEnd = !isMe ? `</a>` : '';

                html += `
                    <div class="global-reactor-item" ${clickAttr} style="${isMe ? 'cursor: pointer;' : 'cursor: default;'}">
                        ${wrapStart}<div class="global-reactor-avatar">
                            <img src="${avatar}" alt="${user.username}" onerror="this.src='/images/default-avatar.svg'" style="${!isMe ? 'pointer-events:none;' : ''}">
                        </div>
                        <div class="global-reactor-info">
                            <div class="global-reactor-name">${usernameLabel}</div>
                            ${removeLabel}
                        </div>
                        <div class="global-reactor-emoji">
                            ${emoji}
                        </div>${wrapEnd}
                    </div>
                `;
            });
        });

        if (!html) {
            list.innerHTML = '<div style="padding: 20px; text-align: center; color: #8696a0;">No reactors could be processed.</div>';
        } else {
            list.innerHTML = html;
        }
    })
    .catch(err => {
        console.error('Fetch reactors failed:', err);
        list.innerHTML = `<div style="padding: 20px; text-align: center; color: #ff3b30;">Error loading reactors.</div>`;
    });
};

/**
 * Handle real-time reaction updates from WebSocket
 */
window.updateReactionsFromSocket = function(data) {
    if (!data || !data.message_id) return;
    renderReactionsBar(data.message_id, data.reactions);
};

</script>
@endsection
