# Design Document: Real-time "Pin to Profile"

## Goal
Make the "Pin to Profile" and "Unpin from Profile" actions work in real-time without a full page reload. Provide immediate visual feedback on the post card and via toast notifications.

## Proposed Changes

### 1. JavaScript Enhancements (`resources/js/legacy/posts.js`)
- Update `window.pinPost` and `window.unpinPost` to handle DOM updates after a successful server response.
- Toggle the menu item text, icon, and `onclick` handler.
- Show/hide the "Pinned" badge on the post card.
- Add/remove the `pinned-post` class on the post card.
- Use `showToast` for feedback.

### 2. Layout Updates (`resources/views/layouts/app.blade.php`)
- Add missing translation keys (`pin_post`, `unpin_post`, `pinned`) to `window.postTranslations` so they are available in `posts.js`.

### 3. Partial Update (`resources/views/partials/post.blade.php`)
- Modify the "Pinned" badge rendering to be more flexible for real-time toggling.
- Ensure the badge can be dynamically inserted or hidden without a reload.

### 4. Controller Check (`app/Http/Controllers/UserController.php`)
- The controller already returns JSON on success, so no changes are needed there.

## Approaches Considered
- **Optimistic UI:** Instant feedback but complex revert logic.
- **Server-Confirmed (Selected):** Reliable, consistent with existing codebase patterns (like likes/saves), and provides a good balance of speed and correctness.

## Verification Plan
- Pin a post from the feed and verify:
    - No page reload occurs.
    - Toast notification appears.
    - "Pinned" badge appears on the post card.
    - Menu item changes to "Unpin from Profile".
- Unpin the post and verify:
    - No page reload occurs.
    - Toast notification appears.
    - "Pinned" badge disappears.
    - Menu item changes to "Pin to Profile".
- Verify that pinning more than 3 posts still triggers the error toast as expected.
