@extends('layouts.app')

@section('title', 'Global Chat - Nexus')

@push('styles')
@vite('resources/css/global-chat.css')
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

    .scroll-bottom-btn {
        position: fixed;
        right: 20px;
        bottom: 85px;
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 999;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .scroll-bottom-btn:hover {
        background: var(--primary);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .scroll-bottom-btn:active {
        transform: scale(0.95);
    }

    .scroll-bottom-btn.show {
        display: flex;
        animation: premiumPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes premiumPop {
        0% { transform: scale(0.5); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }


    .unread-scroll-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: 800;
        min-width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--bg);
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
    }

    @media (max-width: 768px) {
        .chat-page, .global-chat-page {
            height: calc(100vh - 48px);
            height: calc(100dvh - 48px);
            overflow: hidden;
        }

        .mobile-back-btn {
            display: flex !important;
        }

        .scroll-bottom-btn {
            right: 15px;
            bottom: 80px;
            width: 42px;
            height: 42px;
        }

    }

            #online-count {
                font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
                font-size: 13.5px;
                color: #00ff41;
                text-shadow: 0 0 10px rgba(0, 255, 65, 0.7);
                letter-spacing: 0.8px;
                display: flex;
                align-items: center;
                gap: 5px;
                font-weight: 500;
            }

            /* Light Theme Override */
            [data-theme="light"] #online-count,
            .light-mode #online-count {
                color: #008031;
                text-shadow: none;
            }

            @keyframes hackerPulse {
                0%, 100% { opacity: 0.5; }
                50% { opacity: 1; }
            }
        }
    }
    /* Adjust chat header for typing indicator removed */
    .chat-header {
        position: relative;
        overflow: visible !important;
        margin-bottom: 25px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
    }

    .clear-chat-btn {
        padding: 8px 14px;
        border-radius: 980px;
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #ef4444;
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        backdrop-filter: blur(10px);
        white-space: nowrap;
    }
    .clear-chat-btn i { font-size: 14px; }
    .clear-chat-btn:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    }
    .clear-chat-btn:active { transform: scale(0.95); }
    
    [lang="ar"] .header-actions { margin-left: 0; margin-right: auto; flex-direction: row-reverse; }
    [lang="ar"] .clear-chat-btn { flex-direction: row-reverse; }

    @media (max-width: 580px) {
        .clear-chat-btn { 
            padding: 6px 10px; 
            height: auto; 
            width: auto;
            border-radius: 12px; 
            font-size: 10px;
            gap: 4px;
        }
        .clear-chat-btn span { display: inline; }
        .clear-chat-btn i { font-size: 11px; }
        .header-actions { gap: 6px; }
    }
</style>
@endpush

