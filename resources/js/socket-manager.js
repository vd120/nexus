import { io } from 'socket.io-client';

class SocketManager {
    constructor() {
        this.config = window.SOCKET_CONFIG || {};
        this.socket = null;
        this.status = 'DISCONNECTED';
        this.lastStatus = 'DISCONNECTED';
        this.processedMessages = new Set();
        this.notifiedPostSlugs = new Set();
        
        if (this.config.token && this.config.url) {
            this.connect();
            this.clearNotificationsForActiveConversation();
        }
    }

    /**
     * Mark all notifications for the current conversation as read on page load
     */
    async clearNotificationsForActiveConversation() {
        const convId = window.activeConversationId;
        if (!convId || convId === 'global-chat') return;

        try {
            await fetch(`/notifications/mark-conversation-read/${convId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            // Proactively update the local notification count if the global counter exists
            const counter = document.getElementById('notif-badge');
            if (window.loadNotifications) {
                window.loadNotifications(); // Refresh the list and count
            }
        } catch (e) {
            console.error('[SocketManager] Failed to clear notifications for conversation:', e);
        }
    }

    connect() {
        let socketUrl = this.config.url;
        
        // If URL contains localhost but we are accessing via IP, fix it
        if (socketUrl && socketUrl.includes('localhost') && window.location.hostname !== 'localhost') {
            socketUrl = socketUrl.replace('localhost', window.location.hostname);
        }

        this.socket = io(socketUrl, {
            auth: { token: this.config.token }
        });

        this.socket.on('connect', () => {
            this.status = 'CONNECTED';
            this.updateConnectionStatus('online');
            this.updateOnlineStatus(this.config.userId, true);
            this.confirmAllMessagesDelivered();
            
            // Start listeners on first connection
            if (!this.initialized) {
                this.initialize();
                this.initialized = true;
            }

            // Join admin room if applicable
            if (this.config.isAdmin) {
                this.socket.emit('admin:join');
            }
        });
    }

    initialize() {
        if (!this.socket) return;

        this.socket.on('disconnect', () => {
            this.status = 'DISCONNECTED';
            this.updateConnectionStatus('pending');
        });

        this.socket.on('connect_error', () => {
            this.updateConnectionStatus('pending');
        });

        // Online status listeners
        this.socket.on('user:online', (data) => this.updateOnlineStatus(data.userId, true, data.last_active));
        this.socket.on('user:offline', (data) => this.updateOnlineStatus(data.userId, false, data.last_active));
        this.socket.on('users:online', (data) => {
            const onlineIds = new Set((data.userIds || []).map(id => String(id)));
            
            // Sync all online indicators on the page
            document.querySelectorAll('.online-indicator, #chat-user-status').forEach(el => {
                const userId = el.getAttribute('data-user-id');
                if (userId) {
                    const isOnline = onlineIds.has(String(userId)) || String(userId) === String(this.config.userId);
                    this.updateOnlineStatus(userId, isOnline);
                }
            });
        });

        // Message listener
        this.socket.on('chat:message', (msg) => {
            // Duplicate guard: don't process the same message twice
            if (this.processedMessages.has(msg.id)) return;
            this.processedMessages.add(msg.id);
            
            // Limit the size of the set to prevent memory leaks
            if (this.processedMessages.size > 100) {
                const firstItem = this.processedMessages.values().next().value;
                this.processedMessages.delete(firstItem);
            }

            // Process message in sidebar (always)
            if (window.updateExistingConversationItem) window.updateExistingConversationItem(msg);
            
            // Should we add this to the active chat window?
            // We bypass the "own message" check for system messages and group invites 
            // because they are not added to the UI manually by the sender's frontend.
            const isSystem = msg.type === 'system' || msg.type === 'system_cleared' || msg.type === 'group_invite';
            const isOwn = msg.sender_id == this.config.userId;
            const isCurrentConv = String(window.activeConversationId) === String(msg.conversation_id);

            if (!isOwn || isSystem) {
                // Confirm delivery to server globally (on any page)
                // Skip for global chat as it doesn't use delivery receipts
                if (!isOwn && String(msg.conversation_id) !== 'global-chat') {
                    this.confirmMessageDelivery(msg.id);
                }

                if (isCurrentConv && window.addMessage) {
                    window.addMessage(msg);
                    if (window.markMessagesAsRead) window.markMessagesAsRead();
                    
                    // Force hide main typing indicator when message arrives
                    const mainIndicator = document.getElementById('typingIndicator');
                    if (mainIndicator) mainIndicator.style.display = 'none';
                }
            }
        });

        // Typing listener
        this.socket.on('chat:typing', (data) => {
            const convId = String(data.conversationId || data.conversation_id);
            if (!convId) return;

            // Track typing users per conversation
            if (!this.typingStatus) this.typingStatus = new Map();
            if (!this.typingStatus.has(convId)) this.typingStatus.set(convId, new Set());
            
            const typingSet = this.typingStatus.get(convId);
            const username = data.username || data.user_name || (window.chatTranslations?.user || 'User');
            
            if (data.isTyping) {
                typingSet.add(username);
            } else {
                typingSet.delete(username);
            }

            // Determine if this is a group chat to decide on the text format
            const sidebarItem = document.querySelector(`.conversation-item[data-conversation-id="${convId}"]`);
            const isGroup = sidebarItem ? (sidebarItem.getAttribute('data-is-group') === 'true') : (String(window.activeConversationId) === convId && window.isGroupChat);

            // Generate the typing text
            const usernames = Array.from(typingSet);
            let typingText = '';
            
            if (isGroup) {
                if (usernames.length === 1) {
                    typingText = (window.chatTranslations?.is_typing || ':user is typing...').replace(':user', usernames[0]);
                } else if (usernames.length === 2) {
                    typingText = usernames[0] + ' ' + (window.chatTranslations?.and || 'and') + ' ' + usernames[1] + ' ' + (window.chatTranslations?.are_typing || 'are typing...');
                } else if (usernames.length > 2) {
                    typingText = window.chatTranslations?.users_typing || 'multiple people are typing...';
                }
            } else {
                // For 1-1 chats, just show "typing..."
                typingText = window.chatTranslations?.typing || 'typing...';
            }

            const hasTypers = usernames.length > 0;

            // Update Main chat indicator
            const mainIndicator = document.getElementById('typingIndicator');
            if (mainIndicator && String(window.activeConversationId) === convId) {
                const textEl = mainIndicator.querySelector('.typing-text');
                if (textEl) textEl.textContent = typingText;
                
                const chatMessages = document.getElementById('chatMessages');
                const wasAtBottom = chatMessages && (chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight < 100);
                
                mainIndicator.style.display = hasTypers ? 'flex' : 'none';
                
                // If we were at bottom, stay at bottom after showing indicator
                if (hasTypers && wasAtBottom && chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            }

            // Update Sidebar indicator
            if (sidebarItem) {
                let sidebarIndicator = sidebarItem.querySelector('.typing-indicator-sidebar');
                const previewWrapper = sidebarItem.querySelector('.preview-content-wrapper');
                
                // Ensure indicator exists
                if (!sidebarIndicator) {
                    const previewContainer = sidebarItem.querySelector('.conv-preview');
                    if (previewContainer) {
                        sidebarIndicator = document.createElement('span');
                        sidebarIndicator.className = 'typing-indicator-sidebar';
                        previewContainer.appendChild(sidebarIndicator);
                    }
                }

                if (hasTypers) {
                    if (sidebarIndicator) sidebarIndicator.textContent = typingText;
                    sidebarItem.classList.add('is-typing');
                    if (sidebarIndicator) sidebarIndicator.style.setProperty('display', 'block', 'important');
                    if (previewWrapper) previewWrapper.style.setProperty('display', 'none', 'important');
                } else {
                    sidebarItem.classList.remove('is-typing');
                    if (sidebarIndicator) sidebarIndicator.style.setProperty('display', 'none', 'important');
                    if (previewWrapper) previewWrapper.style.setProperty('display', 'block', 'important');
                }
            }
        });
        
        // Notification listener
        this.socket.on('notification:new', (notif) => {
            // Check if this is a chat-related notification (message or reaction)
            const isChatRelated = notif.type === 'message' || notif.type === 'chat_reaction';
            const notifConvId = notif.data?.conversation_id || notif.data?.message?.conversation_id;
            
            // Check if it belongs to the currently active chat
            const isActiveChat = notifConvId && window.activeConversationId && 
                               String(window.activeConversationId) === String(notifConvId);

            // Silence if DND is on OR if it's the active chat
            const isDND = localStorage.getItem('nexus_dnd_enabled') === 'true';
            
            if (!isActiveChat && !isDND) {
                // Audible + tactile arrival (respects user's nexus_sound preference)
                if (window.NexusSoul) window.NexusSoul.feedback.notification();
                // Bell shake — purely visual, communicates "something happened"
                const bell = document.getElementById('notif-bell-icon');
                if (bell) {
                    bell.classList.remove('nx-bell-ring'); // restart animation
                    void bell.offsetWidth;
                    bell.classList.add('nx-bell-ring');
                }
                // TRACKING: If this is a post-related notification, track the slug to prevent double-toast from post:new
                if (notif.data && notif.data.post_slug) {
                    this.notifiedPostSlugs.add(notif.data.post_slug);
                    // Clear after 10 seconds to keep memory lean
                    setTimeout(() => this.notifiedPostSlugs.delete(notif.data.post_slug), 10000);
                }

                if (window.showToast) {
                    const avatarUrl = notif.data?.from_user?.avatar_url || notif.data?.reactor_avatar || notif.data?.sender_avatar;
                    const message = notif.message || '';
                    
                    // Pass the notification ID in extraData so showToast can mark it as read on click
                    window.showToast(message, notif.type, avatarUrl, notif.link, 4000, { ...notif.data, notification_id: notif.id });
                }
                if (window.loadNotifications) window.loadNotifications();
            }
            
            // Even if suppressed, we might want to update the sidebar or notification badge
            if (isActiveChat && window.loadNotifications) {
                window.loadNotifications();
            }

            // Sync global message badges on all pages
            if (isChatRelated && window.updateMobileBadge) {
                window.updateMobileBadge();
            }
        });

        this.socket.on('notification:count', (data) => {
            if (window.updateNotificationBadge) window.updateNotificationBadge(data.unread_count);
        });

        // Admin: New Report Listener
        this.socket.on('admin:report:new', (data) => {
            if (this.config.isAdmin) {
                if (window.showToast) {
                    const reasonStr = window.chatTranslations?.report_reason_prefix || 'New report for: ';
                    window.showToast(reasonStr + (data.reason || 'content'), 'warning', null, `/admin/reports/${data.report_slug}`, 5000, data);
                }
                
                // If on admin dashboard, trigger a refresh of stats
                if (window.refreshAdminDashboardStats) {
                    window.refreshAdminDashboardStats(data);
                }
            }
        });

        // Read receipts listener
        this.socket.on('chat:read', (data) => {
            if (String(window.activeConversationId) === String(data.conversation_id) || !data.conversation_id) {
                if (window.updateReadReceiptsUI) {
                    window.updateReadReceiptsUI(data.read_message_ids, data.reader_id, data);
                }
                // Refresh message info if open
                if (window.refreshMessageInfoIfOpen) {
                    if (data.read_message_ids && data.read_message_ids.length > 0) {
                        data.read_message_ids.forEach(id => window.refreshMessageInfoIfOpen(id, data.conversation_id));
                    } else {
                        window.refreshMessageInfoIfOpen(null, data.conversation_id);
                    }
                }
            }
            // Update sidebar globally
            if (window.updateSidebarReadReceipts) {
                window.updateSidebarReadReceipts(data.conversation_id, true, false, data.reader_id, data);
            }
            
            // Sync global badges
            if (window.updateMobileBadge) window.updateMobileBadge();
        });

        // Delivery receipts listener
        this.socket.on('chat:delivered', (data) => {
            if (String(window.activeConversationId) === String(data.conversation_id)) {
                if (window.updateDeliveredReceiptsUI) {
                    window.updateDeliveredReceiptsUI(data.message_id, data);
                }
                // Refresh message info if open
                if (window.refreshMessageInfoIfOpen) {
                    window.refreshMessageInfoIfOpen(data.message_id, data.conversation_id);
                }
            }
            // Update sidebar globally
            if (window.updateSidebarReadReceipts) {
                window.updateSidebarReadReceipts(data.conversation_id, false, true, null, data);
            }
        });

        // Message deletion listener
        this.socket.on('chat:message:deleted', (data) => {
            if (window.handleDeleteMessage) {
                window.handleDeleteMessage(data.message_id, data.delete_type, data.deleted_for);
            }
        });

        // Conversation deletion listener
        this.socket.on('chat:conversation:deleted', (data) => {
            const convId = data.conversation_id;
            const sidebar = document.getElementById('sidebarConvList');
            
            // 1. Remove from sidebar
            const sidebarItem = document.querySelector(`.conversation-item[data-conversation-id="${convId}"]`);
            if (sidebarItem) {
                sidebarItem.remove();
            }

            // Show empty state if no conversations left
            if (sidebar && sidebar.querySelectorAll('.conversation-item').length === 0 && !sidebar.querySelector('.empty-state')) {
                const noMsgs = window.chatTranslations?.no_messages_yet || 'No messages yet';
                const startNew = window.chatTranslations?.start_new_conversation || 'Start a new conversation';
                
                sidebar.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-comments"></i></div>
                        <h3>${noMsgs}</h3>
                        <p>${startNew}</p>
                    </div>
                `;
            }

            // 2. If active chat, redirect with notification
            if (String(window.activeConversationId) === String(convId)) {
                if (window.showToast) {
                    window.showToast(window.chatTranslations?.conversation_deleted || 'This conversation has been deleted.', 'warning');
                }
                
                // Redirect after a small delay
                setTimeout(() => {
                    window.location.href = '/chat';
                }, 1500);
            }

            // 3. Clear typing indicators
            const mainIndicator = document.getElementById('typingIndicator');
            if (mainIndicator && String(window.activeConversationId) === String(convId)) {
                mainIndicator.style.display = 'none';
            }
        });


