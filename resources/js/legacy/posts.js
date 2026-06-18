/* Posts Functions - External File */

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
                
                // Ignore future timestamps (more than 60 seconds) due to timezone or clock sync issues
                if (diffSeconds < -60) return;
                
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
                
                // Prefer a dedicated `.time-text` element (new post-time structure).
                // Fall back to first non-empty text node (legacy structure used in
                // comments, group post headers, etc.).
                const timeTextEl = el.querySelector('.time-text');
                if (timeTextEl) {
                    if (timeTextEl.textContent.trim() !== text) {
                        timeTextEl.textContent = text;
                    }
                    return;
                }

                let textNode = null;
                for (let node of el.childNodes) {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '') {
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
                
                // Show admin delete only if viewer is admin (platform or community) and is NOT the owner
                const isGroupAdminOrMod = window.COMMUNITY_ROLE === 'admin' || window.COMMUNITY_ROLE === 'moderator';
                postElement.querySelectorAll('.context-admin').forEach(el => {
                    el.style.display = ((isAdmin || isGroupAdminOrMod) && !isOwner) ? 'block' : 'none';
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
                
                // Finalize dropdown visibility (e.g., Pin vs Unpin toggling)
                const dropdown = postElement.querySelector('.post-menu-dropdown');
                if (dropdown) {
                    const isPinned = postElement.classList.contains('pinned-post');
                    const pinItem = postElement.querySelector(`#pin-menu-item-${postElement.dataset.postId}`);
                    const unpinItem = postElement.querySelector(`#unpin-menu-item-${postElement.dataset.postId}`);
                    
                    if (pinItem) {
                        pinItem.style.display = (isOwner && !isPinned) ? 'block' : 'none';
                    }
                    if (unpinItem) {
                        unpinItem.style.display = (isOwner && isPinned) ? 'block' : 'none';
                    }
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
                                    <div class="empty-state-icon-wrap" aria-hidden="true">
                                        <i class="fas fa-feather-pointed"></i>
                                    </div>
                                    <h3>${t.no_posts_yet || 'No posts yet'}</h3>
                                    <p>${t.be_first_to_post || 'Be the first to share something!'}</p>
                                    <button type="button" class="empty-state-cta" onclick="document.getElementById('composer-pill').click();">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                        ${t.post || 'Post'}
                                    </button>
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
                    // Already removed in the response handler above
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

            // Trigger haptic + sound feedback
            if (window.NexusSoul) {
                isCurrentlyLiked ? window.NexusSoul.feedback.unlike() : window.NexusSoul.feedback.like();
            }

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

            // Trigger haptic + sound feedback
            if (window.NexusSoul) {
                isCurrentlySaved ? window.NexusSoul.feedback.unsave() : window.NexusSoul.feedback.save();
            }

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
            // Show modal immediately with skeleton
            const existingModal = document.getElementById('likers-modal');
            if (existingModal) existingModal.remove();
            const modal = document.createElement('div');
            modal.id = 'likers-modal';
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:10000;display:flex;align-items:center;justify-content:center;';
            modal.onclick = function(e) { if (e.target === modal) modal.remove(); };
            const content = document.createElement('div');
            content.style.cssText = 'background:var(--surface,#161616);border:1px solid var(--border,#2a2a2a);border-radius:16px;width:90%;max-width:400px;max-height:80vh;overflow-y:auto;padding:20px;';
            content.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border,#2a2a2a);"><h3 style="margin:0;font-size:18px;font-weight:700;">' + (window.chatTranslations?.likes || 'Likes') + '</h3><button onclick="document.getElementById(\'likers-modal\').remove()" style="background:none;border:none;color:var(--text-muted);font-size:24px;cursor:pointer;padding:0;line-height:1;">&times;</button></div>'
                + Array.from({length: 4}, () => '<div style="display:flex;gap:12px;padding:10px 0;align-items:center;"><div class="sk" style="width:44px;height:44px;border-radius:50%;flex-shrink:0;"></div><div style="flex:1;display:flex;flex-direction:column;gap:6px;"><div class="sk sk-line" style="width:60%;"></div><div class="sk sk-line" style="width:35%;"></div></div></div>').join('');
            modal.appendChild(content);
            document.body.appendChild(modal);

            fetch(`/posts/${slug}/likers`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                const m = document.getElementById('likers-modal');
                if (!m) return;
                m.remove();
                if (data.success && data.likers && data.likers.length > 0) {
                    showLikersModal(data.likers);
                } else {
                    showToast(window.chatTranslations?.no_likes_yet || 'No likes yet', 'info');
                }
            })
            .catch(error => {
                const m = document.getElementById('likers-modal');
                if (m) m.remove();
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
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:10000;display:flex;align-items:center;justify-content:center;';

            const content = document.createElement('div');
            content.style.cssText = 'background:var(--surface,#161616);border:1px solid var(--border,#2a2a2a);border-radius:16px;width:90%;max-width:400px;max-height:80vh;overflow-y:auto;padding:20px;';

            const header = document.createElement('div');
            header.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border,#2a2a2a);';
            header.innerHTML = '<h3 style="margin:0;font-size:18px;font-weight:700;color:var(--text);">' + (window.chatTranslations?.likes || 'Likes') + ' (' + likers.length + ')</h3><button onclick="document.getElementById(\'likers-modal\').remove()" style="background:none;border:none;color:var(--text-muted,rgba(255,255,255,0.55));font-size:24px;cursor:pointer;padding:0;line-height:1;">&times;</button>';

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
                    : '<div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary,#6366f1),var(--secondary,#4ea8de));display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:white;">' + initial + '</div>')
                    + '<div style="flex:1;min-width:0;"><div style="font-weight:600;font-size:14px;direction:ltr;text-align:left;">@' + escapeHtml(displayName) + '</div>'
                    + (liker.name ? '<div style="font-size:12px;color:var(--text-muted,rgba(255,255,255,0.55));">' + escapeHtml(liker.name) + '</div>' : '')
                    + '</div>'
                    + (liker.is_verified ? verifiedBadgeSvg(liker.id, '1em') : '');

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
            // Target the count span explicitly — the like button now has a
            // .like-burst-wrap <span> wrapping the heart icon, so a generic
            // btn.querySelector('span') was hitting the burst wrap and
            // (a) reading NaN for the count, (b) overwriting the heart icon.
            const countSpan = btn.querySelector('.comment-likes-count');
            const heartIcon = btn.querySelector('.like-heart') || btn.querySelector('i.fa-heart');
            const isCurrentlyLiked = btn.classList.contains('liked');
            const currentCount = countSpan ? (parseInt(countSpan.textContent, 10) || 0) : 0;
            const nextCount = isCurrentlyLiked ? Math.max(0, currentCount - 1) : currentCount + 1;

            // Optimistic UI update
            if (isCurrentlyLiked) {
                btn.classList.remove('liked');
                btn.setAttribute('aria-pressed', 'false');
                if (heartIcon) { heartIcon.classList.remove('fas'); heartIcon.classList.add('far'); }
            } else {
                btn.classList.add('liked');
                btn.setAttribute('aria-pressed', 'true');
                if (heartIcon) { heartIcon.classList.remove('far'); heartIcon.classList.add('fas'); }
            }
            if (countSpan) countSpan.textContent = nextCount;

            function revert() {
                if (isCurrentlyLiked) {
                    btn.classList.add('liked');
                    btn.setAttribute('aria-pressed', 'true');
                    if (heartIcon) { heartIcon.classList.remove('far'); heartIcon.classList.add('fas'); }
                } else {
                    btn.classList.remove('liked');
                    btn.setAttribute('aria-pressed', 'false');
                    if (heartIcon) { heartIcon.classList.remove('fas'); heartIcon.classList.add('far'); }
                }
                if (countSpan) countSpan.textContent = currentCount;
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
                    revert();
                    return;
                }
                // If the server returned the authoritative count, trust it.
                if (countSpan && typeof data.likes_count === 'number') {
                    countSpan.textContent = data.likes_count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                revert();
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

            // Trigger haptic + sound feedback
            if (window.NexusSoul) {
                isFollowing ? window.NexusSoul.feedback.unfollow() : window.NexusSoul.feedback.follow();
            }
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
                    const nowFollowing = data.is_following;
                    
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
        function escapeAttr(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function currentUserAvatarUrl() {
            const composerAvatar = document.querySelector('.create-post-avatar, .composer-pill .avatar');
            if (composerAvatar && composerAvatar.src) return composerAvatar.src;
            const socketAvatar = window.NexusSocket && window.NexusSocket.config && window.NexusSocket.config.userAvatarUrl;
            if (socketAvatar) return socketAvatar;
            return '/images/default-avatar.svg';
        }
        function postHasSocialGroup(postId) {
            const post = postId ? document.getElementById('post-' + postId) : null;
            return !!(post && post.dataset.socialGroupId);
        }

        function verifiedBadgeSvg(userId, size) {
            size = size || '.85em';
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'
                + ' width="' + size + '" height="' + size + '"'
                + ' style="display:inline-block;vertical-align:middle;margin-left:.2em;flex-shrink:0;"'
                + ' aria-label="Verified" role="img">'
                + '<circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/>'
                + '<path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'
                + '</svg>';
        }

        window.renderCommentHtml = function(comment, level = 0, currentUserId = null) {
            const t = getTranslations();
            const isAnonymous = !!comment.is_anonymous;
            const author = comment.user || {};
            const isAuthor = currentUserId && String(comment.user_id) === String(currentUserId);
            const isLiked = !!comment.user_liked;
            const likesCount = comment.likes_count || 0;
            const username = escapeAttr(author.username || 'user');
            const displayName = escapeAttr(author.username || 'user');
            const parentAuthorText = isAnonymous
                ? (t.anonymous_participant || 'Anonymous Participant')
                : '@' + (author.username || 'user');
            const createdAt = comment.created_at || new Date().toISOString();
            const isArabic = /[\u0600-\u06FF]/.test(comment.content || '');
            const postId = comment.post_id;
            const showAnonChip = postHasSocialGroup(postId);

            const avatarHtml = isAnonymous
                ? '<div class="comment-avatar-placeholder anonymous" aria-hidden="true"><i class="fas fa-user-secret"></i></div>'
                : '<a href="/users/' + username + '" style="flex-shrink:0;display:flex;"><img src="' + escapeAttr(author.avatar_url || '/images/default-avatar.svg') + '" alt="" class="comment-avatar" style="pointer-events:none;" onerror="this.onerror=null;this.src=\'/images/default-avatar.svg\'"></a>';

            const authorVerifiedBadge = (!isAnonymous && author.is_verified) ? verifiedBadgeSvg(author.id || comment.user_id) : '';
            const authorNameHtml = isAnonymous
                ? '<span class="comment-name anonymous-name">' + (t.anonymous_participant || 'Anonymous Participant') + '</span>'
                : '<div class="comment-name-row">'
                    + '<a href="/users/' + username + '" class="comment-name">' + displayName + '</a>'
                    + authorVerifiedBadge
                    + (comment.role_badge_html || '')
                  + '</div>';

            const deleteBtnHtml = isAuthor
                ? '<button type="button" class="delete-comment-btn"'
                    + ' onclick="deleteComment(' + comment.id + ', this)"'
                    + ' title="' + escapeAttr(t.delete_comment || 'Delete') + '"'
                    + ' aria-label="' + escapeAttr(t.delete_comment || 'Delete') + '">'
                    + '<i class="fas fa-trash-alt" aria-hidden="true"></i>'
                  + '</button>'
                : '';

            const likeAria = isLiked ? (t.unlike || 'Unlike') : (t.like || 'Like');
            const likeBtnHtml =
                '<button type="button"'
                + ' class="comment-action-btn comment-like-btn' + (isLiked ? ' liked' : '') + '"'
                + ' onclick="likeComment(' + comment.id + ', this)"'
                + ' aria-label="' + escapeAttr(likeAria) + '"'
                + ' aria-pressed="' + (isLiked ? 'true' : 'false') + '">'
                    + '<span class="like-burst-wrap" aria-hidden="true">'
                        + '<i class="' + (isLiked ? 'fas' : 'far') + ' fa-heart like-heart"></i>'
                    + '</span>'
                    + '<span class="comment-likes-count">' + likesCount + '</span>'
                + '</button>';

            const replyBtnHtml = (level < 4)
                ? '<button type="button" class="comment-action-btn comment-reply-btn"'
                    + ' onclick="toggleReplyForm(' + comment.id + ')"'
                    + ' aria-label="' + escapeAttr(t.reply || 'Reply') + '"'
                    + ' aria-expanded="false"'
                    + ' aria-controls="reply-form-' + comment.id + '">'
                    + '<i class="fas fa-reply" aria-hidden="true"></i>'
                    + '<span>' + (t.reply || 'Reply') + '</span>'
                  + '</button>'
                : '';

            const placeholderText = t.reply_to
                ? String(t.reply_to).replace(':user', parentAuthorText)
                : ('Reply to ' + parentAuthorText + '\u2026');

            const anonChipHtml = showAnonChip
                ? '<label class="reply-anon-chip"'
                    + ' title="' + escapeAttr(t.post_anonymously || 'Post anonymously') + '"'
                    + ' aria-label="' + escapeAttr(t.post_anonymously || 'Post anonymously') + '">'
                    + '<input type="checkbox" id="reply-anon-' + comment.id + '"'
                        + ' onchange="toggleReplyAnon(' + comment.id + ', \'' + escapeAttr(currentUserAvatarUrl()) + '\')">'
                    + '<i class="fas fa-user-secret" aria-hidden="true"></i>'
                  + '</label>'
                : '';

            const replyFormHtml = (level < 4)
                ? '<div class="reply-form reply-form--minimal"'
                    + ' id="reply-form-' + comment.id + '"'
                    + ' style="display: none;"'
                    + ' data-parent-author="' + escapeAttr(parentAuthorText) + '">'
                    + '<div class="reply-input-wrapper">'
                        + '<img src="' + escapeAttr(currentUserAvatarUrl()) + '" alt=""'
                            + ' class="reply-avatar" id="reply-avatar-' + comment.id + '">'
                        + '<label for="reply-content-' + comment.id + '" class="visually-hidden">'
                            + (t.write_a_reply || 'Write a reply')
                        + '</label>'
                        + '<textarea id="reply-content-' + comment.id + '"'
                            + ' class="reply-textarea"'
                            + ' placeholder="' + escapeAttr(placeholderText) + '"'
                            + ' dir="auto" maxlength="5000" rows="1"'
                            + ' data-reply-submit-target="' + comment.id + '"'
                            + ' data-reply-post-id="' + postId + '"></textarea>'
                        + anonChipHtml
                        + '<button type="button" class="reply-submit-btn"'
                            + ' onclick="submitReply(' + comment.id + ', ' + postId + ')"'
                            + ' aria-label="' + escapeAttr(t.send || 'Send') + '" disabled>'
                            + '<i class="fas fa-paper-plane" aria-hidden="true"></i>'
                        + '</button>'
                    + '</div>'
                    + '<div class="reply-hint" aria-hidden="true">'
                        + '<span>Esc to cancel \u00B7 \u2318 + Enter to send</span>'
                    + '</div>'
                  + '</div>'
                : '';

            return ''
                + '<div class="comment-item ' + (level > 0 ? 'nested' : '') + ' level-' + level
                    + (isAnonymous ? ' is-anonymous' : '') + '"'
                    + ' data-comment-id="' + comment.id + '"'
                    + ' id="comment-' + comment.id + '">'
                    + '<div class="comment-header">'
                        + '<div class="comment-author">'
                            + avatarHtml
                            + '<div class="comment-author-info">'
                                + authorNameHtml
                                + '<span class="comment-time" data-timestamp="' + escapeAttr(createdAt) + '">'
                                    + (t.just_now || 'Just now')
                                + '</span>'
                            + '</div>'
                        + '</div>'
                        + deleteBtnHtml
                    + '</div>'
                    + '<div class="comment-content">'
                        + '<p dir="auto" style="' + (isArabic ? 'direction: rtl; text-align: right;' : '') + '">'
                            + (comment.content || '')
                        + '</p>'
                    + '</div>'
                    + '<div class="comment-actions-bar">'
                        + likeBtnHtml
                        + replyBtnHtml
                    + '</div>'
                    + replyFormHtml
                    + '<div class="replies-container"></div>'
                + '</div>';
        };

        window.submitComment = function(postSlug, postId) {
            const textarea = document.getElementById('comment-content-' + postSlug);
            const content = textarea?.value.trim();
            const anonToggle = document.getElementById('comment-anon-' + postSlug);
            const isAnonymous = anonToggle ? anonToggle.checked : false;

            if (!content) {
                const t = (typeof getTranslations === 'function') ? getTranslations() : (window.chatTranslations || {});
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
                    if (window.NexusSoul) window.NexusSoul.feedback.send();
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
                    if (window.NexusSoul) window.NexusSoul.feedback.error();
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
            const t = (typeof getTranslations === 'function') ? getTranslations() : (window.chatTranslations || {});

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
        // Ensure the engagement bar + comment-count span exist for this post.
        // The server-rendered partial omits the whole bar when a post has no
        // reactions and no comments. When the *first* comment arrives in
        // realtime (or via own submit) we need to create the bar ourselves so
        // the "N comments" text appears next to the new comment.
        function ensureCommentCountElement(postId) {
            const postEl = document.getElementById('post-' + postId);
            if (!postEl) return null;

            let bar = postEl.querySelector(':scope > .post-engagement-bar');
            if (!bar) {
                bar = document.createElement('div');
                bar.className = 'post-engagement-bar';
                const actions = postEl.querySelector(':scope > .post-actions');
                if (actions) {
                    postEl.insertBefore(bar, actions);
                } else {
                    postEl.appendChild(bar);
                }
            }

            let countEl = bar.querySelector('.engagement-comments');
            if (!countEl) {
                countEl = document.createElement('span');
                countEl.className = 'engagement-comments';
                bar.appendChild(countEl);
            }
            return countEl;
        }

        function commentsLabel() {
            const t = (typeof getTranslations === 'function') ? getTranslations() : (window.chatTranslations || {});
            return t.comments || (window.chatTranslations && window.chatTranslations.comments) || 'comments';
        }

        function setCommentCount(postId, count) {
            const safe = Math.max(0, count);
            const postEl = document.getElementById('post-' + postId);
            if (!postEl) return;
            const bar = postEl.querySelector(':scope > .post-engagement-bar');

            if (safe === 0) {
                // Drop the count text. If the bar is now empty (no reactions row), remove the bar too.
                const countEl = bar ? bar.querySelector('.engagement-comments') : null;
                if (countEl) countEl.remove();
                if (bar && !bar.querySelector('.reaction-summary-bar')) bar.remove();
                return;
            }

            const el = ensureCommentCountElement(postId);
            if (el) el.textContent = safe + ' ' + commentsLabel();
        }

        function updateCommentCountUI(postId, delta) {
            const postEl = document.getElementById('post-' + postId);
            if (!postEl) return;
            const countEl = postEl.querySelector('.engagement-comments');
            let currentCount = 0;
            if (countEl) {
                const match = countEl.textContent.match(/\d+/);
                currentCount = match ? parseInt(match[0], 10) : 0;
            }
            setCommentCount(postId, currentCount + delta);
        }

        // Expose so the socket listener (and anything else) can set an
        // authoritative count from the server payload.
        window.setPostCommentCount = setCommentCount;

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
                        const t = typeof getTranslations === 'function' ? getTranslations() : {};
                        const emptyStateHtml = `
                            <div class="empty-state">
                                <div class="empty-state-icon-wrap" aria-hidden="true">
                                    <i class="fas fa-feather-pointed"></i>
                                </div>
                                <h3>${t.no_posts_yet || 'No posts yet'}</h3>
                                <p>${t.be_first_to_post || 'Be the first to share something!'}</p>
                                <button type="button" class="empty-state-cta" onclick="document.getElementById('composer-pill').click();">
                                    <i class="fas fa-pen" aria-hidden="true"></i>
                                    ${t.post || 'Post'}
                                </button>
                            </div>
                        `;
                        container.innerHTML = emptyStateHtml;
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

            let repliesContainer = parentComment.querySelector(':scope > .replies-container');
            if (!repliesContainer) {
                repliesContainer = document.createElement('div');
                repliesContainer.className = 'replies-container';
                parentComment.appendChild(repliesContainer);
            }

            // Ensure a .hidden-replies bucket exists so the connector-line CSS
            // (.hidden-replies::before) can draw the trunk segment.
            let hiddenReplies = repliesContainer.querySelector(':scope > .hidden-replies');
            if (!hiddenReplies) {
                hiddenReplies = document.createElement('div');
                hiddenReplies.className = 'hidden-replies';
                hiddenReplies.id = 'hidden-replies-' + parentId;
                hiddenReplies.style.display = '';
                repliesContainer.appendChild(hiddenReplies);
            } else {
                hiddenReplies.style.display = '';
            }

            // Hide the "Show N replies" button — replies are now visible inline
            // (no point telling the user to "show" something they can see).
            // Keep aria-expanded in sync in case it ever becomes visible again.
            const showRepliesAlways = repliesContainer.querySelector(':scope > .show-replies-always');
            if (showRepliesAlways) {
                showRepliesAlways.style.display = 'none';
                const btn = showRepliesAlways.querySelector('.show-replies-btn');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            }

            let parentLevel = 0;
            parentComment.className.split(' ').forEach(c => {
                if (c.startsWith('level-')) parentLevel = parseInt(c.split('-')[1]) || 0;
            });

            const html = renderCommentHtml(replyData, parentLevel + 1, currentUserId);
            hiddenReplies.insertAdjacentHTML('afterbegin', html);
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
                
                // Render the authoritative count from the server. Creates the
                // engagement bar if the post had zero comments before.
                if (data.count !== undefined && typeof window.setPostCommentCount === 'function') {
                    window.setPostCommentCount(data.post_id, data.count);
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
            const t = (typeof getTranslations === 'function') ? getTranslations() : (window.chatTranslations || {});
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
            const t = (typeof getTranslations === 'function') ? getTranslations() : (window.chatTranslations || {});
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
            const isProfilePage = !!document.getElementById('npTabs');
            
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
                    postEl.style.background = 'rgba(99, 102, 241, 0.1)';
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
            const t = (typeof getTranslations === 'function') ? getTranslations() : (window.chatTranslations || {});
            isReorderMode = !isReorderMode;
            const container = document.getElementById('pinnedPostsContainer');
            const reorderBtn = document.getElementById('reorderBtn');
            if (!container || !reorderBtn) return;

            if (isReorderMode) {
                // Enable reorder UI
                container.style.border = '2px dashed var(--primary)';
                container.style.padding = '10px';
                container.style.borderRadius = '20px';
                container.style.background = 'rgba(99, 102, 241, 0.03)';

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
        window.runOnPageLoad(function() {
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
                                window.showToast(data.message || 'Report submitted successfully', 'success');
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
            // Picker open — tactile selection feedback
            if (window.NexusSoul) window.NexusSoul.feedback.open();

            const card = button.closest('.post-card');
            if (!card) return;

            const picker = card.querySelector('.reaction-picker')
                || document.querySelector('.reaction-picker[data-post-slug="' + postSlug + '"]');
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

            // Move picker to body once to escape overflow:hidden / will-change:transform /
            // content-visibility containment. Keep it there permanently — moving it back on
            // close was causing repeated DOM reflow + layer recreation on every open.
            if (picker.parentElement !== document.body) {
                document.body.appendChild(picker);
            }

            picker.style.visibility = 'hidden';
            picker.style.display = 'block';

            const btnRect = button.getBoundingClientRect();
            // Cache picker dimensions after first measure — they don't change across opens,
            // so we avoid the forced sync layout that offsetHeight/offsetWidth trigger.
            if (!picker._dimsCached) {
                picker._cachedH = picker.offsetHeight || 56;
                picker._cachedW = picker.offsetWidth || 290;
                picker._dimsCached = true;
            }
            const pickerH = picker._cachedH;
            const pickerW = picker._cachedW;

            const isRtl = document.documentElement.dir === 'rtl';
            let left = isRtl ? (btnRect.right - pickerW) : btnRect.left;
            let top = btnRect.top - pickerH - 8;

            if (left + pickerW > window.innerWidth - 8) left = window.innerWidth - pickerW - 8;
            if (left < 8) left = 8;
            if (top < 8) top = btnRect.bottom + 8;

            picker.style.left = left + 'px';
            picker.style.top = top + 'px';
            picker.style.visibility = 'visible';

            window.postReactionState.activePicker = picker;
            window.postReactionState.activeButton = button;

            setTimeout(() => {
                document.addEventListener('click', handlePostReactionPickerOutside);
                window.addEventListener('scroll', closePostReactionPicker, true);
                window.addEventListener('resize', closePostReactionPicker);
            }, 10);
        };

        /**
         * Remove reaction from a post — optimistic, fire-and-forget.
         */
        window.removePostReaction = function(postSlug, button) {
            const card = button.closest('.post-card');
            if (!card) return;

            const csrfToken = (window.postReactionState && window.postReactionState.csrfToken) || '';
            if (!csrfToken) {
                console.error('postReactionState.csrfToken missing — aborting reaction removal');
                return;
            }

            // Tag this request so out-of-order responses (user rapidly tapped
            // multiple times) can be discarded. Only the latest token applies.
            const requestToken = (card._reactionRequestSeq || 0) + 1;
            card._reactionRequestSeq = requestToken;

            // Whoosh — the reacted state is going away
            if (window.NexusSoul) window.NexusSoul.feedback.unreact();

            // OPTIMISTIC UI: paint default state immediately, then fire request.
            const snap = {
                reacted: button.classList.contains('reacted'),
                reaction: button.dataset.currentReaction || '',
                innerHTML: button.innerHTML,
                title: button.title,
            };

            button.classList.remove('reacted');
            button.dataset.currentReaction = '';
            button.innerHTML = '<i class="far fa-heart" aria-hidden="true"></i>';
            button.title = 'React';

            // Optimistic counter update — drop one from the total + drop the emoji
            // icon if the user was the only carrier (server reconciles).
            applyOptimisticReactionDelta(card, snap.reaction, '');

            // Pop the heart icon: small jiggle on removal. Button does a deeper
            // compress to mirror the add animation's weight.
            const heartIcon = button.querySelector('i');
            if (heartIcon) {
                heartIcon.style.transformOrigin = 'center';
                heartIcon.style.transform = 'scale(0.4) rotate(20deg)';
                heartIcon.style.opacity = '0.4';
                heartIcon.style.transition =
                    'transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.18s ease-out';
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        heartIcon.style.transform = 'scale(1) rotate(0deg)';
                        heartIcon.style.opacity = '1';
                    });
                });
            }

            button.style.transition = 'transform 0.32s cubic-bezier(0.16, 1, 0.3, 1)';
            button.style.transform = 'scale(0.84)';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    button.style.transform = 'scale(1)';
                });
            });

            if (!navigator.onLine && window._pwaEnqueue) {
                window._pwaEnqueue('post_reaction_remove', postSlug, {});
                button.classList.add('reaction-pending');
            } else {
            fetch('/posts/' + postSlug + '/react', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    // Discard stale responses — a newer action has been issued.
                    if (card._reactionRequestSeq !== requestToken) return;
                    if (data && data.success) {
                        updatePostReactionSummary(card, data.reaction_summaries, postSlug, data.reactors);
                    } else {
                        revertReactionBtn(button, snap);
                        applyOptimisticReactionDelta(card, '', snap.reaction);
                        alert((data && data.message) || 'Failed to remove reaction');
                    }
                })
                .catch(function (error) {
                    if (card._reactionRequestSeq !== requestToken) return;
                    console.error('Error removing reaction:', error);
                    revertReactionBtn(button, snap);
                    applyOptimisticReactionDelta(card, '', snap.reaction);
                });
            }
        };

        window.closePostReactionPicker = function() {
            if (window.postReactionState.activePicker) {
                const picker = window.postReactionState.activePicker;
                picker.style.display = 'none';
                // Picker stays in document.body — moving it back was triggering
                // expensive DOM reflow + compositor layer destruction on each close.
                window.postReactionState.activePicker = null;
                window.postReactionState.activeButton = null;
            }
            document.removeEventListener('click', handlePostReactionPickerOutside);
            window.removeEventListener('scroll', closePostReactionPicker, true);
            window.removeEventListener('resize', closePostReactionPicker);
        };

        function handlePostReactionPickerOutside(event) {
            const picker = window.postReactionState.activePicker;
            if (picker && !picker.contains(event.target) && !event.target.classList.contains('react-btn')) {
                closePostReactionPicker();
            }
        }

        // Optimistically nudge the reaction summary bar (total + emoji icons)
        // without waiting for the server. The server response later replaces this
        // with authoritative data. Mirrors the user's mental model: "my action
        // affected the counter instantly."
        //
        // oldEmoji = the emoji the user had before (or '' / null)
        // newEmoji = the emoji they're picking (or '' / null when removing)
        function applyOptimisticReactionDelta(card, oldEmoji, newEmoji) {
            let summaryBar = card.querySelector('.reaction-summary-bar');
            let engagementBar = card.querySelector(':scope > .post-engagement-bar');

            // Total delta: +1 adding, -1 removing, 0 switching
            const had = !!oldEmoji, has = !!newEmoji;
            const delta = (has && !had) ? 1 : (!has && had) ? -1 : 0;

            // Read current total
            let currentCount = 0;
            if (summaryBar) {
                const el = summaryBar.querySelector('.reaction-total-count');
                if (el) currentCount = parseInt(el.dataset.total || el.textContent, 10) || 0;
            }
            const newCount = Math.max(0, currentCount + delta);

            // Falling to zero: drop the bar (and engagement wrapper if empty)
            if (newCount === 0) {
                if (summaryBar) summaryBar.remove();
                if (engagementBar && !engagementBar.querySelector('.engagement-comments')) {
                    // Mirror of the touch-leak guard in the create path: when
                    // the engagement bar disappears, the comments section shifts
                    // up ~30px and the user's finger ends up over the comment
                    // textarea, focusing it. Block pointer events on the
                    // comments section for the leftover click.
                    const commentsSection = card.querySelector('.post-comments-section');
                    if (commentsSection) {
                        commentsSection.style.pointerEvents = 'none';
                        setTimeout(function () {
                            commentsSection.style.pointerEvents = '';
                        }, 500);
                    }
                    engagementBar.remove();
                }
                return;
            }

            // Materialize missing structures (first reaction on this post).
            // Build the ENTIRE bar in a detached state first, then insert in one
            // go — guarantees the user never sees an empty engagement bar paint.
            const needsEngagement = !engagementBar;
            const needsSummary = !summaryBar;

            if (needsEngagement) {
                engagementBar = document.createElement('div');
                engagementBar.className = 'post-engagement-bar';
            }
            if (needsSummary) {
                const slug = card.dataset.postSlug || '';
                summaryBar = document.createElement('button');
                summaryBar.type = 'button';
                summaryBar.className = 'reaction-summary-bar';
                summaryBar.setAttribute('aria-label', 'Reactions');
                // Guard against the touch that just added the reaction landing
                // here. When the engagement bar materializes, it pushes the
                // react button ~30px down — the user's finger ends up over the
                // newly-created summary bar, and the click event lands on it.
                // Swallow clicks for 500ms after creation so the modal only
                // opens on an intentional second tap.
                const createdAt = Date.now();
                summaryBar.addEventListener('click', function (ev) {
                    if (Date.now() - createdAt < 500) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        return;
                    }
                    if (slug && typeof window.openPostReactorsModal === 'function') {
                        window.openPostReactorsModal(slug);
                    }
                });
            }

            let emojisDisplay = summaryBar.querySelector('.reaction-emojis-display');
            let countSpan = summaryBar.querySelector('.reaction-total-count');
            if (!emojisDisplay) {
                emojisDisplay = document.createElement('span');
                emojisDisplay.className = 'reaction-emojis-display';
            }
            if (!countSpan) {
                countSpan = document.createElement('span');
                countSpan.className = 'reaction-total-count';
            }

            // Decrement OLD emoji's per-emoji count. If it drops to 0, the
            // user was the only carrier — drop the icon from the bar with a
            // shrink-fade. If others still have it, keep it visible.
            if (oldEmoji) {
                const oldNode = emojisDisplay.querySelector(
                    '.reaction-emoji-count[data-reaction="' + oldEmoji + '"]'
                );
                if (oldNode) {
                    const cur = parseInt(oldNode.dataset.count, 10);
                    const remaining = isNaN(cur) ? 0 : Math.max(0, cur - 1);
                    if (remaining === 0) {
                        oldNode.dataset.count = '0';
                        oldNode.style.transformOrigin = 'center';
                        oldNode.style.transition =
                            'transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease-out';
                        oldNode.style.transform = 'scale(0.3)';
                        oldNode.style.opacity = '0';
                        // Track the pending removal so a rapid A→B→A switch can
                        // cancel it. Without this, the setTimeout deletes the
                        // node we just rescued, leaving a gap in the bar.
                        if (oldNode._removalTimer) clearTimeout(oldNode._removalTimer);
                        oldNode._removalTimer = setTimeout(function () {
                            // Re-check the count: a rapid re-add may have
                            // incremented data-count above zero. Bail out and
                            // let the new animation drive the visual state.
                            const stillZero = (parseInt(oldNode.dataset.count, 10) || 0) === 0;
                            if (stillZero && oldNode.parentNode) {
                                oldNode.parentNode.removeChild(oldNode);
                            }
                            oldNode._removalTimer = null;
                        }, 280);
                    } else {
                        oldNode.dataset.count = String(remaining);
                    }
                }
            }

            // Add or increment NEW emoji.
            if (newEmoji) {
                let existing = emojisDisplay.querySelector(
                    '.reaction-emoji-count[data-reaction="' + newEmoji + '"]'
                );
                if (existing) {
                    const cur = parseInt(existing.dataset.count, 10);
                    existing.dataset.count = String((isNaN(cur) ? 0 : cur) + 1);
                    // If this node was mid-shrink (pending removal), cancel and
                    // restore it visually so the new addition isn't deleted.
                    if (existing._removalTimer) {
                        clearTimeout(existing._removalTimer);
                        existing._removalTimer = null;
                        existing.style.transition =
                            'transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.18s ease-out';
                        existing.style.transform = 'scale(1)';
                        existing.style.opacity = '1';
                        existing._didIntroAnim = false;
                    }
                } else {
                    const span = document.createElement('span');
                    span.className = 'reaction-emoji-count';
                    span.dataset.reaction = newEmoji;
                    span.dataset.count = '1';
                    span.style.zIndex = '10';
                    const imgPath = typeof window.getReactionImage === 'function'
                        ? window.getReactionImage(newEmoji) : null;
                    if (imgPath) {
                        const img = document.createElement('img');
                        img.src = imgPath;
                        img.alt = newEmoji;
                        span.appendChild(img);
                    } else {
                        span.textContent = newEmoji;
                    }
                    emojisDisplay.insertBefore(span, emojisDisplay.firstChild);
                }
            }

            // Set count text BEFORE insertion so the first paint shows it.
            countSpan.textContent = newCount;

            // Assemble the summary bar before it enters the document.
            if (!emojisDisplay.parentNode) summaryBar.appendChild(emojisDisplay);
            if (!countSpan.parentNode) summaryBar.appendChild(countSpan);

            // Now attach everything to the document in one operation.
            if (needsSummary) {
                engagementBar.insertBefore(summaryBar, engagementBar.firstChild);
            }
            if (needsEngagement) {
                const actions = card.querySelector(':scope > .post-actions');
                if (actions) card.insertBefore(engagementBar, actions);
                else card.appendChild(engagementBar);
            }

            // Pulse the count with a stronger ease-out-expo pop. The bigger
            // initial scale + slower settle gives the tactile "got it" feel.
            countSpan.style.transformOrigin = 'center';
            countSpan.style.transition = 'transform 0.45s cubic-bezier(0.16, 1, 0.3, 1)';
            countSpan.style.transform = 'scale(1.4)';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    countSpan.style.transform = 'scale(1)';
                });
            });

            // Pop the newly-added emoji icon in the bar (the first .reaction-emoji-count)
            if (newEmoji) {
                const justAdded = emojisDisplay.querySelector(
                    '.reaction-emoji-count[data-reaction="' + newEmoji + '"]'
                );
                if (justAdded && !justAdded._didIntroAnim) {
                    justAdded._didIntroAnim = true;
                    justAdded.style.transformOrigin = 'center';
                    justAdded.style.transform = 'scale(0.2) rotate(-15deg)';
                    justAdded.style.opacity = '0';
                    justAdded.style.transition =
                        'transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease-out';
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            justAdded.style.transform = 'scale(1) rotate(0deg)';
                            justAdded.style.opacity = '1';
                        });
                    });
                }
            }
        }

        // Snapshot a button's state so we can revert if the server rejects an
        // optimistic update. Captures everything user-visible that we change.
        function snapshotReactionBtn(btn) {
            return {
                reacted: btn.classList.contains('reacted'),
                reaction: btn.dataset.currentReaction || '',
                innerHTML: btn.innerHTML,
                title: btn.title,
            };
        }

        function revertReactionBtn(btn, snap) {
            btn.classList.toggle('reacted', snap.reacted);
            btn.dataset.currentReaction = snap.reaction;
            btn.innerHTML = snap.innerHTML;
            btn.title = snap.title;
            // Clear all inline state set by applyReactedVisual / removePostReaction
            btn.style.transform = '';
            btn.style.opacity = '';
            btn.style.transition = '';
        }

        // Paint the reacted state into the button locally — no server wait.
        function applyReactedVisual(btn, emoji) {
            // Sound + haptics — fires once per reaction, the moment the user commits.
            if (window.NexusSoul) window.NexusSoul.feedback.react();
            btn.classList.add('reacted');
            btn.dataset.currentReaction = emoji;
            btn.title = 'Reacted';

            const emojiSpan = document.createElement('span');
            emojiSpan.className = 'react-emoji';
            const imgPath = typeof window.getReactionImage === 'function' ? window.getReactionImage(emoji) : null;
            if (imgPath) {
                // No inline width/height — the .react-emoji img CSS rule owns sizing
                // so the server-rendered and JS-rendered versions match exactly.
                emojiSpan.innerHTML = '<img src="' + imgPath + '" alt="' + emoji + '">';
            } else {
                emojiSpan.textContent = emoji;
            }
            btn.innerHTML = '';
            btn.appendChild(emojiSpan);

            // Pop the emoji in: start small + invisible, expand past 1.0 with
            // ease-out-expo, settle. Two-stage transform on the inner span so
            // the button itself stays steady and the icon does the dance.
            emojiSpan.style.transformOrigin = 'center';
            emojiSpan.style.transform = 'scale(0.2)';
            emojiSpan.style.opacity = '0';
            emojiSpan.style.transition =
                'transform 0.42s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.18s ease-out';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    emojiSpan.style.transform = 'scale(1)';
                    emojiSpan.style.opacity = '1';
                });
            });

            // Subtle button compress to add weight to the tap.
            btn.style.transition = 'transform 0.32s cubic-bezier(0.16, 1, 0.3, 1)';
            btn.style.transform = 'scale(0.88)';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    btn.style.transform = 'scale(1)';
                });
            });
        }

        window.selectPostReaction = function(optionButton, postSlug, emoji) {
            // Picker may have been moved to body, so find card by slug instead of DOM traversal
            const card = document.querySelector('.post-card[data-post-slug="' + postSlug + '"]');
            if (!card) return;

            const reactionBtn = card.querySelector('.react-btn');
            if (!reactionBtn) return;

            const csrfToken = (window.postReactionState && window.postReactionState.csrfToken) || '';
            if (!csrfToken) {
                console.error('postReactionState.csrfToken missing — aborting reaction');
                return;
            }

            const currentReaction = reactionBtn.dataset.currentReaction;

            // If clicking the same reaction, remove it instead.
            if (currentReaction === emoji) {
                closePostReactionPicker();
                if (typeof window.removePostReaction === 'function') {
                    window.removePostReaction(postSlug, reactionBtn);
                }
                return;
            }

            // Tag this request so out-of-order responses can be discarded.
            const requestToken = (card._reactionRequestSeq || 0) + 1;
            card._reactionRequestSeq = requestToken;

            // OPTIMISTIC UI: paint the new reacted state immediately, update the
            // counter, close the picker, then send the request in the background.
            // The user feels zero latency between their gesture and visual change.
            const snap = snapshotReactionBtn(reactionBtn);
            closePostReactionPicker();
            applyReactedVisual(reactionBtn, emoji);
            applyOptimisticReactionDelta(card, snap.reaction, emoji);

            // Remove active indicators inside the picker (in case it reopens later)
            card.querySelectorAll('.reaction-option').forEach(function (option) {
                option.classList.remove('active-reaction');
            });

            if (!navigator.onLine && window._pwaEnqueue) {
                window._pwaEnqueue('post_reaction', postSlug, { emoji: emoji });
                reactionBtn.classList.add('reaction-pending');
            } else {
            fetch('/posts/' + postSlug + '/react', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ emoji: emoji }),
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (card._reactionRequestSeq !== requestToken) return;
                    if (data && data.success) {
                        updatePostReactionSummary(card, data.reaction_summaries, postSlug, data.reactors);
                    } else {
                        revertReactionBtn(reactionBtn, snap);
                        applyOptimisticReactionDelta(card, emoji, snap.reaction);
                        alert((data && data.message) || 'Failed to add reaction');
                    }
                })
                .catch(function (error) {
                    if (card._reactionRequestSeq !== requestToken) return;
                    console.error('Error adding reaction:', error);
                    revertReactionBtn(reactionBtn, snap);
                    applyOptimisticReactionDelta(card, emoji, snap.reaction);
                });
            }
        };

        window.updatePostReactionSummary = function(card, reactionSummaries, postSlug, reactors) {
            let summaryBar = card.querySelector('.reaction-summary-bar');
            let engagementBar = card.querySelector(':scope > .post-engagement-bar');

            // Find post slug - now reliably on the card
            if (!postSlug) {
                postSlug = card.dataset.postSlug ||
                           card.querySelector('.reaction-picker')?.dataset.postSlug ||
                           card.querySelector('.react-btn')?.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
            }

            // Total first so we know if we need to materialize the bar
            let totalCount = 0;
            for (const summary of Object.values(reactionSummaries)) {
                totalCount += summary.count;
            }

            // If the post was rendered with no engagement (0 reactions, 0 comments)
            // the server omits the entire <.post-engagement-bar>. On the FIRST
            // reaction we must inject it ourselves before .post-actions so the
            // summary has somewhere to live without a page reload.
            if (!engagementBar && totalCount > 0) {
                engagementBar = document.createElement('div');
                engagementBar.className = 'post-engagement-bar';
                const actions = card.querySelector(':scope > .post-actions');
                if (actions) {
                    card.insertBefore(engagementBar, actions);
                } else {
                    card.appendChild(engagementBar);
                }
            }

            if (!summaryBar && engagementBar) {
                summaryBar = document.createElement('button');
                summaryBar.type = 'button';
                summaryBar.className = 'reaction-summary-bar';
                summaryBar.setAttribute('aria-label', 'Reactions');
                // Same touch-leak guard as the optimistic path (see applyOptimisticReactionDelta)
                const createdAt = Date.now();
                summaryBar.addEventListener('click', function (ev) {
                    if (Date.now() - createdAt < 500) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        return;
                    }
                    if (postSlug && typeof openPostReactorsModal === 'function') {
                        openPostReactorsModal(postSlug);
                    }
                });
                engagementBar.insertBefore(summaryBar, engagementBar.firstChild);
            }

            if (totalCount === 0) {
                if (summaryBar) summaryBar.remove();
                // If the engagement bar is now empty (no comments either), drop it
                if (engagementBar && !engagementBar.querySelector('.engagement-comments')) {
                    engagementBar.remove();
                }
                return;
            } else if (summaryBar) {
                summaryBar.style.display = 'flex';
            }

            if (!summaryBar) return;

            // Reconcile in-place — don't wipe innerHTML, that nukes optimistic UI
            let emojisDisplay = summaryBar.querySelector('.reaction-emojis-display');
            if (!emojisDisplay) {
                emojisDisplay = document.createElement('span');
                emojisDisplay.className = 'reaction-emojis-display';
                summaryBar.insertBefore(emojisDisplay, summaryBar.firstChild);
            }

            let countSpan = summaryBar.querySelector('.reaction-total-count');
            if (!countSpan) {
                countSpan = document.createElement('span');
                countSpan.className = 'reaction-total-count';
                summaryBar.appendChild(countSpan);
            }

            // Compute desired emoji list from server
            const wantedEmojis = [];
            for (const [emojiKey, summary] of Object.entries(reactionSummaries)) {
                const emoji = summary.reaction_type || emojiKey;
                if (summary.count > 0) wantedEmojis.push(emoji);
            }

            // Current emoji list painted optimistically. Treat mid-shrink nodes
            // (queued for removal, data-count=0) as already-gone so we don't
            // count them when comparing sets.
            const existingNodes = Array.from(emojisDisplay.querySelectorAll('.reaction-emoji-count'))
                .filter(el => (parseInt(el.dataset.count, 10) || 0) > 0 || !el._removalTimer);
            const existingEmojis = existingNodes.map(el => el.dataset.reaction);

            // Compare as SETS — order alone shouldn't trigger a rebuild that
            // throws away the pop animation we just played.
            const sameSet = wantedEmojis.length === existingEmojis.length &&
                wantedEmojis.every(e => existingEmojis.indexOf(e) !== -1);

            // Build a lookup of authoritative per-emoji counts
            const serverCounts = {};
            for (const [emojiKey, summary] of Object.entries(reactionSummaries)) {
                const emoji = summary.reaction_type || emojiKey;
                serverCounts[emoji] = summary.count;
            }

            // Only rebuild emojis if the optimistic state diverges from server truth
            if (!sameSet) {
                emojisDisplay.innerHTML = '';
                let emojiIndex = 0;
                for (const emoji of wantedEmojis) {
                    const emojiSpan = document.createElement('span');
                    emojiSpan.className = 'reaction-emoji-count';
                    emojiSpan.dataset.reaction = emoji;
                    emojiSpan.dataset.count = String(serverCounts[emoji] || 0);
                    emojiSpan.style.zIndex = String(10 - emojiIndex);
                    const imgPath = window.getReactionImage(emoji);
                    if (imgPath) {
                        const img = document.createElement('img');
                        img.src = imgPath;
                        img.alt = emoji;
                        emojiSpan.appendChild(img);
                    } else {
                        emojiSpan.textContent = emoji;
                    }
                    emojisDisplay.appendChild(emojiSpan);
                    emojiIndex++;
                }
            } else {
                // Same emoji set — just sync the data-count values for future
                // optimistic updates so they have accurate per-emoji counts.
                for (const node of existingNodes) {
                    const e = node.dataset.reaction;
                    if (serverCounts[e] !== undefined) {
                        node.dataset.count = String(serverCounts[e]);
                    }
                }
            }

            // Update count only if different — avoids any visual blink
            const currentCount = parseInt(countSpan.textContent, 10) || 0;
            if (reactors && reactors.length > 0) {
                const labels = window.reactionLabels || { you: 'You', and: 'and', others: 'others' };
                const me = reactors.some(function(r) { return Number(r.id) === Number(window.currentUserId); });
                const others = reactors.filter(function(r) { return Number(r.id) !== Number(window.currentUserId); });
                var text;
                if (me && others.length === 0) {
                    text = labels.you;
                } else if (!me && others.length === 1) {
                    text = others[0].username;
                } else if (me && others.length === 1) {
                    text = labels.you + ', ' + others[0].username;
                } else if (!me && others.length === 2) {
                    text = others[0].username + ' ' + labels.and + ' ' + others[1].username;
                } else if (me && others.length === 2) {
                    text = labels.you + ', ' + others[0].username + ' ' + labels.and + ' ' + others[1].username;
                } else if (!me) {
                    text = others[0].username + ', ' + others[1].username + ' ' + labels.and + ' ' + (others.length - 2) + ' ' + labels.others;
                } else {
                    text = labels.you + ', ' + others[0].username + ' ' + labels.and + ' ' + (others.length - 2) + ' ' + labels.others;
                }
                if (countSpan.textContent !== text) {
                    countSpan.textContent = text;
                    countSpan.dataset.total = totalCount;
                }
            } else {
                const currentCount = parseInt(countSpan.dataset.total || countSpan.textContent, 10) || 0;
                if (currentCount !== totalCount) {
                    countSpan.textContent = totalCount;
                    countSpan.dataset.total = totalCount;
                }
            }

            // Reset reactors modal body to loading state immediately
            if (postSlug) {
                const body = document.getElementById('post-reactors-modal-body-' + postSlug);
                if (body) {
                    body.innerHTML = Array.from({length: 4}, () => '<div style="display:flex;gap:12px;padding:10px 14px;align-items:center;"><div class="sk" style="width:40px;height:40px;border-radius:50%;flex-shrink:0;"></div><div style="flex:1;display:flex;flex-direction:column;gap:6px;"><div class="sk sk-line" style="width:55%;"></div><div class="sk sk-line" style="width:35%;"></div></div></div>').join('');
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
                body.innerHTML = Array.from({length: 4}, () => '<div style="display:flex;gap:12px;padding:10px 14px;align-items:center;"><div class="sk" style="width:40px;height:40px;border-radius:50%;flex-shrink:0;"></div><div style="flex:1;display:flex;flex-direction:column;gap:6px;"><div class="sk sk-line" style="width:55%;"></div><div class="sk sk-line" style="width:35%;"></div></div></div>').join('');
                
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
                        ? '<a href="/users/' + reactor.username + '" style="display:flex;flex-shrink:0;"><img src="' + reactor.avatar + '" alt="" class="reactor-avatar" style="pointer-events:none;"></a>'
                        : '<div class="reactor-avatar-placeholder">' + reactor.username.charAt(0).toUpperCase() + '</div>';

                    let reactionHtml = reactor.reaction_type;
                    const imgPath = window.getReactionImage(reactor.reaction_type);
                    if (imgPath) {
                        reactionHtml = `<img src="${imgPath}" alt="${reactor.reaction_type}" style="width: 24px; height: 24px; object-fit: contain;">`;
                    }

                    itemDiv.innerHTML =
                        '<div class="reactor-user-info">' +
                            avatarHtml +
                            '<a href="/users/' + reactor.username + '" class="reactor-name" style="display:inline-flex;align-items:center;gap:.2em;">@' + escapeHtml(reactor.username) + (reactor.is_verified ? verifiedBadgeSvg(reactor.id) : '') + '</a>' +
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

/* ============================================================
   Comment / reply realtime polish — site-wide.
   Event delegation so dynamically inserted DOM (socket pushes,
   load-more, ajax inserts) all pick up the behavior automatically.
   ============================================================ */
(function () {
    function autosize(ta) {
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 180) + 'px';  // matches CSS max-height
    }

    function syncFormState(ta) {
        const form = ta.closest('.reply-form--minimal');
        if (!form) return;
        const hasContent = ta.value.trim().length > 0;
        form.classList.toggle('has-content', hasContent);
        const sendBtn = form.querySelector('.reply-submit-btn');
        if (sendBtn) sendBtn.disabled = !hasContent;
    }

    document.addEventListener('input', function (e) {
        const ta = e.target;
        if (!(ta instanceof HTMLTextAreaElement)) return;
        if (!ta.classList.contains('reply-textarea')) return;
        autosize(ta);
        syncFormState(ta);
    }, true);

    document.addEventListener('keydown', function (e) {
        const ta = e.target;
        if (!(ta instanceof HTMLTextAreaElement)) return;
        if (!ta.classList.contains('reply-textarea')) return;
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            const commentId = ta.dataset.replySubmitTarget;
            const postId = ta.dataset.replyPostId;
            if (commentId && postId && typeof window.submitReply === 'function') {
                window.submitReply(parseInt(commentId, 10), parseInt(postId, 10));
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            const commentId = ta.dataset.replySubmitTarget;
            if (commentId && typeof window.toggleReplyForm === 'function') {
                ta.blur();
                window.toggleReplyForm(parseInt(commentId, 10));
            }
        }
    });

    // Patch toggleReplyForm to sync aria-expanded + autosize + focus on open,
    // and clear .has-content on close.
    const origToggleReply = window.toggleReplyForm;
    if (typeof origToggleReply === 'function' && !origToggleReply.__nexusPatched) {
        window.toggleReplyForm = function (commentId) {
            const r = origToggleReply.apply(this, arguments);
            const form = document.getElementById('reply-form-' + commentId);
            const isOpen = form && form.style.display !== 'none';
            const btn = document.querySelector('[aria-controls="reply-form-' + commentId + '"]');
            if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (isOpen) {
                const ta = document.getElementById('reply-content-' + commentId);
                if (ta) { autosize(ta); syncFormState(ta); ta.focus(); }
            } else if (form) {
                form.classList.remove('has-content');
            }
            return r;
        };
        window.toggleReplyForm.__nexusPatched = true;
    }

    const origToggleNested = window.toggleNestedReplies;
    if (typeof origToggleNested === 'function' && !origToggleNested.__nexusPatched) {
        window.toggleNestedReplies = function (commentId, show) {
            const r = origToggleNested.apply(this, arguments);
            const wrap = document.getElementById('hidden-replies-' + commentId);
            const isOpen = wrap && wrap.style.display !== 'none';
            const btn = document.querySelector('[aria-controls="hidden-replies-' + commentId + '"]');
            if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            return r;
        };
        window.toggleNestedReplies.__nexusPatched = true;
    }

    /* ----- Heart burst on comment-like click ----- */
    const BURST_COLORS = ['#ef4444', '#f87171', '#fbbf24', '#f97316', '#ec4899'];
    const BURST_COUNT = 6;
    const BURST_LIFETIME = 720;
    const HEART_PULSE_LIFETIME = 600;

    function spawnLikeBurst(btn) {
        const wrap = btn.querySelector('.like-burst-wrap');
        if (!wrap) return;

        btn.classList.add('is-bursting');
        setTimeout(function () { btn.classList.remove('is-bursting'); }, HEART_PULSE_LIFETIME);

        for (let i = 0; i < BURST_COUNT; i++) {
            const p = document.createElement('span');
            p.className = 'like-burst-particle';
            const angle = (i / BURST_COUNT) * Math.PI * 2 + (Math.random() - 0.5) * 0.7;
            const distance = 16 + Math.random() * 14;
            p.style.setProperty('--burst-x', Math.cos(angle) * distance + 'px');
            p.style.setProperty('--burst-y', Math.sin(angle) * distance + 'px');
            p.style.background = BURST_COLORS[Math.floor(Math.random() * BURST_COLORS.length)];
            p.style.animationDelay = (Math.random() * 60) + 'ms';
            wrap.appendChild(p);
            setTimeout(function () { p.remove(); }, BURST_LIFETIME);
        }
    }

    // Capture-phase so we read pre-toggle .liked state (likeComment flips it after fetch)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.comment-like-btn');
        if (!btn) return;
        const willLike = !btn.classList.contains('liked');
        if (willLike) spawnLikeBurst(btn);
    }, true);
})();

