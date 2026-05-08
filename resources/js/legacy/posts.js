/* Posts Functions - External File */

(function() {
    'use strict';

    if (typeof window.postFunctionsInitialized === 'undefined') {
        window.postFunctionsInitialized = true;

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        function getTranslations() {
            const el = document.getElementById('post-translations');
            try {
                return JSON.parse(el?.textContent || '{}');
            } catch {
                return {};
            }
        }

        function updateAllTimestamps() {
            const elements = document.querySelectorAll('[data-timestamp]');
            if (elements.length === 0) return;
            
            const now = new Date();
            const t = getTranslations();
            
            // Map units based on locale (simple check)
            const isArabic = document.documentElement.lang === 'ar';
            const units = {
                m: isArabic ? 'د' : 'm',
                h: isArabic ? 'س' : 'h',
                d: isArabic ? 'ي' : 'd',
                w: isArabic ? 'أ' : 'w'
            };

            elements.forEach(el => {
                const timestampStr = el.dataset.timestamp;
                if (!timestampStr) return;
                
                const timestamp = new Date(timestampStr);
                const diffSeconds = Math.floor((now - timestamp) / 1000);
                
                let text = '';
                // Handle slight clock drift (up to 5 seconds ahead) or very recent
                if (diffSeconds < 60) {
                    text = t.just_now || (isArabic ? 'الآن' : 'Just now');
                } else if (diffSeconds < 3600) {
                    text = Math.floor(diffSeconds / 60) + units.m;
                } else if (diffSeconds < 86400) {
                    text = Math.floor(diffSeconds / 3600) + units.h;
                } else if (diffSeconds < 604800) {
                    text = Math.floor(diffSeconds / 86400) + units.d;
                } else if (diffSeconds < 2592000) {
                    text = Math.floor(diffSeconds / 604800) + units.w;
                } else {
                    return; // Too old for auto-update
                }
                
                // Update text node
                let textNode = null;
                for (let node of el.childNodes) {
                    if (node.nodeType === Node.TEXT_NODE) {
                        textNode = node;
                        break;
                    }
                }
                
                if (textNode) {
                    const current = textNode.textContent.trim();
                    if (current !== text) {
                        textNode.textContent = ' ' + text + ' ';
                    }
                } else {
                    el.prepend(document.createTextNode(' ' + text + ' '));
                }
            });
        }

        // Start interval
        setInterval(updateAllTimestamps, 60000);
        // Initial run
        setTimeout(updateAllTimestamps, 500); 

        window.initializePostComponents = function(postElement) {
            if (!postElement) return;
            
            const currentUserId = window.SOCKET_CONFIG?.userId;
            const isAdmin = window.SOCKET_CONFIG?.isAdmin;
            const ownerId = postElement.dataset.ownerId;
            const isBroadcast = postElement.classList.contains('viewer-context-needed');
            
            if (isBroadcast) {
                const isOwner = currentUserId && String(currentUserId) === String(ownerId);
                
                // Show/hide based on owner status
                postElement.querySelectorAll('.context-owner').forEach(el => {
                    el.style.display = isOwner ? 'block' : 'none';
                });
                
                postElement.querySelectorAll('.context-not-owner').forEach(el => {
                    el.style.display = isOwner ? 'none' : 'block';
                });
                
                postElement.querySelectorAll('.context-admin').forEach(el => {
                    el.style.display = isAdmin ? 'block' : 'none';
                });
                
                // Special case for follow button which might be inline-flex
                const followBtn = postElement.querySelector('.quick-follow-btn.context-not-owner');
                if (followBtn) {
                    const authorId = parseInt(followBtn.dataset.authorId);
                    const followingList = window.SOCKET_CONFIG?.following || [];
                    const isFollowing = followingList.includes(authorId);
                    
                    if (isFollowing) {
                        const t = typeof getTranslations === 'function' ? getTranslations() : {};
                        followBtn.classList.add('following');
                        followBtn.dataset.following = 'true';
                        const span = followBtn.querySelector('span');
                        if (span) span.textContent = t.following || 'Following';
                    }
                    
                    followBtn.style.display = isOwner ? 'none' : 'inline-flex';
                }
                
                // Finalize dropdown visibility
                const dropdown = postElement.querySelector('.post-menu-dropdown');
                if (dropdown) {
                    // Reset all items display except those we explicitly set
                    // This handles things like Pin/Unpin logic inside context-owner
                }
            }
            
            // Re-initialize other dynamic elements if needed
            if (typeof window.initPostMedia === 'function') {
                window.initPostMedia(postElement);
            }
        };

        window.deletePost = function(slug, btn) {
            const t = getTranslations();
            const isAdminDelete = btn.getAttribute('data-is-admin-delete') === 'true';
            let reason = '';

            if (isAdminDelete) {
                reason = prompt('Admin Action: Please provide a reason for deleting this post:', '');
                if (reason === null) return; // Cancelled
                if (reason.trim() === '') {
                    alert('A reason is required for admin deletions.');
                    return;
                }
            } else {
                if (!confirm(t.delete_post_confirm || 'Delete this post?')) return;
            }

            const postCard = btn.closest('.post-card');

            fetch(`/posts/${slug}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => {
                if (response.ok) {
                    if (postCard) {
                        const container = postCard.parentElement;
                        postCard.remove();
                        
                        // Check if feed is empty
                        if (container && container.querySelectorAll('.post-card').length === 0) {
                            const emptyStateHtml = `
                                <div class="empty-state">
                                    <i class="fas fa-newspaper"></i>
                                    <h3>${t.no_posts_yet || 'No posts yet'}</h3>
                                    <p>${t.be_first_to_post || 'Be the first to share something!'}</p>
                                </div>
                            `;
                            container.innerHTML = emptyStateHtml;
                        }
                    }
                    showToast(t.post_deleted || 'Post deleted', 'success');
                    return { success: true };
                }
                return response.json().catch(() => {
                    window.location.reload();
                });
            })
            .then(data => {
                if (data && data.success && postCard) {
                    const container = postCard.parentElement;
                    postCard.remove();
                    
                    // Check if feed is empty
                    if (container && container.querySelectorAll('.post-card').length === 0) {
                        const emptyStateHtml = `
                            <div class="empty-state">
                                <i class="fas fa-newspaper"></i>
                                <h3>${t.no_posts_yet || 'No posts yet'}</h3>
                                <p>${t.be_first_to_post || 'Be the first to share something!'}</p>
                            </div>
                        `;
                        container.innerHTML = emptyStateHtml;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(t.failed_to_delete_post || 'Failed to delete post', 'error');
                window.location.reload();
            });
        };

        window.toggleLike = function(slug, btn) {
            const isCurrentlyLiked = btn.classList.contains('liked');
            const icon = btn.querySelector('i');
            const postCard = btn.closest('.post-card');
            const engagementCount = postCard?.querySelector('[data-engagement-count] span');

            // Immediate UI toggle
            if (isCurrentlyLiked) {
                btn.classList.remove('liked');
                if (icon) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
                if (engagementCount) {
                    const current = parseInt(engagementCount.textContent) || 0;
                    engagementCount.textContent = Math.max(0, current - 1);
                }
            } else {
                btn.classList.add('liked');
                if (icon) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                }
                if (engagementCount) {
                    const current = parseInt(engagementCount.textContent) || 0;
                    engagementCount.textContent = current + 1;
                }
            }

            fetch(`/posts/${slug}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert on failure
                    if (isCurrentlyLiked) {
                        btn.classList.add('liked');
                        if (icon) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        }
                    } else {
                        btn.classList.remove('liked');
                        if (icon) {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }
                    }
                    if (engagementCount && data.likes_count !== undefined) {
                        engagementCount.textContent = data.likes_count;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert on error
                if (isCurrentlyLiked) {
                    btn.classList.add('liked');
                    if (icon) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    }
                } else {
                    btn.classList.remove('liked');
                    if (icon) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                }
            });
        };

        window.toggleSave = function(slug, btn) {
            const isCurrentlySaved = btn.classList.contains('saved');
            const icon = btn.querySelector('i');

            // Immediate UI toggle
            if (isCurrentlySaved) {
                btn.classList.remove('saved');
                if (icon) { icon.classList.remove('fas'); icon.classList.add('far'); }
            } else {
                btn.classList.add('saved');
                if (icon) { icon.classList.remove('far'); icon.classList.add('fas'); }
            }

            fetch(`/posts/${slug}/save`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.saved) {
                    btn.classList.add('saved');
                    if (icon) { icon.classList.remove('far'); icon.classList.add('fas'); }
                    showToast(window.chatTranslations?.post_saved_success || 'Post saved', 'success');
                } else {
                    btn.classList.remove('saved');
                    if (icon) { icon.classList.remove('fas'); icon.classList.add('far'); }
                    showToast(window.chatTranslations?.post_removed_from_saved || 'Removed from saved', 'info');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert on error
                if (isCurrentlySaved) {
                    btn.classList.add('saved');
                    if (icon) { icon.classList.remove('far'); icon.classList.add('fas'); }
                } else {
                    btn.classList.remove('saved');
                    if (icon) { icon.classList.remove('fas'); icon.classList.add('far'); }
                }
            });
        };

        window.sharePost = function(slug, username) {
            const url = window.location.origin + '/posts/' + slug;
            const title = 'Post by ' + (username || 'user');

            // Use native share API on mobile (best UX)
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: 'Check out this post',
                    url: url
                }).catch(function(err) {
                    // User cancelled share or error — fallback to copy
                    if (err.name !== 'AbortError') {
                        copyToClipboard(url);
                    }
                });
            } else {
                // Desktop fallback: copy link to clipboard
                copyToClipboard(url);
            }
        };

        function copyToClipboard(url) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    showToast(window.chatTranslations?.post_link_copied || 'Link copied', 'success');
                }).catch(function() {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        }

        window.copyPostLink = function(slug) {
            const url = window.location.origin + '/posts/' + slug;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast(window.chatTranslations?.post_link_copied || 'Link copied', 'success');
                }).catch(() => {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        };

        function fallbackCopy(text) {
            try {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                const ok = document.execCommand('copy');
                document.body.removeChild(ta);
                if (ok) {
                    showToast(window.chatTranslations?.post_link_copied || 'Link copied', 'success');
                } else {
                    showToast(window.chatTranslations?.failed_to_copy_link || 'Failed to copy', 'error');
                }
            } catch (e) {
                showToast(window.chatTranslations?.failed_to_copy_link || 'Failed to copy', 'error');
            }
        }

        window.showLikers = function(slug) {
            fetch(`/posts/${slug}/likers`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.likers && data.likers.length > 0) {
                    showLikersModal(data.likers);
                } else {
                    showToast(window.chatTranslations?.no_likes_yet || 'No likes yet', 'info');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(window.chatTranslations?.could_not_load_likers || 'Could not load likers', 'error');
            });
        };

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showLikersModal(likers) {
            const existingModal = document.getElementById('likers-modal');
            if (existingModal) existingModal.remove();

            const modal = document.createElement('div');
            modal.id = 'likers-modal';
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:10000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);';

            const content = document.createElement('div');
            content.style.cssText = 'background:var(--surface,#161616);border:1px solid var(--border,#2a2a2a);border-radius:16px;width:90%;max-width:400px;max-height:80vh;overflow-y:auto;padding:20px;';

            const header = document.createElement('div');
            header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border,#2a2a2a);';
            header.innerHTML = '<h3 style="margin:0;font-size:18px;font-weight:700;color:var(--text);">' + (window.chatTranslations?.likes || 'Likes') + ' (' + likers.length + ')</h3><button onclick="document.getElementById(\'likers-modal\').remove()" style="background:none;border:none;color:var(--text-muted,#86868b);font-size:24px;cursor:pointer;padding:0;line-height:1;">&times;</button>';

            const list = document.createElement('div');
            list.style.cssText = 'display:flex;flex-direction:column;gap:8px;';

            likers.forEach(liker => {
                const avatar = liker.avatar || null;
                const displayName = liker.username || liker.name || 'User';
                const initial = displayName ? displayName.charAt(0).toUpperCase() : '?';

                const item = document.createElement('a');
                item.href = '/users/' + liker.username;
                item.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;text-decoration:none;color:inherit;transition:background 0.2s;';
                item.onmouseover = () => item.style.background = 'var(--surface-hover,#1c1c1e)';
                item.onmouseout = () => item.style.background = 'transparent';

                item.innerHTML = (avatar
                    ? '<img src="' + avatar + '" alt="' + displayName + '" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">'
                    : '<div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary,#5e60ce),var(--secondary,#4ea8de));display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:white;">' + initial + '</div>')
                    + '<div style="flex:1;min-width:0;"><div style="font-weight:600;font-size:14px;direction:ltr;text-align:left;">@' + escapeHtml(displayName) + '</div>'
                    + (liker.name ? '<div style="font-size:12px;color:var(--text-muted,#86868b);">' + escapeHtml(liker.name) + '</div>' : '')
                    + '</div>'
                    + (liker.is_verified ? '<i class="fas fa-check-circle" style="color:#22c55e;font-size:16px;flex-shrink:0;"></i>' : '');

                list.appendChild(item);
            });

            content.appendChild(header);
            content.appendChild(list);
            modal.appendChild(content);
            document.body.appendChild(modal);

            modal.onclick = (e) => {
                if (e.target === modal) modal.remove();
            };
        }

        window.toggleComments = function(postId, show) {
            const hiddenComments = document.getElementById('hidden-comments-' + postId);
            const showMoreBtn = document.querySelector('#post-' + postId + ' .show-more-comments');

            if (hiddenComments) {
                hiddenComments.style.display = show ? 'block' : 'none';
            }

            if (showMoreBtn) {
                showMoreBtn.style.display = show ? 'none' : 'block';
            }
        };

        window.likeComment = function(commentId, btn) {
            const countSpan = btn.querySelector('span');
            const isCurrentlyLiked = btn.classList.contains('liked');
            const currentCount = countSpan ? parseInt(countSpan.textContent) || 0 : 0;

            if (isCurrentlyLiked) {
                btn.classList.remove('liked');
                if (countSpan) countSpan.textContent = currentCount - 1;
            } else {
                btn.classList.add('liked');
                if (countSpan) countSpan.textContent = currentCount + 1;
            }

            fetch(`/comments/${commentId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    if (isCurrentlyLiked) {
                        btn.classList.add('liked');
                        if (countSpan) countSpan.textContent = currentCount;
                    } else {
                        btn.classList.remove('liked');
                        if (countSpan) countSpan.textContent = currentCount;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (isCurrentlyLiked) {
                    btn.classList.add('liked');
                    if (countSpan) countSpan.textContent = currentCount;
                } else {
                    btn.classList.remove('liked');
                    if (countSpan) countSpan.textContent = currentCount;
                }
            });
        };

        window.deleteComment = function(commentId, btn) {
            const t = getTranslations();
            if (!confirm(t.delete_comment_confirm || 'Delete this comment?')) return;

            fetch(`/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentItem = btn.closest('.comment-item');
                    if (commentItem) commentItem.remove();
                }
            })
            .catch(error => console.error('Error:', error));
        };

        window.togglePostContent = function(btn) {
            const postText = btn.previousElementSibling;
            const showMoreText = btn.querySelector('.show-more-text');
            const showLessText = btn.querySelector('.show-less-text');

            if (postText.classList.contains('truncated')) {
                postText.classList.remove('truncated');
                postText.classList.add('expanded');
                if (showMoreText) showMoreText.style.display = 'none';
                if (showLessText) showLessText.style.display = 'inline';
            } else {
                postText.classList.remove('expanded');
                postText.classList.add('truncated');
                if (showMoreText) showMoreText.style.display = 'inline';
                if (showLessText) showLessText.style.display = 'none';
            }
        };

        window.openMediaModal = function(postId, index) {
            const mediaContainer = document.querySelector('.post-media[data-post-id="' + postId + '"]');
            if (!mediaContainer) return;

            const mediaData = JSON.parse(mediaContainer.getAttribute('data-media-list'));
            if (!mediaData || mediaData.length === 0) return;

            window.currentMediaList = mediaData;
            window.currentMediaIndex = parseInt(index);

            const modal = document.getElementById('media-modal');
            if (!modal) return;
            
            updateMediaModal();

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        // Event delegation for media triggers (fallback/robustness)
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('.media-modal-trigger');
            if (trigger && !trigger.hasAttribute('onclick')) {
                // If it doesn't have inline onclick (already handled), handle it here
                const mediaItem = trigger.closest('.media-item');
                const postMedia = trigger.closest('.post-media');
                if (mediaItem && postMedia) {
                    const postId = postMedia.getAttribute('data-post-id');
                    const index = trigger.getAttribute('data-media-index') || 0;
                    window.openMediaModal(postId, index);
                }
            }
        });

        function updateMediaModal() {
            const modal = document.getElementById('media-modal');
            const mediaContent = modal.querySelector('.media-modal-content');

            if (!window.currentMediaList || window.currentMediaList.length === 0) return;

            const currentItem = window.currentMediaList[window.currentMediaIndex];
            if (!currentItem) return;

            mediaContent.innerHTML = '<button class="media-modal-close" onclick="closeMediaModal()" title="Close"><i class="fas fa-times"></i></button>'
                + (window.currentMediaIndex > 0 ? '<button class="media-modal-nav media-modal-prev" onclick="navigateMedia(-1)" title="Previous"><i class="fas fa-chevron-left"></i></button>' : '')
                + (window.currentMediaIndex < window.currentMediaList.length - 1 ? '<button class="media-modal-nav media-modal-next" onclick="navigateMedia(1)" title="Next"><i class="fas fa-chevron-right"></i></button>' : '')
                + '<div class="media-modal-counter">' + (window.currentMediaIndex + 1) + ' / ' + window.currentMediaList.length + '</div>';

            if (currentItem.type === 'image') {
                const img = document.createElement('img');
                img.src = currentItem.src;
                img.alt = 'Media';
                img.onclick = (e) => e.stopPropagation();
                mediaContent.appendChild(img);
            } else if (currentItem.type === 'video') {
                const video = document.createElement('video');
                video.src = currentItem.src;
                video.controls = true;
                video.autoplay = true;
                video.onclick = (e) => e.stopPropagation();
                mediaContent.appendChild(video);
            }
        }

        window.closeMediaModal = function(event) {
            if (event && event.target !== event.currentTarget) return;
            const modal = document.getElementById('media-modal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
            window.currentMediaList = null;
            window.currentMediaIndex = null;
        };

        window.navigateMedia = function(direction) {
            if (!window.currentMediaList) return;
            const newIndex = window.currentMediaIndex + direction;
            if (newIndex >= 0 && newIndex < window.currentMediaList.length) {
                window.currentMediaIndex = newIndex;
                updateMediaModal();
            }
        };

        window.quickFollow = function(username, btn) {
            const isFollowing = btn.getAttribute('data-following') === 'true';
            const span = btn.querySelector('span');

            fetch('/users/' + username + '/follow', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const nowFollowing = data.following;
                    
                    // Update all follow buttons for this user on the page
                    document.querySelectorAll('.quick-follow-btn[data-username="' + username + '"]').forEach(button => {
                        if (nowFollowing) {
                            button.classList.add('following');
                            button.setAttribute('data-following', 'true');
                            const btnSpan = button.querySelector('span');
                            if (btnSpan) btnSpan.textContent = window.chatTranslations?.following || 'Following';
                        } else {
                            button.classList.remove('following');
                            button.setAttribute('data-following', 'false');
                            const btnSpan = button.querySelector('span');
                            if (btnSpan) btnSpan.textContent = window.chatTranslations?.follow || 'Follow';
                        }
                    });

                    // Broadcast via socket if available
                    if (window.socket && window.socket.connected) {
                        window.socket.emit('user:follow', {
                            followerId: window.currentUserId,
                            followedUsername: username,
                            following: nowFollowing
                        });
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        };

        // Listen for follow/unfollow events from socket
        if (typeof window.updateFollowButtons === 'undefined') {
            window.updateFollowButtons = function(data) {
                const { followerId, followedUsername, following } = data;
                
                // Don't update if it's the current user's action (already updated)
                if (followerId === window.currentUserId) return;

                // Update all follow buttons for this user
                document.querySelectorAll('.quick-follow-btn[data-username="' + followedUsername + '"]').forEach(button => {
                    const span = button.querySelector('span');
                    if (following) {
                        button.classList.add('following');
                        button.setAttribute('data-following', 'true');
                        if (span) span.textContent = window.chatTranslations?.following || 'Following';
                    } else {
                        button.classList.remove('following');
                        button.setAttribute('data-following', 'false');
                        if (span) span.textContent = window.chatTranslations?.follow || 'Follow';
                    }
                });
            };
        }

        // Submit comment function
        /**
         * Helper to render comment HTML dynamically for real-time updates
         */
        window.renderCommentHtml = function(comment, level = 0, currentUserId = null) {
            const t = getTranslations();
            const isAnonymous = !!comment.is_anonymous;
            const author = comment.user || {};
            const isAuthor = currentUserId && String(comment.user_id) === String(currentUserId);
            
            let avatarHtml = '';
            let authorNameHtml = '';
            
            if (isAnonymous) {
                avatarHtml = '<div class="comment-avatar-placeholder"><i class="fas fa-user-secret"></i></div>';
                authorNameHtml = '<span class="comment-name">' + (t.anonymous_participant || 'Anonymous Participant') + '</span>';
            } else {
                const avatarUrl = author.avatar_url || '/assets/images/default-avatar.png';
                const username = author.username || 'user';
                const roleBadgeHtml = comment.role_badge_html || '';
                
                avatarHtml = '<img src="' + avatarUrl + '" alt="Avatar" class="comment-avatar">';
                authorNameHtml = 
                    '<div class="comment-name-row">' +
                        '<a href="/users/' + username + '" class="comment-name">' + username + '</a>' +
                        roleBadgeHtml +
                    '</div>';
            }

            const deleteBtnHtml = isAuthor 
                ? '<button type="button" class="delete-comment-btn" onclick="deleteComment(' + comment.id + ', this)" title="Delete"><i class="fas fa-trash-alt"></i></button>'
                : '';

            const replyBtnHtml = (level < 4) 
                ? '<button type="button" class="comment-action-btn" onclick="toggleReplyForm(' + comment.id + ')"><i class="fas fa-reply"></i><span>' + (t.reply || 'Reply') + '</span></button>'
                : '';

            const createdAt = comment.created_at || new Date().toISOString();
            const isArabic = /[\u0600-\u06FF]/.test(comment.content || '');

            return `
                <div class="comment-item ${level > 0 ? 'nested' : ''} level-${level}" data-comment-id="${comment.id}">
                    <div class="comment-header">
                        <div class="comment-author">
                            ${avatarHtml}
                            <div class="comment-author-info">
                                ${authorNameHtml}
                                <span class="comment-time" data-timestamp="${createdAt}">${t.just_now || 'Just now'}</span>
                            </div>
                        </div>
                        ${deleteBtnHtml}
                    </div>
                    <div class="comment-content">
                        <p style="${isArabic ? 'direction: rtl; text-align: right;' : ''}">${comment.content}</p>
                    </div>
                    <div class="comment-actions-bar">
                        <button type="button" class="comment-action-btn" onclick="likeComment(${comment.id}, this)">
                            <i class="fas fa-heart"></i>
                            <span class="comment-likes-count">${comment.likes_count || 0}</span>
                        </button>
                        ${replyBtnHtml}
                    </div>
                    ${level < 4 ? `
                        <div class="reply-form" id="reply-form-${comment.id}" style="display: none;">
                            <div class="reply-input-wrapper">
                                <textarea id="reply-content-${comment.id}" placeholder="${t.write_a_reply || 'Write a reply...'}" maxlength="5000"></textarea>
                                <button type="button" onclick="submitReply(${comment.id}, ${comment.post_id})">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <button type="button" class="cancel-reply" onclick="toggleReplyForm(${comment.id})">${t.cancel || 'Cancel'}</button>
                        </div>
                    ` : ''}
                    <div class="replies-container"></div>
                </div>
            `;
        };

        window.submitComment = function(postSlug, postId) {
            const textarea = document.getElementById('comment-content-' + postSlug);
            const content = textarea?.value.trim();
            const anonToggle = document.getElementById('comment-anon-' + postSlug);
            const isAnonymous = anonToggle ? anonToggle.checked : false;

            if (!content) {
                if (typeof window.showToast === 'function') window.showToast(t.please_write_comment || 'Please write a comment', 'error');
                return;
            }

            fetch('/comments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    content: content, 
                    post_id: postId,
                    is_anonymous: isAnonymous
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.comment) {
                    if (textarea) textarea.value = '';
                    if (anonToggle) anonToggle.checked = false; // Reset toggle

                    if (!document.querySelector('[data-comment-id="' + data.comment.id + '"]')) {
                        const commentsList = document.querySelector('#post-' + postId + ' .comments-list');
                        if (commentsList) {
                            const html = renderCommentHtml(data.comment, 0, window.NexusSocket?.config?.userId);
                            commentsList.insertAdjacentHTML('afterbegin', html);
                            updateCommentCountUI(postId, 1);
                        }
                    }
                } else {
                    if (typeof window.showToast === 'function') window.showToast(data.message || 'Failed to post comment', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof window.showToast === 'function') window.showToast('Error posting comment', 'error');
            });
        };

        window.submitReply = function(commentId, postId) {
            const textarea = document.getElementById('reply-content-' + commentId);
            const content = textarea?.value.trim();
            const anonToggle = document.getElementById('reply-anon-' + commentId);
            const isAnonymous = anonToggle ? anonToggle.checked : false;

            if (!content) {
                if (typeof window.showToast === 'function') window.showToast(t.please_write_reply || 'Please write a reply', 'error');
                return;
            }

            fetch('/comments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    content: content, 
                    post_id: postId, 
                    parent_id: commentId,
                    is_anonymous: isAnonymous
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.comment) {
                    if (textarea) textarea.value = '';
                    if (anonToggle) anonToggle.checked = false; // Reset toggle
                    const replyForm = document.getElementById('reply-form-' + commentId);
                    if (replyForm) replyForm.style.display = 'none';

                    if (!document.querySelector('[data-comment-id="' + data.comment.id + '"]')) {
                        appendReplyToDOM(commentId, data.comment, window.NexusSocket?.config?.userId);
                        updateCommentCountUI(postId, 1);
                    }
                } else {
                    if (typeof window.showToast === 'function') window.showToast(data.message || t.failed_to_post_reply || 'Failed to post reply', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof window.showToast === 'function') window.showToast(t.failed_to_post_reply || 'Error posting reply', 'error');
            });
        };
        function updateCommentCountUI(postId, delta) {
            const engagementComments = document.querySelector('#post-' + postId + ' .engagement-comments');
            if (engagementComments) {
                const text = engagementComments.textContent;
                const match = text.match(/(\d+)/);
                const currentCount = match ? parseInt(match[1]) : 0;
                const wordPart = text.replace(/\d+/, '').trim();
                engagementComments.textContent = (currentCount + delta) + (wordPart.startsWith(' ') ? '' : ' ') + wordPart;
            }
        }

        window.handlePostCommented = function(data) {
            const postId = data.post_id;
            const comment = data.comment;
            
            if (!postId || !comment) return;

            // Update comment count UI
            updateCommentCountUI(postId, 1);

            // Check if comment already exists to prevent duplicates (sender already added it)
            if (document.querySelector('[data-comment-id="' + comment.id + '"]')) {
                return;
            }

            const currentUserId = window.NexusSocket?.config?.userId;

            // Handle replies (comments with parent_id)
            if (comment.parent_id) {
                if (typeof appendReplyToDOM === 'function') {
                    appendReplyToDOM(comment.parent_id, comment, currentUserId);
                }
                return;
            }

            // Handle top-level comments
            const commentsList = document.querySelector('#post-' + postId + ' .comments-list');
            if (commentsList) {
                const html = renderCommentHtml(comment, 0, currentUserId);
                commentsList.insertAdjacentHTML('afterbegin', html);
            }
        };

        window.handleCommentLiked = function(data) {
            const commentId = data.comment_id;
            const likesCount = data.likes_count;
            
            if (!commentId) return;

            const commentItem = document.querySelector('[data-comment-id="' + commentId + '"]');
            if (commentItem) {
                const countLabel = commentItem.querySelector('.comment-likes-count');
                if (countLabel) {
                    countLabel.textContent = likesCount > 0 ? likesCount : '';
                }
            }
        };

        window.handlePostDeleted = function(data) {
            const postId = data.post_id;
            if (!postId) return;

            const postElement = document.getElementById('post-' + postId);
            if (postElement) {
                postElement.classList.add('removing');
                setTimeout(() => {
                    postElement.remove();
                    
                    // Check if feed is now empty
                    const container = document.getElementById('posts-container') || document.getElementById('posts-feed');
                    if (container && container.querySelectorAll('.post-card').length === 0) {
                        // We could show an empty state here if we had one
                    }
                }, 300);
            }
        };

        window.handleCommentDeleted = function(data) {
            const commentId = data.comment_id;
            const postId = data.post_id;
            
            if (!commentId) return;

            const commentElement = document.querySelector('[data-comment-id="' + commentId + '"]');
            if (commentElement) {
                commentElement.classList.add('removing');
                setTimeout(() => {
                    commentElement.remove();
                }, 300);
            }

            if (postId) {
                updateCommentCountUI(postId, -1);
            }
        };

        function appendReplyToDOM(parentId, replyData, currentUserId) {
            const parentComment = document.querySelector('[data-comment-id="' + parentId + '"]');
            if (!parentComment) return;

            let repliesContainer = parentComment.querySelector('.replies-container');
            const showRepliesAlways = parentComment.querySelector('.show-replies-always');

            if (!repliesContainer) {
                repliesContainer = document.createElement('div');
                repliesContainer.className = 'replies-container';
                parentComment.appendChild(repliesContainer);
            }

            if (showRepliesAlways) showRepliesAlways.style.display = 'none';
            
            // Get level of parent to determine child level
            let parentLevel = 0;
            const parentClasses = parentComment.className.split(' ');
            parentClasses.forEach(c => {
                if (c.startsWith('level-')) parentLevel = parseInt(c.split('-')[1]) || 0;
            });

            const html = renderCommentHtml(replyData, parentLevel + 1, currentUserId);
            repliesContainer.insertAdjacentHTML('afterbegin', html);
        }

        // Real-time listener for comments
        if (window.NexusSocket) {
            window.NexusSocket.on('post:commented', (data) => {
                if (!data.comment) return;
                
                // Skip if we already added it (self-post)
                if (document.querySelector('[data-comment-id="' + data.comment.id + '"]')) {
                    return;
                }

                if (data.comment.parent_id) {
                    appendReplyToDOM(data.comment.parent_id, data.comment, window.NexusSocket?.config?.userId);
                } else {
                    const commentsList = document.querySelector('#post-' + data.post_id + ' .comments-list');
                    if (commentsList) {
                        const html = renderCommentHtml(data.comment, 0, window.NexusSocket?.config?.userId);
                        commentsList.insertAdjacentHTML('afterbegin', html);
                    }
                }
                
                // Update count from server data
                const engagementComments = document.querySelector('#post-' + data.post_id + ' .engagement-comments');
                if (engagementComments && data.count !== undefined) {
                    const text = engagementComments.textContent;
                    const wordPart = text.replace(/\d+/, '').trim();
                    engagementComments.textContent = data.count + (wordPart.startsWith(' ') ? '' : ' ') + wordPart;
                }
            });
        }

        
        window.toggleReplyForm = function(commentId) {
            const form = document.getElementById('reply-form-' + commentId);
            if (form) {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
                if (form.style.display === 'block') {
                    const textarea = form.querySelector('textarea');
                    if (textarea) textarea.focus();
                }
            }
        };

        window.toggleNestedReplies = function(commentId, show) {
            const hiddenReplies = document.getElementById('hidden-replies-' + commentId);
            const parentComment = document.querySelector('[data-comment-id="' + commentId + '"]');
            if (!parentComment) return;

            const showMoreBtn = parentComment.querySelector('.show-more-replies');
            const showRepliesAlways = parentComment.querySelector('.show-replies-always');

            if (hiddenReplies) {
                hiddenReplies.style.display = show ? 'block' : 'none';
            }

            if (showMoreBtn) showMoreBtn.style.display = 'none';
            if (showRepliesAlways) showRepliesAlways.style.display = show ? 'none' : 'block';
        };

        // Report Modal Functions
        let currentPostSlug = null;
        window.pinPost = function(arg1, arg2) {
            let e, postId;
            if (arg1 && typeof arg1.preventDefault === 'function') {
                e = arg1;
                postId = arg2;
            } else if (arg2 && typeof arg2.preventDefault === 'function') {
                e = arg2;
                postId = arg1;
            } else {
                postId = arg1;
            }

            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
                e.stopPropagation();
            }

            // Close all dropdown menus immediately
            document.querySelectorAll('.post-menu-dropdown').forEach(menu => menu.style.display = 'none');

            if (!window.currentUserUsername) {
                console.error('Username not found');
                if (typeof window.showToast === 'function') window.showToast('You must be logged in', 'error');
                return;
            }

            // Add loading state to the post card
            const cards = document.querySelectorAll(`[data-post-id="${postId}"]`);
            cards.forEach(card => card.classList.add('post-loading'));

            fetch('/users/' + window.currentUserUsername + '/posts/' + postId + '/pin', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.showToast === 'function') window.showToast(data.message || t.post_pinned || 'Post pinned', 'success');
                    updatePinnedUI(postId, true);
                } else {
                    if (typeof window.showToast === 'function') window.showToast(data.message || t.failed_to_pin_post || 'Failed to pin post', 'error');
                    cards.forEach(card => card.classList.remove('post-loading'));
                }
            })
            .catch((err) => {
                console.error('Pin error:', err);
                if (typeof window.showToast === 'function') window.showToast('Failed to pin post', 'error');
                cards.forEach(card => card.classList.remove('post-loading'));
            });
        };

        window.unpinPost = function(arg1, arg2) {
            let e, postId;
            if (arg1 && typeof arg1.preventDefault === 'function') {
                e = arg1;
                postId = arg2;
            } else if (arg2 && typeof arg2.preventDefault === 'function') {
                e = arg2;
                postId = arg1;
            } else {
                postId = arg1;
            }

            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
                e.stopPropagation();
            }

            // Close all dropdown menus immediately
            document.querySelectorAll('.post-menu-dropdown').forEach(menu => menu.style.display = 'none');

            if (!window.currentUserUsername) {
                console.error('Username not found');
                return;
            }

            // Add loading state
            const cards = document.querySelectorAll(`[data-post-id="${postId}"]`);
            cards.forEach(card => card.classList.add('post-loading'));

            fetch('/users/' + window.currentUserUsername + '/posts/' + postId + '/unpin', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.showToast === 'function') window.showToast(data.message || t.post_unpinned || 'Post unpinned', 'success');
                    updatePinnedUI(postId, false);
                } else {
                    if (typeof window.showToast === 'function') window.showToast(data.message || t.failed_to_unpin_post || 'Failed to unpin post', 'error');
                    cards.forEach(card => card.classList.remove('post-loading'));
                }
            })
            .catch((err) => {
                console.error('Unpin error:', err);
                if (typeof window.showToast === 'function') window.showToast('Failed to unpin post', 'error');
                cards.forEach(card => card.classList.remove('post-loading'));
            });
        };

        /**
         * Unified UI update for pin/unpin
         */
        function updatePinnedUI(postId, isPinned) {
            // Check if we are on the profile page
            const isProfilePage = !!document.getElementById('pinnedPostsContainer');
            
            if (isProfilePage) {
                // On profile page, we reload the page as requested
                window.location.reload();
                return;
            }

            // On other pages (like Feed), we update the UI without reload
            const allInstances = document.querySelectorAll(`[data-post-id="${postId}"]`);
            
            allInstances.forEach(card => {
                const badge = card.querySelector('.pinned-icon-simple');
                const pinMenu = card.querySelector(`[id^="pin-menu-item-"]`);
                const unpinMenu = card.querySelector(`[id^="unpin-menu-item-"]`);
                
                if (isPinned) {
                    card.classList.add('pinned-post');
                    if (badge) badge.style.display = 'inline-block';
                    if (pinMenu) pinMenu.style.display = 'none';
                    if (unpinMenu) unpinMenu.style.display = 'block';
                } else {
                    card.classList.remove('pinned-post');
                    if (badge) badge.style.display = 'none';
                    if (pinMenu) pinMenu.style.display = 'block';
                    if (unpinMenu) unpinMenu.style.display = 'none';
                }
                
                card.classList.remove('post-loading');
            });
        }

        // No real-time listeners for pinning (as requested)

        /**
         * Reorder DOM elements based on IDs
         */
        function applyNewOrder(postIds) {
            const container = document.getElementById('pinnedPostsContainer');
            if (!container) return;

            // Sort existing elements
            const posts = Array.from(container.querySelectorAll('.pinned-post'));
            const postMap = new Map();
            posts.forEach(p => postMap.set(p.getAttribute('data-post-id'), p));

            postIds.forEach(id => {
                const postEl = postMap.get(String(id));
                if (postEl) {
                    container.appendChild(postEl); // appendChild moves it if it already exists
                    // Add a subtle flash animation to show it moved
                    postEl.style.transition = 'background 0.5s ease';
                    const originalBg = postEl.style.background;
                    postEl.style.background = 'rgba(94, 96, 206, 0.1)';
                    setTimeout(() => postEl.style.background = originalBg, 500);
                }
            });
        }

        window.focusCommentInput = function(slug) {
            const textarea = document.getElementById('comment-content-' + slug);
            if (textarea) {
                textarea.focus();
                // Smooth scroll to the input if it's not well in view
                textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        };

        window.togglePostMenu = function(postId) {
            // Close all other menus first
            document.querySelectorAll('.post-menu-dropdown').forEach(menu => {
                if (menu.id !== 'post-menu-' + postId) {
                    menu.style.display = 'none';
                }
            });
            
            const menu = document.getElementById('post-menu-' + postId);
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            }
        };

        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.post-header-actions')) {
                document.querySelectorAll('.post-menu-dropdown').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });

        window.openReportModal = function(slug, postId) {
            // Close the menu first
            document.querySelectorAll('.post-menu-dropdown').forEach(menu => {
                menu.style.display = 'none';
            });
            
            currentPostSlug = slug;
            const modal = document.getElementById('report-modal');
            const form = document.getElementById('report-form');

            if (modal && form) {
                form.action = '/posts/' + slug + '/report';
                form.reset();
                document.getElementById('other-reason-group').style.display = 'none';
                document.getElementById('submit-report-btn').disabled = true;
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        };

        window.closeReportModal = function() {
            const modal = document.getElementById('report-modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
                currentPostSlug = null;
            }
        };

        // ========================================
        // Reorder Pinned Posts
        // ========================================
        let isReorderMode = false;

        window.toggleReorderMode = function() {
            isReorderMode = !isReorderMode;
            const container = document.getElementById('pinnedPostsContainer');
            const reorderBtn = document.getElementById('reorderBtn');
            if (!container || !reorderBtn) return;

            if (isReorderMode) {
                // Enable reorder UI
                container.style.border = '2px dashed var(--primary)';
                container.style.padding = '10px';
                container.style.borderRadius = '20px';
                container.style.background = 'rgba(94, 96, 206, 0.03)';

                // Add drag handles and arrows to posts
                document.querySelectorAll('.pinned-posts-container .pinned-post').forEach((post, index, all) => {
                    post.style.cursor = 'grab';
                    post.style.opacity = '0.9';
                    post.setAttribute('draggable', 'true');
                    post.style.position = 'relative';

                    // Inject arrow controls
                    const controls = document.createElement('div');
                    controls.className = 'pinned-reorder-controls';
                    controls.style.cssText = 'position: absolute; right: 16px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 100;';
                    
                    const btnStyle = 'width: 40px; height: 40px; border-radius: 12px; background: rgba(22, 22, 22, 0.8); backdrop-filter: blur(8px); border: 1px solid var(--primary); color: var(--primary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); font-size: 16px;';
                    
                    const upBtn = document.createElement('button');
                    upBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
                    upBtn.style.cssText = btnStyle;
                    upBtn.onclick = (e) => { e.stopPropagation(); window.movePinnedPost(post, 'up'); };
                    upBtn.onmouseover = () => { upBtn.style.background = 'var(--primary)'; upBtn.style.color = 'white'; };
                    upBtn.onmouseout = () => { upBtn.style.background = 'rgba(22, 22, 22, 0.8)'; upBtn.style.color = 'var(--primary)'; };
                    if (index === 0) upBtn.style.opacity = '0.2', upBtn.style.pointerEvents = 'none';

                    const downBtn = document.createElement('button');
                    downBtn.innerHTML = '<i class="fas fa-arrow-down"></i>';
                    downBtn.style.cssText = btnStyle;
                    downBtn.onclick = (e) => { e.stopPropagation(); window.movePinnedPost(post, 'down'); };
                    downBtn.onmouseover = () => { downBtn.style.background = 'var(--primary)'; downBtn.style.color = 'white'; };
                    downBtn.onmouseout = () => { downBtn.style.background = 'rgba(22, 22, 22, 0.8)'; downBtn.style.color = 'var(--primary)'; };
                    if (index === all.length - 1) downBtn.style.opacity = '0.2', downBtn.style.pointerEvents = 'none';

                    controls.appendChild(upBtn);
                    controls.appendChild(downBtn);
                    post.appendChild(controls);
                });

                reorderBtn.innerHTML = `<i class="fas fa-check"></i> <span>${t.done || 'Done'}</span>`;
                reorderBtn.style.background = 'var(--primary)';
                reorderBtn.style.color = 'white';

                // Initialize drag and drop
                initDragAndDrop();
            } else {
                // Disable reorder UI
                container.style.border = 'none';
                container.style.padding = '';
                container.style.background = '';

                document.querySelectorAll('.pinned-posts-container .pinned-post').forEach(post => {
                    post.style.cursor = '';
                    post.style.opacity = '';
                    post.removeAttribute('draggable');
                    const controls = post.querySelector('.pinned-reorder-controls');
                    if (controls) controls.remove();
                });

                reorderBtn.innerHTML = `<i class="fas fa-sort"></i> <span>${t.reorder || 'Reorder'}</span>`;
                reorderBtn.style.background = '';
                reorderBtn.style.color = '';

                // Save new order and then reload
                savePinnedOrder();
                setTimeout(() => window.location.reload(), 800);
            }
        };

        window.movePinnedPost = function(post, direction) {
            const container = document.getElementById('pinnedPostsContainer');
            if (!container || !post) return;

            const posts = Array.from(container.querySelectorAll('.pinned-post'));
            const index = posts.indexOf(post);
            if (index === -1) return;

            let targetPost = null;
            if (direction === 'up' && index > 0) {
                targetPost = posts[index - 1];
            } else if (direction === 'down' && index < posts.length - 1) {
                targetPost = posts[index + 1];
            }

            if (!targetPost) return;

            // Visual feedback - swap animation
            const postRect = post.getBoundingClientRect();
            const targetRect = targetPost.getBoundingClientRect();
            const deltaY = postRect.top - targetRect.top;

            post.style.transition = 'none';
            targetPost.style.transition = 'none';
            
            post.style.transform = `translateY(${-deltaY}px)`;
            targetPost.style.transform = `translateY(${deltaY}px)`;

            // Force reflow
            post.offsetHeight;

            post.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            targetPost.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

            post.style.transform = 'translateY(0)';
            targetPost.style.transform = 'translateY(0)';

            // Reorder in DOM after animation
            setTimeout(() => {
                if (direction === 'up') {
                    container.insertBefore(post, targetPost);
                } else {
                    container.insertBefore(targetPost, post);
                }
                post.style.transition = '';
                targetPost.style.transition = '';
                window.refreshReorderArrows();
            }, 300);
        };

        window.refreshReorderArrows = function() {
            const container = document.getElementById('pinnedPostsContainer');
            if (!container) return;
            const posts = container.querySelectorAll('.pinned-post');
            posts.forEach((post, index) => {
                const controls = post.querySelector('.pinned-reorder-controls');
                if (controls) {
                    const up = controls.firstChild;
                    const down = controls.lastChild;
                    
                    up.style.opacity = index === 0 ? '0.2' : '1';
                    up.style.pointerEvents = index === 0 ? 'none' : 'auto';
                    
                    down.style.opacity = index === posts.length - 1 ? '0.2' : '1';
                    down.style.pointerEvents = index === posts.length - 1 ? 'none' : 'auto';
                }
            });
        };

        function initDragAndDrop() {
            const posts = document.querySelectorAll('.pinned-post');
            let draggedPost = null;

            posts.forEach(post => {
                post.addEventListener('dragstart', function(e) {
                    draggedPost = this;
                    setTimeout(() => this.style.opacity = '0.5', 0);
                    e.dataTransfer.effectAllowed = 'move';
                });

                post.addEventListener('dragend', function() {
                    this.style.opacity = '0.9';
                    draggedPost = null;
                    document.querySelectorAll('.pinned-post').forEach(p => p.style.borderTop = 'none');
                });

                post.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    this.style.borderTop = '3px solid var(--primary)';
                });

                post.addEventListener('dragleave', function() {
                    this.style.borderTop = 'none';
                });

                post.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.style.borderTop = 'none';

                    if (draggedPost !== this) {
                        const container = document.getElementById('pinnedPostsContainer');
                        const allPosts = Array.from(container.querySelectorAll('.pinned-post'));
                        const draggedIndex = allPosts.indexOf(draggedPost);
                        const dropIndex = allPosts.indexOf(this);

                        if (draggedIndex < dropIndex) {
                            container.insertBefore(draggedPost, this.nextSibling);
                        } else {
                            container.insertBefore(draggedPost, this);
                        }
                    }
                });
            });
        }

        function savePinnedOrder() {
            const container = document.getElementById('pinnedPostsContainer');
            if (!container) return;
            
            const postIds = Array.from(container.querySelectorAll('.pinned-post')).map(post => {
                return post.getAttribute('data-post-id');
            });

            if (postIds.length === 0) return;

            fetch(`/users/${window.currentUserUsername}/pinned-posts/reorder`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ post_ids: postIds })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.showToast === 'function') window.showToast(data.message || 'Order saved', 'success');
                }
            })
            .catch((err) => {
                console.error('Reorder error:', err);
                if (typeof window.showToast === 'function') window.showToast('Failed to save order', 'error');
            });
        }

        window.toggleOtherReason = function() {
            const reasonSelect = document.getElementById('report-reason');
            const otherGroup = document.getElementById('other-reason-group');
            const submitBtn = document.getElementById('submit-report-btn');
            
            if (reasonSelect && otherGroup) {
                if (reasonSelect.value === 'other') {
                    otherGroup.style.display = 'block';
                } else {
                    otherGroup.style.display = 'none';
                }
            }
            
            if (submitBtn && reasonSelect) {
                submitBtn.disabled = !reasonSelect.value;
            }
        };

        // Character count for report content
        document.addEventListener('DOMContentLoaded', function() {
            const contentTextarea = document.getElementById('report-content');
            const charCount = document.getElementById('char-count');
            
            if (contentTextarea && charCount) {
                contentTextarea.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                });
            }

            // Close modal on outside click
            const modal = document.getElementById('report-modal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeReportModal();
                    }
                });
            }

            // Handle form submission
            const reportForm = document.getElementById('report-form');
            if (reportForm) {
                reportForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = document.getElementById('submit-report-btn');

                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                    }

                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { 
                                throw err; 
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            closeReportModal();
                            if (typeof window.showToast === 'function') {
                                window.showToast('Report submitted successfully', 'success');
                            }
                        } else {
                            const message = data.message || data.error || 'Failed to submit report';
                            if (typeof window.showToast === 'function') {
                                window.showToast(message, 'error');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let errorMessage = 'Failed to submit report';
                        
                        // Handle validation errors
                        if (error.errors) {
                            const firstError = Object.values(error.errors)[0];
                            errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                        } else if (error.message) {
                            errorMessage = error.message;
                        } else if (error.error) {
                            errorMessage = error.error;
                        }
                        
                        if (typeof window.showToast === 'function') {
                            window.showToast(errorMessage, 'error');
                        }
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-flag"></i> Submit Report';
                        }
                    });
                });
            }
        });

        // ========================================
        // Post Reaction Functions
        // ========================================

        if (!window.postReactionState) {
            window.postReactionState = {
                activePicker: null,
                activeButton: null,
                csrfToken: getCsrfToken(),
            };
        }

        window.togglePostReaction = function(button, postSlug) {
            const card = button.closest('.post-card');
            if (!card) return;

            const picker = card.querySelector('.reaction-picker');
            if (!picker) return;

            // If picker is already open for this button, close it
            if (window.postReactionState.activePicker === picker && window.postReactionState.activeButton === button) {
                closePostReactionPicker();
                return;
            }

            // Always open the picker (whether reacted or not)
            closePostReactionPicker();

            // Highlight current reaction
            const currentReaction = button.dataset.currentReaction;
            const reactionOptions = picker.querySelectorAll('.reaction-option');
            reactionOptions.forEach(option => {
                if (option.dataset.emoji === currentReaction) {
                    option.classList.add('active-reaction');
                } else {
                    option.classList.remove('active-reaction');
                }
            });

            picker.style.display = 'block';
            window.postReactionState.activePicker = picker;
            window.postReactionState.activeButton = button;

            setTimeout(() => {
                document.addEventListener('click', handlePostReactionPickerOutside);
            }, 10);
        };

        /**
         * Remove reaction from a post
         */
        window.removePostReaction = async function(postSlug, button) {
            const card = button.closest('.post-card');
            if (!card) return;

            const csrfToken = window.postReactionState.csrfToken;

            // Smooth transition out
            button.style.transition = 'transform 0.2s ease, opacity 0.2s ease';
            button.style.transform = 'scale(0.8)';
            button.style.opacity = '0.5';

            try {
                const response = await fetch('/posts/' + postSlug + '/react', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    // Smooth transition to default state
                    button.classList.remove('reacted');
                    button.dataset.currentReaction = '';
                    button.innerHTML = '<i class="far fa-smile"></i>';
                    button.title = 'React';
                    
                    // Animate back to normal
                    requestAnimationFrame(() => {
                        button.style.transform = 'scale(1)';
                        button.style.opacity = '1';
                    });

                    updatePostReactionSummary(card, data.reaction_summaries, postSlug);
                } else {
                    // Revert animation on error
                    button.style.transform = 'scale(1)';
                    button.style.opacity = '1';
                    alert(data.message || 'Failed to remove reaction');
                }
            } catch (error) {
                console.error('Error removing reaction:', error);
                button.style.transform = 'scale(1)';
                button.style.opacity = '1';
                alert('An error occurred while removing reaction');
            }
        };

        window.closePostReactionPicker = function() {
            if (window.postReactionState.activePicker) {
                window.postReactionState.activePicker.style.display = 'none';
                window.postReactionState.activePicker = null;
                window.postReactionState.activeButton = null;
            }
            document.removeEventListener('click', handlePostReactionPickerOutside);
        };

        function handlePostReactionPickerOutside(event) {
            const picker = window.postReactionState.activePicker;
            if (picker && !picker.contains(event.target) && !event.target.classList.contains('react-btn')) {
                closePostReactionPicker();
            }
        }

        window.selectPostReaction = async function(optionButton, postSlug, emoji) {
            const card = optionButton.closest('.post-card');
            if (!card) return;

            const reactionBtn = card.querySelector('.react-btn');
            if (!reactionBtn) return;

            const csrfToken = window.postReactionState.csrfToken;

            // If clicking the same reaction, remove it
            const currentReaction = reactionBtn.dataset.currentReaction;
            if (currentReaction === emoji) {
                await removePostReaction(postSlug, reactionBtn);
                closePostReactionPicker();
                return;
            }

            try {
                const response = await fetch('/posts/' + postSlug + '/react', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ emoji: emoji }),
                });

                const data = await response.json();

                if (data.success) {
                    // Smooth transition to reacted state
                    reactionBtn.style.transition = 'transform 0.2s ease, opacity 0.2s ease';
                    reactionBtn.style.transform = 'scale(0.8)';
                    reactionBtn.style.opacity = '0.5';

                    // Update content
                    reactionBtn.classList.add('reacted');
                    reactionBtn.dataset.currentReaction = emoji;

                    // Use image if available, else fallback to text
                    const emojiSpan = document.createElement('span');
                    emojiSpan.className = 'react-emoji';
                    
                    const imgPath = window.getReactionImage(emoji);
                    if (imgPath) {
                        emojiSpan.innerHTML = `<img src="${imgPath}" alt="${emoji}" style="width: 24px; height: 24px; vertical-align: middle;">`;
                    } else {
                        emojiSpan.textContent = emoji;
                    }
                    reactionBtn.innerHTML = '';
                    reactionBtn.appendChild(emojiSpan);

                    reactionBtn.title = 'Reacted';

                    // Remove active indicators
                    const reactionOptions = card.querySelectorAll('.reaction-option');
                    reactionOptions.forEach(option => option.classList.remove('active-reaction'));

                    // Animate to final state
                    requestAnimationFrame(() => {
                        reactionBtn.style.transform = 'scale(1)';
                        reactionBtn.style.opacity = '1';
                    });

                    updatePostReactionSummary(card, data.reaction_summaries, postSlug);
                    closePostReactionPicker();
                    // Animation removed
                } else {
                    // Revert animation on error
                    reactionBtn.style.transform = 'scale(1)';
                    reactionBtn.style.opacity = '1';
                    alert(data.message || 'Failed to add reaction');
                }
            } catch (error) {
                console.error('Error adding reaction:', error);
                alert('An error occurred while adding reaction');
            }
        };

        window.updatePostReactionSummary = function(card, reactionSummaries, postSlug) {
            let summaryBar = card.querySelector('.reaction-summary-bar');
            const engagementBar = card.querySelector('.post-engagement-bar');

            // Find post slug - now reliably on the card
            if (!postSlug) {
                postSlug = card.dataset.postSlug || 
                           card.querySelector('.reaction-picker')?.dataset.postSlug || 
                           card.querySelector('.react-btn')?.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
            }

            if (!summaryBar && engagementBar) {
                // Create the reaction summary as the first child of engagement bar
                summaryBar = document.createElement('span');
                summaryBar.className = 'reaction-summary-bar';
                summaryBar.style.cssText = 'cursor: pointer; display: flex; align-items: center; gap: 4px;';
                summaryBar.onclick = function() {
                    if (postSlug) openPostReactorsModal(postSlug);
                };
                // Insert as first child, comments span is last
                engagementBar.insertBefore(summaryBar, engagementBar.firstChild);
            }

            // If no reactions, hide the summary bar if it exists
            let totalCount = 0;
            for (const summary of Object.values(reactionSummaries)) {
                totalCount += summary.count;
            }

            if (totalCount === 0) {
                if (summaryBar) summaryBar.style.display = 'none';
                return;
            } else if (summaryBar) {
                summaryBar.style.display = 'flex';
            }

            if (!summaryBar) return;

            summaryBar.innerHTML = '';

            const emojisDisplay = document.createElement('span');
            emojisDisplay.className = 'reaction-emojis-display';
            let emojiIndex = 0;
            for (const [emojiKey, summary] of Object.entries(reactionSummaries)) {
                // Determine the correct emoji character (it might be the key or inside the summary)
                const emoji = summary.reaction_type || emojiKey;
                
                if (summary.count > 0) {
                    const emojiSpan = document.createElement('span');
                    emojiSpan.className = 'reaction-emoji-count';
                    emojiSpan.dataset.reaction = emoji;
                    
                    const imgPath = window.getReactionImage(emoji);
                    if (imgPath) {
                        emojiSpan.innerHTML = `<img src="${imgPath}" alt="${emoji}" style="width: 100%; height: 100%; object-fit: contain;">`;
                    } else {
                        emojiSpan.textContent = emoji;
                    }
                    emojiSpan.style.cssText = `background: var(--surface); border-radius: 50%; padding: 0; margin-right: -6px; border: 1px solid var(--border); width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; position: relative; z-index: ${10 - emojiIndex}; overflow: hidden;`;
                    emojisDisplay.appendChild(emojiSpan);
                    emojiIndex++;
                }
            }

            const countSpan = document.createElement('span');
            countSpan.className = 'reaction-total-count';
            countSpan.textContent = totalCount;

            summaryBar.appendChild(emojisDisplay);
            summaryBar.appendChild(countSpan);

            // Reset reactors modal body to loading state immediately
            if (postSlug) {
                const body = document.getElementById('post-reactors-modal-body-' + postSlug);
                if (body) {
                    body.innerHTML = '<div class="reactors-loading"><i class="fas fa-spinner fa-spin"></i></div>';
                }
            }
        };

        window.showPostReactionAnimation = function(button, emoji) {
            // Animation removed
        };

        window.openPostReactorsModal = async function(postSlug) {
            const modal = document.getElementById('post-reactors-modal-' + postSlug);
            if (!modal) return;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            const body = document.getElementById('post-reactors-modal-body-' + postSlug);
            if (body) {
                // Show loading state
                body.innerHTML = '<div class="reactors-loading"><i class="fas fa-spinner fa-spin"></i></div>';
                
                try {
                    // Added timestamp to force database check every time
                    const response = await fetch('/posts/' + postSlug + '/reactions?t=' + new Date().getTime(), {
                        headers: {
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache, no-store, must-revalidate',
                            'Pragma': 'no-cache',
                            'Expires': '0'
                        },
                    });

                    if (!response.ok) throw new Error('Network response was not ok');

                    const data = await response.json();

                    if (data.success) {
                        renderPostReactors(body, data.data);
                    } else {
                        const t_fail = window.chatTranslations?.failed_to_load_reactions || 'Failed to load reactions';
                        body.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">' + t_fail + '</p>';
                    }
                } catch (error) {
                    console.error('Error loading reactors:', error);
                    const t_err = window.chatTranslations?.error_loading_reactions || 'Error loading reactions';
                    body.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">' + t_err + '</p>';
                }
            }
        };

        window.renderPostReactors = function(container, reactors) {
            container.innerHTML = '';

            const t_all = window.chatTranslations?.all || 'All';
            const t_empty = window.chatTranslations?.no_reactions_yet || 'No reactions yet';

            if (!reactors || reactors.length === 0) {
                container.innerHTML = '<div class="reactors-empty"><i class="far fa-smile"></i><p>' + t_empty + '</p></div>';
                return;
            }

            // Create tab navigation
            const tabNav = document.createElement('div');
            tabNav.className = 'reactors-tabs';

            // "All" tab
            const allTab = document.createElement('button');
            allTab.className = 'reactor-tab active';
            allTab.innerHTML = t_all + ' <span>' + reactors.length + '</span>';
            allTab.onclick = () => filterReactors('all');
            tabNav.appendChild(allTab);

            // Group by emoji for tabs
            const grouped = {};
            reactors.forEach(r => {
                if (!grouped[r.reaction_type]) grouped[r.reaction_type] = [];
                grouped[r.reaction_type].push(r);
            });

            Object.entries(grouped).forEach(([emoji, list]) => {
                const tab = document.createElement('button');
                tab.className = 'reactor-tab';
                tab.dataset.type = emoji;
                
                let emojiHtml = emoji;
                const imgPath = window.getReactionImage(emoji);
                if (imgPath) {
                    emojiHtml = `<img src="${imgPath}" alt="${emoji}" style="width: 18px; height: 18px; vertical-align: middle;">`;
                }
                
                tab.innerHTML = emojiHtml + ' <span>' + list.length + '</span>';
                tab.onclick = () => filterReactors(emoji);
                tabNav.appendChild(tab);
            });

            container.appendChild(tabNav);

            const listContainer = document.createElement('div');
            listContainer.className = 'reactors-list';
            container.appendChild(listContainer);

            function filterReactors(type) {
                container.querySelectorAll('.reactor-tab').forEach(t => {
                    t.classList.remove('active');
                });

                if (type === 'all') {
                    allTab.classList.add('active');
                } else {
                    // Find the tab that matches the emoji
                    const tabs = container.querySelectorAll('.reactor-tab');
                    tabs.forEach(t => {
                        if (t.dataset.type === type) {
                            t.classList.add('active');
                        }
                    });
                }

                listContainer.innerHTML = '';
                const filtered = type === 'all' ? reactors : reactors.filter(r => r.reaction_type === type);
                filtered.forEach(reactor => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'reactor-item';
                    
                    const avatarHtml = reactor.avatar 
                        ? '<img src="' + reactor.avatar + '" alt="" class="reactor-avatar">'
                        : '<div class="reactor-avatar-placeholder">' + reactor.username.charAt(0).toUpperCase() + '</div>';

                    let reactionHtml = reactor.reaction_type;
                    const imgPath = window.getReactionImage(reactor.reaction_type);
                    if (imgPath) {
                        reactionHtml = `<img src="${imgPath}" alt="${reactor.reaction_type}" style="width: 24px; height: 24px; object-fit: contain;">`;
                    }

                    itemDiv.innerHTML =
                        '<div class="reactor-user-info">' +
                            avatarHtml +
                            '<a href="/users/' + reactor.username + '" class="reactor-name">@' + escapeHtml(reactor.username) + '</a>' +
                        '</div>' +
                        '<span class="reactor-emoji-badge">' + reactionHtml + '</span>';
                    listContainer.appendChild(itemDiv);
                });
            }

            // Initial render
            filterReactors('all');
        };

        window.closePostReactorsModal = function() {
            const activeModal = document.querySelector('.reactors-modal.active');
            if (activeModal) {
                activeModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePostReactorsModal();
            }
        });
    }
})();

        window.toggleReplyAnon = function(commentId, realAvatarUrl) {
            const checkbox = document.getElementById('reply-anon-' + commentId);
            const avatar = document.getElementById('reply-avatar-' + commentId);
            
            if (avatar) {
                if (checkbox && checkbox.checked) {
                    avatar.style.display = 'none';
                    let placeholder = avatar.parentElement.querySelector('.reply-avatar-placeholder');
                    if (!placeholder) {
                        placeholder = document.createElement('div');
                        placeholder.className = 'reply-avatar reply-avatar-placeholder';
                        placeholder.innerHTML = '<i class="fas fa-user-secret"></i>';
                        avatar.parentElement.insertBefore(placeholder, avatar);
                    }
                } else {
                    avatar.style.display = 'block';
                    const placeholder = avatar.parentElement.querySelector('.reply-avatar-placeholder');
                    if (placeholder) placeholder.remove();
                }
            }
        };
