(function() {
    const chatContainer = document.getElementById('chatContainer');
    const messagesWrapper = document.getElementById('messagesWrapper');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const emojiPicker = document.getElementById('emojiPicker');
    let currentEmojiMessageId = null;

    // 1. Scroll to bottom on load
    const scrollToBottomBtn = document.getElementById('scrollToBottomBtn');
    const unreadScrollBadge = document.getElementById('unreadScrollBadge');
    let unreadCountWhileScrolled = 0;

    if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
        
        // Scroll Sensing using IntersectionObserver (The most reliable way for mobile)
        const scrollSentinel = document.getElementById('scrollSentinel');
        if (scrollSentinel && scrollToBottomBtn) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Bottom is visible
                        scrollToBottomBtn.classList.remove('show');
                        unreadCountWhileScrolled = 0;
                        unreadScrollBadge.style.display = 'none';
                        unreadScrollBadge.textContent = '0';
                    } else {
                        // Bottom is hidden (user scrolled up)
                        scrollToBottomBtn.classList.add('show');
                    }
                });
            }, {
                root: chatContainer,
                threshold: 0.1
            });

            observer.observe(scrollSentinel);
        }
    }

    if (scrollToBottomBtn) {
        scrollToBottomBtn.addEventListener('click', () => {
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: 'smooth'
            });
        });
    }

    // 2. Real-time Listeners
    function initSocket() {
        if (!window.NexusSocket) {
            setTimeout(initSocket, 500);
            return;
        }

        console.log('%c [Chat] Initializing Real-time... ', 'background: #222; color: #bada55;');
        
        // Set active conversation ID for global chat
        window.activeConversationId = 'global-chat';

        // Helper to update UI count
        const updateCount = (userIds) => {
            let count = (userIds && Array.isArray(userIds)) ? userIds.length : 1;
            if (count < 1) count = 1;
            
            const countEl = document.getElementById('online-count');
            if (countEl) {
                const text = `[ ONLINE: ${count} ]`;
                countEl.textContent = text;
                countEl.setAttribute('data-text', text);
                console.log(`%c [Chat] Counter updated: ${count} `, 'color: #00ff00; font-weight: bold;');
            }
        };

        // Raw Socket Join
        const socket = window.NexusSocket.socket;
        if (socket) {
            console.log('%c [Chat] Sending join: global-chat ', 'color: #ffc107;');
            socket.emit('conversation:join', { conversationId: 'global-chat' });

            // Direct Raw Listener for reliability
            socket.on('conversation:users', (data) => {
                console.log('%c [Chat] Received user list: ', 'color: cyan;', data);
                if (data.conversationId === 'global-chat') {
                    updateCount(data.userIds);
                }
            });

            // Standard listeners
            socket.on('chat:message', (data) => {
                if (data.conversation_id === 'global-chat') {
                    appendMessage(data);
                    hideTypingIndicator(data.user_id || data.sender_id);
                }
            });

            socket.on('chat:reaction', (data) => {
                if (data.conversation_id === 'global-chat') {
                    updateReactionsUI(data);
                }
            });

            socket.on('chat:typing', (data) => {
                if (data.conversationId === 'global-chat' && String(data.user_id || data.userId) !== String(window.SOCKET_CONFIG.userId)) {
                    showTypingIndicator(data);
                }
            });

            socket.on('chat:message-deleted', (data) => {
                if (data.conversation_id === 'global-chat') {
                    const msgEl = document.querySelector(`.message-item[data-id="${data.message_id}"]`);
                    if (msgEl) {
                        const bubble = msgEl.querySelector('.message-bubble');
                        if (bubble) {
                            const deletedText = chatContainer.getAttribute('data-message-deleted-text') || 'This message was deleted';
                            bubble.classList.add('deleted-msg');
                            bubble.innerHTML = `
                                <p class="deleted-text">
                                    <i class="far fa-trash-alt"></i> 
                                    <em>${deletedText}</em>
                                </p>
                            `;
                        }
                        const reactBtn = msgEl.querySelector('.quick-react-btn');
                        if (reactBtn) reactBtn.remove();
                    }
                }
            });

            if (socket.connected) {
                updateCount([window.SOCKET_CONFIG.userId]);
            }
        }
    }

    initSocket();

    // 3. Send Message
    window.handleSendMessage = async function(e) {
        e.preventDefault();
        const content = messageInput.value.trim();
        if (!content) return;

        const btn = document.getElementById('sendBtn');
        btn.disabled = true;
        btn.style.opacity = '0.5';

        try {
            const response = await fetch('/global-chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ 
                    content,
                    reply_to: currentReplyId
                })
            });

            if (!response.ok) throw new Error('Failed to send');
            messageInput.value = '';
            cancelReply();
            messageInput.focus();
        } catch (err) {
            console.error(err);
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    };

    // 4. Reactions
    window.addReaction = async function(messageId, emoji) {
        try {
            await fetch(`/global-chat/react/${messageId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ reaction: emoji })
            });
            closeEmojiPicker();
        } catch (err) {
            console.error(err);
        }
    };

    // 5. UI Helpers
    function appendMessage(data) {
        // Prevent duplicates
        if (document.querySelector(`.message-item[data-id="${data.id}"]`)) return;

        const senderId = data.sender_id || data.user_id;
        const senderName = data.sender ? data.sender.name : (data.name || 'User');
        const senderUsername = data.sender ? data.sender.username : (data.username || 'user');
        const senderAvatar = data.sender ? data.sender.avatar_url : (data.avatar_url || '');

        const isOwn = String(senderId) === String(window.SOCKET_CONFIG.userId);
        const div = document.createElement('div');
        div.className = `message-item ${isOwn ? 'own' : ''}`;
        div.setAttribute('data-id', data.id);
        div.setAttribute('data-my-reaction', '');

        const time = new Date(data.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        div.innerHTML = `
            <div class="message-avatar">
                <img src="${senderAvatar}" alt="${senderUsername}">
            </div>
            <div class="message-content">
                <div class="message-info">
                    <span class="user-name">${senderName}</span>
                    <span class="user-tag">@${senderUsername}</span>
                </div>
                <div class="bubble-container">
                    <div class="message-bubble">
                        ${data.reply_data ? `
                            <div class="replied-message-box" onclick="scrollToMessage(${data.reply_data.id})">
                                <span class="replied-user">@${data.reply_data.username}</span>
                                <span class="replied-content">${escapeHtml(data.reply_data.content)}</span>
                            </div>
                        ` : ''}
                        <p>${escapeHtml(data.content)}</p>

                        <div class="msg-item-actions">
                            <button class="msg-action-trigger" onclick="toggleMsgMenu(event, ${data.id})">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="msg-dropdown" id="msgDropdown-${data.id}">
                                ${isOwn ? `
                                    <button class="menu-item danger" onclick="deleteGlobalMessage(${data.id})">
                                        <i class="far fa-trash-alt"></i> Delete
                                    </button>
                                ` : `
                                    <button class="menu-item" onclick="reportGlobalMessage(${data.id})">
                                        <i class="far fa-flag"></i> Report
                                    </button>
                                `}
                            </div>
                        </div>

                        <div class="message-reactions-bar" id="reactions-${data.id}"></div>
                    </div>

                    <div class="msg-side-actions">
                        <button class="side-action-btn react" onclick="toggleEmojiPicker(event, ${data.id})" title="React">
                            <i class="far fa-smile"></i>
                        </button>
                        <button class="side-action-btn reply" onclick="startReply(${data.id}, '${addslashes(data.sender.username)}', '${addslashes(data.content.substring(0, 50))}')" title="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                    </div>
                </div>
                <span class="message-time">${time}</span>
            </div>
        `;

        messagesWrapper.appendChild(div);
        
        // If user is near bottom, scroll automatically, otherwise increment badge
        const isAtBottom = chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight < 300;
        if (isAtBottom) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        } else {
            unreadCountWhileScrolled++;
            unreadScrollBadge.textContent = unreadCountWhileScrolled > 99 ? '99+' : unreadCountWhileScrolled;
            unreadScrollBadge.style.display = 'flex';
        }
    }

    function updateReactionsUI(data) {
        const container = document.getElementById(`reactions-${data.message_id}`);
        if (!container) return;

        container.innerHTML = '';
        
        const currentUserId = Number(window.SOCKET_CONFIG.userId);

        if (data.reactions && data.reactions.length > 0) {
            const totalCount = data.reactions.reduce((sum, r) => sum + r.count, 0);
            
            // Find if I have any reaction
            let myEmoji = null;
            data.reactions.forEach(r => {
                const userIds = Array.isArray(r.user_ids) ? r.user_ids.map(id => Number(id)) : [];
                if (userIds.includes(currentUserId)) {
                    myEmoji = r.reaction;
                }
            });

            const pill = document.createElement('div');
            pill.className = `reaction-group-pill ${myEmoji ? 'has-mine' : ''}`;
            
            // Toggle my emoji if exists, else first
            const emojiToToggle = myEmoji || data.reactions[0].reaction;
            pill.onclick = (e) => {
                e.stopPropagation();
                window.showMessageReactors(data.message_id);
            };
            
            let emojiStackHtml = '<div class="reaction-emoji-stack">';
            data.reactions.forEach(r => {
                emojiStackHtml += `<span class="stack-emoji">${r.reaction}</span>`;
            });
            emojiStackHtml += '</div>';

            pill.innerHTML = `
                ${emojiStackHtml}
                <span class="reaction-total-count">${totalCount}</span>
            `;
            container.appendChild(pill);
            container.style.display = 'flex';
        } else {
            container.style.display = 'none';
        }
    }

    // Typing Logic
    let typingTimeout = null;
    messageInput.addEventListener('input', () => {
        if (!window.NexusSocket) return;
        
        window.NexusSocket.emit('chat:typing', {
            conversationId: 'global-chat',
            isTyping: true,
            user_id: window.SOCKET_CONFIG.userId,
            username: window.SOCKET_CONFIG.username
        });

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            window.NexusSocket.emit('chat:typing', {
                conversationId: 'global-chat',
                isTyping: false,
                user_id: window.SOCKET_CONFIG.userId
            });
        }, 3000);
    });

    const typingUsers = new Set();
    function showTypingIndicator(data) {
        if (data.isTyping) {
            typingUsers.add(data.username);
        } else {
            typingUsers.delete(data.username);
        }
        updateTypingUI();
    }

    function hideTypingIndicator(userId) {
        // Find username from userId if possible, or just clear all for simplicity
        // For now, let's just let the timeout handle it or clear when message arrives
    }

    function updateTypingUI() {
        let indicator = document.getElementById('globalTypingIndicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'globalTypingIndicator';
            indicator.className = 'typing-indicator-wrapper';
            messagesWrapper.appendChild(indicator);
        }

        const users = Array.from(typingUsers);
        if (users.length > 0) {
            const text = users.length === 1 ? `${users[0]} is typing...` : 'Multiple people are typing...';
            indicator.innerHTML = `<span class="typing-dot"></span><span class="typing-text">${text}</span>`;
            indicator.style.display = 'flex';
            const isAtBottom = chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight < 300;
            if (isAtBottom) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        } else {
            indicator.style.display = 'none';
        }
    }

    // Reply Logic
    let currentReplyId = null;

    window.startReply = function(id, username, text) {
        currentReplyId = id;
        document.getElementById('replyUser').textContent = '@' + username;
        document.getElementById('replyText').textContent = text;
        document.getElementById('replyPreview').style.display = 'flex';
        messageInput.focus();
        
        // Close dropdown
        document.querySelectorAll('.msg-dropdown').forEach(d => d.classList.remove('show'));
    };

    window.cancelReply = function() {
        currentReplyId = null;
        document.getElementById('replyPreview').style.display = 'none';
    };

    window.scrollToMessage = function(id) {
        const el = document.querySelector(`.message-item[data-id="${id}"]`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('highlight-msg');
            setTimeout(() => el.classList.remove('highlight-msg'), 2000);
        }
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function addslashes(str) {
        if (!str) return '';
        return str.replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }

    // Emoji Picker Logic
    // Message Options Logic
    window.toggleMsgMenu = function(event, id) {
        event.preventDefault();
        event.stopPropagation();
        
        const dropdown = document.getElementById('msgDropdown-' + id);
        if (!dropdown) return;

        document.querySelectorAll('.msg-dropdown').forEach(d => {
            if (d !== dropdown) d.classList.remove('show');
        });
        
        dropdown.classList.toggle('show');
    };



    window.deleteGlobalMessage = async function(id) {
        if (!confirm('Are you sure you want to delete this message?')) return;
        
        try {
            const res = await fetch(`/global-chat/message/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                const msgEl = document.querySelector(`.message-item[data-id="${id}"]`);
                if (msgEl) {
                    const bubble = msgEl.querySelector('.message-bubble');
                    if (bubble) {
                        const deletedText = chatContainer.getAttribute('data-message-deleted-text') || 'This message was deleted';
                        bubble.classList.add('deleted-msg');
                        bubble.innerHTML = `
                            <p class="deleted-text">
                                <i class="far fa-trash-alt"></i> 
                                <em>${deletedText}</em>
                            </p>
                        `;
                    }
                    const reactBtn = msgEl.querySelector('.quick-react-btn');
                    if (reactBtn) reactBtn.remove();
                }
            }
        } catch (err) {
            console.error(err);
        }
        document.querySelectorAll('.msg-dropdown').forEach(d => d.classList.remove('show'));
    };

    window.reportGlobalMessage = function(id) {
        // Implement report logic or redirect
        alert('Reported message ' + id);
        document.querySelectorAll('.msg-dropdown').forEach(d => d.classList.remove('show'));
    };

    document.addEventListener('click', () => {
        document.querySelectorAll('.msg-dropdown').forEach(d => d.classList.remove('show'));
    });

    window.toggleEmojiPicker = function(e, messageId) {
        e.stopPropagation();
        currentEmojiMessageId = messageId;
        const btn = e.currentTarget;
        const rect = btn.getBoundingClientRect();
        
        // Show first to get dimensions
        emojiPicker.style.display = 'block';
        emojiPicker.style.visibility = 'hidden'; // Hide while calculating
        
        // Use timeout or immediate since it's already in DOM
        const pickerWidth = emojiPicker.offsetWidth || 160;
        const pickerHeight = emojiPicker.offsetHeight || 120;

        // Calculate centered position above the button
        let top = rect.top - pickerHeight - 10;
        let left = rect.left - (pickerWidth / 2) + (rect.width / 2);

        // Viewport boundaries check to prevent going off-screen
        if (left < 10) left = 10;
        if (left + pickerWidth > window.innerWidth - 10) {
            left = window.innerWidth - pickerWidth - 10;
        }
        
        // If there's no space above, show it below
        if (top < 10) {
            top = rect.bottom + 10;
        }

        emojiPicker.style.top = top + 'px';
        emojiPicker.style.left = left + 'px';
        emojiPicker.style.visibility = 'visible'; // Finally show it

        // Highlight existing reaction
        const msgItem = btn.closest('.message-item');
        const existingReaction = msgItem.getAttribute('data-my-reaction');
        
        emojiPicker.querySelectorAll('.emoji-list span').forEach(s => {
            s.classList.remove('active');
            const emojiText = s.textContent.trim();
            if (existingReaction && emojiText === existingReaction.trim()) {
                s.classList.add('active');
                console.log('Highlighting active emoji:', emojiText);
            }
        });
    };

    window.selectEmoji = function(emoji) {
        if (currentEmojiMessageId) {
            window.addReaction(currentEmojiMessageId, emoji);
        }
    };

    window.showMessageReactors = function(messageId) {
        const modal = document.getElementById('reactorsModalOverlay');
        const list = document.getElementById('reactorsList');
        
        if (!modal || !list) {
            console.error('Reactors modal elements not found in DOM');
            return;
        }

        list.innerHTML = '<div style="padding: 20px; text-align: center; color: #8696a0;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        modal.style.display = 'flex';

        fetch(`/global-chat/message/${messageId}/reactions`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.reactions || data.reactions.length === 0) {
                list.innerHTML = '<div style="padding: 20px; text-align: center; color: #8696a0;">No reactions found.</div>';
                return;
            }

            let html = '';
            data.reactions.forEach((group) => {
                group.users.forEach((user) => {
                    const isMe = String(user.id) === String(window.SOCKET_CONFIG.userId);
                    const avatar = user.avatar_url || '/images/default-avatar.svg';
                    const emoji = group.reaction_type;
                    
                    html += `
                        <div class="global-reactor-item" onclick="window.location.href='/users/${user.username}'" style="cursor: pointer;">
                            <div class="global-reactor-avatar">
                                <img src="${avatar}" alt="${user.username}" onerror="this.src='/images/default-avatar.svg'">
                            </div>
                            <div class="global-reactor-info">
                                <div class="global-reactor-name">${user.name} ${isMe ? '(You)' : ''}</div>
                                <div class="global-reactor-username">@${user.username}</div>
                            </div>
                            <div class="global-reactor-emoji">${emoji}</div>
                        </div>
                    `;
                });
            });
            list.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            list.innerHTML = '<div style="padding: 20px; text-align: center; color: #f44336;">Error loading reactions.</div>';
        });
    };

    function closeEmojiPicker() {
        emojiPicker.style.display = 'none';
        currentEmojiMessageId = null;
    }

    document.addEventListener('click', closeEmojiPicker);
})();
