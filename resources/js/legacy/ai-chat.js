/* AI Chat Functions */

(function() {
    'use strict';
    
    // Self-healing runOnPageLoad for standalone usage
    if (!window.runOnPageLoad) {
        window.runOnPageLoad = function(cb) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', cb);
            } else {
                setTimeout(cb, 0);
            }
        };
    }

    window.runOnPageLoad(function() {
        const chatContainer = document.getElementById('chatContainer');
        const chatInput = document.getElementById('chatInputNative');
        const sendBtn = document.getElementById('sendBtn');
        const stopBtn = document.getElementById('stopBtn');
        const welcomeMessage = document.getElementById('welcomeMessage');
        const userAvatar = chatContainer.getAttribute('data-user-avatar');
        const userName = chatContainer.getAttribute('data-user-name');

        let isTyping = false;
        let typingTimeout;
        let welcomeHidden = false;

        if (chatInput) {
            chatInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                if (sendBtn) sendBtn.disabled = !this.value.trim();
            });

            chatInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }

        if (sendBtn) {
            sendBtn.addEventListener('click', sendMessage);
        }

        if (stopBtn) {
            stopBtn.addEventListener('click', stopGenerating);
        }

        window.sendQuickMessage = function(text) {
            if (chatInput) {
                chatInput.value = text;
                if (sendBtn) sendBtn.disabled = false;
                sendMessage();
            }
        };

        function sendMessage() {
            const message = chatInput?.value.trim();
            if (!message) return;

            if (!welcomeHidden && welcomeMessage) {
                welcomeMessage.style.display = 'none';
                welcomeHidden = true;
            }

            addMessage(message, 'user');
            if (chatInput) {
                chatInput.value = '';
                chatInput.style.height = 'auto';
            }
            if (sendBtn) sendBtn.disabled = true;

            const typingIndicator = showTyping();

            fetch('/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            })
            .then(res => res.json())
            .then(data => {
                hideTyping(typingIndicator);
                if (data.success && data.response) {
                    addMessageWithTyping(data.response, 'ai');
                } else {
                    addMessage('Sorry, I encountered an error.', 'ai');
                }
            })
            .catch(err => {
                console.error('AI Error:', err);
                hideTyping(typingIndicator);
                addMessage('Sorry, I\'m having trouble connecting.', 'ai');
            });
        }

        function addMessage(text, type) {
            const div = document.createElement('div');
            div.className = 'message ' + type;

            const time = new Date().toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            }).toLowerCase();

            const avatarHtml = type === 'user' ? 
                `<img src="${userAvatar}" alt="${userName}" class="avatar-img">` : 
                '<i class="fas fa-robot"></i>';
            const content = type === 'ai' ? formatResponse(text) : escapeHtml(text);

            div.innerHTML = '<div class="message-avatar">' + avatarHtml + '</div>' +
                '<div class="message-bubble ' + (type === 'user' ? 'user-bubble' : '') + '">' +
                '<p>' + content + '</p>' +
                '<div class="message-time">' + time + '</div></div>';

            if (chatContainer) chatContainer.appendChild(div);
            scrollToBottom();
        }

        function addMessageWithTyping(fullText, type) {
            const div = document.createElement('div');
            div.className = 'message ' + type;

            const time = new Date().toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            }).toLowerCase();

            div.innerHTML = '<div class="message-avatar"><i class="fas fa-robot"></i></div>' +
                '<div class="message-bubble">' +
                '<div class="typing-indicator"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div>' +
                '<div class="message-time">' + time + '</div></div>';

            if (chatContainer) chatContainer.appendChild(div);
            scrollToBottom();

            setTimeout(() => {
                startTypewriterEffect(div, fullText);
            }, 100);
        }

        function startTypewriterEffect(messageDiv, fullText) {
            const bubble = messageDiv.querySelector('.message-bubble');
            const contentP = document.createElement('p');
            contentP.className = 'message-content';
            contentP.style.minHeight = '20px';

            const typingIndicator = bubble.querySelector('.typing-indicator');
            if (typingIndicator) typingIndicator.remove();
            bubble.insertBefore(contentP, bubble.querySelector('.message-time'));

            const cursor = document.createElement('span');
            cursor.className = 'cursor';
            contentP.appendChild(cursor);

            let charIndex = 0;
            isTyping = true;

            if (chatInput) chatInput.disabled = true;
            if (stopBtn) stopBtn.style.display = 'flex';

            stopBtn.onclick = function() {
                isTyping = false;
                clearTimeout(typingTimeout);
                if (stopBtn) stopBtn.style.display = 'none';
                cursor.remove();
                contentP.innerHTML = formatResponse(fullText);
                if (chatInput) {
                    chatInput.disabled = false;
                    chatInput.focus();
                }
                setTimeout(scrollToBottom, 50);
            };

            function typeNextChar() {
                if (!isTyping) return;

                if (charIndex < fullText.length) {
                    const remaining = fullText.substring(charIndex);
                    let chunk = '';
                    let charsToSkip = 1;

                    const emojiMatch = remaining.match(/^[\p{Emoji}]/u);
                    if (emojiMatch) {
                        chunk = emojiMatch[0];
                        charsToSkip = 1;
                    } else if (fullText[charIndex] === '\n') {
                        chunk = '<br>';
                        charsToSkip = 1;
                    } else if (remaining.startsWith('**')) {
                        const endBold = remaining.indexOf('**', 2);
                        if (endBold !== -1) {
                            const boldText = remaining.substring(2, endBold);
                            chunk = '<strong>' + boldText + '</strong>';
                            charsToSkip = endBold + 2;
                        } else {
                            chunk = remaining[0];
                            charsToSkip = 1;
                        }
                    } else {
                        chunk = remaining[0];
                        charsToSkip = 1;
                    }

                    const tempSpan = document.createElement('span');
                    tempSpan.innerHTML = chunk;
                    while (tempSpan.firstChild) {
                        contentP.insertBefore(tempSpan.firstChild, cursor);
                    }

                    charIndex += charsToSkip;
                    scrollToBottom();

                    const speed = getTypingSpeed(fullText[charIndex - 1]) + Math.random() * 10;
                    typingTimeout = setTimeout(typeNextChar, speed);
                } else {
                    if (stopBtn) stopBtn.style.display = 'none';
                    cursor.remove();
                    contentP.innerHTML = formatResponse(fullText);
                    if (chatInput) {
                        chatInput.disabled = false;
                        chatInput.focus();
                    }
                    isTyping = false;
                }
            }

            typeNextChar();
        }

        function getTypingSpeed(char) {
            if (char === '\n') return 50;
            if (char === ' ') return 25;
            if (char.match(/[a-z0-9]/i)) return 15;
            if (char.match(/[.,!?;:]/)) return 80;
            if (char.match(/[\u0600-\u06FF]/)) return 20;
            return 20;
        }

        function stopGenerating() {
            isTyping = false;
            clearTimeout(typingTimeout);
            const cursor = document.querySelector('.cursor');
            if (cursor) cursor.remove();
            if (stopBtn) stopBtn.style.display = 'none';
        }

        function showTyping() {
            const div = document.createElement('div');
            div.className = 'message ai typing-indicator-msg';
            div.innerHTML = '<div class="message-avatar"><i class="fas fa-robot"></i></div>' +
                '<div class="message-bubble"><div class="typing-indicator"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div></div>';
            if (chatContainer) chatContainer.appendChild(div);
            scrollToBottom();
            return div;
        }

        function hideTyping(indicator) {
            if (indicator && indicator.parentNode) {
                indicator.remove();
            }
        }

        function formatResponse(text) {
            let formatted = escapeHtml(text);
            const emojiRegex = /[\p{Emoji}]/gu;
            formatted = formatted.replace(emojiRegex, '<span class="emoji">$&</span>');
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            formatted = formatted.replace(/\n/g, '<br>');
            return formatted;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function scrollToBottom() {
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

        window.clearChat = function() {
            if (!confirm('Are you sure you want to clear the chat?')) return;

            const messages = chatContainer?.querySelectorAll('.message:not(#welcomeMessage)');
            if (messages) {
                messages.forEach(msg => msg.remove());
            }

            if (welcomeMessage) {
                welcomeMessage.style.display = 'flex';
                welcomeHidden = false;
            }

            if (chatInput) chatInput.value = '';
            if (sendBtn) sendBtn.disabled = true;
        };
    });
})();
