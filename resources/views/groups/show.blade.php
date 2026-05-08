@extends('layouts.app')

@section('title', $group->name . ' - ' . __('chat.group_settings'))

@section('content')
<div class="settings-page" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <div class="settings-header" style="display: flex; align-items: center; gap: 24px; margin-bottom: 40px;">
        <div class="settings-back">
            <a href="{{ route('chat.show', optional($group->conversation)->slug ?? '') }}" class="btn-icon" style="background: var(--surface); border: 1px solid var(--border); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text); text-decoration: none;">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        <div>
            <h1 style="font-size: 28px; font-weight: 800; margin: 0;">{{ __('chat.group_settings') }}</h1>
            <p style="color: var(--text-muted); margin: 4px 0 0;">{{ $group->name }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom: 24px; padding: 16px; border-radius: 12px; background: rgba(0, 168, 132, 0.1); border: 1px solid var(--primary); color: var(--primary);">
            {{ session('success') }}
        </div>
    @endif

    <div class="settings-layout">
        {{-- Navigation Tabs --}}
        <aside class="settings-sidebar">
            <nav class="settings-nav" style="display: flex; flex-direction: column; gap: 8px;">
                <button class="nav-item active" onclick="showTab('general')" id="tab-btn-general">
                    <i class="fas fa-cog"></i> {{ __('chat.general') }}
                </button>
                <button class="nav-item" onclick="showTab('members')" id="tab-btn-members">
                    <i class="fas fa-users"></i> {{ __('chat.members') }} <span id="nav-member-count" style="margin-left: auto; opacity: 0.7; font-size: 0.9em;">({{ $group->members->count() }})</span>
                </button>
                <button class="nav-item" onclick="showTab('preferences')" id="tab-btn-preferences">
                    <i class="fas fa-user-cog"></i> {{ __('chat.my_preferences') }}
                </button>
            </nav>
        </aside>

        {{-- Content Area --}}
        <main class="settings-content">
            {{-- General Settings Tab --}}
            <div id="tab-general" class="tab-pane active">
                <section class="settings-section">
                    <h3 style="margin-bottom: 24px; font-size: 20px;">{{ __('chat.group_information') }}</h3>
                    
                    <form action="{{ route('groups.update', $group) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: block; font-weight: 700; margin-bottom: 8px;">{{ __('chat.group_avatar') }}</label>
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <img src="{{ $group->avatar ? asset('storage/' . $group->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($group->name) }}" alt="" id="avatar-preview" style="width: 80px; height: 80px; border-radius: 20px; object-fit: cover; border: 1px solid var(--border);">
                                @if($group->isAdmin(auth()->user()))
                                    <div class="custom-file-upload">
                                        <label for="avatar-input" class="btn btn-ghost" style="cursor: pointer;">
                                            <i class="fas fa-camera"></i> {{ __('chat.change_photo') }}
                                        </label>
                                        <input type="file" id="avatar-input" name="avatar" style="display: none;" onchange="previewImage(this)">
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: block; font-weight: 700; margin-bottom: 8px;">{{ __('chat.group_name') }}</label>
                            <input type="text" name="name" value="{{ $group->name }}" class="form-control" {{ !$group->isAdmin(auth()->user()) ? 'disabled' : '' }}>
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label style="display: block; font-weight: 700; margin-bottom: 8px;">{{ __('chat.description') }} ({{ __('chat.optional') }})</label>
                            <textarea name="description" class="form-control" rows="3" {{ !$group->isAdmin(auth()->user()) ? 'disabled' : '' }}>{{ $group->description }}</textarea>
                        </div>

                        @if($group->isAdmin(auth()->user()))
                            <div style="margin-top: 32px;">
                                <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 32px; font-weight: 700;">{{ __('chat.save_changes') }}</button>
                            </div>
                        @endif
                    </form>
                </section>

                <section class="settings-section" style="margin-top: 48px; border-top: 1px solid var(--border); padding-top: 40px;">
                    <h3 style="margin-bottom: 16px; font-size: 20px; color: var(--danger);">{{ __('chat.danger_zone') }}</h3>
                    <p style="color: var(--text-muted); margin-bottom: 24px;">{{ __('chat.danger_zone_desc') }}</p>
                    
                    <div style="display: flex; gap: 16px;">
                        <form action="{{ route('groups.leave', $group) }}" method="POST" onsubmit="return confirm('{{ __('chat.confirm_leave') }}')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger" style="height: 48px; font-weight: 700;">
                                <i class="fas fa-sign-out-alt"></i> {{ __('chat.leave_group') }}
                            </button>
                        </form>
                        
                        @if($group->creator_id == auth()->id())
                            <form action="{{ route('groups.destroy', $group) }}" method="POST" onsubmit="return confirm('{{ __('chat.confirm_delete_group') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="height: 48px; font-weight: 700;">
                                    <i class="fas fa-trash-alt"></i> {{ __('chat.delete_group') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </section>
            </div>

            {{-- Members Tab --}}
            <div id="tab-members" class="tab-pane">
                <section class="settings-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 id="member-count-title" data-template="{{ __('chat.group_members') }}" style="margin: 0; font-size: 20px;">{{ __('chat.group_members') }} ({{ $group->members->count() }})</h3>
                        @if($group->isAdmin(auth()->user()))
                            <button class="btn btn-ghost" onclick="showAddMemberModal()">
                                <i class="fas fa-plus"></i> {{ __('chat.add_member') }}
                            </button>
                        @endif
                    </div>

                    <div class="members-list" style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach($group->members->sortByDesc('role') as $member)
                            @include('groups.partials.member-item', ['member' => $member, 'group' => $group])
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- Preferences Tab --}}
            <div id="tab-preferences" class="tab-pane">
                <section class="settings-section">
                    <h3 style="margin-bottom: 24px; font-size: 20px;">{{ __('chat.my_preferences') }}</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="preference-item" style="display: flex; justify-content: space-between; align-items: center; padding: 20px; background: var(--surface); border: 1px solid var(--border); border-radius: 16px;">
                            <div>
                                <div style="font-weight: 700;">{{ __('chat.mute_notifications') }}</div>
                                <div style="font-size: 13px; color: var(--text-muted);">{{ __('chat.mute_desc') }}</div>
                            </div>
                            <button class="btn btn-ghost" onclick="toggleMute('{{ optional($group->conversation)->slug ?? '' }}')">
                                <i class="fas {{ $group->conversation && optional($group->conversation)->isMutedBy(auth()->id()) ? 'fa-bell-slash text-danger' : 'fa-bell text-success' }}"></i>
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<style>
    .settings-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 40px;
    }

    @media (max-width: 768px) {
        .settings-layout {
            grid-template-columns: 1fr;
            gap: 24px;
        }
        .settings-sidebar {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 8px;
        }
        .settings-nav {
            flex-direction: row !important;
            min-width: max-content;
        }
        .nav-item {
            white-space: nowrap;
        }
        .settings-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 16px !important;
        }
    }

    .nav-item {
        background: none;
        border: 1px solid transparent;
        padding: 12px 16px;
        border-radius: 12px;
        text-align: left;
        color: var(--text-muted);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .nav-item:hover { background: var(--surface-hover); color: var(--text); }
    .nav-item.active { background: var(--primary); color: white; }
    
    .tab-pane { display: none; }
    .tab-pane.active { display: block; animation: fadeIn 0.3s ease-out; }
    
    .member-dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        z-index: 10000 !important;
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        border-radius: 12px !important;
        padding: 8px !important;
        min-width: 180px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.4) !important;
        margin-top: 4px;
    }
    .member-dropdown-menu.show { display: block !important; }
    
    .dropdown-item {
        display: block;
        width: 100%;
        padding: 10px 16px;
        color: var(--text);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        border: none;
        background: none;
        text-align: left;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .form-control {
        width: 100%;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px 16px;
        color: var(--text);
        outline: none;
        transition: border-color 0.2s;
    }
    .form-control:focus { border-color: var(--primary); }
    .form-control:disabled { opacity: 0.7; cursor: not-allowed; }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .btn-outline-danger {
        border: 1px solid var(--danger);
        color: var(--danger);
        background: none;
        padding: 0 20px;
    }
    .btn-outline-danger:hover {
        background: var(--danger);
        color: white;
    }

    .dropdown-item:hover {
        background: var(--surface-hover) !important;
    }
</style>

<script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
        
        document.getElementById('tab-' + tabName).classList.add('active');
        document.getElementById('tab-btn-' + tabName).classList.add('active');
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function toggleMute(convId) {
        if (!convId) return;
        fetch(`/chat/${convId}/mute`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function showAddMemberModal() {
        document.getElementById('addMemberModal').classList.add('show');
    }

    function closeAddMemberModal() {
        document.getElementById('addMemberModal').classList.remove('show');
    }

    function filterUsers(query) {
        const q = query.toLowerCase();
        document.querySelectorAll('.selectable-user').forEach(el => {
            const name = el.getAttribute('data-name').toLowerCase();
            el.style.display = name.includes(q) ? 'flex' : 'none';
        });
    }
    function toggleMemberDropdown(event, memberId) {
        event.stopPropagation();
        const dropdown = document.getElementById('dropdown-' + memberId);
        if (!dropdown) {
            console.error('Dropdown not found for member:', memberId);
            return;
        }
        
        const isOpen = dropdown.classList.contains('show');
        
        // Close all other dropdowns
        document.querySelectorAll('.member-dropdown-menu').forEach(el => {
            el.classList.remove('show');
            el.style.display = 'none';
        });
        
        if (!isOpen) {
            dropdown.classList.add('show');
            dropdown.style.display = 'block';
            console.log('Opened dropdown for:', memberId);
        }
    }

    function updateMemberCount(count) {
        const title = document.getElementById('member-count-title');
        if (title && count !== undefined) {
            const template = title.getAttribute('data-template');
            title.textContent = `${template} (${count})`;
        }
        
        const navCount = document.getElementById('nav-member-count');
        if (navCount && count !== undefined) {
            navCount.textContent = `(${count})`;
        }
    }

    // AJAX form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.closest('.modal') || form.closest('.settings-section')) {
            // Check if it's the general settings form or member actions
            const isGeneralForm = form.action.includes('/update');
            const isAddMemberForm = form.action.includes('/members/add');
            const isRoleForm = form.action.includes('/role');
            const isRemoveForm = form.action.includes('/remove');
            const isLeaveForm = form.action.includes('/leave');
            const isDeleteForm = form.action.includes('/delete');

            if (isGeneralForm || isAddMemberForm || isRoleForm || isRemoveForm || isLeaveForm || isDeleteForm) {
                e.preventDefault();
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
                
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }

                fetch(form.action, {
                    method: form.method || 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        
                        if (data.member_count !== undefined) {
                            updateMemberCount(data.member_count);
                        }

                        if (isGeneralForm) {
                            // Update group name/desc in UI if needed
                            const title = document.querySelector('h1 + p');
                            if (title) title.textContent = formData.get('name');
                        } else if (isAddMemberForm) {
                            closeAddMemberModal();
                            const list = document.querySelector('.members-list');
                            if (list) {
                                // Remove "No members" message if it exists
                                const emptyMsg = list.querySelector('p');
                                if (emptyMsg && emptyMsg.textContent.includes('{{ __('chat.no_contacts_to_add') }}')) {
                                    emptyMsg.remove();
                                }
                                
                                const temp = document.createElement('div');
                                temp.innerHTML = data.html;
                                const newItem = temp.firstElementChild;
                                newItem.style.opacity = '0';
                                newItem.style.transform = 'translateY(10px)';
                                list.appendChild(newItem);
                                
                                // Trigger animation
                                setTimeout(() => {
                                    newItem.style.transition = 'all 0.3s ease-out';
                                    newItem.style.opacity = '1';
                                    newItem.style.transform = 'translateY(0)';
                                }, 10);
                            }
                        } else if (isRoleForm) {
                            const memberItem = form.closest('.member-item');
                            if (memberItem) {
                                const temp = document.createElement('div');
                                temp.innerHTML = data.html;
                                const newItem = temp.firstElementChild;
                                memberItem.replaceWith(newItem);
                            }
                        } else if (isRemoveForm) {
                            const memberItem = form.closest('.member-item');
                            if (memberItem) {
                                memberItem.style.opacity = '0';
                                memberItem.style.transform = 'translateX(20px)';
                                setTimeout(() => memberItem.remove(), 300);
                            }
                        } else if (isLeaveForm || isDeleteForm || data.redirect) {
                            window.location.href = data.redirect || '{{ route('chat.index') }}';
                        }
                    } else {
                        showToast(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Connection error', 'error');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }
                });
            }
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.member-dropdown-menu').forEach(el => {
            el.classList.remove('show');
            el.style.display = 'none';
        });
    });