@section('content')
<div class="global-chat-page">
    <div class="chat-header">
        <div class="header-info">
            <a href="{{ route('home') }}" class="mobile-back-btn">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div class="header-text">
                <h1>Global Community</h1>

            </div>
        </div>
        <div class="header-actions">
            @if(auth()->user()->is_admin)
                <button class="clear-chat-btn" onclick="confirmClearChat()" title="Clear All Messages">
                    <i class="fas fa-trash-sweep"></i>
                    <span>Clear Chat</span>
                </button>
            @endif
            <div class="online-stats">
                <div id="online-count" class="online-count">[ ONLINE: 1 ]</div>
            </div>
        </div>
    </div>

    <div class="chat-container" id="chatMessages" data-message-deleted-text="{{ __('chat.message_deleted') }}">
        <div class="messages-wrapper" id="messagesWrapper">
            @foreach($messages as $msg)
                @php
                    $myReaction = $msg->reactions->where('user_id', auth()->id())->first()?->reaction;
                    $mediaItems = $msg->media;
                @endphp
                <div class="message-item {{ $msg->user_id === auth()->id() ? 'own' : '' }} {{ $msg->reactions->count() > 0 ? 'has-reactions' : '' }}" data-id="{{ $msg->id }}" data-my-reaction="{{ $myReaction }}">
                    <div class="message-avatar">
                        <a href="/users/{{ $msg->user->username }}" style="display:flex;flex-shrink:0;"><img src="{{ $msg->user->avatar_url }}" alt="{{ $msg->user->username }}" style="pointer-events:none;"></a>
                    </div>
                    <div class="message-content">
                        <div class="message-info">
                            <a href="/users/{{ $msg->user->username }}" class="user-tag" style="text-decoration:none;display:inline-flex;align-items:center;gap:.15em;">{{ $msg->user->username }}<x-verified-badge :user="$msg->user" size=".8em" /></a>
                        </div>
                        <div class="bubble-container">
                            <div class="bubble-wrapper">
                                <div class="message-bubble {{ count($mediaItems ?? []) > 0 ? 'has-media' : '' }} {{ $msg->reactions->count() > 0 ? 'has-reactions' : '' }} {{ $msg->content === '---MESSAGE_DELETED---' ? 'deleted-msg' : '' }}">

                                @if($msg->content === '---MESSAGE_DELETED---')
                                    <div class="deleted-msg-wrapper">
                                        <i class="fas fa-ban"></i>
                                        <em class="deleted-text">{{ __('chat.message_deleted') ?? 'This message was deleted' }}</em>
                                    </div>
                                @else
                                    @if($msg->reply_data)
                                        <div class="replied-message-box" onclick="scrollToMessage({{ $msg->reply_data['id'] }})">
                                            <span class="replied-user">{{ $msg->reply_data['username'] }}</span>
                                            <span class="replied-content">{{ $msg->reply_data['content'] }}</span>
                                        </div>
                                    @endif

                                    @if($mediaItems && is_array($mediaItems))
                                        <div class="message-media-album" data-message-id="{{ $msg->id }}">
                                            <script type="application/json" class="media-data">@json($mediaItems)</script>
                                            @php
                                                $displayCount = min(count($mediaItems), 4);
                                                $remainingCount = count($mediaItems) - $displayCount;
                                            @endphp

                                            @if($displayCount === 1)
                                                <div class="media-grid-single">
                                                    @php $media = $mediaItems[0]; @endphp
                                                    @if($media['type'] === 'image')
                                                        <div class="media-item">
                                                            <img src="{{ asset('storage/' . $media['path']) }}" alt="Image" onclick="openMediaViewerFromAlbum(this, {{ $msg->id }}, 0)">
                                                        </div>
                                                    @elseif($media['type'] === 'video')
                                                        <div class="media-item video" onclick="openMediaViewerFromAlbum(this, {{ $msg->id }}, 0)">
                                                            <video src="{{ asset('storage/' . $media['path']) }}"></video>
                                                            <div class="media-overlay"><i class="fas fa-play"></i></div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif($displayCount === 2)
                                                <div class="media-grid-two">
                                                    @foreach(array_slice($mediaItems, 0, 2) as $index => $media)
                                                        @if($media['type'] === 'image')
                                                            <div class="media-item">
                                                                <img src="{{ asset('storage/' . $media['path']) }}" alt="Image" onclick="openMediaViewerFromAlbum(this, {{ $msg->id }}, {{ $index }})">
                                                            </div>
                                                        @elseif($media['type'] === 'video')
                                                            <div class="media-item video">
                                                                <video src="{{ asset('storage/' . $media['path']) }}"></video>
                                                                <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, {{ $msg->id }}, {{ $index }})"><i class="fas fa-play"></i></div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="media-grid-{{ $displayCount }}">
                                                    @foreach(array_slice($mediaItems, 0, $displayCount) as $index => $media)
                                                        @if($media['type'] === 'image')
                                                            <div class="media-item">
                                                                <img src="{{ asset('storage/' . $media['path']) }}" alt="Image" onclick="openMediaViewerFromAlbum(this, {{ $msg->id }}, {{ $index }})">
                                                                @if($index === 3 && $remainingCount > 0)
                                                                    <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, {{ $msg->id }}, 3)">
                                                                        <span class="overlay-text">+{{ $remainingCount }}</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @elseif($media['type'] === 'video')
                                                            <div class="media-item video">
                                                                <video src="{{ asset('storage/' . $media['path']) }}"></video>
                                                                <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, {{ $msg->id }}, {{ $index }})"><i class="fas fa-play"></i></div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($msg->display_content)
                                        <p>{{ $msg->display_content }}</p>
                                    @endif

                                    <div class="msg-item-actions">
                                        <button class="msg-action-trigger" onclick="toggleMsgMenu(event, {{ $msg->id }})">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                        <div class="msg-dropdown" id="msgDropdown-{{ $msg->id }}">
                                            <button class="menu-item" onclick="startReply({{ $msg->id }}, {{ json_encode($msg->user->username) }}, {{ json_encode(Str::limit($msg->display_content, 50)) }})">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                            @if($msg->user_id === auth()->id() || auth()->user()->is_admin)
                                                <button class="menu-item danger" onclick="deleteGlobalMessage({{ $msg->id }})">
                                                    <i class="far fa-trash-alt"></i> Delete
                                                </button>
                                            @else
                                                <button class="menu-item" onclick="reportGlobalMessage({{ $msg->id }})">
                                                    <i class="far fa-flag"></i> Report
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <span class="message-time">{{ $msg->created_at->format('h:i a') }}</span>
                                @endif
                                </div>
                                <div class="message-reactions-bar" id="reactions-{{ $msg->id }}">
                                    @if($msg->reactions->count() > 0)
                                        @php
                                            $grouped = $msg->reactions->groupBy('reaction');
                                            $hasMine = $msg->reactions->contains('user_id', auth()->id());
                                            $totalCount = $msg->reactions->count();
                                        @endphp
                                        <div class="reaction-group-pill {{ $hasMine ? 'has-mine' : '' }}" onclick="event.stopPropagation(); showMessageReactors({{ $msg->id }})">
                                            <div class="reaction-emoji-stack">
                                                @foreach($grouped as $emoji => $group)
                                                    <span class="stack-emoji">{{ $emoji }}</span>
                                                @endforeach
                                            </div>
                                            <span class="reaction-total-count">{{ $totalCount }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($msg->content !== '---MESSAGE_DELETED---')
                                <div class="msg-side-actions">
                                    <button class="side-action-btn react" onclick="toggleEmojiPicker(event, {{ $msg->id }})" title="React">
                                        <i class="far fa-smile"></i>
                                    </button>
                                    <button class="side-action-btn reply" onclick="startReply({{ $msg->id }}, {{ json_encode($msg->user->username) }}, {{ json_encode(Str::limit($msg->display_content, 50)) }})" title="Reply">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            <div id="scrollSentinel" style="height: 1px; width: 100%;"></div>
        </div>

        <button id="scrollToBottomBtn" class="scroll-bottom-btn" title="Scroll to Bottom">
            <i class="fas fa-chevron-down"></i>
            <span id="unreadScrollBadge" class="unread-scroll-badge" style="display: none;">0</span>
        </button>
    </div>

    <div class="chat-input-area">
        <div id="replyPreview" class="reply-preview-bar" style="display: none;">
            <div class="reply-info">
                <div class="reply-user" id="replyUser"></div>
                <div class="reply-text" id="replyText"></div>
            </div>
            <button class="cancel-reply" onclick="cancelReply()"><i class="fas fa-times"></i></button>
        </div>

        <div id="mediaPreview" class="media-preview" style="display: none;">
            <div class="main-preview-container">
                <div id="mainPreviewDisplay" class="main-preview-display">
                    <!-- Large focused media will be rendered here -->
                </div>
            </div>

            <div class="preview-carousel">
                <button type="button" class="carousel-arrow left" onclick="movePreview(-1)" title="Previous">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="preview-slides" id="previewSlides">
                    <!-- Slides will be added here -->
                </div>
                <button type="button" class="carousel-arrow right" onclick="movePreview(1)" title="Next">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="preview-indicators" id="previewIndicators">
                <!-- Dots will be added here -->
            </div>
            <div class="preview-info">
                <span id="previewCount">1 / 1</span>
                <button type="button" class="clear-all" onclick="clearMediaPreview()" title="Remove All">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>

        <form id="chatForm" onsubmit="handleSendMessage(event)" enctype="multipart/form-data">
            <div class="input-actions-left">
                <label for="mediaInput" class="attach-btn" title="Attach Media">
                    <i class="fas fa-paperclip"></i>
                </label>
                <input type="file" id="mediaInput" accept="image/*,video/*" multiple onchange="handleMediaSelect(event)" style="display: none;">
            </div>
            <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off">
            <button type="submit" id="sendBtn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

{{-- Enhanced Media Viewer --}}
<div id="mediaViewer" class="nexus-gallery" aria-hidden="true">
    <div class="gallery-overlay"></div>
    
    <div class="gallery-header">
        <div class="gallery-info"></div>
        <div class="gallery-actions">
            <button id="galleryClose" class="gallery-btn" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="gallery-main">
        <button id="galleryPrev" class="gallery-nav-btn left" title="Previous">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="gallery-content">
            <img id="galleryImage" src="" alt="Gallery Image" style="display: none;">
            <video id="galleryVideo" src="" controls style="display: none;"></video>
        </div>

        <button id="galleryNext" class="gallery-nav-btn right" title="Next">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <div class="gallery-footer">
        <span id="galleryCounter" class="gallery-counter">0 / 0</span>
    </div>
</div>

<div class="message-options-backdrop" id="msgOptsBackdrop" onclick="closeAllMenus()"></div>

<div class="emoji-picker-dropdown" id="emojiPicker">
    <div class="emoji-list">
        @foreach(\App\Models\Post::REACTION_EMOJIS as $emoji)
            <span onclick="selectEmoji('{{ $emoji }}')" title="{{ $emoji }}">
                {{ $emoji }}
            </span>
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
    <script>
        // Lock body scroll for chat page to prevent dual scrolling
        document.body.style.overflow = 'hidden';
        // Clean up on leave
        window.addEventListener('beforeunload', () => {
            document.body.style.overflow = 'auto';
        });

        // Set global variables for Socket Manager
        window.activeConversationId = 'global-chat';
        window.isGroupChat = true;
    </script>

    @vite(['resources/js/legacy/global-chat.js'])
@endpush
