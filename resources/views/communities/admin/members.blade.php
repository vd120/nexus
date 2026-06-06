@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="admin-page">
    <div class="admin-page-header">
        <h1 class="admin-page-title">{{ __('community_admin.manage_members') }}</h1>
        <p class="admin-page-subtitle">{{ __('community_admin.manage_members_subtitle') }}</p>
    </div>

    <div class="panel settings-section panel--overflow">
        <div class="panel-header admin-panel-header">
            <h3>{{ __('community_admin.member_list') }} ({{ $members->total() }})</h3>
            <div class="admin-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="member-search-admin" placeholder="{{ __('community_admin.search_members') }}">
            </div>
        </div>
        <div class="panel-body no-padding">
            <div class="admin-members-list">
                @foreach($members as $member)
                    <div class="admin-member-item" id="member-{{ $member->user->id }}">
                        <div class="user-info">
                            <a href="{{ route('users.show', $member->user) }}" style="display:flex;flex-shrink:0;"><img src="{{ $member->user->avatar_url }}" alt="" class="admin-member-avatar" style="pointer-events:none;"></a>
                            <div class="meta">
                                <div class="name-badge-row">
                                    <a href="{{ route('users.show', $member->user) }}" style="text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:.2em;"><strong class="admin-member-name">{{ $member->user->name }}</strong><x-verified-badge :user="$member->user" size=".85em" /></a>
                                    <div class="member-badges-list" id="member-badges-{{ $member->user->id }}">
                                        @foreach($member->badges as $badge)
                                            <span class="mini-badge" title="{{ $badge->name }}" style="color: {{ $badge->color }}; background: {{ $badge->color }}15;">
                                                <i class="{{ $badge->icon }}"></i>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                                <a href="{{ route('users.show', $member->user) }}" class="admin-member-handle" style="text-decoration:none;color:var(--text-muted);">{{ '@' . $member->user->username }}</a>
                            </div>
                        </div>
                        
                        <div class="member-actions-admin">
                            <span class="role-badge role-{{ $member->role }}">{{ __('messages.role_' . $member->role) }}</span>
                            
                            @if($member->user->id !== $group->creator_id)
                                <div class="admin-dropdown">
                                    <button class="btn-icon-admin" onclick="toggleMemberDropdown(event, '{{ $member->user->id }}')">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu-admin" id="dropdown-{{ $member->user->id }}">
                                        <button onclick="updateRole('{{ $member->user->id }}', 'admin')">
                                            <i class="fas fa-crown"></i> {{ __('community_admin.make_admin') }}
                                        </button>
                                        <button onclick="updateRole('{{ $member->user->id }}', 'moderator')">
                                            <i class="fas fa-shield-alt"></i> {{ __('community_admin.make_moderator') }}
                                        </button>
                                        <button onclick="updateRole('{{ $member->user->id }}', 'member')">
                                            <i class="fas fa-user"></i> {{ __('community_admin.make_member') }}
                                        </button>
                                        
                                        <div class="dropdown-divider-admin"></div>
                                        <span class="dropdown-header-admin">{{ __('community_admin.manage_badges') }}</span>
                                        @foreach($group->badges as $badge)
                                            @php $hasBadge = $member->badges->contains($badge->id); @endphp
                                            <button onclick="toggleBadge('{{ $member->user->id }}', '{{ $badge->id }}', this)" class="badge-toggle-btn {{ $hasBadge ? 'active' : '' }}">
                                                <i class="{{ $badge->icon }}" style="color: {{ $badge->color }}"></i>
                                                <span>{{ $badge->name }}</span>
                                                @if($hasBadge)
                                                    <i class="fas fa-check check-mark"></i>
                                                @endif
                                            </button>
                                        @endforeach

                                        <div class="dropdown-divider-admin"></div>
                                        <button onclick="muteMember('{{ $member->user->id }}')" class="danger">
                                            <i class="fas fa-volume-mute"></i> {{ __('community_admin.mute') }}
                                        </button>
                                        <button onclick="removeMember('{{ $member->user->id }}')" class="danger">
                                            <i class="fas fa-user-minus"></i> {{ __('community_admin.remove') }}
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="pagination">
        {{ $members->links() }}
    </div>
</div>

