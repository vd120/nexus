# Real-time "Pin to Profile" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable pinning and unpinning posts in real-time on the feed without page reloads.

**Architecture:** Server-confirmed UI updates. The client sends a request, waits for a successful JSON response, and then updates the DOM (menu items, badges, and classes) and shows a toast.

**Tech Stack:** Laravel (Blade), JavaScript (Fetch API), Vanilla CSS.

---

### Task 1: Update Global Translations

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Add missing translation keys to `window.postTranslations`**

```javascript
// Locate window.postTranslations in resources/views/layouts/app.blade.php around line 350
window.postTranslations = {
    // ... existing ...
    confirm_pin_post: '{{ __('users.confirm_pin_post') }}',
    confirm_unpin_post: '{{ __('users.confirm_unpin_post') }}',
    post_pinned: '{{ __('users.post_pinned') }}',
    post_unpinned: '{{ __('users.post_unpinned') }}',
    pin_post: '{{ __('users.pin_post') }}', // Added
    unpin_post: '{{ __('users.unpin_post') }}', // Added
    pinned: '{{ __('users.pinned') }}', // Added
};
```

- [ ] **Step 2: Verify translations are rendered in the browser (Manual Check)**

### Task 2: Refactor Post Partials for Real-time Toggling

**Files:**
- Modify: `resources/views/partials/post.blade.php`
- Modify: `resources/views/partials/group-post-header.blade.php`

- [ ] **Step 1: Wrap the pinned badge in a container and ensure it can be toggled (post.blade.php)**

```html
{{-- resources/views/partials/post.blade.php --}}
{{-- Around line 12-25, wrap the badge in a div that is always present but conditionally hidden --}}
<div class="pinned-badge-container" id="pinned-badge-{{ $post->id }}" style="{{ (isset($isPinned) && $isPinned) || $post->isPinned() ? '' : 'display: none;' }}">
    <div class="pinned-badge" style="display: flex; align-items: center; gap: 8px; padding: 18px 20px; background: linear-gradient(to right, var(--primary-glow, rgba(94, 96, 206, 0.12)), transparent); border-bottom: 1px solid var(--border); font-size: 13px; color: var(--primary); font-weight: 700;">
        <div style="display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: var(--primary); color: white; border-radius: 6px; font-size: 12px; box-shadow: 0 2px 8px var(--primary-glow);">
            <i class="fas fa-thumbtack" style="transform: rotate(45deg);"></i>
        </div>
        <span style="letter-spacing: 0.5px; text-transform: uppercase; font-size: 11px;">{{ __('users.pinned') }}</span>
        @if(auth()->check() && auth()->id() === $post->user_id)
            <button type="button" class="unpin-btn" onclick="unpinPost({{ $post->id }})" style="margin-left: auto; background: var(--surface-hover); border: 1px solid var(--border); color: var(--text-muted); cursor: pointer; padding: 6px 12px; font-size: 11px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all 0.2s;" title="{{ __('users.unpin') }}">
                <i class="fas fa-times"></i>
                <span>{{ __('users.unpin') }}</span>
            </button>
        @endif
    </div>
</div>
```

- [ ] **Step 2: Add IDs to the menu items for easier targeting (post.blade.php)**

```html
{{-- Around line 90 --}}
<button type="button" id="pin-menu-item-{{ $post->id }}" class="menu-item" onclick="pinPost({{ $post->id }})" style="{{ $post->isPinned() ? 'display: none;' : '' }}">
    <i class="fas fa-thumbtack"></i> {{ __('users.pin_post') }}
</button>
<button type="button" id="unpin-menu-item-{{ $post->id }}" class="menu-item" onclick="unpinPost({{ $post->id }})" style="{{ !$post->isPinned() ? 'display: none;' : '' }}">
    <i class="fas fa-thumbtack" style="transform: rotate(45deg);"></i> {{ __('users.unpin_post') }}
</button>
```

- [ ] **Step 3: Update menu items in group-post-header.blade.php**