        // Real-time post reactions
        this.socket.on('post:reacted', (data) => {
            const postCard = document.querySelector(`.post-card[data-post-id="${data.post_id}"]`);
            if (postCard && window.updatePostReactionSummary) {
                window.updatePostReactionSummary(postCard, data.reaction_summaries, null, data.reactors);
            }
        });

        // Real-time post likes
        this.socket.on('post:liked', (data) => {
            const postCard = document.querySelector(`.post-card[data-post-id="${data.post_id}"]`);
            if (postCard) {
                const likeCountEl = postCard.querySelector('.likes-count');
                if (likeCountEl) {
                    likeCountEl.textContent = data.count;
                }
            }
        });

        // Real-time new posts
        this.socket.on('post:new', (data) => {
            // Skip if this is the current user's own post (they get owner HTML via AJAX response)
            if (data.user_id && window.currentUserId && Number(data.user_id) === Number(window.currentUserId)) {
                return;
            }

            // Find appropriate container (home feed or group feed)
            const container = document.getElementById('posts-container') || document.getElementById('posts-feed');

            if (container && data.html) {
                // Check if post already exists
                if (document.getElementById(`post-${data.id}`)) {
                    return;
                }

                // If on a specific group feed, only show if it matches this group
                const targetGroupId = container.dataset.groupId;
                const incomingGroupId = data.social_group_id;
                
                if (targetGroupId && String(targetGroupId) !== String(incomingGroupId)) {
                    // This is a group-specific feed, and the incoming post is for a different group (or global)
                    return;
                }

                // Prepend new post
                container.insertAdjacentHTML('afterbegin', data.html);

                // Remove empty state if exists
                const emptyState = container.querySelector('.empty-state');
                if (emptyState) {
                    emptyState.remove();
                }
                
                // Re-initialize post components if needed (e.g. tooltips, sliders)
                if (window.initializePostComponents) {
                    window.initializePostComponents(document.getElementById(`post-${data.id}`));
                }
            }
        });