</script>

{{-- Add Member Modal --}}
<div class="modal" id="addMemberModal">
    <div class="modal-content">
        <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 24px; display: flex; justify-content: space-between; align-items: center;">
            <h5 class="modal-title" style="font-weight: 800; margin: 0;">{{ __('chat.add_member') }}</h5>
            <button type="button" class="btn-icon" onclick="closeAddMemberModal()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 20px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="search-box" style="margin-bottom: 20px;">
                    <input type="text" class="form-control" placeholder="{{ __('chat.search_contacts') }}" oninput="filterUsers(this.value)">
                </div>
                
                <form action="{{ route('groups.members.add', $group) }}" method="POST">
                    @csrf
                    <div class="users-list" style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                        @php
                            $existingMemberIds = $group->members->pluck('user_id')->toArray();
                            
                            $following = auth()->user()->following()->get();
                            $followers = auth()->user()->followersList()->get();
                            
                            $contacts = $following->merge($followers)
                                        ->filter()
                                        ->unique('id')
                                        ->reject(function($u) use ($existingMemberIds) {
                                            return in_array($u->id, $existingMemberIds);
                                        });
                        @endphp
                        
                        @forelse($contacts as $user)
                            <label class="selectable-user" data-name="{{ $user->username }}" style="display: flex; align-items: center; padding: 12px; border-radius: 12px; cursor: pointer; transition: background 0.2s;">
                                <input type="radio" name="user_id" value="{{ $user->id }}" style="margin-right: 16px;" required>
                                <img src="{{ $user->avatar_url }}" alt="" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 12px;">
                                <span style="font-weight: 600;">{{ $user->username }}</span>
                            </label>
                        @empty
                            <p style="text-align: center; color: var(--text-muted);">{{ __('chat.no_contacts_to_add') }}</p>
                        @endforelse
                    </div>
                    
                    @if($contacts->count() > 0)
                        <div style="margin-top: 24px;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; font-weight: 700;">{{ __('chat.add_to_group') }}</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
</div>

<style>
    .selectable-user:hover { background: var(--surface-hover); }
    .selectable-user input:checked + span { color: var(--primary); }
</style>
@endsection
