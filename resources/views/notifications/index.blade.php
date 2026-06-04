@extends('layouts.app')

@section('title', __('notifications.notifications'))

@section('content')
<div class="notifications-page" style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <div class="notifications-header" style="margin-bottom: 30px;">
        <h1 style="font-size: 24px; font-weight: 700; color: var(--text); margin-bottom: 8px;">
            <i class="fas fa-bell" style="margin-right: 10px;"></i>
            {{ __('notifications.notifications') }}
        </h1>
        <p style="color: var(--text-muted); font-size: 14px;">
            {{ __('notifications.enable_push_desc') }}
        </p>
    </div>

    <div class="notifications-content" id="notifications-list">
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-bell-slash" style="font-size: 64px; color: var(--text-muted); opacity: 0.5; margin-bottom: 20px;"></i>
            <p style="color: var(--text-muted); font-size: 16px;">{{ __('notifications.no_notifications') }}</p>
        </div>
    </div>
</div>

<style>
.notifications-page {
    min-height: calc(100vh - 80px);
}

.notifications-header {
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding-bottom: 20px;
}

.notifications-content {
    padding: 20px 0;
}
</style>

<script>
function escapeHtml(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function notifPageSkeleton(n) {
    return Array.from({length: n}, () =>
        `<div style="display:flex;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border-color,#2a2a3e);align-items:flex-start;">
            <div class="sk" style="width:42px;height:42px;border-radius:50%;flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:8px;padding-top:4px;">
                <div class="sk sk-line" style="width:75%;"></div>
                <div class="sk sk-line" style="width:40%;"></div>
            </div>
        </div>`
    ).join('');
}

// Load notifications
async function loadNotificationsList() {
    const list = document.getElementById('notifications-list');
    if (list && !list.querySelector('.notification-item')) {
        list.innerHTML = notifPageSkeleton(6);
    }
    try {
        const response = await fetch('/notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'include',
        });

        if (!response.ok) throw new Error('Failed to load notifications');

        const data = await response.json();
        const list = document.getElementById('notifications-list');

        if (!data.notifications || data.notifications.length === 0) {
            list.innerHTML = `
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-bell-slash" style="font-size: 64px; color: var(--text-muted); opacity: 0.5; margin-bottom: 20px;"></i>
                    <p style="color: var(--text-muted); font-size: 16px;">{{ __('notifications.no_notifications') }}</p>
                </div>
            `;
            return;
        }

        list.innerHTML = data.notifications.map(n => {
            const typeColor = getTypeColor(n.type);
            const typeIcon = getTypeIcon(n.type);
            
            // Check for reaction image
            let reactionImg = null;
            if (['post_reaction', 'chat_reaction', 'story_reaction', 'like', 'event_reaction'].includes(n.type)) {
                // For 'like', we use the heart SVG by default
                const emoji = n.type === 'like' ? '❤️' : (n.data ? n.data.reaction_type : null);
                if (emoji) {
                    reactionImg = window.getReactionImage ? window.getReactionImage(emoji) : null;
                }
            }

            return `
            <div class="notification-item" onclick="if(window.handleNotifClick) window.handleNotifClick(${n.id}, '${n.link || ''}')" style="
                display: flex;
                align-items: flex-start;
                gap: 15px;
                padding: 15px;
                border-bottom: 1px solid rgba(255,255,255,0.05);
                cursor: pointer;
                transition: background 0.2s;
                ${!n.read_at ? 'background: rgba(99, 102, 241, 0.05); border-left: 3px solid var(--primary);' : ''}
            ">
                <div style="
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: ${typeColor.bg};
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    position: relative;
                ">
                    ${reactionImg ? 
                        `<img src="${reactionImg}" style="width: 24px; height: 24px; object-fit: contain;">` :
                        `<i class="fas fa-${typeIcon}" style="color: ${typeColor.color};"></i>`
                    }
                </div>
                <div style="flex: 1; min-width: 0;">
                    <p style="color: var(--text); font-size: 14px; margin-bottom: 5px; word-wrap: break-word;">${escapeHtml(n.message || 'Notification')}</p>
                    <p style="color: var(--text-muted); font-size: 12px;">${formatDate(n.created_at)}</p>
                    ${n.link ? `<span style="color: var(--primary); font-size: 13px; text-decoration: none; display: inline-block; margin-top: 5px;">View →</span>` : ''}
                </div>
            </div>
        `}).join('');
    } catch (error) {
        console.error('Error loading notifications:', error);
        document.getElementById('notifications-list').innerHTML = `
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-exclamation-circle" style="font-size: 64px; color: var(--danger); opacity: 0.5; margin-bottom: 20px;"></i>
                <p style="color: var(--text-muted); font-size: 16px;">Error loading notifications</p>
            </div>
        `;
    }
}

// Helper function to get icon based on notification type
function getTypeIcon(type) {
    const icons = {
        'like': 'heart',
        'comment': 'comment',
        'follow': 'user-plus',
        'mention': 'at',
        'message': 'envelope',
        'story_reaction': 'heart',
        'post_reaction': 'heart',
        'chat_reaction': 'heart',
        'group_invite': 'users',
        'event_reaction': 'star',
        'birthday': 'birthday-cake',
        'birthday_reminder': 'birthday-cake',
        'anniversary': 'undo',
        'report_accepted': 'check-circle',
        'report_rejected': 'times-circle',
    };
    return icons[type] || 'bell';
}

// Helper function to get color based on notification type
function getTypeColor(type) {
    const colors = {
        'like': { bg: 'rgba(239, 68, 68, 0.1)', color: '#ef4444' },
        'comment': { bg: 'rgba(59, 130, 246, 0.1)', color: '#3b82f6' },
        'follow': { bg: 'rgba(16, 185, 129, 0.1)', color: '#10b981' },
        'mention': { bg: 'rgba(139, 92, 246, 0.1)', color: '#8b5cf6' },
        'message': { bg: 'rgba(245, 158, 11, 0.1)', color: '#f59e0b' },
        'story_reaction': { bg: 'rgba(236, 72, 153, 0.1)', color: '#ec4899' },
        'post_reaction': { bg: 'rgba(239, 68, 68, 0.1)', color: '#ef4444' },
        'chat_reaction': { bg: 'rgba(239, 68, 68, 0.1)', color: '#ef4444' },
        'group_invite': { bg: 'rgba(99, 102, 241, 0.1)', color: '#6366f1' },
        'event_reaction': { bg: 'rgba(245, 158, 11, 0.1)', color: '#f59e0b' },
        'birthday': { bg: 'rgba(236, 72, 153, 0.1)', color: '#ec4899' },
        'birthday_reminder': { bg: 'rgba(236, 72, 153, 0.1)', color: '#ec4899' },
        'anniversary': { bg: 'rgba(99, 102, 241, 0.1)', color: '#6366f1' },
        'report_accepted': { bg: 'rgba(16, 185, 129, 0.1)', color: '#10b981' },
        'report_rejected': { bg: 'rgba(239, 68, 68, 0.1)', color: '#ef4444' },
    };
    return colors[type] || { bg: 'rgba(99, 102, 241, 0.1)', color: '#6366f1' };
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString();
}

// Load on page load
window.runOnPageLoad( loadNotificationsList);
</script>
@endsection
