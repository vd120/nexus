@extends('layouts.app')

@section('title', __('messages.home'))
@section('main_class', 'full-width')
@section('content_class', 'full-width')

@push('styles')
@vite(['resources/css/posts-index.css', 'resources/css/chat-show.css'])
<style>
/* ============================================================
   FEED PAGE OVERRIDES (feed-specific UI only — composer, empty state, skeleton)
   Post-card improvements live in partial-posts.css so they ripple
   to every page that includes partials.post.
   ============================================================ */

/* Accessibility helper */
.visually-hidden, .sr-only {
    position: absolute;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
}

/* Composer pill: brand color icon, real button semantics */
.composer-pill .pill-icon-btn i { color: var(--primary) !important; }
.composer-pill-btn {
    all: unset;
    box-sizing: border-box;
    cursor: pointer;
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
}
.composer-pill-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
    border-radius: 999px;
}
.composer-expanded .post-action-btn i { color: var(--primary) !important; }

/* Empty state: warmer, gradient bubble, primary CTA */
.empty-state {
    padding: 80px 24px 60px;
    text-align: center;
    background: var(--surface);
    border: 1px dashed var(--border);
    border-radius: 24px;
    margin: 12px 0;
}
.empty-state .empty-state-icon-wrap {
    width: 84px;
    height: 84px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.12));
    display: flex;
    align-items: center;
    justify-content: center;
}
.empty-state .empty-state-icon-wrap i {
    font-size: 32px;
    color: var(--primary);
    width: auto;
    margin: 0;
    opacity: 1;
    display: inline;
}
.empty-state h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 6px;
    letter-spacing: -0.01em;
}
.empty-state p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 24px;
}
.empty-state-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 999px;
    background: var(--gradient-brand, linear-gradient(135deg, var(--primary), #8b5cf6));
    color: #fff;
    border: none;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: filter 0.18s ease, transform 0.18s ease;
}
@media (hover: hover) {
    .empty-state-cta:hover { filter: brightness(1.08); transform: translateY(-1px); }
}

/* Skeleton loader for infinite scroll */
.post-skeleton {
    border-radius: 18px;
    border: 1px solid var(--border);
    padding: 16px;
    background: var(--surface);
    margin-bottom: 16px;
}
.post-skeleton .row { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
.skeleton-shape {
    background: linear-gradient(90deg, var(--surface-hover) 0%, var(--border) 50%, var(--surface-hover) 100%);
    background-size: 200% 100%;
    animation: skeletonShimmer 1.4s ease-in-out infinite;
    border-radius: 8px;
}
.skeleton-avatar { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; }
.skeleton-line { height: 12px; flex: 1; }
.skeleton-line.short { max-width: 40%; }
.skeleton-line.long { max-width: 90%; }
.skeleton-media { height: 220px; border-radius: 14px; margin: 8px 0; }
.skeleton-actions { display: flex; gap: 16px; margin-top: 14px; }
.skeleton-action { width: 64px; height: 28px; border-radius: 999px; }
@keyframes skeletonShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
@endpush

@section('content')

<div class="feed-layout">
    @auth
        @include('partials.sidebars.left')
    @endauth
    <div class="feed-container">
        @if(session('verified'))
            <script>showToast('{{ __('messages.email_verified_success_toast') }}', 'success');</script>
        @endif

        @if(request()->query('share-intent') === '1' && session()->has('share_target_payload'))
        <script>window.__shareTargetPayload = @json(session()->pull('share_target_payload'));</script>
        @endif

        @auth
        {{-- Pulse — visible only on mobile/tablet where the right sidebar is hidden --}}
        <div class="pulse-mobile-host">
            @include('partials.pulse-card', ['variant' => 'mobile'])
        </div>
        {{-- Memory Prompt — weekly contemplative card, mobile variant --}}
        <div class="memory-mobile-host">
            @include('partials.memory-prompt-card', ['variant' => 'mobile'])
        </div>
        @endauth

        @auth
        {{-- Stories - Always show section --}}
        @php
            $viewedStoryIds = collect();
            if (auth()->check()) {
                $viewedStoryIds = auth()->user()->storyViews()->pluck('story_id');
            }
        @endphp
        <div class="stories-section">
            <div class="stories-header">
                <h3>{{ __('messages.stories') }}</h3>
                <a href="{{ route('stories.index') }}" class="btn btn-ghost" style="padding: 6px 12px; font-size: 13px;">
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i> {{ __('messages.view_all_stories') }}
                </a>
            </div>
            <div class="stories-scroll" id="stories-scroll">
                @if($myStories->count() > 0)
                    @php
                    $latestMyStory = $myStories->sortByDesc('created_at')->first();
                    @endphp
                    <button type="button" class="story-item" onclick="viewStoryFromHome('{{ auth()->user()->username }}', '{{ $latestMyStory->slug }}')" aria-label="{{ __('messages.your_story') }}">
                        <div class="story-avatar-wrapper">
                            <div class="story-avatar">
                                <img src="{{ auth()->user()->avatar_url }}" alt="">
                            </div>
                        </div>
                        <div class="story-name">{{ __('messages.your_story') }}</div>
                    </button>
                @else
                <button type="button" class="story-item create" onclick="window.location.href='{{ route('stories.create') }}'" aria-label="{{ __('messages.your_story') }}">
                    <div class="story-avatar-wrapper">
                        <div class="story-avatar">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="story-name">{{ __('messages.your_story') }}</div>
                </button>
                @endif

                @foreach($followedUsersWithStories as $user)
                    @php
                    $latestStory = $user->activeStories->sortByDesc('created_at')->first();
                    $isUnread = $latestStory && !$viewedStoryIds->contains($latestStory->id);
                    @endphp
                    @if($latestStory)
                    <button type="button" class="story-item {{ $isUnread ? 'unread' : '' }}" data-username="{{ $user->username }}" onclick="viewStoryFromHome('{{ $user->username }}', '{{ $latestStory->slug }}')" aria-label="{{ $user->username }}">
                        <div class="story-avatar-wrapper">
                            <div class="story-avatar">
                                <img src="{{ $user->avatar_url }}" alt="">
                            </div>
                        </div>
                        <div class="story-name">{{ $user->username }}</div>
                    </button>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Create Post - Pill default, expands on click --}}
        <div class="create-post" id="composer">
            {{-- Compact pill (default) - real button for keyboard/SR --}}
            <button type="button" class="composer-pill composer-pill-btn" id="composer-pill" aria-label="{{ __('messages.whats_on_your_mind') }}" aria-expanded="false" aria-controls="composer-expanded-region">
                <img src="{{ auth()->user()->avatar_url }}" alt="" class="avatar">
                <span class="prompt">{{ __('messages.whats_on_your_mind') }}</span>
                <span class="pill-actions">
                    <span class="pill-icon-btn" aria-hidden="true">
                        <i class="fas fa-image"></i>
                    </span>
                    <span class="pill-icon-btn" id="pill-poll-btn" aria-label="{{ __('posts.add_poll') }}" title="{{ __('posts.add_poll') }}" role="button" tabindex="0">
                        <i class="fas fa-poll"></i>
                    </span>
                </span>
            </button>
            {{-- Expanded full editor --}}
            <div class="composer-expanded" id="composer-expanded-region">
                <div class="create-post-header">
                    <img src="{{ auth()->user()->avatar_url }}" alt="" class="create-post-avatar">
                    <div class="create-post-author-info">
                        <span class="create-post-author">{{ auth()->user()->name ?: auth()->user()->username }}</span>
                        <span class="create-post-handle">{{ '@' . auth()->user()->username }}</span>
                    </div>
                    <button type="button" class="privacy-btn" id="privacy-btn" onclick="togglePrivacy()" aria-label="{{ __('messages.public') }}">
                        <i class="fas fa-globe" id="privacy-icon" aria-hidden="true"></i> <span id="privacy-text">{{ __('messages.public') }}</span>
                    </button>
                </div>
                <label for="post-content" class="visually-hidden">{{ __('messages.whats_on_your_mind') }}</label>
                <textarea id="post-content" placeholder="{{ __('messages.whats_on_your_mind') }}" dir="auto" style="margin-top: 12px;"></textarea>
                <div id="hashtag-suggestions" class="hashtag-suggestions" style="display: none;"></div>
                <div class="post-actions">
                    <div class="post-actions-left">
                        <label for="media" class="post-action-btn" style="cursor: pointer;">
                            <i class="fas fa-image" aria-hidden="true"></i> <span>{{ __('messages.media') }}</span>
                        </label>
                        <input type="file" id="media" accept="image/*,video/*" multiple style="display: none;" onchange="previewMedia(this)">
                        <button type="button" class="post-action-btn" id="poll-toggle-btn" onclick="togglePollBuilder()" title="{{ __('posts.add_poll') }}" aria-label="{{ __('posts.add_poll') }}">
                            <i class="fas fa-poll" aria-hidden="true"></i> <span>{{ __('posts.add_poll') }}</span>
                        </button>
                        <button type="button" class="post-action-btn sensitive-pill" id="sensitive-toggle-btn" onclick="toggleSensitive()" aria-label="{{ __('posts.mark_as_sensitive') }}">
                            <i class="fas fa-eye-slash" aria-hidden="true"></i>
                            <span>{{ __('posts.mark_as_sensitive') }}</span>
                        </button>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="submitPost()">
                        {{ __('messages.post') }}
                    </button>
                </div>
                <input type="hidden" id="is-private" value="0">
                <input type="hidden" id="is-sensitive" value="0">
                {{-- Poll builder --}}
                <div id="poll-builder"
                    data-label-option="{{ __('posts.poll_option_placeholder') }}"
                    data-label-max="{{ __('posts.poll_max_options') }}"
                    style="display:none; margin-top:12px; padding:12px; border:1px solid var(--border-color,#2a2a3e); border-radius:8px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <strong style="font-size:.875rem;">{{ __('posts.poll_add_title') }}</strong>
                        <button type="button" onclick="togglePollBuilder()" aria-label="{{ __('auth.close') }}" style="background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.7;font-size:1rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;transition:opacity .15s,background .15s;" onmouseover="this.style.opacity='1';this.style.background='rgba(255,255,255,.07)'" onmouseout="this.style.opacity='.7';this.style.background='none'">&times;</button>
                    </div>
                    <input type="text" id="poll-question" placeholder="{{ __('posts.poll_question_placeholder') }}" maxlength="255" style="width:100%;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;margin-bottom:8px;">
                    <div id="poll-options-list">
                        <div class="poll-opt-row" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <input type="text" class="poll-opt-input" placeholder="{{ __('posts.poll_option_placeholder', ['n' => 1]) }}" maxlength="255" style="flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;">
                            <button type="button" onclick="removePollOption(this)" style="visibility:hidden;flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.opacity='1';this.style.background='rgba(255,255,255,.07)'" onmouseout="this.style.opacity='.6';this.style.background='none'">&times;</button>
                        </div>
                        <div class="poll-opt-row" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <input type="text" class="poll-opt-input" placeholder="{{ __('posts.poll_option_placeholder', ['n' => 2]) }}" maxlength="255" style="flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;">
                            <button type="button" onclick="removePollOption(this)" style="visibility:hidden;flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.opacity='1';this.style.background='rgba(255,255,255,.07)'" onmouseout="this.style.opacity='.6';this.style.background='none'">&times;</button>
                        </div>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;flex-wrap:wrap;gap:8px;">
                        <button type="button" id="add-poll-option" onclick="addPollOption()" style="font-size:.8rem;background:none;border:1px dashed var(--primary,#8b5cf6);border-radius:6px;cursor:pointer;color:var(--primary,#8b5cf6);padding:.3rem .65rem;transition:background .15s,opacity .15s;" onmouseover="this.style.background='rgba(139,92,246,.1)'" onmouseout="this.style.background='none'">{{ __('posts.poll_add_option') }}</button>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <label for="poll-expiry" style="font-size:.8rem;opacity:.7;">{{ __('posts.poll_ends_label') }}</label>
                            <select id="poll-expiry" style="font-size:.8rem;padding:.25rem .5rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;">
                                <option value="">{{ __('posts.poll_never') }}</option>
                                <option value="1d">{{ __('posts.poll_1d') }}</option>
                                <option value="3d">{{ __('posts.poll_3d') }}</option>
                                <option value="7d">{{ __('posts.poll_7d') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div id="media-preview-container" style="display: none; margin-top: 12px;">
                    <div id="media-previews" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
                </div>
                <div id="post-upload-progress" style="display:none;margin-top:8px;">
                    <div style="height:4px;background:rgba(139,92,246,0.15);border-radius:2px;overflow:hidden;">
                        <div id="post-upload-progress-fill" style="height:100%;width:0%;background:var(--primary,#8b5cf6);transition:width 0.2s ease;border-radius:2px;"></div>
                    </div>
                    <span id="post-upload-progress-text" style="font-size:11px;color:var(--text-muted,#6b7280);float:right;margin-top:3px;">0%</span>
                </div>
            </div>
        </div>
        @endauth

        {{-- Posts Feed --}}
        <div class="posts-feed" id="posts-container">
            @forelse($posts as $post)
                @include('partials.post', ['post' => $post])
            @empty
                <div class="empty-state">
                    <div class="empty-state-icon-wrap" aria-hidden="true">
                        <i class="fas fa-feather-pointed"></i>
                    </div>
                    <h3>{{ __('messages.no_posts_yet') }}</h3>
                    <p>{{ __('messages.be_first_to_post') }}</p>
                    @auth
                        <button type="button" class="empty-state-cta" onclick="document.getElementById('composer-pill').click();">
                            <i class="fas fa-pen" aria-hidden="true"></i>
                            {{ __('messages.post') }}
                        </button>
                    @endauth
                </div>
            @endforelse

            {{-- Loading Indicator for Infinite Scroll: skeleton cards --}}
            <div id="infinite-scroll-loader" style="display: none;" aria-live="polite" aria-label="Loading more posts">
                @for ($i = 0; $i < 2; $i++)
                <div class="post-skeleton" aria-hidden="true">
                    <div class="row">
                        <div class="skeleton-shape skeleton-avatar"></div>
                        <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                            <div class="skeleton-shape skeleton-line short"></div>
                            <div class="skeleton-shape skeleton-line short" style="max-width:25%;"></div>
                        </div>
                    </div>
                    <div class="skeleton-shape skeleton-line long" style="margin-bottom:6px;"></div>
                    <div class="skeleton-shape skeleton-line long" style="max-width:65%;"></div>
                    <div class="skeleton-shape skeleton-media"></div>
                    <div class="skeleton-actions">
                        <div class="skeleton-shape skeleton-action"></div>
                        <div class="skeleton-shape skeleton-action"></div>
                        <div class="skeleton-shape skeleton-action"></div>
                    </div>
                </div>
                @endfor
            </div>

            {{-- No More Posts Message --}}
            <div id="no-more-posts" style="display: none; text-align: center; padding: 30px; color: var(--text-muted);">
                <i class="fas fa-check-circle" style="font-size: 28px; margin-bottom: 10px; opacity: 0.5;" aria-hidden="true"></i>
                <p>{{ __('messages.no_more_posts') }}</p>
            </div>
        </div>

        @guest
        <div class="guest-cta">
            <h3>{{ __('messages.join_community') }}</h3>
            <p>{{ __('messages.sign_up_to_post') }}</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <a href="{{ route('register') }}" class="btn btn-primary">{{ __('messages.sign_up') }}</a>
                <a href="{{ route('login') }}" class="btn">{{ __('messages.sign_in') }}</a>
            </div>
        </div>
        @endguest
    </div>

    @auth
        @include('partials.sidebars.right')
    @endauth

</div>

@auth
    {{-- Floating Action Button to toggle Chat Drawer --}}
    <button class="floating-chat-btn" id="chat-drawer-toggle" title="{{ __('chat.messages') }}" aria-label="{{ __('chat.messages') }}">
        <i class="fa-regular fa-comment-dots" aria-hidden="true"></i>
        @php
            $unreadMessagesCount = \App\Models\Message::where('sender_id', '!=', auth()->id())
                ->whereNull('read_at')
                ->whereHas('conversation', function($q) {
                    $q->where('user1_id', auth()->id())
                      ->orWhere('user2_id', auth()->id())
                      ->orWhereHas('group.members', function($q2) {
                          $q2->where('user_id', auth()->id());
                      });
                })
                ->count();
        @endphp
        <span class="btn-badge" id="chat-drawer-badge" style="{{ $unreadMessagesCount > 0 ? 'display: flex;' : 'display: none;' }}">
            {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
        </span>
    </button>

    {{-- Sliding Chat Drawer --}}
    <div class="chat-drawer" id="chat-drawer">
        <div class="chat-drawer-header">
            <button class="chat-drawer-close" id="chat-drawer-close" title="{{ __('messages.close') }}" aria-label="{{ __('messages.close') }}">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="chat-drawer-body">
            <div class="drawer-loader" id="chat-drawer-loader">
                <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                <span>{{ __('notifications.loading') }}...</span>
            </div>
            <iframe class="chat-iframe" id="chat-drawer-iframe" data-src="{{ route('chat.index') }}" title="{{ __('chat.messages') }}"></iframe>
        </div>
    </div>
@endauth


<script>
// Composer pill → expanded panel (instant pill swap, animated expand)
(function () {
    function openComposer() {
        const composer = document.getElementById('composer');
        if (!composer || composer.classList.contains('is-open')) return;
        const pill = document.getElementById('composer-pill');
        if (pill) pill.setAttribute('aria-expanded', 'true');
        composer.classList.add('is-open');
    }
    document.addEventListener('DOMContentLoaded', function () {
        const pill = document.getElementById('composer-pill');
        if (pill) pill.addEventListener('click', function (e) {
            const openPoll = !!e.target.closest('#pill-poll-btn');
            openComposer();
            if (openPoll) {
                const builder = document.getElementById('poll-builder');
                if (builder && builder.style.display === 'none') togglePollBuilder();
            }
        });

        // PWA shortcut: ?action=new-post — auto-open the composer
        if (new URLSearchParams(window.location.search).get('action') === 'new-post') {
            openComposer();
        }

        // Share Target pre-fill (PWA share sheet — additive)
        if (window.__shareTargetPayload) {
            const payload = window.__shareTargetPayload;
            const parts = [payload.title, payload.text, payload.url].filter(Boolean);
            const text = parts.join('\n');
            if (text) {
                const ta = document.getElementById('post-content');
                if (ta) ta.value = text;
            }
            openComposer();
        }
    });
})();

function togglePrivacy() {
    const input = document.getElementById('is-private');
    const icon = document.getElementById('privacy-icon');
    const text = document.getElementById('privacy-text');
    const btn = document.getElementById('privacy-btn');

    if (input.value == '0') {
        input.value = '1';
        icon.className = 'fas fa-lock';
        text.innerText = '{{ __('messages.private') }}';
        if (btn) btn.setAttribute('aria-label', '{{ __('messages.private') }}');
    } else {
        input.value = '0';
        icon.className = 'fas fa-globe';
        text.innerText = '{{ __('messages.public') }}';
        if (btn) btn.setAttribute('aria-label', '{{ __('messages.public') }}');
    }
}

function toggleSensitive() {
    const input = document.getElementById('is-sensitive');
    const btn = document.getElementById('sensitive-toggle-btn');
    if (input.value === '0') {
        input.value = '1';
        btn.classList.add('sensitive-pill--on');
        if (window.showToast) showToast('Post will be marked as sensitive content.', 'info');
    } else {
        input.value = '0';
        btn.classList.remove('sensitive-pill--on');
    }
}

function togglePollBuilder() {
    const builder = document.getElementById('poll-builder');
    const btn = document.getElementById('poll-toggle-btn');
    const isVisible = builder.style.display !== 'none';
    builder.style.display = isVisible ? 'none' : 'block';
    btn.style.opacity = isVisible ? '1' : '';
    btn.style.color = isVisible ? '' : 'var(--accent,#6366f1)';
}

function addPollOption() {
    const list = document.getElementById('poll-options-list');
    const builder = document.getElementById('poll-builder');
    const count = list.querySelectorAll('.poll-opt-input').length;
    const maxMsg = builder?.dataset.labelMax || 'Maximum 4 poll options.';
    const optionLabel = builder?.dataset.labelOption || 'Option :n';
    if (count >= 4) { if (window.showToast) showToast(maxMsg, 'info'); return; }

    const row = document.createElement('div');
    row.className = 'poll-opt-row';
    row.style.cssText = 'display:flex;align-items:center;gap:6px;margin-bottom:6px;';

    const inp = document.createElement('input');
    inp.type = 'text';
    inp.className = 'poll-opt-input';
    inp.placeholder = optionLabel.replace(':n', count + 1);
    inp.maxLength = 255;
    inp.style.cssText = 'flex:1;box-sizing:border-box;padding:.5rem .75rem;border-radius:6px;border:1px solid var(--border-color,#2a2a3e);background:var(--input-bg,#12121f);color:inherit;font-size:.875rem;';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = '&times;';
    btn.setAttribute('onclick', 'removePollOption(this)');
    btn.style.cssText = 'flex-shrink:0;background:none;border:1px solid var(--border-color,#2a2a3e);border-radius:6px;cursor:pointer;color:inherit;opacity:.6;font-size:.875rem;width:1.75rem;height:1.75rem;display:flex;align-items:center;justify-content:center;';
    btn.onmouseover = function() { this.style.opacity='1'; this.style.background='rgba(255,255,255,.07)'; };
    btn.onmouseout  = function() { this.style.opacity='.6'; this.style.background='none'; };

    row.appendChild(inp);
    row.appendChild(btn);
    list.appendChild(row);

    refreshPollDeleteButtons();
    if (count + 1 >= 4) document.getElementById('add-poll-option').style.display = 'none';
    inp.focus();
}

function removePollOption(btn) {
    const list = document.getElementById('poll-options-list');
    if (list.querySelectorAll('.poll-opt-input').length <= 2) return;
    btn.closest('.poll-opt-row').remove();
    refreshPollDeleteButtons();
    document.getElementById('add-poll-option').style.display = '';
    // Re-number placeholders
    const builder = document.getElementById('poll-builder');
    const optionLabel = builder?.dataset.labelOption || 'Option :n';
    list.querySelectorAll('.poll-opt-input').forEach((inp, i) => {
        if (!inp.value) inp.placeholder = optionLabel.replace(':n', i + 1);
    });
}

function refreshPollDeleteButtons() {
    const list = document.getElementById('poll-options-list');
    const rows = list.querySelectorAll('.poll-opt-row');
    const canDelete = rows.length > 2;
    rows.forEach(row => {
        const btn = row.querySelector('button');
        if (btn) btn.style.visibility = canDelete ? 'visible' : 'hidden';
    });
}

function viewStoryFromHome(username, slug) {
    if (window.NexusSoul) window.NexusSoul.feedback.open();
    window.location.href = `/stories/${username}/${slug}?from=home`;
}

function compressImageFile(file) {
    if (!file.type.startsWith('image/') || file.type === 'image/gif') return Promise.resolve(file);
    if (file.size < 150 * 1024) return Promise.resolve(file);
    return new Promise(function(resolve) {
        var img = new Image();
        var url = URL.createObjectURL(file);
        img.onload = function() {
            URL.revokeObjectURL(url);
            var w = img.naturalWidth, h = img.naturalHeight;
            if (w > 1280 || h > 1280) {
                if (w >= h) { h = Math.round(h * 1280 / w); w = 1280; }
                else { w = Math.round(w * 1280 / h); h = 1280; }
            }
            var canvas = document.createElement('canvas');
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            canvas.toBlob(function(blob) {
                if (!blob || blob.size >= file.size) { resolve(file); return; }
                resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.78);
        };
        img.onerror = function() { URL.revokeObjectURL(url); resolve(file); };
        img.src = url;
    });
}

window.previewMedia = async function(input) {
    if (!input || !input.files || input.files.length === 0) {
        // PWA: check if camera permission was denied (additive — no effect on normal browser flow)
        if (navigator.permissions && input.capture) {
            navigator.permissions.query({ name: 'camera' }).then(function (result) {
                if (result.state === 'denied' && window.showToast) {
                    window.showToast(
                        '{{ __('messages.camera_permission_denied', ['default' => 'Camera permission denied. Enable it in your device settings.']) }}',
                        'error', null, null, 5000
                    );
                }
            }).catch(function () {});
        }
        return;
    }
    for (var i = 0; i < input.files.length; i++) {
        uploadedFiles.push(await compressImageFile(input.files[i]));
    }
    renderMediaPreviews();
};

let uploadedFiles = [];

function renderMediaPreviews() {
    const container = document.getElementById('media-preview-container');
    const previews = document.getElementById('media-previews');
    if (!container || !previews) return;
    previews.innerHTML = '';

    if (uploadedFiles.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'block';

    const clearAllBtn = document.createElement('button');
    clearAllBtn.type = 'button';
    clearAllBtn.id = 'clear-all-media-btn';
    clearAllBtn.innerHTML = '<i class="fas fa-trash-alt" aria-hidden="true"></i> ' + ((window.chatTranslations && window.chatTranslations.clear_all) || 'Clear All');
    clearAllBtn.onclick = clearAllMedia;
    clearAllBtn.style.cssText = 'padding:8px 16px;background:rgba(220,38,38,0.1);color:#dc2626;border:1px solid rgba(220,38,38,0.3);border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.2s;margin-bottom:12px;width:100%;flex:0 0 100%;';
    previews.appendChild(clearAllBtn);

    uploadedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative;width:100px;height:100px;border-radius:12px;overflow:hidden;flex-shrink:0;';

            const media = file.type.startsWith('image/')
                ? `<img src="${e.target.result}" alt="" style="width:100%;height:100%;object-fit:cover;">`
                : `<video src="${e.target.result}" muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>`;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.setAttribute('aria-label', 'Remove');
            removeBtn.onclick = function() { removeMedia(index); };
            removeBtn.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            removeBtn.style.cssText = 'position:absolute;top:4px;right:4px;width:24px;height:24px;background:rgba(0,0,0,0.7);color:white;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:all 0.2s;z-index:10;-webkit-tap-highlight-color:transparent;';

            div.innerHTML = media;
            div.appendChild(removeBtn);
            previews.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

window.clearAllMedia = function() {
    if (uploadedFiles.length === 0) return;
    if (!confirm('{{ __('messages.remove_all_media_confirm') }}')) return;
    uploadedFiles = [];
    updateFileInput();
    renderMediaPreviews();
};

window.removeMedia = function(index) {
    uploadedFiles.splice(index, 1);
    updateFileInput();
    renderMediaPreviews();
};

function updateFileInput() {
    const fileInput = document.getElementById('media');
    if (!fileInput) return;
    const dt = new DataTransfer();
    uploadedFiles.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
}

window.submitPost = async function() {
    const contentEl = document.getElementById('post-content');
    const isPrivateEl = document.getElementById('is-private');
    const previewContainer = document.getElementById('media-preview-container');
    const previews = document.getElementById('media-previews');

    const content = (contentEl?.value || '').trim();
    const isPrivate = isPrivateEl?.value || '0';

    const pollBuilder = document.getElementById('poll-builder');
    const hasPoll = pollBuilder && pollBuilder.style.display !== 'none';

    if (!content && uploadedFiles.length === 0 && !hasPoll) {
        if (window.showToast) window.showToast('{{ __('messages.please_enter_content_or_media') }}', 'error');
        return;
    }

    const submitBtn = document.querySelector('button[onclick="submitPost()"]') || document.querySelector('.create-post .btn-primary');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> {{ __('messages.posting') }}';
        submitBtn.disabled = true;
    }

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
    formData.append('content', content);
    formData.append('is_private', isPrivate);
    formData.append('is_sensitive', document.getElementById('is-sensitive')?.value || '0');

    // Append poll data if builder is open
    if (pollBuilder && pollBuilder.style.display !== 'none') {
        const pollQ = document.getElementById('poll-question')?.value.trim();
        if (pollQ) formData.append('poll_question', pollQ);
        document.querySelectorAll('.poll-opt-input').forEach((inp, i) => {
            const val = inp.value.trim();
            if (val) formData.append(`poll_options[${i}]`, val);
        });
        const expiry = document.getElementById('poll-expiry')?.value;
        if (expiry) {
            const days = parseInt(expiry);
            const expiresAt = new Date(Date.now() + days * 86400000).toISOString().slice(0, 19).replace('T', ' ');
            formData.append('poll_expires_at', expiresAt);
        }
    }

    uploadedFiles.forEach((file, i) => formData.append(`media[${i}]`, file));

    try {
        const {ok, data} = await new Promise(function(resolve) {
            var xhr = new XMLHttpRequest();
            var progressBar = document.getElementById('post-upload-progress');
            var progressFill = document.getElementById('post-upload-progress-fill');
            var progressText = document.getElementById('post-upload-progress-text');
            xhr.upload.onprogress = function(e) {
                if (!e.lengthComputable || !progressBar) return;
                var pct = Math.round(e.loaded / e.total * 100);
                progressBar.style.display = 'block';
                if (progressFill) progressFill.style.width = pct + '%';
                if (progressText) progressText.textContent = pct + '%';
            };
            xhr.onload = function() {
                if (progressBar) progressBar.style.display = 'none';
                try { resolve({ok: xhr.status >= 200 && xhr.status < 300, data: JSON.parse(xhr.responseText)}); }
                catch(e) { resolve({ok: false, data: {}}); }
            };
            xhr.onerror = function() {
                if (progressBar) progressBar.style.display = 'none';
                resolve({ok: false, data: {}});
            };
            xhr.open('POST', '{{ route('posts.store') }}');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });

        if (ok && data.success) {
            if (window.NexusSoul) window.NexusSoul.feedback.post();
            if (window.showToast) window.showToast(data.message || '{{ __('messages.post_created_toast') }}', 'success');

            const container = document.getElementById('posts-container');
            if (container && data.post_html) {
                const emptyState = container.querySelector('.empty-state');
                if (emptyState) emptyState.remove();

                const alreadyInDom = data.post && data.post.id
                    ? !!document.getElementById(`post-${data.post.id}`)
                    : false;

                if (!alreadyInDom) {
                    container.insertAdjacentHTML('afterbegin', data.post_html);
                }

                if (window.initializePostComponents && data.post && data.post.id) {
                    const newPostEl = document.getElementById(`post-${data.post.id}`);
                    if (newPostEl) window.initializePostComponents(newPostEl);
                }

                if (typeof applyRTLToArabicText === 'function') applyRTLToArabicText();
            }

            if (contentEl) contentEl.value = '';
            if (isPrivateEl) isPrivateEl.value = '0';
            const sensitiveEl = document.getElementById('is-sensitive');
            if (sensitiveEl) { sensitiveEl.value = '0'; }
            const sensitiveBtn = document.getElementById('sensitive-toggle-btn');
            if (sensitiveBtn) { sensitiveBtn.classList.remove('sensitive-pill--on'); }
            const pollBuilder = document.getElementById('poll-builder');
            if (pollBuilder) { pollBuilder.style.display = 'none'; }
            const pollToggleBtn = document.getElementById('poll-toggle-btn');
            if (pollToggleBtn) { pollToggleBtn.style.opacity = '1'; pollToggleBtn.style.color = ''; }
            uploadedFiles = [];
            updateFileInput();
            if (previews) previews.innerHTML = '';
            if (previewContainer) previewContainer.style.display = 'none';

            const privacyIcon = document.getElementById('privacy-icon');
            const privacyText = document.getElementById('privacy-text');
            if (privacyIcon) privacyIcon.className = 'fas fa-globe';
            if (privacyText) privacyText.innerText = '{{ __('messages.public') }}';
        } else {
            const msg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : null) || '{{ __('messages.failed_to_create_post') }}';
            if (window.showToast) window.showToast(msg, 'error');
        }
    } catch (err) {
        const pb = document.getElementById('post-upload-progress');
        if (pb) pb.style.display = 'none';
        if (window.showToast) window.showToast('{{ __('messages.error_creating_post') }}', 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('chat-drawer-toggle');
    const closeBtn = document.getElementById('chat-drawer-close');
    const drawer = document.getElementById('chat-drawer');
    const iframe = document.getElementById('chat-drawer-iframe');
    const loader = document.getElementById('chat-drawer-loader');

    if (!toggleBtn || !drawer || !iframe) return;

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        drawer.classList.toggle('open');
        document.body.classList.toggle('chat-drawer-open', drawer.classList.contains('open'));

        if (drawer.classList.contains('open') && !iframe.src) {
            const url = iframe.getAttribute('data-src');
            iframe.src = url;

            iframe.onload = () => {
                if (loader) loader.style.display = 'none';
                iframe.style.display = 'block';
            };
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            drawer.classList.remove('open');
            document.body.classList.remove('chat-drawer-open');
        });
    }

    document.addEventListener('click', (e) => {
        if (drawer.classList.contains('open') &&
            !drawer.contains(e.target) &&
            !toggleBtn.contains(e.target)) {
            drawer.classList.remove('open');
            document.body.classList.remove('chat-drawer-open');
        }
    });
});

// Infinite scroll only (no manual Load More button)
window.currentFeedPage = {{ $posts->currentPage() }};
window.isLoadingPosts = false;
window.hasMorePosts = {{ $posts->hasMorePages() ? 'true' : 'false' }};

window.loadMorePosts = async function() {
    if (window.isLoadingPosts || !window.hasMorePosts) return;

    window.isLoadingPosts = true;
    const infiniteLoader = document.getElementById('infinite-scroll-loader');
    if (infiniteLoader) infiniteLoader.style.display = 'block';

    try {
        const nextPage = window.currentFeedPage + 1;
        const response = await fetch(`/posts/load-more?page=${nextPage}&per_page=5`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error('Network error');

        const data = await response.json();

        if (data.success) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;

            const postsList = document.getElementById('posts-container');
            const loaderEl = document.getElementById('infinite-scroll-loader');

            Array.from(tempDiv.children).forEach(child => {
                postsList.insertBefore(child, loaderEl);
            });

            window.currentFeedPage = nextPage;
            window.hasMorePosts = data.has_more;

            if (!data.has_more) {
                if (loaderEl) loaderEl.style.display = 'none';
                const noMore = document.getElementById('no-more-posts');
                if (noMore) noMore.style.display = 'block';
            }
        }
    } catch (err) {
        if (typeof window.showToast === 'function') {
            window.showToast('Failed to load more posts', 'error');
        }
    } finally {
        window.isLoadingPosts = false;
        if (window.hasMorePosts && document.getElementById('infinite-scroll-loader')) {
            document.getElementById('infinite-scroll-loader').style.display = 'none';
        }
    }
};

window.addEventListener('scroll', () => {
    if (!window.hasMorePosts || window.isLoadingPosts) return;
    if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 1000) {
        window.loadMorePosts();
    }
}, { passive: true });

// If the initial 5 posts don't fill the viewport, auto-load the next batch
document.addEventListener('DOMContentLoaded', () => {
    if (window.hasMorePosts && document.documentElement.scrollHeight <= window.innerHeight) {
        window.loadMorePosts();
    }
});

@php
    $globalChatPreviewMessagesPayload = $globalChatMessages->map(function ($message) {
        return [
            'id' => $message->id,
            'username' => $message->user->username ?? 'Unknown',
            'avatar_url' => $message->user->avatar_url ?? '/images/default-avatar.svg',
            'content' => trim($message->display_content),
            'time' => $message->created_at->diffForHumans(),
        ];
    })->values();
@endphp

window.globalChatPreviewMessages = @json($globalChatPreviewMessagesPayload);

(function () {
    const PREVIEW_MAX = 2;

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }

    function buildMsgEl(msg, animate) {
        const el = document.createElement('div');
        el.className = 'global-chat-message' + (animate ? ' is-new' : '');
        el.setAttribute('data-message-id', String(msg.id));
        el.innerHTML =
            '<div class="global-chat-avatar">' +
                '<a href="/users/' + encodeURIComponent(msg.username) + '" style="display:flex;flex-shrink:0;"><img src="' + escapeHtml(msg.avatar_url) + '" alt="' + escapeHtml(msg.username) + '" onerror="this.src=\'/images/default-avatar.svg\'" style="pointer-events:none;"></a>' +
            '</div>' +
            '<div class="global-chat-body">' +
                '<div class="global-chat-header">' +
                    '<a href="/users/' + encodeURIComponent(msg.username) + '" class="global-chat-username" style="text-decoration:none;">' + escapeHtml(msg.username) + '</a>' +
                    '<span class="global-chat-time">' + escapeHtml(msg.time) + '</span>' +
                '</div>' +
                '<p>' + escapeHtml(msg.content || '') + '</p>' +
            '</div>';
        return el;
    }

    function emptyState() {
        const el = document.createElement('div');
        el.className = 'fsr-empty small';
        el.innerHTML = '<span class="fsr-empty-icon"><i class="fas fa-comments" aria-hidden="true"></i></span><p>{{ __("messages.no_messages") }}</p>';
        return el;
    }

    function renderGlobalChatPreview() {
        const container = document.getElementById('global-chat-preview-list');
        if (!container) return;
        container.innerHTML = '';

        const msgs = window.globalChatPreviewMessages || [];
        if (!msgs.length) {
            container.appendChild(emptyState());
            return;
        }

        msgs.slice(-PREVIEW_MAX).reverse().forEach(function (msg) {
            container.appendChild(buildMsgEl(msg, false));
        });
    }

    function addGlobalChatPreviewMessage(msg) {
        if (!msg || String(msg.conversation_id) !== 'global-chat') return;
        if (!window.globalChatPreviewMessages) window.globalChatPreviewMessages = [];

        const exists = window.globalChatPreviewMessages.some(function (m) {
            return String(m.id) === String(msg.id);
        });
        if (exists) return;

        const entry = {
            id: msg.id,
            username: (msg.sender && msg.sender.username) ? msg.sender.username : 'Unknown',
            avatar_url: (msg.sender && msg.sender.avatar_url) ? msg.sender.avatar_url : '/images/default-avatar.svg',
            content: msg.content || '',
            time: '{{ __("messages.just_now") ?? "now" }}'
        };
        window.globalChatPreviewMessages.push(entry);
        if (window.globalChatPreviewMessages.length > PREVIEW_MAX) {
            window.globalChatPreviewMessages.shift();
        }

        const container = document.getElementById('global-chat-preview-list');
        if (!container) return;

        const empty = container.querySelector('.fsr-empty');
        if (empty) { empty.remove(); }

        const existing = container.querySelectorAll('.global-chat-message');
        if (existing.length >= PREVIEW_MAX) {
            const oldest = existing[existing.length - 1];
            oldest.classList.add('is-out');
            setTimeout(function () { oldest.remove(); }, 300);
        }

        const newEl = buildMsgEl(entry, true);
        container.insertBefore(newEl, container.firstChild);

        const card = container.closest('.fsr-chat-card');
        if (card) {
            card.classList.add('has-new-msg');
            setTimeout(function () { card.classList.remove('has-new-msg'); }, 800);
        }
    }

    function initGlobalChatPreview() {
        const attach = function () {
            if (!window.NexusSocket || !window.NexusSocket.socket) return false;
            const socket = window.NexusSocket.socket;

            const joinRoom = function () {
                socket.emit('conversation:join', { conversationId: 'global-chat' });
            };
            joinRoom();
            socket.on('connect', joinRoom);

            socket.on('chat:message', addGlobalChatPreviewMessage);
            return true;
        };
        if (!attach()) {
            const t = setInterval(function () { if (attach()) clearInterval(t); }, 250);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        renderGlobalChatPreview();
        initGlobalChatPreview();
    });
})();
</script>
@endsection