        // Real-time community member counts
        this.socket.on('community:member_count', (data) => {
            const slug = data.slug;
            const count = data.members_count;
            if (!slug || count === undefined) return;

            // 1. Update header on community page
            const headerEl = document.querySelector(`[data-community-members-count="${slug}"]`);
            if (headerEl) {
                const strongEl = headerEl.querySelector('strong');
                if (strongEl) {
                    strongEl.textContent = Number(count).toLocaleString();
                }
            }

            // 2. Update Your Communities mini card list
            const miniEl = document.querySelector(`[data-mini-members-count="${slug}"]`);
            if (miniEl) {
                const label = window.layoutTranslations?.members || 'members';
                miniEl.textContent = `${Number(count).toLocaleString()} ${label}`;
            }

            // 3. Update Discovery Grid card
            const discoveryEl = document.querySelector(`[data-discovery-members-count="${slug}"]`);
            if (discoveryEl) {
                discoveryEl.innerHTML = `<i class="fas fa-users"></i> ${Number(count).toLocaleString()}`;
            }
        });

        // Real-time new stories
        this.socket.on('story:new', (data) => {
            if (window.addStoryToSection) {
                window.addStoryToSection(data);
            }
        });

        // Real-time deleted stories
        this.socket.on('story:deleted', (data) => {
            if (window.removeStoryFromSection) {
                window.removeStoryFromSection(data.username);
            }
        });