/* ============================================================
   Facebook-style react-button gesture (touch / pen only)
   ─────────────────────────────────────────────────────────────
   • Tap (release within 180ms)  → toggle default 👍 reaction
   • Long-press (held ≥ 180ms)   → open the picker
   • While holding after long-press → drag to any reaction; release to pick
   • Mouse pointer = untouched (desktop keeps existing behavior:
     the inline onclick="togglePostReaction(...)" opens the picker)
   ============================================================ */
(function () {
    const HOLD_THRESHOLD_MS = 180;
    const MOVE_CANCEL_PX    = 10;
    const DEFAULT_EMOJI     = '👍';

    let pressTimer    = null;
    let longPressFired = false;
    let suppressNextClick = false;
    let startX = 0, startY = 0;
    let activeBtn = null;
    let activePointerId = null;
    let peekedOption = null;

    function clearTimer() {
        if (pressTimer) { clearTimeout(pressTimer); pressTimer = null; }
    }
    function getPostSlug(btn) {
        if (btn.dataset.postSlugCache) return btn.dataset.postSlugCache;
        const card = btn.closest('.post-card');
        const slug = (card && card.dataset.postSlug) ||
                     (btn.getAttribute('onclick') || '').match(/'([^']+)'/)?.[1] ||
                     '';
        if (slug) btn.dataset.postSlugCache = slug;
        return slug;
    }

    function tapToggleDefault(btn) {
        const slug = getPostSlug(btn);
        if (!slug) return;
        const current = btn.dataset.currentReaction;
        if (current) {
            if (typeof window.removePostReaction === 'function') {
                window.removePostReaction(slug, btn);
            }
        } else {
            if (typeof window.selectPostReaction === 'function') {
                window.selectPostReaction(null, slug, DEFAULT_EMOJI);
            }
        }
    }

    function openPicker(btn) {
        const slug = getPostSlug(btn);
        if (!slug) return;
        if (typeof window.togglePostReaction === 'function') {
            window.togglePostReaction(btn, slug);
        }
    }

    function clearPeek() {
        if (peekedOption) {
            peekedOption.classList.remove('reaction-option--peeked');
            peekedOption = null;
        }
    }

    function isPickerOpen() {
        return !!(window.postReactionState && window.postReactionState.activePicker
            && window.postReactionState.activePicker.style.display !== 'none');
    }

    function getActivePicker() {
        return window.postReactionState && window.postReactionState.activePicker;
    }

    function findOptionAtPoint(x, y) {
        const el = document.elementFromPoint(x, y);
        if (!el) return null;
        return el.closest('.reaction-option');
    }

    document.addEventListener('pointerdown', function (e) {
        const btn = e.target.closest('.react-btn');
        if (!btn) return;
        if (e.pointerType === 'mouse') return;

        activeBtn = btn;
        activePointerId = e.pointerId;
        longPressFired = false;
        startX = e.clientX;
        startY = e.clientY;
        clearPeek();

        try { btn.setPointerCapture(e.pointerId); } catch (_) {}

        clearTimer();
        pressTimer = setTimeout(function () {
            longPressFired = true;
            if (window.NexusHaptics && typeof window.NexusHaptics.impact === 'function') {
                try { window.NexusHaptics.impact('medium'); } catch (_) {}
            }
            openPicker(btn);
        }, HOLD_THRESHOLD_MS);
    }, { passive: true });

    document.addEventListener('pointermove', function (e) {
        if (activePointerId !== null && e.pointerId !== activePointerId) return;

        if (!longPressFired) {
            if (!pressTimer) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            if (dx * dx + dy * dy > MOVE_CANCEL_PX * MOVE_CANCEL_PX) {
                clearTimer();
                activeBtn = null;
                activePointerId = null;
            }
            return;
        }

        if (!isPickerOpen()) return;

        const option = findOptionAtPoint(e.clientX, e.clientY);
        if (option === peekedOption) return;

        if (peekedOption) peekedOption.classList.remove('reaction-option--peeked');
        peekedOption = option;
        if (peekedOption) {
            peekedOption.classList.add('reaction-option--peeked');
            if (window.NexusHaptics && typeof window.NexusHaptics.selection === 'function') {
                try { window.NexusHaptics.selection(); } catch (_) {}
            }
        }
    }, { passive: true });

    document.addEventListener('pointerup', function (e) {
        if (activePointerId !== null && e.pointerId !== activePointerId) return;

        const wasLongPress = longPressFired;
        const btn = activeBtn;
        clearTimer();

        if (btn) {
            try { btn.releasePointerCapture(e.pointerId); } catch (_) {}
        }

        if (e.pointerType === 'mouse') {
            activeBtn = null;
            activePointerId = null;
            return;
        }

        if (wasLongPress) {
            suppressNextClick = true;

            const option = findOptionAtPoint(e.clientX, e.clientY) || peekedOption;
            if (option && btn) {
                const emoji = option.dataset.emoji;
                const slug = getPostSlug(btn);
                if (emoji && slug && typeof window.selectPostReaction === 'function') {
                    if (window.NexusHaptics && typeof window.NexusHaptics.impact === 'function') {
                        try { window.NexusHaptics.impact('light'); } catch (_) {}
                    }
                    window.selectPostReaction(option, slug, emoji);
                }
            } else if (typeof window.closePostReactionPicker === 'function') {
                window.closePostReactionPicker();
            }
            clearPeek();
        } else if (btn && (e.target.closest('.react-btn') === btn || e.target === btn)) {
            suppressNextClick = true;
            tapToggleDefault(btn);
        }

        activeBtn = null;
        activePointerId = null;
        longPressFired = false;
    }, { passive: true });

    document.addEventListener('pointercancel', function (e) {
        if (activeBtn) {
            try { activeBtn.releasePointerCapture(e.pointerId); } catch (_) {}
        }
        clearTimer();
        clearPeek();
        longPressFired = false;
        activeBtn = null;
        activePointerId = null;
    }, { passive: true });

    // Capture-phase click suppressor: kills the inline onclick that would
    // otherwise fire after touchend (synthetic mouse event on iOS/Android).
    // Covers both .react-btn (long-press flow) and .reaction-option (slide-to-pick
    // would otherwise also invoke the option's inline onclick after we've already
    // selected via pointerup).
    document.addEventListener('click', function (e) {
        const target = e.target.closest('.react-btn, .reaction-option');
        if (!target) return;
        if (suppressNextClick) {
            suppressNextClick = false;
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);

    // Suppress the native long-press context menu on the react button and the
    // picker (CSS -webkit-touch-callout handles iOS Safari; this catches
    // Android Chrome and anything that ignores the CSS hint).
    document.addEventListener('contextmenu', function (e) {
        if (e.target.closest('.react-btn') ||
            e.target.closest('.reaction-picker') ||
            e.target.closest('.reaction-option')) {
            e.preventDefault();
        }
    });
})();
