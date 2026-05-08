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

    <div class="panel settings-section">
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
                            <img src="{{ $member->user->avatar_url }}" alt="" class="admin-member-avatar">
                            <div class="meta">
                                <div class="name-badge-row">
                                    <strong class="admin-member-name">{{ $member->user->name }}</strong>
                                    <div class="member-badges-list" id="member-badges-{{ $member->user->id }}">
                                        @foreach($member->badges as $badge)
                                            <span class="mini-badge" title="{{ $badge->name }}" style="color: {{ $badge->color }}; background: {{ $badge->color }}15;">
                                                <i class="{{ $badge->icon }}"></i>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                                <span class="admin-member-handle">{{ '@' . $member->user->username }}</span>
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

<style>
    .admin-page { max-width: 1100px; margin: 0 auto; }
    
    .admin-panel-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 20px 24px; }
    
    .admin-search-box { position: relative; flex: 1; max-width: 300px; }
    .admin-search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
    .admin-search-box input { width: 100%; background: var(--surface-hover); border: 1px solid var(--border); padding: 10px 16px 10px 40px; border-radius: 12px; color: var(--text); font-size: 14px; outline: none; transition: 0.2s; }
    .admin-search-box input:focus { border-color: var(--admin-accent); background: var(--surface); }

    .admin-members-list { display: flex; flex-direction: column; }
    .admin-member-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid var(--border); transition: 0.2s; }
    .admin-member-item:hover { background: var(--surface-hover); }
    .admin-member-item:last-child { border-bottom: none; }
    
    .user-info { display: flex; align-items: center; gap: 14px; }
    .admin-member-avatar { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border); }
    .admin-member-name { display: block; font-size: 15px; color: var(--text); font-weight: 700; margin-bottom: 2px; }
    .admin-member-handle { font-size: 12px; color: var(--text-muted); font-weight: 500; }

    .member-actions-admin { display: flex; align-items: center; gap: 16px; }
    
    .role-badge { font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 8px; letter-spacing: 0.5px; }
    .role-admin { background: rgba(217, 119, 6, 0.1); color: #d97706; }
    .role-moderator { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
    .role-member { background: var(--surface-hover); color: var(--text-muted); }

    .btn-icon-admin { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-icon-admin:hover { border-color: var(--admin-accent); color: var(--admin-accent); }

    .admin-dropdown { position: relative; }
    .dropdown-menu-admin { 
        position: absolute; right: 0; top: calc(100% + 8px); 
        background: var(--surface); border: 1px solid var(--border); 
        border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
        z-index: 1000; min-width: 200px; display: none; padding: 8px;
        animation: fadeIn 0.2s ease-out;
    }
    .dropdown-menu-admin.show { display: block; }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-menu-admin button { 
        width: 100%; padding: 12px 14px; text-align: left; background: none; border: none; 
        font-size: 14px; font-weight: 600; color: var(--text); cursor: pointer; 
        border-radius: 10px; display: flex; align-items: center; gap: 10px; transition: 0.2s;
    }
    .dropdown-menu-admin button i { width: 18px; text-align: center; font-size: 14px; opacity: 0.7; }
    .dropdown-menu-admin button:hover { background: var(--surface-hover); color: var(--admin-accent); }
    .dropdown-menu-admin button:hover i { opacity: 1; }
    .dropdown-menu-admin button.danger { color: #ef4444; }
    .dropdown-menu-admin button.danger:hover { background: rgba(239, 68, 68, 0.05); }
    .dropdown-divider-admin { height: 1px; background: var(--border); margin: 6px; }

    .dropdown-header-admin { display: block; padding: 8px 14px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
    
    .badge-toggle-btn { position: relative; }
    .badge-toggle-btn .check-mark { margin-left: auto; color: var(--success); font-size: 12px; }
    .badge-toggle-btn.active { background: var(--admin-accent-glow); }

    .name-badge-row { display: flex; align-items: center; gap: 8px; }
    .member-badges-list { display: flex; align-items: center; gap: 4px; }
    .mini-badge { width: 20px; height: 20px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; border: 1px solid transparent; }

    .no-padding { padding: 0 !important; }

    @media (max-width: 600px) {
        .admin-panel-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        .admin-search-box { max-width: 100%; }
        .admin-member-item { flex-direction: column; align-items: flex-start; gap: 16px; }
        .member-actions-admin { width: 100%; justify-content: space-between; }
    }
</style>

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
            showToast("{{ __('community_admin.role_updated', ['role' => '']) }}" + role, 'success');
            
            // Update UI dynamically
            const memberItem = document.getElementById(`member-${userId}`);
            if (memberItem) {
                const badge = memberItem.querySelector('.role-badge');
                if (badge) {
                    badge.textContent = role.charAt(0).toUpperCase() + role.slice(1);
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