```html
{{-- resources/views/partials/group-post-header.blade.php --}}
{{-- Around line 75 --}}
@if($post->user_id === auth()->id())
    <button type="button" id="pin-menu-item-{{ $post->id }}" class="menu-item" onclick="pinPost({{ $post->id }})" style="{{ $post->isPinned() ? 'display: none;' : '' }}">
        <i class="fas fa-thumbtack"></i> {{ __('users.pin_post') }}
    </button>
    <button type="button" id="unpin-menu-item-{{ $post->id }}" class="menu-item" onclick="unpinPost({{ $post->id }})" style="{{ !$post->isPinned() ? 'display: none;' : '' }}">
        <i class="fas fa-thumbtack" style="transform: rotate(45deg);"></i> {{ __('users.unpin_post') }}
    </button>
    <button type="button" class="menu-item" onclick="deletePost('{{ $post->slug }}', this)">
        <i class="fas fa-trash"></i> {{ __('messages.delete_post') }}
    </button>
@elseif($post->canDelete(auth()->user()))
```

### Task 3: Implement Real-time JavaScript Logic

**Files:**
- Modify: `resources/js/legacy/posts.js`

- [ ] **Step 1: Update `window.pinPost` to update UI without reload**

```javascript
window.pinPost = function(postId) {
    if (!confirm(window.postTranslations?.confirm_pin_post || 'Pin this post to your profile?')) return;
    if (!window.currentUserUsername) return console.error('Username not found');

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
            if (typeof window.showToast === 'function') window.showToast(window.postTranslations?.post_pinned || 'Post pinned', 'success');
            
            // DOM Updates
            const card = document.getElementById('post-' + postId);
            if (card) {
                card.classList.add('pinned-post');
                const badge = document.getElementById('pinned-badge-' + postId);
                if (badge) badge.style.display = 'block';
                
                const pinMenu = document.getElementById('pin-menu-item-' + postId);
                const unpinMenu = document.getElementById('unpin-menu-item-' + postId);
                if (pinMenu) pinMenu.style.display = 'none';
                if (unpinMenu) unpinMenu.style.display = 'block';
            }
        } else {
            if (typeof window.showToast === 'function') window.showToast(data.message || 'Failed to pin post', 'error');
        }
    })
    .catch(() => {
        if (typeof window.showToast === 'function') window.showToast('Failed to pin post', 'error');
    });
};
```

- [ ] **Step 2: Update `window.unpinPost` to update UI without reload**

```javascript
window.unpinPost = function(postId) {
    if (!confirm(window.postTranslations?.confirm_unpin_post || 'Unpin this post?')) return;
    if (!window.currentUserUsername) return console.error('Username not found');

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
            if (typeof window.showToast === 'function') window.showToast(window.postTranslations?.post_unpinned || 'Post unpinned', 'success');
            
            // DOM Updates
            const card = document.getElementById('post-' + postId);
            if (card) {
                card.classList.remove('pinned-post');
                const badge = document.getElementById('pinned-badge-' + postId);
                if (badge) badge.style.display = 'none';
                
                const pinMenu = document.getElementById('pin-menu-item-' + postId);
                const unpinMenu = document.getElementById('unpin-menu-item-' + postId);
                if (pinMenu) pinMenu.style.display = 'block';
                if (unpinMenu) unpinMenu.style.display = 'none';
            }
        } else {
            if (typeof window.showToast === 'function') window.showToast(data.message || 'Failed to unpin post', 'error');
        }
    })
    .catch(() => {
        if (typeof window.showToast === 'function') window.showToast('Failed to unpin post', 'error');
    });
};
```

### Task 4: Final Verification

- [ ] **Step 1: Test Pinning**
    - Click "Pin to Profile".
    - Confirm "Pinned" toast appears.
    - Confirm Badge appears.
    - Confirm Menu toggles to "Unpin".
    - Confirm NO page reload occurred.

- [ ] **Step 2: Test Unpinning**
    - Click "Unpin from Profile".
    - Confirm "Unpinned" toast appears.
    - Confirm Badge disappears.
    - Confirm Menu toggles to "Pin".
    - Confirm NO page reload occurred.
