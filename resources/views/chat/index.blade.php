@extends('layouts.app')

@section('title', __('chat.messages'))

@section('content')
<style>
/* Override layout constraints for full width chat */
.app-layout, .main-content {
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
}
.chat-page {
    max-width: 100% !important;
    margin-top: 0;
}
</style>
<div class="chat-page">
    <div class="chat-layout">
        @include('chat.partials.sidebar')

        {{-- Main Content - Welcome Screen --}}
        <main class="chat-welcome">
            <div class="welcome-content">
                <div class="welcome-icon">
                    <svg viewBox="0 0 24 24" width="120" height="120">
                        <path fill="currentColor" d="M12.001 2.002c-5.522 0-9.999 4.477-9.999 9.999 0 1.752.451 3.397 1.244 4.848L2.001 21.998l5.298-1.392c1.396.761 2.987 1.196 4.702 1.196 5.522 0 9.999-4.477 9.999-9.999s-4.477-9.999-9.999-9.999zm0 18.181c-1.496 0-2.896-.394-4.114-1.086l-.294-.168-3.049.802.815-2.972-.192-.305c-.762-1.212-1.166-2.613-1.166-4.053 0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8z"/>
                    </svg>
                </div>
                <h1>{{ __('chat.nexus_web') }}</h1>
                <p>{{ __('chat.welcome_message') }}</p>
                <p class="small-text">{{ __('chat.end_to_end_encrypted') }}</p>
            </div>
            <div class="welcome-footer">
                <button class="icon-btn large" onclick="showUserSearch()" title="{{ __('chat.start_chat') }}">
                    <i class="fas fa-message"></i>
                    <span>{{ __('chat.start_chat') }}</span>
                </button>
            </div>
        </main>
    </div>

    </div>
</div>

<style>
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
    --wa-yellow: var(--warning, #f7b928);
}

* { box-sizing: border-box; }

.chat-page {
    height: calc(100vh - 64px);
    background: var(--wa-bg);
    overflow: hidden;
}

.chat-layout {
    display: flex;
    height: 100%;
    width: 100%;
}

/* Sidebar */
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

.icon-btn.large {
    width: auto;
    height: 44px;
    padding: 0 20px;
    border-radius: 22px;
    background: var(--wa-accent);
    color: white;
    gap: 8px;
}

.icon-btn.large:hover {
    background: var(--wa-accent);
    opacity: 0.9;
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

/* Stories Section */
.stories-section {
    padding: 12px 0;
    border-bottom: 1px solid var(--wa-border);
}

.stories-header {
    padding: 0 16px;
    margin-bottom: 10px;
    font-size: 13px;
    color: var(--wa-text-muted);
    font-weight: 500;
}

.stories-scroll {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    padding: 0 12px;
    scrollbar-width: thin;
    scrollbar-color: var(--wa-border) transparent;
}

.stories-scroll::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.stories-scroll::-webkit-scrollbar-thumb {
    background: var(--wa-border);
    border-radius: 3px;
}

.story-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    min-width: 64px;
}

.story-ring {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    padding: 2px;
    border: 3px solid var(--wa-accent);
    margin-bottom: 6px;
}

.story-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    background: var(--wa-bg);
}

.story-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 600;
    font-size: 16px;
    border-radius: 50%;
}

.avatar-fallback.group {
    background: linear-gradient(135deg, var(--wa-accent), var(--wa-blue));
}

.story-label {
    font-size: 11px;
    color: var(--wa-text-muted);
    text-align: center;
    max-width: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.empty-stories {
    padding: 10px 16px;
    color: var(--wa-text-muted);
    font-size: 13px;
}

/* Conversations List */
.conversations-list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--wa-border) transparent;
}

.conversations-list::-webkit-scrollbar {
    width: 6px;
}