        // Real-time comments
        this.socket.on('post:commented', (data) => {
            if (window.handlePostCommented) {
                window.handlePostCommented(data);
            }
        });

        // Real-time comment likes
        this.socket.on('comment:liked', (data) => {
            if (window.handleCommentLiked) {
                window.handleCommentLiked(data);
            }
        });

        // Real-time deletions
        this.socket.on('post:deleted', (data) => {
            if (window.handlePostDeleted) {
                window.handlePostDeleted(data);
            }
        });

        this.socket.on('comment:deleted', (data) => {
            if (window.handleCommentDeleted) {
                window.handleCommentDeleted(data);
            }
        });

        this.socket.on('poll:vote', (data) => {
            if (window.updatePollUI) {
                document.querySelectorAll(`.post-poll[data-post-slug="${data.post_slug}"]`).forEach(container => {
                    window.updatePollUI(container, null, data.results, data.total_votes);
                });
            }
        });

        // Real-time chat clearing
        this.socket.on('chat:cleared', (data) => {
            // 1. Update sidebar/chats list globally
            const item = document.querySelector(`.conversation-item[data-conversation-id="${data.conversation_id}"]`);
            if (item) {
                const previewEl = item.querySelector('.preview-text');
                if (previewEl) {
                    const userText = (data.user_id == this.config.userId) ? (window.chatTranslations?.you || 'You') : (data.username || 'User');
                    const clearMsg = (window.chatTranslations?.cleared_the_chat || 'Cleared the chat').replace(':user', userText);
                    previewEl.textContent = clearMsg;
                    
                    // Remove unread state if it was active
                    item.classList.remove('unread');
                    const pill = item.querySelector('.unread-pill');
                    if (pill) pill.remove();
                    previewEl.classList.remove('unread-text');
                }
                
                // Remove checkmarks if any
                const checkIcon = item.querySelector('.preview-content-wrapper i');
                if (checkIcon) checkIcon.remove();

                // Move to top of sidebar
                const sidebar = document.getElementById('sidebarConvList');
                if (sidebar) {
                    sidebar.prepend(item);
                }
            }

            // 2. If it's the active conversation, clear the message window
            if (String(window.activeConversationId) === String(data.conversation_id)) {
                if (window.handleChatCleared) {
                    window.handleChatCleared(data);
                } else {
                    const container = document.getElementById('chatMessages');
                    if (container) {
                        container.innerHTML = '';
                        if (window.addMessage) {
                            window.addMessage({
                                id: data.message_id,
                                conversation_id: data.conversation_id,
                                sender_id: data.user_id,
                                content: 'system_cleared',
                                type: 'system',
                                created_at: new Date().toISOString(),
                                username: data.username
                            });
                        }
                    }
                }
            }
        });

