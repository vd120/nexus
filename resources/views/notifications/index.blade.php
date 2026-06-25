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
        @if($notifications->isEmpty())
            <div id="notif-empty-state" style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-bell-slash" style="font-size: 64px; color: var(--text-muted); opacity: 0.5; margin-bottom: 20px;"></i>
                <p style="color: var(--text-muted); font-size: 16px;">{{ __('notifications.no_notifications') }}</p>
            </div>
        @else
            @foreach($notifications as $n)
            @php
                $typeColors = [
                    'like'                 => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#ef4444'],
                    'comment'              => ['bg' => 'rgba(59,130,246,0.1)',  'color' => '#3b82f6'],
                    'comment_reply'        => ['bg' => 'rgba(59,130,246,0.1)',  'color' => '#3b82f6'],
                    'comment_like'         => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#ef4444'],
                    'follow'               => ['bg' => 'rgba(16,185,129,0.1)',  'color' => '#10b981'],
                    'mention'              => ['bg' => 'rgba(139,92,246,0.1)',  'color' => '#8b5cf6'],
                    'message'              => ['bg' => 'rgba(245,158,11,0.1)',  'color' => '#f59e0b'],
                    'story_reaction'       => ['bg' => 'rgba(236,72,153,0.1)',  'color' => '#ec4899'],
                    'post_reaction'        => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#ef4444'],
                    'chat_reaction'        => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#ef4444'],
                    'group_invite'         => ['bg' => 'rgba(99,102,241,0.1)',  'color' => '#6366f1'],
                    'group_join'           => ['bg' => 'rgba(99,102,241,0.1)',  'color' => '#6366f1'],
                    'call'                 => ['bg' => 'rgba(16,185,129,0.1)',  'color' => '#10b981'],
                    'report_accepted'      => ['bg' => 'rgba(16,185,129,0.1)',  'color' => '#10b981'],
                    'report_rejected'      => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#ef4444'],
                    'report_action_owner'  => ['bg' => 'rgba(245,158,11,0.1)',  'color' => '#f59e0b'],
                    'community_report_new' => ['bg' => 'rgba(245,158,11,0.1)',  'color' => '#f59e0b'],
                ];
                $typeIcons = [
                    'like'                 => 'heart',
                    'comment'              => 'comment',
                    'comment_reply'        => 'reply',
                    'comment_like'         => 'heart',
                    'follow'               => 'user-plus',
                    'mention'              => 'at',
                    'message'              => 'envelope',
                    'story_reaction'       => 'heart',
                    'post_reaction'        => 'heart',
                    'chat_reaction'        => 'heart',
                    'group_invite'         => 'users',
                    'group_join'           => 'users',
                    'call'                 => 'phone',
                    'report_accepted'      => 'check-circle',
                    'report_rejected'      => 'times-circle',
                    'report_action_owner'  => 'shield-alt',
                    'community_report_new' => 'flag',
                ];
                $tc = $typeColors[$n->type] ?? ['bg' => 'rgba(99,102,241,0.1)', 'color' => '#6366f1'];
                $icon = $typeIcons[$n->type] ?? 'bell';
            @endphp
            <div class="notification-item"
                 onclick="if(window.handleNotifClick) window.handleNotifClick({{ $n->id }}, '{{ $n->link ?? '' }}')"
                 style="display:flex;align-items:flex-start;gap:15px;padding:15px;border-bottom:1px solid rgba(255,255,255,0.05);cursor:pointer;transition:background 0.2s;{{ !$n->read_at ? 'background:rgba(99,102,241,0.05);border-left:3px solid var(--primary);' : '' }}"
                 data-id="{{ $n->id }}"
                 data-type="{{ $n->type }}"
                 data-notif-data='@json($n->data)'>
                <div style="width:40px;height:40px;border-radius:50%;background:{{ $tc['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-{{ $icon }}" style="color:{{ $tc['color'] }};"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="color:var(--text);font-size:14px;margin-bottom:5px;word-wrap:break-word;">{{ $n->message ?? __('notifications.new_notification') }}</p>
                    <p style="color:var(--text-muted);font-size:12px;">{{ $n->created_at->diffForHumans() }}</p>
                    @if($n->link)
                        <span style="color:var(--primary);font-size:13px;display:inline-block;margin-top:5px;">{{ __('messages.view') }} →</span>
                    @endif
                </div>
            </div>
            @endforeach
        @endif
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
// Mark items as read when clicked — delegate to global handler
document.getElementById('notifications-list').addEventListener('click', function(e) {
    const item = e.target.closest('.notification-item');
    if (item) {
        item.style.background = '';
        item.style.borderLeft = '';
    }
});

// Decrypt notifications on page load
document.addEventListener("DOMContentLoaded", async function() {
    const items = document.querySelectorAll('.notification-item[data-type="message"]');
    for (const item of items) {
        let notifData = item.getAttribute('data-notif-data');
        if (notifData) {
            try {
                notifData = JSON.parse(notifData);
                if (notifData && notifData.is_e2e_encrypted && notifData.encrypted_content) {
                    decryptPageNotification(item, notifData);
                }
            } catch(e) {
                console.error("Failed to parse notif data", e);
            }
        }
    }
});