.conversations-list::-webkit-scrollbar-thumb {
    background: var(--wa-border);
    border-radius: 3px;
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

.conv-avatar img {
    border-radius: 50%;
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

.conv-title {
    font-size: 15px;
    font-weight: 500;
    color: var(--wa-text);
}



.conv-time {
    font-size: 12px;
    color: var(--wa-text-muted);
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

.conv-preview .preview-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    min-width: 0;
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

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
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
    margin-left: 8px;
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

.start-chat-btn {
    background: var(--wa-accent);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.start-chat-btn:hover {
    background: var(--wa-accent);
    opacity: 0.9;
    transform: translateY(-2px);
}

/* Welcome Screen */
.chat-welcome {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--wa-bg);
    border-bottom: 6px solid var(--wa-accent);
}

.welcome-content {
    text-align: center;
    padding: 40px;
    max-width: 500px;
}

.welcome-icon {
    color: var(--wa-text-muted);
    margin-bottom: 24px;
    opacity: 0.5;
}

.welcome-content h1 {
    margin: 0 0 16px;
    font-size: 32px;
    font-weight: 300;
    color: var(--wa-text);
}

.welcome-content p {
    margin: 0 0 8px;
    font-size: 14px;
    color: var(--wa-text-muted);
    line-height: 1.5;
}

.small-text {
    font-size: 12px;
    opacity: 0.6;
}

.welcome-footer {
    margin-top: 40px;
}

/* Responsive */

/* Responsive */
@media (max-width: 900px) {
    .chat-sidebar {
        width: 100%;
        min-width: 100%;
    }

    .chat-welcome {
        display: none;
    }
}

@media (max-width: 600px) {
    .chat-page {
        height: calc(100vh - 56px);
    }

    .chat-sidebar {
        width: 100%;
    }

    .sidebar-header {
        padding: 10px 12px;
    }

    .header-actions {
        gap: 4px;
    }

    .icon-btn {
        width: 34px;
        height: 34px;
        font-size: 14px;
    }

    .story-ring {
        width: 50px;
        height: 50px;
    }

    .conversation-item {
        padding: 10px 12px;
    }

    .conv-avatar img,
    .conv-avatar .avatar-fallback {
        width: 42px;
        height: 42px;
    }

    .conv-title {
        font-size: 14px;
    }

    .conv-preview {
        font-size: 12px;
    }

    .conv-time {
        font-size: 11px;
    }

    .unread-pill {
        font-size: 10px;
        padding: 1px 6px;
        min-width: 18px;
    }
}

@media (max-width: 400px) {
    .conv-title {
        font-size: 13px;
    }

    .conv-preview {
        font-size: 11px;
    }

    .conv-time {
        font-size: 10px;
    }
}
</style>

<script>
    window.activeConversationId = null;
</script>

@push('scripts')
{{-- E2E Encryption Initialization --}}
@vite(['resources/js/e2e/crypto-core.js', 'resources/js/e2e/media-crypto.js', 'resources/js/e2e/e2e-manager.js'])
<script type="module">
async function getManager() {
    if (window.getE2EManager) {
        return await window.getE2EManager();
    }
    return new Promise(resolve => {
        const interval = setInterval(() => {
            if (window.getE2EManager) {
                clearInterval(interval);
                window.getE2EManager().then(resolve);
            }
        }, 50);
        setTimeout(() => {
            clearInterval(interval);
            resolve(window.e2eManager || null);
        }, 3000);
    });
}

async function decryptSidebarPreviews() {
    const previews = [...document.querySelectorAll('.preview-text[data-encrypted-preview]')];
    const e2e = await getManager();
    if (!e2e) {
        // Fallback reveal
        previews.forEach(el => el.style.opacity = '1');
        return;
    }

    await Promise.all(previews.map(async (el) => {
        let rawContent = el.dataset.encryptedPreview;
        const senderId = parseInt(el.dataset.latestMessageSenderId || '0');
        const prefix = el.dataset.previewPrefix || '';
        if (!rawContent) return;

        if (rawContent.includes('&quot;')) {
            const txt = document.createElement('textarea');
            txt.innerHTML = rawContent;
            rawContent = txt.value;
        }

        try {
            const parsed = JSON.parse(rawContent);
            let decryptTarget = parsed;
            if (parsed.__nexus_reply__ && typeof parsed.content === 'string') {
                try {
                    const inner = JSON.parse(parsed.content);
                    if (inner.__nexus_encrypted__) decryptTarget = inner;
                } catch (e) {}
            }

            if (!decryptTarget.__nexus_encrypted__) {
                el.style.opacity = '1';
                return;
            }

            const conversationItem = el.closest('.conversation-item');
            const isGroup = conversationItem?.getAttribute('data-is-group') === 'true';
            const conversationId = conversationItem?.getAttribute('data-conversation-id') || '';

            let decrypted;
            if (isGroup) {
                decryptTarget.conversation_id = conversationId;
                decrypted = await e2e.decryptGroupMessage(decryptTarget);
            } else {
                const otherUserId = parseInt(conversationItem?.getAttribute('data-user-id') || '0');
                decrypted = await e2e.decryptMessage(decryptTarget, senderId, otherUserId);
            }

            const plaintext = decrypted.text || '';
            const displayText = plaintext.length > 30 ? plaintext.substring(0, 27) + '...' : plaintext;
            el.textContent = prefix + displayText;
            el.style.opacity = '1';
        } catch (e) {
            console.warn('Failed to decrypt sidebar preview:', e);
            el.textContent = prefix + '🔒 ' + ('{{ __('chat.e2e_encrypted') }}' || 'Encrypted message');
            el.style.opacity = '1';
        }
    }));
}

async function initE2E() {
    try {
        const e2e = await getManager();
        if (!e2e) {
            console.warn('E2E encryption not supported or not loaded in this browser');
            return;
        }

        const hasKeys = await e2e.db.hasKeys();
        if (!hasKeys) {
            await e2e.ensureKeys();
            await e2e.registerKeys();
        } else {
            // Key maintenance runs in background — don't block preview decrypt
            (async () => {
                try {
                    const myUserId = {{ auth()->id() }};
                    const checkResp = await fetch(`/api/e2e/keys/${myUserId}?t=${Date.now()}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (checkResp.status === 404) {
                        console.log('Server public keys missing. Re-registering existing keys.');
                        await e2e.registerKeys();
                    }
                } catch (e) {
                    console.error('Failed to verify/re-register keys on server:', e);
                }
            })();
        }

        await decryptSidebarPreviews();
    } catch (err) {
        console.error('E2E initialization error:', err);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initE2E);
} else {
    initE2E();
}
document.addEventListener('turbo:load', initE2E);
</script>
@endpush
@endsection
