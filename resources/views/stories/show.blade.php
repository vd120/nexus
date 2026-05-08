<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Story - {{ $user->username }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/stories-show.css') }}">
    <script>
        window.reactionImages = {};
        window.getReactionImage = function(emoji) { return null; };
    </script>
</head>
<body>
    <div class="story-viewer">
        <div class="story-overlay" onclick="closeViewer()"></div>

        <div class="story-container">
            <!-- Progress Bars -->
            <div class="story-progress">
                @for($i = 0; $i < $stories->count(); $i++)
                    <div class="progress-bar {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}">
                        <div class="progress-fill"></div>
                    </div>
                @endfor
            </div>

            <!-- Header -->
            <div class="story-header">
                <div class="story-user">
                    <img src="{{ $user->avatar_url }}" alt="Avatar" class="story-avatar">
                    <div class="story-info">
                        <span class="story-username">{{ $user->username }}</span>
                        <span class="story-time" id="story-time"></span>
                    </div>
                </div>
                <div class="story-header-actions">
                    @if($user->id === auth()->id())
                        <button class="story-delete" onclick="deleteStory('{{ $stories->first()->slug }}')" title="{{ __('messages.delete_story') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Content -->
            <div class="story-content">
                @foreach($stories as $index => $story)
                    <div class="story-slide {{ $index === 0 ? 'active' : '' }}" data-story-slug="{{ $story->slug }}" data-index="{{ $index }}" data-created-at="{{ $story->created_at }}">
                        @if($story->media_type === 'text')
                            {{-- Text-only story with custom background --}}
                            @php
                                $bgColor = $story->metadata['bg_color'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                            @endphp
                            <div class="story-text-container" style="background: {{ $bgColor }}">
                                <div class="story-text-content">{{ $story->content }}</div>
                            </div>
                        @else
                            {{-- Media story (image or video) --}}
                            @if($story->media_type === 'image')
                                <img src="{{ asset('storage/' . $story->media_path) }}" alt="Story" class="story-media">
                            @else
                                <video autoplay muted class="story-media" playsinline loop>
                                    <source src="{{ asset('storage/' . $story->media_path) }}" type="video/mp4">
                                </video>
                            @endif

                            @if(isset($story->content) && strlen(trim($story->content)) > 0)
                                <div class="story-caption-wrapper">
                                    <div class="story-caption">{{ $story->content }}</div>
                                    @if(mb_strlen($story->content) > 120)
                                        <button class="show-more-btn" onclick="toggleCaption(this, event)">{{ __('messages.show_more') }}</button>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Tap Areas -->
            <div class="tap-area tap-left" onclick="previousStory()"></div>
            <div class="tap-area tap-right" onclick="nextStory()"></div>

            <!-- Navigation -->
            <button class="nav-btn prev-btn" onclick="previousStory()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="nav-btn next-btn" onclick="nextStory()">
                <i class="fas fa-chevron-right"></i>
            </button>

            <!-- Controls -->
            <div class="story-controls">
                @if($user->id === auth()->id())
                    <a href="{{ route('stories.viewers', [$user, $stories->first()]) }}" class="control-btn viewers-btn" title="{{ __('messages.view_who_watched') }}">
                        <i class="fas fa-eye"></i>
                        <span>{{ $stories->first()->storyViews->count() ?? 0 }}</span>
                    </a>
                @else
                    <div></div>
                @endif

                <!-- Message Input - Only show if not viewing own story -->
                @if($user->id !== auth()->id())
                <div class="story-message-input-wrapper">
                    <input type="text" class="story-message-input" id="story-message" placeholder="{{ __('messages.send_message') }}" onkeypress="handleMessageKeypress(event)">
                    <button class="story-send-btn" onclick="sendStoryMessage()" id="story-send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <div class="story-sending-indicator" id="story-sending-indicator" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>{{ __('messages.sending_reply') }}</span>
                    </div>
                </div>
                @endif

                <button class="control-btn reaction-btn" onclick="toggleReaction()" id="reaction-btn">
                    <i class="fas fa-heart"></i>
                </button>
            </div>



        </div>
    </div>

    <script>
        let currentIndex = 0;
        const stories = document.querySelectorAll('.story-slide');
        const progressBars = document.querySelectorAll('.progress-bar');
        let storyTimer = null;
        let isPaused = false;
        let messageInput = null;


        // Clear timer completely
        function clearStoryTimer() {
            if (storyTimer) {
                clearTimeout(storyTimer);
                storyTimer = null;
            }
        }

        // Initialize message input reference
        function initMessageInput() {
            messageInput = document.getElementById('story-message');
            if (messageInput) {
                // Focus: Pause timer immediately
                messageInput.addEventListener('focus', function() {
                    isPaused = true;
                    clearStoryTimer();
                    
                    // Also pause CSS animation
                    const activeBar = progressBars[currentIndex];
                    if (activeBar) {
                        const fill = activeBar.querySelector('.progress-fill');
                        if (fill) {
                            fill.classList.add('paused');
                            fill.style.animationPlayState = 'paused';
                        }
                    }
                });

                // Blur: Resume only if empty
                messageInput.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        isPaused = false;
                        
                        // Resume CSS animation
                        const activeBar = progressBars[currentIndex];
                        if (activeBar) {
                            const fill = activeBar.querySelector('.progress-fill');
                            if (fill) {
                                fill.classList.remove('paused');
                                fill.style.animationPlayState = 'running';
                            }
                        }
                        
                        startTimer();
                    }
                });

                // Input: Keep timer paused while typing
                messageInput.addEventListener('input', function() {
                    isPaused = true;
                    clearStoryTimer();
                });

                // Also pause on click/touch
                messageInput.addEventListener('click', function() {
                    isPaused = true;
                    clearStoryTimer();
                });
            }
        }

        function startTimer() {
            if (isPaused) return;
            clearTimeout(storyTimer);
            const currentStory = stories[currentIndex];
            const isVideo = currentStory.querySelector('video');
            
            if (isVideo) {
                // For videos, wait for video to end or use a minimum timeout
                const video = isVideo;
                
                // Max timeout is 1 minute (60000ms)
                const maxTimeout = 60000;
                
                // If video is already loaded and has a valid duration
                if (video.duration && !isNaN(video.duration) && video.duration > 0) {
                    // Calculate remaining time if video is playing
                    const remainingTime = (video.duration - video.currentTime) * 1000;
                    // Use at least 1 second minimum, at most the remaining video time + buffer, capped at 1 minute
                    const duration = Math.min(maxTimeout, Math.max(1000, remainingTime + 500));
                    
                    storyTimer = setTimeout(() => {
                        nextStory();
                    }, duration);
                    
                    // Also listen for video ended event as backup
                    video.onended = function() {
                        clearTimeout(storyTimer);
                        nextStory();
                    };
                } else {
                    // If video duration not available, use default 30 seconds
                    // and try to detect when video ends
                    const duration = 30000;
                    
                    storyTimer = setTimeout(() => {
                        nextStory();
                    }, duration);
                    
                    // Try to detect when video ends
                    video.addEventListener('loadedmetadata', function() {
                        if (video.duration && !isNaN(video.duration) && video.duration > 0) {
                            clearTimeout(storyTimer);
                            const remainingTime = (video.duration - video.currentTime) * 1000;
                            const videoDuration = Math.min(maxTimeout, Math.max(1000, remainingTime + 500));
                            
                            storyTimer = setTimeout(() => {
                                nextStory();
                            }, videoDuration);
                            
                            video.onended = function() {
                                clearTimeout(storyTimer);
                                nextStory();
                            };
                        }
                    });
                }
            } else {
                // Check if it's a text story or image story
                const isTextStory = currentStory.querySelector('.story-text-container');
                
                if (isTextStory) {
                    // For text stories, give more time to read (7 seconds)
                    const duration = 7000;
                    storyTimer = setTimeout(() => {
                        nextStory();
                    }, duration);
                } else {
                    // For images, use 5 seconds
                    const duration = 5000;
                    storyTimer = setTimeout(() => {
                        nextStory();
                    }, duration);
                }
            }
        }

        function pauseTimer() {
            isPaused = true;
            clearStoryTimer();
            // Also pause the CSS animation
            const activeBar = progressBars[currentIndex];
            if (activeBar) {
                const fill = activeBar.querySelector('.progress-fill');
                if (fill) {
                    fill.classList.add('paused');
                    fill.style.animationPlayState = 'paused';
                }
            }
        }

        function resumeTimer() {
            isPaused = false;
            // Remove the paused class to resume CSS animation
            const activeBar = progressBars[currentIndex];
            if (activeBar) {
                const fill = activeBar.querySelector('.progress-fill');
                if (fill) {
                    fill.classList.remove('paused');
                    fill.style.animationPlayState = 'running';
                }
            }
            startTimer();
        }

        function updateDisplay() {
            stories.forEach((slide, i) => {
                slide.classList.toggle('active', i === currentIndex);
            });

            progressBars.forEach((bar, i) => {
                bar.classList.remove('active', 'completed');
                const fill = bar.querySelector('.progress-fill');
                
                if (i < currentIndex) {
                    bar.classList.add('completed');
                    if (fill) {
                        fill.style.width = '100%';
                        fill.style.animation = 'none';
                    }
                } else if (i === currentIndex) {
                    bar.classList.add('active');
                    // Get the story at this index
                    const storySlide = stories[i];
                    const isVideo = storySlide.querySelector('video');
                    
                    // Set animation duration based on content type
                    let duration = 5; // default seconds for images
                    
                    if (isVideo) {
                        // For videos, we'll try to get the duration
                        const video = isVideo;
                        if (video.duration && !isNaN(video.duration) && video.duration > 0) {
                            duration = Math.ceil(video.duration);
                        } else {
                            duration = 30; // default for videos if duration not available
                        }
                    }
                    
                    if (fill) {
                        // Remove old animation and restart
                        fill.style.animation = 'none';
                        fill.offsetHeight; // Trigger reflow
                        fill.style.animation = `progress ${duration}s linear forwards`;
                        
                        // If paused, keep it paused
                        if (isPaused) {
                            fill.classList.add('paused');
                            fill.style.animationPlayState = 'paused';
                        } else {
                            fill.classList.remove('paused');
                            fill.style.animationPlayState = 'running';
                        }
                    }
                } else {
                    if (fill) {
                        fill.style.width = '0%';
                        fill.style.animation = 'none';
                    }
                }
            });

            const activeStory = stories[currentIndex];
            const timeEl = document.getElementById('story-time');
            if (timeEl && activeStory) {
                const createdAt = new Date(activeStory.dataset.createdAt);
                timeEl.textContent = timeAgo(createdAt);
            }

            const video = activeStory.querySelector('video');
            if (video) {
                video.currentTime = 0;
                video.play();
            }

            startTimer();
            checkUserReaction();
        }

        function nextStory() {
            if (currentIndex < stories.length - 1) {
                currentIndex++;
                updateDisplay();
            } else {
                closeViewer();
            }
        }

        function previousStory() {
            if (currentIndex > 0) {
                // Clear timer before changing index to prevent race conditions
                clearStoryTimer();
                
                // Reset the progress bar for the story we're going back to
                const prevIndex = currentIndex - 1;
                const prevBar = progressBars[prevIndex];
                if (prevBar) {
                    const fill = prevBar.querySelector('.progress-fill');
                    if (fill) {
                        fill.style.width = '0%';
                        fill.style.animation = 'none';
                        fill.offsetHeight; // Trigger reflow
                        fill.style.animation = `progress 5s linear forwards`;
                    }
                }
                
                currentIndex--;
                updateDisplay();
            }
        }

        function closeViewer() {
            clearTimeout(storyTimer);
            // Check if user came from home page
            const urlParams = new URLSearchParams(window.location.search);
            const from = urlParams.get('from');
            if (from === 'home') {
                window.location.href = '{{ route("home") }}';
            } else {
                window.location.href = '{{ route("stories.index") }}';
            }
        }

        function toggleReaction() {
            const btn = document.getElementById('reaction-btn');
            if (!btn) return;

            const story = stories[currentIndex];
            const storySlug = story.dataset.storySlug;
            const username = '{{ $user->username }}';
            
            const hasReaction = btn.classList.contains('has-reaction');
            
            // Optimistic UI Update: Toggle immediately
            updateReactionButton(!hasReaction);

            if (hasReaction) {
                // Was liked, now remove
                removeReaction(storySlug, username, true);
            } else {
                // Was not liked, now add
                addReaction(storySlug, username, '❤️', true);
            }
        }

        function addReaction(storySlug, username, emoji, isOptimistic = false) {
            fetch('/stories/' + username + '/' + storySlug + '/react', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ reaction_type: emoji })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success && isOptimistic) {
                    // Revert UI if server failed
                    updateReactionButton(false);
                }
            })
            .catch(err => {
                console.error('Error adding reaction:', err);
                if (isOptimistic) updateReactionButton(false);
            });
        }

        function removeReaction(storySlug, username, isOptimistic = false) {
            fetch('/stories/' + username + '/' + storySlug + '/react', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success && isOptimistic) {
                    // Revert UI if server failed
                    updateReactionButton(true);
                }
            })
            .catch(err => {
                console.error('Error removing reaction:', err);
                if (isOptimistic) updateReactionButton(true);
            });
        }

        function updateReactionButton(hasReaction) {
            const btn = document.getElementById('reaction-btn');
            if (!btn) return;

            if (hasReaction) {
                btn.classList.add('has-reaction');
            } else {
                btn.classList.remove('has-reaction');
            }
        }

        function checkUserReaction() {
            const story = stories[currentIndex];
            if (!story) return;
            const storySlug = story.dataset.storySlug;
            const username = '{{ $user->username }}';

            fetch('/stories/' + username + '/' + storySlug + '/check-reaction')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.has_reaction) {
                        updateReactionButton(true);
                    }
                })
                .catch(err => console.error('Error checking reaction:', err));
        }



        function deleteStory(storySlug) {
            if (!confirm('{{ __('messages.delete_story_confirm') }}')) return;

            const username = '{{ $user->username }}';
            fetch('/stories/' + username + '/' + storySlug, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store flag in localStorage for the redirect page
                    localStorage.setItem('story_deleted', 'true');
                    window.location.href = '{{ route("stories.index") }}';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showToast === 'function') {
                    const t = window.chatTranslations || {};
                    showToast(t.failed_to_delete_story || '{{ __('messages.failed_to_delete_story') }}', 'error');
                }
            });
        }

        function toggleCaption(btn, event) {
            if (event) event.stopPropagation();
            const wrapper = btn.closest('.story-caption-wrapper');
            const caption = wrapper.querySelector('.story-caption');
            const isExpanded = wrapper.classList.contains('expanded');
            
            if (isExpanded) {
                wrapper.classList.remove('expanded');
                btn.textContent = '{{ __('messages.show_more') }}';
                resumeTimer();
            } else {
                wrapper.classList.add('expanded');
                btn.textContent = '{{ __('messages.show_less') }}';
                pauseTimer();
            }
        }

        function timeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            const t = window.chatTranslations || {};
            if (seconds < 60) return t.just_now || 'Just now';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return minutes + (t.minutes_ago_short || 'm ago');
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + (t.hours_ago_short || 'h ago');
            return Math.floor(hours / 24) + (t.days_ago_short || 'd ago');
        }

        function handleMessageKeypress(e) {
            if (e.key === 'Enter') {
                sendStoryMessage();
            }
        }

        async function sendStoryMessage() {
            const input = document.getElementById('story-message');
            const sendBtn = document.getElementById('story-send-btn');
            const sendingIndicator = document.getElementById('story-sending-indicator');
            const message = input.value.trim();

            if (!message) return;

            // Pause timer while sending
            pauseTimer();

            // Hide send button, show sending indicator
            sendBtn.style.display = 'none';
            sendingIndicator.style.display = 'flex';

            const storyAuthorId = '{{ $user->id }}';
            const storyAuthorName = '{{ $user->username }}';
            const currentStorySlug = stories[currentIndex].dataset.storySlug;

            // Add "from story" indicator to message
            const messageWithIndicator = `📸 Reply to your story: ${message}`;

            try {
                // First, get or create conversation
                const response = await fetch('/chat/start/' + storyAuthorId, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!data.success) {
                    // Handle error
                    const t = window.chatTranslations || {};
                    if (typeof showToast === 'function') {
                        showToast(data.error || (t.failed_to_send_message || '{{ __('messages.failed_to_send_message') }}'), 'error');
                    }
                    sendBtn.style.display = 'flex';
                    sendingIndicator.style.display = 'none';
                    resumeTimer();
                    return;
                }

                // Use slug for route (Conversation model uses slug as route key)
                const conversationSlug = data.slug;

                if (conversationSlug) {
                    // Now send the message to this conversation
                    const messageResponse = await fetch('/chat/' + conversationSlug, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            content: messageWithIndicator,
                            story_slug: currentStorySlug
                        })
                    });

                    const messageData = await messageResponse.json();

                    if (messageData.success) {
                        // Show success feedback with WhatsApp-style toast
                        // Store in sessionStorage so it persists even if page closes
                        sessionStorage.setItem('storyReplySent', JSON.stringify({
                            message: 'Reply sent to ' + storyAuthorName + ' 📸',
                            type: 'success',
                            time: Date.now()
                        }));

                        const t = window.chatTranslations || {};
                        if (typeof showToast === 'function') {
                            showToast(t.story_shared_success || '{{ __('messages.story_shared_success') }}', 'success', null, null, 5000);
                        }
                    } else {
                        const t = window.chatTranslations || {};
                        if (typeof showToast === 'function') {
                            showToast(t.failed_to_send_message || '{{ __('messages.failed_to_send_message') }}', 'error', null, null, 5000);
                        }
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                const t = window.chatTranslations || {};
                if (typeof showToast === 'function') {
                    showToast(t.failed_to_send_message || '{{ __('messages.failed_to_send_message') }}', 'error', null, null, 5000);
                }
            }

            // Clear input and restore button
            input.value = '';
            sendBtn.style.display = 'flex';
            sendingIndicator.style.display = 'none';
            
            // Resume timer after sending
            resumeTimer();
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            initMessageInput();
            updateDisplay();
            startTimer();

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') nextStory();
                if (e.key === 'ArrowLeft') previousStory();
                if (e.key === 'Escape') closeViewer();
            });
            
            // Check if there's a pending story reply toast from navigation
            const pendingToast = sessionStorage.getItem('storyReplySent');
            if (pendingToast) {
                const toastData = JSON.parse(pendingToast);
                // Only show if it's recent (within 10 seconds)
                if (Date.now() - toastData.time < 10000) {
                    setTimeout(() => {
                        if (typeof showToast === 'function') {
                            showToast(toastData.message, toastData.type, null, null, 5000);
                        }
                        sessionStorage.removeItem('storyReplySent');
                    }, 500);
                } else {
                    sessionStorage.removeItem('storyReplySent');
                }
            }
            
            // Check if current user has already reacted to this story
            checkUserReaction();
        });
    </script>
</body>
</html>