async function decryptPageNotification(item, data) {
    try {
        const e2e = await getE2EManager();
        if (!e2e) return;
        
        let rawContent = data.encrypted_content;
        if (rawContent.includes("&quot;")) {
            const t = document.createElement("textarea");
            t.innerHTML = rawContent;
            rawContent = t.value;
        }
        
        let parsed = JSON.parse(rawContent);
        let decryptTarget = parsed;
        if (parsed.__nexus_reply__ && typeof parsed.content === "string") {
            try {
                const inner = JSON.parse(parsed.content);
                if (inner.__nexus_encrypted__) decryptTarget = inner;
            } catch (e) {}
        }
        
        if (!decryptTarget.__nexus_encrypted__) return;
        
        const decryptPromise = (data.is_group === true || String(data.is_group) === "true")
            ? e2e.decryptGroupMessage({
                  ...decryptTarget,
                  conversation_id: data.conversation_id,
              })
            : e2e.decryptMessage(
                  decryptTarget,
                  data.sender_id,
              );
              
        const timeout = new Promise((_, rej) =>
            setTimeout(() => rej(new Error("timeout")), 2000)
        );
        
        const result = await Promise.race([decryptPromise, timeout]);
        const text = result?.text || "";
        if (text) {
            const pEl = item.querySelector('p');
            if (pEl) {
                const sender = data.sender_username || "";
                const prefix = sender ? `${sender}: ` : "";
                const cleanText = window.sanitizeMessage ? window.sanitizeMessage(text) : text;
                const truncatedText = cleanText.length > 120 ? cleanText.substring(0, 120) + '...' : cleanText;
                
                // Check if this is a group message
                const isGroup = data.is_group || false;
                const groupName = data.group_name || null;
                if (isGroup && groupName) {
                    pEl.textContent = `${groupName} - ${prefix}${truncatedText}`;
                } else {
                    pEl.textContent = `${prefix}${truncatedText}`;
                }
            }
        }
    } catch (err) {
        console.error("[E2E] Failed to decrypt page notification:", err);
    }
}

// Real-time: prepend new notification when received via socket
window.addEventListener('notification:page:new', function(e) {
    const n = e.detail;
    if (!n) return;
    const list = document.getElementById('notifications-list');
    const empty = document.getElementById('notif-empty-state');
    if (empty) empty.remove();

    const typeColors = {
        like: 'rgba(239,68,68,0.1)', comment: 'rgba(59,130,246,0.1)',
        follow: 'rgba(16,185,129,0.1)', mention: 'rgba(139,92,246,0.1)',
        message: 'rgba(245,158,11,0.1)', post_reaction: 'rgba(239,68,68,0.1)',
        group_invite: 'rgba(99,102,241,0.1)',
    };
    const typeTextColors = {
        like: '#ef4444', comment: '#3b82f6', follow: '#10b981',
        mention: '#8b5cf6', message: '#f59e0b', post_reaction: '#ef4444',
        group_invite: '#6366f1',
    };
    const typeIcons = {
        like: 'heart', comment: 'comment', comment_reply: 'reply',
        follow: 'user-plus', mention: 'at', message: 'envelope',
        post_reaction: 'heart', group_invite: 'users', call: 'phone',
    };

    const bg = typeColors[n.type] || 'rgba(99,102,241,0.1)';
    const color = typeTextColors[n.type] || '#6366f1';
    const icon = typeIcons[n.type] || 'bell';

    const div = document.createElement('div');
    div.className = 'notification-item';
    div.setAttribute('onclick', `if(window.handleNotifClick) window.handleNotifClick(${n.id}, '${n.link || ''}')`);
    div.setAttribute('data-id', n.id);
    div.setAttribute('data-type', n.type);
    div.setAttribute('data-notif-data', JSON.stringify(n.data));
    div.style.cssText = `display:flex;align-items:flex-start;gap:15px;padding:15px;border-bottom:1px solid rgba(255,255,255,0.05);cursor:pointer;transition:background 0.2s;background:rgba(99,102,241,0.05);border-left:3px solid var(--primary);`;
    div.innerHTML = `
        <div style="width:40px;height:40px;border-radius:50%;background:${bg};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-${icon}" style="color:${color};"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <p style="color:var(--text);font-size:14px;margin-bottom:5px;word-wrap:break-word;">${window.escapeHtml ? window.escapeHtml(n.message || '') : (n.message || '')}</p>
            <p style="color:var(--text-muted);font-size:12px;">{{ __('messages.just_now') }}</p>
        </div>`;
    list.prepend(div);

    let notifData = n.data;
    if (typeof notifData === 'string') {
        try { notifData = JSON.parse(notifData); } catch(e) { notifData = {}; }
    }
    if (n.type === 'message' && notifData?.is_e2e_encrypted && notifData?.encrypted_content) {
        decryptPageNotification(div, notifData);
    }
});
</script>
@endsection
