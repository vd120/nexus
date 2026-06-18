@extends('layouts.app')

@section('title', __('messages.home') . ' · Preview')
@section('main_class', 'full-width')
@section('content_class', 'full-width')

@push('styles')
@vite(['resources/css/posts-index.css', 'resources/css/chat-show.css'])
<style>
/* ============================================================
   FEED PREVIEW OVERRIDES
   Loaded after posts-index.css + partial-posts.css so these win.
   Scoped to .feed-preview wrapper to avoid leaking to other views.
   ============================================================ */

.feed-preview { --preview-radius: 18px; }

/* Accessibility: visually hidden but readable by screen readers */
.feed-preview .visually-hidden,
.feed-preview .sr-only {
    position: absolute;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
}

/* ---------- POST CARD: shape & rhythm ---------- */
.feed-preview .post-card {
    position: relative;
    border-radius: var(--preview-radius);
    overflow: hidden;
    box-shadow: 0 1px 0 rgba(0,0,0,0.04);
    border: 1px solid var(--border);
    transition: border-color 0.2s ease, transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
[data-theme="dark"] .feed-preview .post-card {
    box-shadow: 0 1px 0 rgba(255,255,255,0.03);
    border-color: rgba(255,255,255,0.07);
}
@media (hover: hover) {
    .feed-preview .post-card:hover { border-color: rgba(99, 102, 241, 0.18); }
}

/* ---------- PINNED POST: subtle top accent band ---------- */
.feed-preview .post-card.pinned-post .pinned-accent {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), #8b5cf6, var(--primary));
    background-size: 200% 100%;
    animation: pinnedShimmer 6s ease-in-out infinite;
    z-index: 2;
    pointer-events: none;
}
@keyframes pinnedShimmer {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
.feed-preview .pinned-icon-simple {
    margin-inline-start: 10px;
    font-size: 12px;
    color: var(--primary);
    opacity: 0.9;
    /* No more rotate(45deg) — looks broken in RTL */
}

/* ---------- ANONYMOUS POST: tinted surface, circle avatar ---------- */
.feed-preview .post-card.is-anonymous {
    background:
        linear-gradient(180deg, rgba(148, 163, 184, 0.05), transparent 80px),
        var(--surface);
}
[data-theme="dark"] .feed-preview .post-card.is-anonymous {
    background:
        linear-gradient(180deg, rgba(255,255,255,0.025), transparent 80px),
        var(--surface);
}
.feed-preview .anonymous-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;  /* circle, matches normal avatars */
    background: linear-gradient(135deg, #4b5563, #1f2937);
    color: rgba(255,255,255,0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.feed-preview .anonymous-name {
    font-weight: 600;
    font-style: italic;
    color: var(--text-muted);
}

/* ---------- AUTHOR ROW: no wrapping, ellipsis instead ---------- */
.feed-preview .post-time {
    flex-wrap: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.feed-preview .post-time .author-handle { max-width: 160px; }

/* ---------- QUICK FOLLOW: chip, no glow ---------- */
.feed-preview .quick-follow-btn {
    box-shadow: none !important;
    padding: 3px 10px;
    background: var(--gradient-brand, linear-gradient(135deg, var(--primary), #8b5cf6));
}
.feed-preview .quick-follow-btn:hover { filter: brightness(1.08); box-shadow: none !important; }
.feed-preview .quick-follow-btn.following {
    background: var(--surface-hover);
    border: 1px solid var(--border);
    color: var(--text);
}

/* ---------- PRIVACY BADGE: smaller, no leading dot ---------- */
.feed-preview .privacy-badge { padding-inline-start: 6px; }
.feed-preview .privacy-badge::before { display: none; }
.feed-preview .privacy-badge i { font-size: 10px; opacity: 0.55; }

/* ---------- ENGAGEMENT BAR: no divider, left-aligned ---------- */
.feed-preview .post-engagement-bar {
    border-bottom: none;
    padding: 8px 16px 4px;
    gap: 12px;
    justify-content: flex-start;
}
.feed-preview .reaction-summary-bar {
    background: transparent;
    border: none;
    padding: 4px 8px 4px 4px;
    border-radius: 999px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.18s ease;
}
.feed-preview .reaction-summary-bar:hover { background: var(--surface-hover); }
.feed-preview .reaction-emojis-display { display: inline-flex; align-items: center; }
.feed-preview .reaction-emoji-count {
    width: 22px; height: 22px;
    background: var(--surface);
    border: 1.5px solid var(--surface);
    border-radius: 50%;
    margin-inline-end: -6px;   /* RTL-safe */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    font-size: 11px;
}
.feed-preview .reaction-emoji-count:last-child { margin-inline-end: 0; }
.feed-preview .reaction-emoji-count img { width: 100%; height: 100%; object-fit: contain; }
.feed-preview .reaction-total-count {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    margin-inline-start: 8px;
}
.feed-preview .engagement-comments {
    margin-inline-start: auto;
    font-size: 12.5px;
    color: var(--text-muted);
}

/* ---------- ACTION BAR: balanced grid, labels for SR only ---------- */
.feed-preview .post-actions {
    padding: 4px 12px 10px;
    gap: 4px;
}
.feed-preview .left-actions { gap: 4px; }
.feed-preview .action-btn {
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 1.1rem;
    color: var(--text);
    background: transparent;
    transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
}
.feed-preview .action-btn i { font-size: 1.1rem; }
@media (hover: hover) {
    .feed-preview .action-btn:hover {
        background: var(--surface-hover);
        opacity: 1;
    }
    .feed-preview .action-btn.comment-btn:hover { color: var(--primary); }
    .feed-preview .action-btn.share-btn:hover { color: #22c55e; }
    .feed-preview .action-btn.save-btn:hover { color: #f59e0b; }
    .feed-preview .action-btn.react-btn:hover { color: #ef4444; }
}
.feed-preview .action-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}
.feed-preview .action-btn.saved { color: #f59e0b; }
.feed-preview .action-btn.saved i { font-weight: 900; }

/* ---------- SHOW MORE: actionable, not muted ---------- */
.feed-preview .show-more-btn {
    color: var(--primary);
    font-weight: 600;
    font-size: 13px;
    padding: 6px 0;
}
.feed-preview .show-more-btn:hover { text-decoration: underline; }

/* ---------- POST TEXT TRUNCATION: char-only, no line clamp ---------- */
.feed-preview .post-text { display: block; }

/* ---------- COMPOSER PILL: brand color, not Facebook green ---------- */
.feed-preview .composer-pill .pill-icon-btn i { color: var(--primary) !important; }
.feed-preview .composer-pill-btn {
    /* Composer pill is now a real button */
    all: unset;
    box-sizing: border-box;
    cursor: pointer;
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
}
.feed-preview .composer-pill-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
    border-radius: 999px;
}
.feed-preview .composer-expanded .post-action-btn i { color: var(--primary) !important; }

/* ---------- EMPTY STATE: warmer, with primary CTA ---------- */
.feed-preview .empty-state {
    padding: 80px 24px 60px;
    text-align: center;
    background: var(--surface);
    border: 1px dashed var(--border);
    border-radius: 24px;
    margin: 12px 0;
}
.feed-preview .empty-state-icon-wrap {
    width: 84px;
    height: 84px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.12));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: var(--primary);
}
.feed-preview .empty-state h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 6px;
    letter-spacing: -0.01em;
}
.feed-preview .empty-state p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 24px;
}
.feed-preview .empty-state-cta {
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
.feed-preview .empty-state-cta:hover { filter: brightness(1.08); transform: translateY(-1px); }

/* ---------- MEDIA: click-catcher as <button> ---------- */
.feed-preview .media-click-catcher {
    position: absolute;
    inset: 0;
    background: transparent;
    border: none;
    cursor: pointer;
    z-index: 20;
    padding: 0;
}
.feed-preview .media-click-catcher:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: -2px;
}

/* ---------- SKELETON LOADING ---------- */
.feed-preview .post-skeleton {
    border-radius: var(--preview-radius);
    border: 1px solid var(--border);
    padding: 16px;
    background: var(--surface);
    margin-bottom: 16px;
}
.feed-preview .post-skeleton .row { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
.feed-preview .skeleton-shape {
    background: linear-gradient(90deg, var(--surface-hover) 0%, var(--border) 50%, var(--surface-hover) 100%);
    background-size: 200% 100%;
    animation: skeletonShimmer 1.4s ease-in-out infinite;
    border-radius: 8px;
}
.feed-preview .skeleton-avatar { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; }
.feed-preview .skeleton-line { height: 12px; flex: 1; }
.feed-preview .skeleton-line.short { max-width: 40%; }
.feed-preview .skeleton-line.long { max-width: 90%; }
.feed-preview .skeleton-media { height: 220px; border-radius: 14px; margin: 8px 0; }
.feed-preview .skeleton-actions { display: flex; gap: 16px; margin-top: 14px; }
.feed-preview .skeleton-action { width: 64px; height: 28px; border-radius: 999px; }
@keyframes skeletonShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ---------- MOBILE: restore card separation ---------- */
@media (max-width: 768px) {
    .feed-preview .post-card {
        border: 1px solid var(--border);
        border-radius: 14px;
        background: var(--surface);
        margin-bottom: 10px;
        box-shadow: none;
    }
    [data-theme="dark"] .feed-preview .post-card {
        border-color: rgba(255,255,255,0.06);
    }
    .feed-preview .post-engagement-bar { padding: 6px 12px 2px; }
    .feed-preview .post-actions { padding: 4px 8px 8px; }
    .feed-preview .action-btn { padding: 6px 10px; font-size: 1rem; }
    .feed-preview .action-btn i { font-size: 1.05rem; }
}

/* ---------- PENDING APPROVAL BADGE: minor cleanup ---------- */
.feed-preview .pending-approval-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: rgba(245, 158, 11, 0.08);
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    color: #f59e0b;
    font-weight: 700;
}
.feed-preview .pending-approval-note {
    font-size: 11px;
    font-weight: 500;
    opacity: 0.8;
    margin-inline-start: 4px;
}

/* ---------- COMMENTS & REPLIES ---------- */
.feed-preview .comment-item {
    border-bottom: none;        /* drop divider lines between comments */
    padding: 10px 0 8px;
}

/* ============================================================
   REPLY THREADING — Threads/X-style avatar connectors
   Vertical line drops from parent avatar (center at x=18px on a 36px avatar);
   each nested reply draws a small elbow to itself + continuation line if
   it's not the last sibling.
   ============================================================ */
.feed-preview .comment-item.nested {
    margin-left: 0 !important;
    padding: 8px 0 6px 38px;
    border-left: none !important;
    position: relative;
}
/* Override the base level-N margins entirely */
.feed-preview .comment-item.level-1,
.feed-preview .comment-item.level-2,
.feed-preview .comment-item.level-3,
.feed-preview .comment-item.level-4,
.feed-preview .comment-item.level-5 {
    margin-left: 0 !important;
}
html[dir="rtl"] .feed-preview .comment-item.level-1,
html[dir="rtl"] .feed-preview .comment-item.level-2,
html[dir="rtl"] .feed-preview .comment-item.level-3,
html[dir="rtl"] .feed-preview .comment-item.level-4,
html[dir="rtl"] .feed-preview .comment-item.level-5 {
    margin-right: 0 !important;
}

/* Elbow connector: vertical from item-top down to avatar-center + curved horizontal */
.feed-preview .comment-item.nested::before {
    content: '';
    position: absolute;
    left: 18px;
    top: 0;
    width: 20px;
    height: 26px;
    border-left: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    border-bottom-left-radius: 10px;
    pointer-events: none;
}

/* Continuation line for non-last siblings (keeps the trunk going) */
.feed-preview .comment-item.nested:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 18px;
    top: 26px;
    bottom: 0;
    width: 1px;
    background: var(--border);
    pointer-events: none;
}

/* Trunk bridge — small segment between parent content and first child */
.feed-preview .hidden-replies {
    position: relative;
    margin-top: 10px;
}
.feed-preview .hidden-replies::before {
    content: '';
    position: absolute;
    left: 18px;
    top: -10px;
    width: 1px;
    height: 10px;
    background: var(--border);
    pointer-events: none;
}

/* Dark theme: a touch more visible */
[data-theme="dark"] .feed-preview .comment-item.nested::before {
    border-left-color: rgba(255,255,255,0.10);
    border-bottom-color: rgba(255,255,255,0.10);
}
[data-theme="dark"] .feed-preview .comment-item.nested:not(:last-child)::after,
[data-theme="dark"] .feed-preview .hidden-replies::before {
    background: rgba(255,255,255,0.10);
}

/* RTL: flip the connector to the right side */
html[dir="rtl"] .feed-preview .comment-item.nested {
    padding: 8px 38px 6px 0;
}
html[dir="rtl"] .feed-preview .comment-item.nested::before {
    left: auto;
    right: 18px;
    border-left: none;
    border-right: 1px solid var(--border);
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 10px;
}
html[dir="rtl"] .feed-preview .comment-item.nested:not(:last-child)::after {
    left: auto;
    right: 18px;
}
html[dir="rtl"] .feed-preview .hidden-replies::before {
    left: auto;
    right: 18px;
}
[data-theme="dark"] html[dir="rtl"] .feed-preview .comment-item.nested::before {
    border-right-color: rgba(255,255,255,0.10);
}

/* Mobile: tighter spacing */
@media (max-width: 768px) {
    .feed-preview .comment-item.nested {
        padding-left: 32px;
    }
    .feed-preview .comment-item.nested::before {
        left: 14px;
        width: 18px;
        height: 22px;
    }
    .feed-preview .comment-item.nested:not(:last-child)::after,
    .feed-preview .hidden-replies::before {
        left: 14px;
    }
    html[dir="rtl"] .feed-preview .comment-item.nested {
        padding-left: 0;
        padding-right: 32px;
    }
    html[dir="rtl"] .feed-preview .comment-item.nested::before {
        left: auto;
        right: 14px;
    }
    html[dir="rtl"] .feed-preview .comment-item.nested:not(:last-child)::after,
    html[dir="rtl"] .feed-preview .hidden-replies::before {
        left: auto;
        right: 14px;
    }
}

/* Anonymous comment avatar: neutral, matches anonymous post avatar */
.feed-preview .comment-avatar-placeholder.anonymous {
    background: linear-gradient(135deg, #4b5563, #1f2937);
    color: rgba(255,255,255,0.85);
}
.feed-preview .comment-item.is-anonymous .comment-name.anonymous-name {
    font-style: italic;
    color: var(--text-muted);
}

/* Action buttons: focus rings + intent colors */
.feed-preview .comment-action-btn {
    transition: background 0.18s ease, color 0.18s ease;
}
.feed-preview .comment-action-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}
.feed-preview .comment-action-btn.liked {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.10);
}
.feed-preview .comment-action-btn.liked i { color: #ef4444; }
@media (hover: hover) {
    .feed-preview .comment-action-btn:hover { background: var(--surface-hover); color: var(--text); }
    .feed-preview .comment-action-btn.liked:hover { background: rgba(239, 68, 68, 0.16); color: #ef4444; }
}

/* Delete-comment button: a11y label, calmer hover */
.feed-preview .delete-comment-btn {
    color: var(--text-muted);
}
.feed-preview .delete-comment-btn:hover {
    background: rgba(239, 68, 68, 0.08);
    color: #ef4444;
    opacity: 1;
}
.feed-preview .delete-comment-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* Show replies: chevron + smoother style */
.feed-preview .show-replies-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    color: var(--primary);
    font-size: 12.5px;
    font-weight: 600;
    background: transparent;
    transition: background 0.18s ease, transform 0.18s ease;
}
.feed-preview .show-replies-btn .show-replies-chevron {
    font-size: 10px;
    transition: transform 0.2s ease;
}
.feed-preview .show-replies-btn[aria-expanded="true"] .show-replies-chevron {
    transform: rotate(180deg);
}
@media (hover: hover) {
    .feed-preview .show-replies-btn:hover { background: var(--surface-hover); text-decoration: none; }
}
.feed-preview .show-replies-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* Reply input: auto-grow textarea, calmer submit button */
.feed-preview .reply-textarea {
    flex: 1;
    padding: 8px 14px;
    min-height: 36px;
    max-height: 180px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: var(--surface-hover);
    color: var(--text);
    font: inherit;
    font-size: 13.5px;
    line-height: 1.45;
    resize: none;
    overflow-y: auto;
    transition: border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
}
.feed-preview .reply-textarea:focus {
    outline: none;
    border-color: var(--primary);
    background: var(--surface);
    box-shadow: 0 0 0 3px var(--primary-glow);
}
.feed-preview .reply-submit-btn {
    width: 36px; height: 36px;
    border: none;
    border-radius: 50%;
    background: var(--gradient-brand, linear-gradient(135deg, var(--primary), #8b5cf6));
    color: #fff;
    cursor: pointer;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: none;            /* drop glow */
    transition: filter 0.18s ease, transform 0.18s ease;
}
.feed-preview .reply-submit-btn:hover {
    filter: brightness(1.08);
    transform: none;              /* drop scale */
    box-shadow: none;
}
.feed-preview .reply-submit-btn:active { transform: scale(0.96); }
.feed-preview .reply-submit-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* Cancel-reply: matches the new pill style */
.feed-preview .cancel-reply {
    margin-left: 0;
    padding: 6px 12px;
    border-radius: 999px;
    color: var(--text-muted);
    background: transparent;
}
.feed-preview .cancel-reply:hover { background: var(--surface-hover); color: var(--text); }
.feed-preview .cancel-reply:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* ============================================================
   LIKE BUTTON — heart burst micro-animation
   ============================================================ */
.feed-preview .comment-like-btn {
    position: relative;
    overflow: visible;  /* particles need to escape the button */
}
.feed-preview .like-burst-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
}
.feed-preview .like-heart {
    display: inline-block;
    font-size: 14px;
    transform-origin: center;
    transition: color 0.18s ease;
    will-change: transform;
}
.feed-preview .comment-like-btn.liked .like-heart { color: #ef4444; }
.feed-preview .comment-like-btn.is-bursting .like-heart {
    animation: feedPreviewHeartPop 0.55s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes feedPreviewHeartPop {
    0%   { transform: scale(1); }
    18%  { transform: scale(0.7); }
    45%  { transform: scale(1.45); }
    75%  { transform: scale(0.95); }
    100% { transform: scale(1); }
}

/* Particles */
.feed-preview .like-burst-particle {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    pointer-events: none;
    opacity: 0;
    transform: translate(-50%, -50%);
    will-change: transform, opacity;
    animation: feedPreviewParticleFly 0.7s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
@keyframes feedPreviewParticleFly {
    0%   { opacity: 0; transform: translate(-50%, -50%) scale(0.3); }
    15%  { opacity: 1; }
    100% {
        opacity: 0;
        transform:
            translate(calc(-50% + var(--burst-x, 0px)), calc(-50% + var(--burst-y, 0px)))
            scale(0.6);
    }
}

/* Count pulse on like */
.feed-preview .comment-like-btn .comment-likes-count {
    display: inline-block;
    font-variant-numeric: tabular-nums;
    transition: color 0.2s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.feed-preview .comment-like-btn.is-bursting .comment-likes-count {
    color: #ef4444;
    transform: translateY(-2px);
}

/* ============================================================
   REPLY FORM — minimal inline
   ============================================================ */
.feed-preview .reply-form--minimal {
    margin-top: 8px;
    padding: 0;
    background: transparent;
    border: none;
}
.feed-preview .reply-form--minimal .reply-input-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 4px 0;
    position: relative;
}
.feed-preview .reply-form--minimal .reply-avatar {
    width: 28px;
    height: 28px;
    margin-top: 4px;
    flex-shrink: 0;
    border: none;
}
.feed-preview .reply-form--minimal .reply-textarea {
    flex: 1;
    border: none;
    background: transparent;
    padding: 6px 0;
    min-height: 30px;
    max-height: 180px;
    font-size: 14px;
    line-height: 1.45;
    color: var(--text);
    box-shadow: inset 0 -1px 0 var(--border);
    border-radius: 0;
    resize: none;
    overflow-y: auto;
    transition: box-shadow 0.18s ease;
}
.feed-preview .reply-form--minimal .reply-textarea:focus {
    outline: none;
    background: transparent;
    box-shadow: inset 0 -1px 0 var(--primary);
}
.feed-preview .reply-form--minimal .reply-textarea::placeholder {
    color: var(--text-muted);
    opacity: 0.7;
}

/* Anonymous toggle as an icon chip; reveal alongside the send button */
.feed-preview .reply-form--minimal .reply-anon-chip {
    flex-shrink: 0;
    width: 30px;
    height: 30px;
    margin-top: 2px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: transparent;
    color: var(--text-muted);
    transition: background 0.18s ease, color 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
    opacity: 0;
    transform: scale(0.8);
    pointer-events: none;
}
.feed-preview .reply-form--minimal.has-content .reply-anon-chip,
.feed-preview .reply-form--minimal:focus-within .reply-anon-chip {
    opacity: 1;
    transform: scale(1);
    pointer-events: auto;
}
.feed-preview .reply-form--minimal .reply-anon-chip input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.feed-preview .reply-form--minimal .reply-anon-chip i { font-size: 13px; }
.feed-preview .reply-form--minimal .reply-anon-chip:hover { background: var(--surface-hover); color: var(--text); }
.feed-preview .reply-form--minimal .reply-anon-chip:has(input:checked) {
    background: rgba(99, 102, 241, 0.12);
    color: var(--primary);
}

/* Send button: hidden until content, gradient circle */
.feed-preview .reply-form--minimal .reply-submit-btn {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    margin-top: 1px;
    border: none;
    border-radius: 50%;
    background: var(--gradient-brand, linear-gradient(135deg, var(--primary), #8b5cf6));
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: none;
    opacity: 0;
    transform: scale(0.7);
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), filter 0.18s ease;
}
.feed-preview .reply-form--minimal.has-content .reply-submit-btn {
    opacity: 1;
    transform: scale(1);
    pointer-events: auto;
}
.feed-preview .reply-form--minimal .reply-submit-btn:hover { filter: brightness(1.08); }
.feed-preview .reply-form--minimal .reply-submit-btn:active { transform: scale(0.94); }
.feed-preview .reply-form--minimal .reply-submit-btn:disabled {
    opacity: 0;
    transform: scale(0.7);
    pointer-events: none;
}
.feed-preview .reply-form--minimal .reply-submit-btn:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}
.feed-preview .reply-form--minimal .reply-submit-btn i { font-size: 12px; }

/* Hint row — only on focus, no shift when missing */
.feed-preview .reply-form--minimal .reply-hint {
    font-size: 11px;
    color: var(--text-muted);
    padding: 2px 0 0 38px;  /* line up with textarea (avatar 28 + gap 10) */
    height: 0;
    overflow: hidden;
    opacity: 0;
    transition: height 0.18s ease, opacity 0.18s ease;
}
.feed-preview .reply-form--minimal:focus-within .reply-hint {
    height: 16px;
    opacity: 0.7;
}
html[dir="rtl"] .feed-preview .reply-form--minimal .reply-hint { padding: 2px 38px 0 0; }

/* Mobile: keep things touch-friendly */
@media (max-width: 768px) {
    .feed-preview .reply-form--minimal .reply-textarea { font-size: 16px; } /* avoid iOS zoom */
    .feed-preview .reply-form--minimal .reply-submit-btn { width: 36px; height: 36px; }
    .feed-preview .reply-form--minimal .reply-anon-chip { width: 36px; height: 36px; }
}

/* Drop the side-stripe from comment highlight; keep just the soft glow */
@keyframes feedPreviewHighlightFade {
    0%   { background-color: rgba(99, 102, 241, 0.18); box-shadow: 0 0 20px rgba(99, 102, 241, 0.15); }
    70%  { background-color: rgba(99, 102, 241, 0.08); box-shadow: none; }
    100% { background-color: transparent; box-shadow: none; }
}
.feed-preview .highlight-comment {
    animation: feedPreviewHighlightFade 3.5s cubic-bezier(0.4, 0, 0.2, 1) forwards !important;
    border-left: 0 !important;   /* override the banned side-stripe */
    border-radius: 12px;
}

/* ---------- PREVIEW BANNER ---------- */
.feed-preview-banner {
    max-width: 600px;
    margin: 0 auto 16px;
    padding: 10px 16px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(99,102,241,0.10), rgba(139,92,246,0.08));
    border: 1px solid rgba(99,102,241,0.25);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--text);
}
.feed-preview-banner .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--primary);
    box-shadow: 0 0 8px var(--primary);
}
</style>
@endpush

@section('content')

<div class="feed-layout feed-preview">
    @auth
        @include('partials.sidebars.left')
    @endauth
    <div class="feed-container">
        <div class="feed-preview-banner" role="status">
            <span class="dot" aria-hidden="true"></span>
            <strong>Preview</strong>
            <span style="opacity:0.75">·  improved feed &amp; post card. Live feed at /</span>
        </div>

        @if(session('verified'))
            <script>showToast('{{ __('messages.email_verified_success_toast') }}', 'success');</script>
        @endif

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
            {{-- Compact pill (default) - now a real button for keyboard/SR --}}
            <button type="button" class="composer-pill composer-pill-btn" id="composer-pill" aria-label="{{ __('messages.whats_on_your_mind') }}" aria-expanded="false" aria-controls="composer-expanded-region">
                <img src="{{ auth()->user()->avatar_url }}" alt="" class="avatar">
                <span class="prompt">{{ __('messages.whats_on_your_mind') }}</span>
                <span class="pill-actions">
                    <span class="pill-icon-btn" aria-hidden="true">
                        <i class="fas fa-image"></i>
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
                    </div>
                    <button type="button" class="btn btn-primary" onclick="submitPost()">
                        {{ __('messages.post') }}
                    </button>
                </div>
                <input type="hidden" id="is-private" value="0">
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
                @include('partials.post-preview', ['post' => $post])
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
// Composer pill → expanded (now a real button)
(function () {
    function openComposer() {
        const composer = document.getElementById('composer');
        if (!composer || composer.classList.contains('is-open')) return;
        composer.classList.add('is-open');
        const pill = document.getElementById('composer-pill');
        if (pill) pill.setAttribute('aria-expanded', 'true');
        setTimeout(function () {
            const ta = document.getElementById('post-content');
            if (ta) ta.focus();
        }, 60);
    }
    document.addEventListener('DOMContentLoaded', function () {
        const pill = document.getElementById('composer-pill');
        if (pill) pill.addEventListener('click', openComposer);
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

function viewStoryFromHome(username, slug) {
    window.location.href = `/stories/${username}/${slug}`;
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
    if (!input || !input.files || input.files.length === 0) return;
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

    if (!content && uploadedFiles.length === 0) {
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

// Infinite scroll only (no manual Load More button in preview)
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
        const response = await fetch(`/posts/load-more?page=${nextPage}`, {
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
        if (window.hasMorePosts && infiniteLoader) infiniteLoader.style.display = 'none';
    }
};

window.addEventListener('scroll', () => {
    if (!window.hasMorePosts || window.isLoadingPosts) return;
    if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 1000) {
        window.loadMorePosts();
    }
}, { passive: true });

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

// Reply textarea behaviors: auto-grow + Cmd/Ctrl+Enter submit + ESC dismiss
// + has-content toggle (drives send/anon visibility)
// + aria-expanded sync
(function () {
    function autosize(ta) {
        ta.style.height = 'auto';
        const next = Math.min(ta.scrollHeight, 180); // CSS max-height = 180
        ta.style.height = next + 'px';
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

    // Keep aria-expanded in sync when the reply form toggles open/closed
    // and when nested replies expand. We patch the existing handlers if present.
    const origToggleReply = window.toggleReplyForm;
    if (typeof origToggleReply === 'function') {
        window.toggleReplyForm = function (commentId) {
            const r = origToggleReply.apply(this, arguments);
            const form = document.getElementById('reply-form-' + commentId);
            const isOpen = form && form.style.display !== 'none';
            const btn = document.querySelector('[aria-controls="reply-form-' + commentId + '"]');
            if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (isOpen) {
                const ta = document.getElementById('reply-content-' + commentId);
                if (ta) {
                    autosize(ta);
                    syncFormState(ta);
                    ta.focus();
                }
            } else if (form) {
                form.classList.remove('has-content');
            }
            return r;
        };
    }

    const origToggleNested = window.toggleNestedReplies;
    if (typeof origToggleNested === 'function') {
        window.toggleNestedReplies = function (commentId, show) {
            const r = origToggleNested.apply(this, arguments);
            const wrap = document.getElementById('hidden-replies-' + commentId);
            const isOpen = wrap && wrap.style.display !== 'none';
            const btn = document.querySelector('[aria-controls="hidden-replies-' + commentId + '"]');
            if (btn) btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            return r;
        };
    }

    /* ============================================================
       Override the client-side comment template so realtime + own-submit
       new comments use the preview markup (comment-like-btn, burst wrap,
       minimal reply form, anonymous styling, nested-reply level class).
       The original is at resources/js/legacy/posts.js:775.
       ============================================================ */
    function escapeAttr(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function currentUserAvatarUrl() {
        // Prefer the composer's avatar (always shown on /feed-preview);
        // fall back to socket config; finally a default.
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
    function verifiedBadgeSvgPreview(userId, size) {
        size = size || '.85em';
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'
            + ' width="' + size + '" height="' + size + '"'
            + ' style="display:inline-block;vertical-align:middle;margin-left:.2em;flex-shrink:0;"'
            + ' aria-label="Verified" role="img">'
            + '<circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/>'
            + '<path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'
            + '</svg>';
    }

    function renderCommentHtmlPreview(comment, level, currentUserId) {
        level = level || 0;
        const t = window.chatTranslations || {};
        const isAnonymous = !!comment.is_anonymous;
        const author = comment.user || {};
        const isAuthor = currentUserId && String(comment.user_id) === String(currentUserId);
        const isLiked = !!comment.user_liked;  // new comments: false; backend may set true on socket replays
        const likesCount = comment.likes_count || 0;
        const username = escapeAttr(author.username || 'user');
        const displayName = escapeAttr(author.username || 'user');
        const parentAuthorText = isAnonymous
            ? (t.anonymous_participant || 'Anonymous Participant')
            : '@' + (author.username || 'user');
        const createdAt = comment.created_at || new Date().toISOString();
        const isArabic = /[؀-ۿ]/.test(comment.content || '');
        const postId = comment.post_id;
        const showAnonChip = postHasSocialGroup(postId);

        const avatarHtml = isAnonymous
            ? '<div class="comment-avatar-placeholder anonymous" aria-hidden="true"><i class="fas fa-user-secret"></i></div>'
            : '<a href="/users/' + username + '" style="flex-shrink:0;display:flex;"><img src="' + escapeAttr(author.avatar_url || '/images/default-avatar.svg') + '" alt="" class="comment-avatar" style="pointer-events:none;" onerror="this.onerror=null;this.src=\'/images/default-avatar.svg\'"></a>';

        const authorVerifiedBadge = (!isAnonymous && author.is_verified) ? verifiedBadgeSvgPreview(author.id || comment.user_id) : '';
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
            : ('Reply to ' + parentAuthorText + '…');

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
                    + '<span>Esc to cancel · ⌘ + Enter to send</span>'
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
    }

    // Install the override after the original posts.js IIFE has run.
    // Both the user's own submitComment/submitReply (in posts.js) and the
    // socket listeners (handlePostCommented + the IIFE listener) look up
    // window.renderCommentHtml at call time, so this override wins on
    // every realtime path.
    function installRenderOverride() {
        if (typeof window.renderCommentHtml === 'function' && !window.renderCommentHtml.__previewOverride) {
            window.renderCommentHtml = renderCommentHtmlPreview;
            window.renderCommentHtml.__previewOverride = true;
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', installRenderOverride);
    } else {
        installRenderOverride();
    }
    // Defensive: re-install once after a short delay in case posts.js loads
    // after this script (Vite splits chunks).
    setTimeout(installRenderOverride, 0);
    setTimeout(installRenderOverride, 250);

    /* ============================================================
       Heart burst on comment-like click
       ============================================================ */
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

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.comment-like-btn');
        if (!btn) return;
        // Only burst when we're about to *become* liked (otherwise it fires on un-like too).
        // The original likeComment() flips .liked AFTER, so read current state.
        const willLike = !btn.classList.contains('liked');
        if (willLike) spawnLikeBurst(btn);
    }, true); // capture so we run before the inline onclick fully resolves
})();
</script>
@endsection