        // Real-time message reactions
        this.socket.on('chat:reaction', (data) => {
            if (String(window.activeConversationId) === String(data.conversation_id)) {
                if (window.updateReactionsFromSocket) {
                    window.updateReactionsFromSocket(data);
                }
            }
        });

        // Security Challenge listener (Concurrent Login Approval)
        this.socket.on('security:challenge', (data) => {
            // If this challenge was triggered BY this session, don't show it here
            if (data.except_session && data.except_session === this.config.sessionId) {
                console.log('[SocketManager] Ignoring security challenge from current session');
                return;
            }

            window.currentSecurityChallenge = data.uuid;
            
            const modal = document.getElementById('security-challenge-modal');
            const deviceSpan = document.getElementById('security-device-name');
            const ipSpan = document.getElementById('security-ip');
            
            if (modal && deviceSpan && ipSpan) {
                deviceSpan.textContent = data.device || 'Unknown Device';
                ipSpan.textContent = data.ip || 'Unknown IP';
                
                modal.classList.add('show');
                
                // Play a subtle alert sound if possible
                try {
                    const audio = new Audio('/sounds/security-alert.mp3');
                    audio.play().catch(() => {});
                } catch(e) {}
            }
        });
        
        // Security Approval listener (to close the modal on other devices)
        this.socket.on('security:approved', (data) => {
            const modal = document.getElementById('security-challenge-modal');
            if (modal && window.currentSecurityChallenge === data.uuid) {
                modal.classList.remove('show');
                window.currentSecurityChallenge = null;
                if (window.showToast) {
                    window.showToast('Login was approved from another device.', 'success');
                }
            }
        });