<script>
    function toggleMemberDropdown(event, userId) {
        event.stopPropagation();
        const dropdown = document.getElementById(`dropdown-${userId}`);
        const allDropdowns = document.querySelectorAll('.dropdown-menu-admin');
        
        allDropdowns.forEach(d => {
            if (d.id !== `dropdown-${userId}`) d.classList.remove('show');
        });
        
        dropdown.classList.toggle('show');
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu-admin').forEach(d => d.classList.remove('show'));
    });

    function updateRole(userId, role) {
        fetch(`/communities/{{ $group->slug }}/admin/members/${userId}/role`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ role: role })
        }).then(res => {
            if (!res.ok) throw new Error('Failed to update role');
            return res.json();
        }).then(data => {
            showToast("{{ __('community_admin.role_updated', ['role' => '']) }}" + (data.label || role), 'success');

            // Update UI dynamically
            const memberItem = document.getElementById(`member-${userId}`);
            if (memberItem) {
                const badge = memberItem.querySelector('.role-badge');
                if (badge) {
                    badge.textContent = data.label || (role.charAt(0).toUpperCase() + role.slice(1));
                    badge.className = `role-badge role-${role}`;
                }
            }
        }).catch(err => {
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }

    function removeMember(userId) {
        if (!confirm("{{ __('community_admin.remove_member_confirm') }}")) return;
        fetch(`/communities/{{ $group->slug }}/admin/members/${userId}/remove`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(res => {
            if (!res.ok) throw new Error('Failed to remove member');
            return res.json();
        }).then(data => {
            document.getElementById(`member-${userId}`).style.display = 'none';
            showToast("{{ __('community_admin.member_removed') }}");
        }).catch(err => {
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }

    function muteMember(userId) {
        const durations = [
            { label: '1 hour', hours: 1 },
            { label: '6 hours', hours: 6 },
            { label: '24 hours', hours: 24 },
            { label: '7 days', hours: 168 },
            { label: '30 days', hours: 720 },
        ];
        const choice = prompt(
            "Mute member for how long?\n\n" +
            durations.map((d, i) => `${i + 1}. ${d.label}`).join('\n') +
            "\n\nEnter a number (1-5):"
        );
        if (!choice) return;
        const idx = parseInt(choice) - 1;
        if (isNaN(idx) || idx < 0 || idx >= durations.length) {
            showToast('Invalid selection.', 'error');
            return;
        }
        const mutedUntil = new Date(Date.now() + durations[idx].hours * 3600 * 1000).toISOString().slice(0, 19).replace('T', ' ');
        fetch(`/communities/{{ $group->slug }}/admin/members/${userId}/mute`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ muted_until: mutedUntil })
        }).then(res => {
            if (!res.ok) throw new Error('Failed to mute member');
            return res.json();
        }).then(data => {
            showToast(data.message || "Member muted successfully.", 'success');
        }).catch(err => {
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }

    // Member search — filters visible rows by name/username
    document.getElementById('member-search-admin').addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.admin-member-item').forEach(function (row) {
            const name = (row.querySelector('.admin-member-name')?.textContent || '').toLowerCase();
            const handle = (row.querySelector('.admin-member-handle')?.textContent || '').toLowerCase();
            row.style.display = (!q || name.includes(q) || handle.includes(q)) ? '' : 'none';
        });
    });

    function toggleBadge(userId, badgeId, btn) {
        fetch(`/communities/{{ $group->slug }}/admin/members/${userId}/badges/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ badge_id: badgeId })
        }).then(res => {
            if (!res.ok) throw new Error('Failed to toggle badge');
            return res.json();
        }).then(data => {
            showToast(data.message, 'success');
            
            // Update dropdown UI
            const isActive = data.action === 'granted';
            btn.classList.toggle('active', isActive);
            
            let check = btn.querySelector('.check-mark');
            if (isActive && !check) {
                btn.insertAdjacentHTML('beforeend', '<i class="fas fa-check check-mark"></i>');
            } else if (!isActive && check) {
                check.remove();
            }

            // Update member list UI
            const badgeList = document.getElementById(`member-badges-${userId}`);
            if (badgeList) {
                if (isActive) {
                    // This is a bit lazy but works: we can't easily get badge info here without another request or pre-storing it
                    // Let's just reload the badge list or use the info from the button
                    const icon = btn.querySelector('i').className;
                    const color = btn.querySelector('i').style.color;
                    const name = btn.querySelector('span').textContent;
                    
                    const badgeHtml = `<span class="mini-badge" title="${name}" style="color: ${color}; background: ${color}15;">
                        <i class="${icon}"></i>
                    </span>`;
                    badgeList.insertAdjacentHTML('beforeend', badgeHtml);
                } else {
                    // Find and remove the badge from the list
                    const icon = btn.querySelector('i').className;
                    const badges = badgeList.querySelectorAll('.mini-badge');
                    badges.forEach(b => {
                        if (b.querySelector('i').className === icon) b.remove();
                    });
                }
            }
        }).catch(err => {
            console.error(err);
            showToast('Error: ' + err.message, 'error');
        });
    }
</script>
        </div>
    </main>
</div>
@endsection
