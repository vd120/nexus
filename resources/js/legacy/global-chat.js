(function() {
    const chatMessages = document.getElementById('chatMessages');
    const messagesWrapper = document.getElementById('messagesWrapper');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const emojiPicker = document.getElementById('emojiPicker');
    let currentEmojiMessageId = null;
    let scrollObserver = null;

    // 1. Scroll to bottom on load
    const scrollToBottomBtn = document.getElementById('scrollToBottomBtn');
    const unreadScrollBadge = document.getElementById('unreadScrollBadge');
    let unreadCountWhileScrolled = 0;

    // Media state
    let selectedMediaFiles = [];
    let currentPreviewIndex = 0;

    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Scroll Sensing using IntersectionObserver (The most reliable way for mobile)
        const scrollSentinel = document.getElementById('scrollSentinel');
        if (scrollSentinel && scrollToBottomBtn) {
            scrollObserver = new IntersectionObserver((entries) => {
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
                root: chatMessages,
                threshold: 0.1
            });

            scrollObserver.observe(scrollSentinel);
        }
    }

    if (scrollToBottomBtn) {
        scrollToBottomBtn.addEventListener('click', () => {
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: 'smooth'
            });
        });
    }

    function maintainScroll(action) {
        if (!chatMessages) {
            action();
            return;
        }
        const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight < 300;
        action();
        if (isAtBottom) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    // 2. Real-time Listeners
    function initSocket() {
        if (!window.NexusSocket) {
            setTimeout(initSocket, 500);
            return;
        }

        // Initializing Real-time handled by Socket Manager

        // Helper to update UI count
        const updateCount = (userIds) => {
            let count = (userIds && Array.isArray(userIds)) ? userIds.length : 1;
            if (count < 1) count = 1;
            
            const countEl = document.getElementById('online-count');
            if (countEl) {
                countEl.textContent = `[ ONLINE: ${count} ]`;
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

            socket.on('chat:message', (data) => {
                if (data.conversation_id === 'global-chat' && window.addMessage) {
                    window.addMessage(data);
                }
            });

            socket.on('chat:reaction', (data) => {
                if (data.conversation_id === 'global-chat' && window.updateReactionsFromSocket) {
                    window.updateReactionsFromSocket(data);
                }
            });

            socket.on('chat:message:deleted', (data) => {
                if (data.conversation_id === 'global-chat') {
                    const msgEl = document.querySelector(`.message-item[data-id="${data.message_id}"]`);
                    if (msgEl) {
                        const bubble = msgEl.querySelector('.message-bubble');
                        if (bubble) {
                            const deletedText = chatMessages.getAttribute('data-message-deleted-text') || 'This message was deleted';
                            bubble.classList.add('deleted-msg');
                            const timeEl = bubble.querySelector('.message-time');
                            const timeHtml = timeEl ? timeEl.outerHTML : '';
                            
                            bubble.innerHTML = `
                                <div class="deleted-msg-wrapper">
                                    <i class="fas fa-ban"></i>
                                    <em>${deletedText}</em>
                                </div>
                                ${timeHtml}
                            `;
                        }
                        const reactBtn = msgEl.querySelector('.quick-react-btn');
                        if (reactBtn) reactBtn.remove();
                    }
                }
            });

            socket.on('chat:cleared', (data) => {
                if (data.conversation_id === 'global-chat') {
                    console.log('%c [Chat] Chat cleared by admin ', 'color: #ff4757; font-weight: bold;');
                    if (messagesWrapper) {
                        messagesWrapper.innerHTML = '<div id="scrollSentinel" style="height: 1px; width: 100%;"></div>';
                        // Re-observe the new sentinel
                        const scrollSentinel = document.getElementById('scrollSentinel');
                        if (scrollSentinel && scrollObserver) {
                            scrollObserver.observe(scrollSentinel);
                        }
                    }
                }
            });

            if (socket.connected) {
                updateCount([window.SOCKET_CONFIG.userId]);
            }
        }
    }

    initSocket();

    // Media Selection & Preview
    window.handleMediaSelect = function(e) {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;

        // Max 20 files
        if (selectedMediaFiles.length + files.length > 20) {
            alert('Maximum 20 files allowed');
            return;
        }

        selectedMediaFiles = [...selectedMediaFiles, ...files];
        currentPreviewIndex = selectedMediaFiles.length - files.length;
        updateMediaPreview();
        
        // Reset input so same file can be picked again if needed
        e.target.value = '';
    };

    function updateMediaPreview() {
        const previewArea = document.getElementById('mediaPreview');
        const mainDisplay = document.getElementById('mainPreviewDisplay');
        const slidesContainer = document.getElementById('previewSlides');
        const indicators = document.getElementById('previewIndicators');
        const countDisplay = document.getElementById('previewCount');

        maintainScroll(() => {
            if (selectedMediaFiles.length === 0) {
                previewArea.style.display = 'none';
            } else {
                previewArea.style.display = 'flex';
            }
        });

        if (selectedMediaFiles.length === 0) return;

        slidesContainer.innerHTML = '';
        indicators.innerHTML = '';
        mainDisplay.innerHTML = '';
        
        // Render Main Preview
        const focusedFile = selectedMediaFiles[currentPreviewIndex];
        if (focusedFile) {
            if (focusedFile.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(focusedFile);
                mainDisplay.appendChild(img);
            } else if (focusedFile.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(focusedFile);
                video.controls = true;
                mainDisplay.appendChild(video);
            }
        }

        // Render Thumbnails
        selectedMediaFiles.forEach((file, index) => {
            const slide = document.createElement('div');
            slide.className = `preview-slide ${index === currentPreviewIndex ? 'active' : ''}`;
            slide.onclick = () => {
                currentPreviewIndex = index;
                updateMediaPreview();
            };
            
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                slide.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(file);
                slide.appendChild(video);
                const playIcon = document.createElement('div');
                playIcon.className = 'video-overlay';
                playIcon.innerHTML = '<i class="fas fa-play"></i>';
                slide.appendChild(playIcon);
            }

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-media';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = (e) => {
                e.stopPropagation();
                removeMedia(index);
            };
            slide.appendChild(removeBtn);
            
            slidesContainer.appendChild(slide);

            const dot = document.createElement('div');
            dot.className = `indicator-dot ${index === currentPreviewIndex ? 'active' : ''}`;
            dot.onclick = () => {
                currentPreviewIndex = index;
                updateMediaPreview();
            };
            indicators.appendChild(dot);
        });

        countDisplay.textContent = `${currentPreviewIndex + 1} / ${selectedMediaFiles.length}`;
        
        // Scroll to active slide
        const activeSlide = slidesContainer.children[currentPreviewIndex];
        if (activeSlide) {
            slidesContainer.scrollTo({
                left: activeSlide.offsetLeft - (slidesContainer.offsetWidth / 2) + (activeSlide.offsetWidth / 2),
                behavior: 'smooth'
            });
        }
    }

    window.movePreview = function(dir) {
        currentPreviewIndex += dir;
        if (currentPreviewIndex < 0) currentPreviewIndex = selectedMediaFiles.length - 1;
        if (currentPreviewIndex >= selectedMediaFiles.length) currentPreviewIndex = 0;
        updateMediaPreview();
    };

    function removeMedia(index) {
        selectedMediaFiles.splice(index, 1);
        if (currentPreviewIndex >= selectedMediaFiles.length) {
            currentPreviewIndex = Math.max(0, selectedMediaFiles.length - 1);
        }
        updateMediaPreview();
    }

    window.clearMediaPreview = function() {
        selectedMediaFiles = [];
        currentPreviewIndex = 0;
        updateMediaPreview();
    };

    // 3. Send Message
    window.handleSendMessage = async function(e) {
        e.preventDefault();
        const content = messageInput.value.trim();
        if (!content && selectedMediaFiles.length === 0) return;

        const btn = document.getElementById('sendBtn');
        btn.disabled = true;
        btn.style.opacity = '0.5';

        const formData = new FormData();
        if (content) formData.append('content', content);
        if (currentReplyId) formData.append('reply_to', currentReplyId);
        
        selectedMediaFiles.forEach(file => {
            formData.append('media[]', file);
        });

        try {
            const response = await fetch('/global-chat/send', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (!response.ok) throw new Error('Failed to send');
            
            messageInput.value = '';
            clearMediaPreview();
            cancelReply();
        } catch (err) {
            console.error(err);
            alert('Error sending message');
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
    window.addMessage = function(data) {
        // Prevent duplicates
        if (document.querySelector(`.message-item[data-id="${data.id}"]`)) return;

        const senderId = data.sender_id || data.user_id;
        const senderName = data.sender ? data.sender.name : (data.name || 'User');
        const senderUsername = data.sender ? data.sender.username : (data.username || 'user');
        const senderAvatar = data.sender ? data.sender.avatar_url : (data.avatar_url || '');

        const isVerified = data.sender && data.sender.is_verified;
        const verifiedBadgeHtml = isVerified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
        const isOwn = String(senderId) === String(window.SOCKET_CONFIG.userId);
        const hasReactions = data.reactions && Array.from(data.reactions).length > 0;
        const div = document.createElement('div');
        div.className = `message-item ${isOwn ? 'own' : ''} new-message ${hasReactions ? 'has-reactions' : ''}`;
        div.setAttribute('data-id', data.id);
        div.setAttribute('data-my-reaction', '');

        const _td = new Date(data.created_at);
        const _th = _td.getHours() % 12 || 12;
        const _tm = String(_td.getMinutes()).padStart(2, '0');
        const time = String(_th).padStart(2, '0') + ':' + _tm + ' ' + (_td.getHours() >= 12 ? 'pm' : 'am');

        // Media Grid Logic
        let mediaHtml = '';
        if (data.media && Array.from(data.media).length > 0) {
            const mediaItems = Array.from(data.media);
            const displayCount = Math.min(mediaItems.length, 4);
            const remainingCount = mediaItems.length - displayCount;
            
            let gridClass = `media-grid-${displayCount}`;
            if (displayCount === 1) gridClass = 'media-grid-single';
            else if (displayCount === 2) gridClass = 'media-grid-two';

            mediaHtml = `
                <div class="message-media-album" data-message-id="${data.id}">
                    <script type="application/json" class="media-data">${JSON.stringify(mediaItems)}</script>
                    <div class="${gridClass}">
            `;

            mediaItems.slice(0, displayCount).forEach((item, index) => {
                const assetPath = `/storage/${item.path}`;
                if (item.type === 'image') {
                    mediaHtml += `
                        <div class="media-item">
                            <img src="${assetPath}" alt="Image" onclick="openMediaViewerFromAlbum(this, ${data.id}, ${index})">
                            ${index === 3 && remainingCount > 0 ? `
                                <div class="media-overlay" onclick="openMediaViewerFromAlbum(this, ${data.id}, 3)">
                                    <span class="overlay-text">+${remainingCount}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                } else if (item.type === 'video') {
                    mediaHtml += `
                        <div class="media-item video" onclick="openMediaViewerFromAlbum(this, ${data.id}, ${index})">
                            <video src="${assetPath}"></video>
                            <div class="media-overlay"><i class="fas fa-play"></i></div>
                        </div>
                    `;
                }
            });

            mediaHtml += '</div></div>';
        }

        div.innerHTML = `
            <div class="message-avatar">
                <a href="/users/${senderUsername}" style="display:flex;flex-shrink:0;"><img src="${senderAvatar}" alt="${senderUsername}" style="pointer-events:none;"></a>
            </div>
            <div class="message-content">
                <div class="message-info">
                    <a href="/users/${senderUsername}" class="user-tag" style="text-decoration:none;display:inline-flex;align-items:center;gap:.15em;">${senderUsername}${verifiedBadgeHtml}</a>
                </div>

                <div class="bubble-container">
                    <div class="bubble-wrapper">
                        <div class="message-bubble ${mediaHtml ? 'has-media' : ''} ${data.reactions && data.reactions.length > 0 ? 'has-reactions' : ''}">
                            ${data.reply_data ? `
                                <div class="replied-message-box" onclick="scrollToMessage(${data.reply_data.id})">
                                    <span class="replied-user">${data.reply_data.username}</span>
                                    <span class="replied-content">${escapeHtml(data.reply_data.content)}</span>
                                </div>
                            ` : ''}
                            
                            ${mediaHtml}

                            ${data.content ? `<p>${escapeHtml(data.content)}</p>` : ''}

                            <div class="msg-item-actions">
                                <button class="msg-action-trigger" onclick="toggleMsgMenu(event, ${data.id})">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="msg-dropdown" id="msgDropdown-${data.id}">
                                    <button class="menu-item" onclick="startReply(${data.id}, '${jsEscape(senderUsername)}', '${jsEscape((data.content || '').substring(0, 50))}')">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    ${isOwn || window.SOCKET_CONFIG.isAdmin ? `
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

                            <span class="message-time">${time}</span>
                        </div>
                        <div class="message-reactions-bar" id="reactions-${data.id}"></div>
                    </div>

                    <div class="msg-side-actions">
                        <button class="side-action-btn react" onclick="toggleEmojiPicker(event, ${data.id})" title="React">
                            <i class="far fa-smile"></i>
                        </button>
                        <button class="side-action-btn reply" onclick="startReply(${data.id}, '${jsEscape(senderUsername)}', '${jsEscape((data.content || '').substring(0, 50))}')" title="Reply">
                            <i class="fas fa-reply"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        messagesWrapper.appendChild(div);
        
        // If user is near bottom, scroll automatically, otherwise increment badge
        const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight < 300;
        if (isAtBottom) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        } else {
            unreadCountWhileScrolled++;
            unreadScrollBadge.textContent = unreadCountWhileScrolled > 99 ? '99+' : unreadCountWhileScrolled;
            unreadScrollBadge.style.display = 'flex';
        }
    }

    window.updateReactionsFromSocket = function(data) {
        const container = document.getElementById(`reactions-${data.message_id}`);
        if (!container) return;

        const msgItem = container.closest('.message-item');
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
            
            if (msgItem) {
                msgItem.setAttribute('data-my-reaction', myEmoji || '');
                msgItem.classList.toggle('has-reactions', true);
                const bubble = msgItem.querySelector('.message-bubble');
                if (bubble) bubble.classList.add('has-reactions');
            }


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
            if (msgItem) {
                msgItem.setAttribute('data-my-reaction', '');
                msgItem.classList.remove('has-reactions');
                const bubble = msgItem.querySelector('.message-bubble');
                if (bubble) bubble.classList.remove('has-reactions');
            }
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


    // Reply Logic
    let currentReplyId = null;

    window.startReply = function(id, username, text) {
        currentReplyId = id;
        const replyPreview = document.getElementById('replyPreview');
        const replyUser = document.getElementById('replyUser');
        const replyText = document.getElementById('replyText');
        
        replyUser.textContent = username;
        replyText.textContent = text;
        maintainScroll(() => {
            replyPreview.style.display = 'flex';
        });
        
        // Close dropdown and backdrop
        window.closeAllMenus();
    };

    window.cancelReply = function() {
        currentReplyId = null;
        const replyPreview = document.getElementById('replyPreview');
        maintainScroll(() => {
            replyPreview.style.display = 'none';
        });
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
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function jsEscape(str) {
        if (!str) return '';
        return str.replace(/[\\"']/g, '\\$&')
                  .replace(/\n/g, '\\n')
                  .replace(/\r/g, '\\r')
                  .replace(/\u0000/g, '\\0');
    }

    // Emoji Picker Logic
    // Message Options Logic
    window.toggleMsgMenu = function(event, id) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const dropdown = document.getElementById('msgDropdown-' + id);
        if (!dropdown) return;

        const isShowing = dropdown.classList.contains('show');
        window.closeAllMenus();
        
        if (!isShowing) {
            if (window.innerWidth <= 768) {
                // Move dropdown to body to escape any transform/overflow traps
                document.body.appendChild(dropdown);
                
                const btn = event.currentTarget || event.target.closest('.msg-action-trigger');
                const rect = btn.getBoundingClientRect();
                
                // Show temporarily to get height
                dropdown.style.visibility = 'hidden';
                dropdown.style.display = 'block';
                const h = dropdown.offsetHeight || 100;
                const w = dropdown.offsetWidth || 160;

                let top = rect.bottom + 8;
                let left = rect.right - w;

                // Viewport boundary checks
                if (left < 10) left = 10;
                if (left + w > window.innerWidth - 10) left = window.innerWidth - w - 10;
                
                // If it goes off the bottom, flip it to the top
                if (top + h > window.innerHeight - 10) {
                    top = rect.top - h - 8;
                }

                dropdown.style.top = top + 'px';
                dropdown.style.left = left + 'px';
                dropdown.style.visibility = 'visible';
            }

            dropdown.classList.add('show');
            const backdrop = document.getElementById('msgOptsBackdrop');
            if (backdrop) backdrop.classList.add('show');
        }
    };

    window.closeAllMenus = function() {
        document.querySelectorAll('.msg-dropdown').forEach(d => d.classList.remove('show'));
        document.querySelectorAll('.message-item').forEach(i => i.style.zIndex = '');
        const backdrop = document.getElementById('msgOptsBackdrop');
        if (backdrop) backdrop.classList.remove('show');
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
                        const deletedText = chatMessages.getAttribute('data-message-deleted-text') || 'This message was deleted';
                        bubble.classList.add('deleted-msg');
                        const timeEl = bubble.querySelector('.message-time');
                        const timeHtml = timeEl ? timeEl.outerHTML : '';
                        
                        bubble.innerHTML = `
                            <div class="deleted-msg-wrapper">
                                <i class="fas fa-ban"></i>
                                <em>${deletedText}</em>
                            </div>
                            ${timeHtml}
                        `;
                    }
                    const reactBtn = msgEl.querySelector('.quick-react-btn');
                    if (reactBtn) reactBtn.remove();
                }
            }
        } catch (err) {
            console.error(err);
        }
        window.closeAllMenus();
    };

    window.reportGlobalMessage = function(id) {
        // Implement report logic or redirect
        alert('Reported message ' + id);
        window.closeAllMenus();
    };

    window.confirmClearChat = async function() {
        if (!confirm('Are you sure you want to CLEAR the entire global chat? This cannot be undone.')) return;
        
        try {
            const response = await fetch('/admin/global-chat/clear-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (!data.success) {
                alert(data.message || 'Error clearing chat');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred while clearing chat.');
        }
    };

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.msg-dropdown') && !e.target.closest('.msg-action-trigger')) {
            window.closeAllMenus();
        }
    });

    window.toggleEmojiPicker = function(e, messageId) {
        e.stopPropagation();
        
        // If already open for THIS message, close it (toggle effect)
        if (emojiPicker.style.display === 'block' && emojiPicker.style.visibility === 'visible' && currentEmojiMessageId === messageId) {
            window.closeAllMenus();
            return;
        }

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

        list.innerHTML = Array.from({length: 4}, () => '<div style="display:flex;gap:12px;padding:10px 14px;align-items:center;"><div class="sk" style="width:40px;height:40px;border-radius:50%;flex-shrink:0;"></div><div style="flex:1;display:flex;flex-direction:column;gap:6px;"><div class="sk sk-line" style="width:55%;"></div><div class="sk sk-line" style="width:35%;"></div></div></div>').join('');
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
                    
                    const gcBadge = user.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width=".8em" height=".8em" style="display:inline-block;vertical-align:middle;margin-left:.15em;flex-shrink:0;" aria-label="Verified" role="img"><circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/><path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>` : '';
                    html += `
                        <a href="/users/${user.username}" class="global-reactor-item" style="cursor: pointer; text-decoration: none; color: inherit; display: flex; align-items: center;">
                            <div class="global-reactor-avatar">
                                <img src="${avatar}" alt="${user.username}" onerror="this.src='/images/default-avatar.svg'">
                            </div>
                            <div class="global-reactor-info">
                                <div class="global-reactor-name" style="display:inline-flex;align-items:center;gap:.15em;">${escapeHtml(user.name)}${gcBadge} ${isMe ? '(You)' : ''}</div>
                                <div class="global-reactor-username">${escapeHtml(user.username)}</div>
                            </div>
                            <div class="global-reactor-emoji">${emoji}</div>
                        </a>
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

    // Media Viewer Logic
    let currentGalleryMedia = [];
    let currentGalleryIndex = 0;

    window.openMediaViewerFromAlbum = function(el, messageId, index) {
        const album = el.closest('.message-media-album');
        const scriptData = album.querySelector('.media-data');
        if (!scriptData) return;

        try {
            currentGalleryMedia = JSON.parse(scriptData.textContent);
            currentGalleryIndex = index;
            showMediaViewer();
        } catch (e) {
            console.error('Failed to parse media data', e);
        }
    };

    function showMediaViewer() {
        const viewer = document.getElementById('mediaViewer');
        viewer.setAttribute('aria-hidden', 'false');
        viewer.classList.add('active');
        document.body.style.overflow = 'hidden';
        updateGalleryContent();
    }

    function updateGalleryContent() {
        const img = document.getElementById('galleryImage');
        const video = document.getElementById('galleryVideo');
        const counter = document.getElementById('galleryCounter');
        const prevBtn = document.getElementById('galleryPrev');
        const nextBtn = document.getElementById('galleryNext');
        
        const media = currentGalleryMedia[currentGalleryIndex];
        const assetPath = `/storage/${media.path}`;

        img.style.display = 'none';
        video.style.display = 'none';
        video.pause();

        if (media.type === 'image') {
            img.src = assetPath;
            img.style.display = 'block';
        } else if (media.type === 'video') {
            video.src = assetPath;
            video.style.display = 'block';
        }

        counter.textContent = `${currentGalleryIndex + 1} / ${currentGalleryMedia.length}`;
        prevBtn.style.visibility = currentGalleryIndex > 0 ? 'visible' : 'hidden';
        nextBtn.style.visibility = currentGalleryIndex < currentGalleryMedia.length - 1 ? 'visible' : 'hidden';
    }

    window.closeMediaViewer = function() {
        const viewer = document.getElementById('mediaViewer');
        viewer.setAttribute('aria-hidden', 'true');
        viewer.classList.remove('active');
        document.body.style.overflow = '';
        const video = document.getElementById('galleryVideo');
        video.pause();
    };

    document.getElementById('galleryClose').onclick = window.closeMediaViewer;
    document.getElementById('galleryPrev').onclick = () => {
        if (currentGalleryIndex > 0) {
            currentGalleryIndex--;
            updateGalleryContent();
        }
    };
    document.getElementById('galleryNext').onclick = () => {
        if (currentGalleryIndex < currentGalleryMedia.length - 1) {
            currentGalleryIndex++;
            updateGalleryContent();
        }
    };

    // Keyboard support for gallery
    document.addEventListener('keydown', (e) => {
        const viewer = document.getElementById('mediaViewer');
        if (viewer.classList.contains('active')) {
            if (e.key === 'Escape') window.closeMediaViewer();
            if (e.key === 'ArrowLeft' && currentGalleryIndex > 0) {
                currentGalleryIndex--;
                updateGalleryContent();
            }
            if (e.key === 'ArrowRight' && currentGalleryIndex < currentGalleryMedia.length - 1) {
                currentGalleryIndex++;
                updateGalleryContent();
            }
        }
    });

    document.addEventListener('click', closeEmojiPicker);
})();