        // Security Denial listener
        this.socket.on('security:denied', (data) => {
            const modal = document.getElementById('security-challenge-modal');
            if (modal && window.currentSecurityChallenge === data.uuid) {
                modal.classList.remove('show');
                window.currentSecurityChallenge = null;
            }
        });

        // Global functions for the security modal
        window.approveSecurityChallenge = () => {
            const uuid = window.currentSecurityChallenge;
            if (!uuid) return;
            
            // Clear immediately to prevent the socket listener (security:approved) 
            // from showing a duplicate "approved from another device" toast.
            window.currentSecurityChallenge = null;
            
            const btn = document.getElementById('approve-security-btn');
            const modal = document.getElementById('security-challenge-modal');

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authorizing...';
            }
            
            fetch(`/login/challenge/${uuid}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (modal) modal.classList.remove('show');
                    if (window.showToast) {
                        window.showToast('Access Granted to new device!', 'success');
                    } else {
                        alert('Access Granted!');
                    }
                } else {
                    // Restore UUID if failed so user can try again? 
                    // Actually, better to just let them refresh or wait for new challenge.
                    alert(data.message || 'Approval failed.');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check-circle"></i> Grant Access';
                    }
                }
            })
            .catch(() => {
                alert('Connection error. Please try again.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle"></i> Grant Access';
                }
            });
        };

        window.denySecurityChallenge = () => {
            const uuid = window.currentSecurityChallenge;
            if (!uuid) return;
            
            window.currentSecurityChallenge = null;
            
            const modal = document.getElementById('security-challenge-modal');
            if (modal) modal.classList.remove('show');
            
            fetch(`/login/challenge/${uuid}/deny`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
        };

        // Sync conversation state (unread counts, etc.)
        this.socket.on('chat:conversation:updated', (data) => {
            const item = document.querySelector(`.conversation-item[data-conversation-id="${data.conversation_id}"]`);
            if (item) {
                let pill = item.querySelector('.unread-pill');
                const previewEl = item.querySelector('.preview-text');
                const timeEl = item.querySelector('.conv-time');
                
                // Update latest message ID for read/delivery tracking
                if (data.last_message_id) {
                    item.setAttribute('data-latest-message-id', data.last_message_id);
                }

                // Update preview text if provided
                if (previewEl && data.last_message) {
                    const previewText = window.sanitizeMessage(data.last_message);
                    previewEl.textContent = previewText;
                }

                // Update checkmarks if provided
                if (data.show_checkmarks !== undefined) {
                    const wrapper = item.querySelector('.preview-content-wrapper');
                    if (wrapper) {
                        let checkIcon = wrapper.querySelector('i.fa-check, i.fa-check-double');
                        if (data.show_checkmarks) {
                            if (!checkIcon) {
                                checkIcon = document.createElement('i');
                                wrapper.prepend(checkIcon);
                            }
                            checkIcon.className = 'fas ' + (data.checkmark_class || 'fa-check sent');
                        } else {
                            if (checkIcon) checkIcon.remove();
                        }
                    }
                }

                // Update time if provided
                if (timeEl && data.last_message_time) {
                    const date = new Date(data.last_message_time);
                    let hours = date.getHours();
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    const ampm = hours >= 12 ? 'pm' : 'am';
                    hours = hours % 12;
                    hours = hours ? hours : 12;
                    timeEl.textContent = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
                }
                
                const isActive = String(window.activeConversationId) === String(data.conversation_id);
                const unreadCount = isActive ? 0 : (data.unread_count || 0);



                if (unreadCount > 0) {
                    item.classList.add('unread');
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
                        pill.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    }
                    if (previewEl) previewEl.classList.add('unread-text');
                } else {
                    item.classList.remove('unread');
                    if (pill) pill.remove();
                    if (previewEl) previewEl.classList.remove('unread-text');
                }

                // Move to top if it's a new message update (unless no_reorder is set)
                const sidebar = document.getElementById('sidebarConvList');
                if (sidebar && data.last_message && !data.no_reorder) {
                    sidebar.prepend(item);
                }
            } else {
                // Handle new conversation item creation if it doesn't exist yet
                if (window.addNewConversationItem) {
                    window.addNewConversationItem(data);
                }
            }

            // Sync global badges on all pages
            if (window.updateMobileBadge) window.updateMobileBadge();
        });
    }

    updateConnectionStatus(status) {
        const dot = document.getElementById('connection-status-dot');
        if (dot) {
            dot.className = `status-dot ${status}`;
            dot.title = status === 'online' ? 'Nexus Real-time Engine: Connected' : 'Nexus Real-time Engine: Reconnecting...';
        }

        this.lastStatus = status;
    }

    updateOnlineStatus(userId, isOnline, lastActive = null) {
        if (!userId) return;
        const id = String(userId);
        
        // Update all dots with this user ID
        document.querySelectorAll(`.online-indicator[data-user-id="${id}"]`).forEach(el => {
            if (isOnline) {
                el.classList.add('online');
                el.classList.remove('offline');
            } else {
                el.classList.remove('online');
                el.classList.add('offline');
            }
        });

        // Update chat header if it's the same user
        const headerStatus = document.getElementById('chat-user-status');
        if (headerStatus && headerStatus.getAttribute('data-user-id') == id) {
            headerStatus.className = `status ${isOnline ? 'online' : 'offline'}`;
            const text = headerStatus.querySelector('.status-text');
            if (text) {
                if (isOnline) {
                    text.textContent = window.chatTranslations?.online || 'online';
                } else if (lastActive) {
                    text.textContent = this.formatLastSeen(lastActive);
                } else {
                    // Transitioning to offline without a specific timestamp
                    // Only overwrite if it was previously "online"
                    const currentText = text.textContent.toLowerCase();
                    const onlineText = (window.chatTranslations?.online || 'online').toLowerCase();
                    if (currentText.includes(onlineText)) {
                        text.textContent = window.chatTranslations?.offline || 'offline';
                    }
                }
            }
        }
    }

    formatLastSeen(dateStr) {
        if (!dateStr) return window.chatTranslations?.offline || 'offline';
        
        try {
            const date = new Date(dateStr);
            const now = new Date();
            const isToday = date.toDateString() === now.toDateString();
            
            const yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);
            const isYesterday = date.toDateString() === yesterday.toDateString();
            
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'pm' : 'am';
            hours = hours % 12;
            hours = hours ? hours : 12;
            const timeStr = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
            
            const trans = window.chatTranslations || {};
            const lastSeenLabel = trans.last_seen || 'Last seen';
            const atLabel = trans.at || 'at';
            
            if (isToday) {
                return `${lastSeenLabel} ${trans.today || 'Today'} ${atLabel} ${timeStr}`;
            } else if (isYesterday) {
                return `${lastSeenLabel} ${trans.yesterday || 'Yesterday'} ${atLabel} ${timeStr}`;
            } else {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${lastSeenLabel} ${day}/${month}/${year} ${timeStr}`;
            }
        } catch (e) {
            return window.chatTranslations?.offline || 'offline';
        }
    }

    on(event, callback) {
        if (this.socket) {
            this.socket.on(event, callback);
        } else {
            // If socket not initialized yet, wait and retry
            setTimeout(() => this.on(event, callback), 100);
        }
    }

    emit(event, data) {
        if (this.socket && this.socket.connected) {
            this.socket.emit(event, data);
        }
    }

    joinConversation(id) { this.emit('conversation:join', { conversationId: id }); }
    leaveConversation(id) { this.emit('conversation:leave', { conversationId: id }); }
    sendTyping(id, isTyping) { 
        this.emit('chat:typing', { 
            conversationId: id, 
            isTyping,
            username: this.config.username 
        }); 
    }

    confirmMessageDelivery(messageId) {
        if (!messageId) return;
        fetch('/chat/confirm-delivery', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message_id: messageId })
        }).catch(err => {});
    }

    confirmAllMessagesDelivered() {
        fetch('/chat/mark-delivered-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).catch(err => console.error('Bulk delivery confirmation failed:', err));
    }
}

window.NexusSocket = new SocketManager();
export default window.NexusSocket;
