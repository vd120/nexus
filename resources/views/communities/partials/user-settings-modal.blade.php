<div id="user-settings-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content settings-modal-box">
        <div class="modal-header">
            <div class="header-text">
                <h3>Settings</h3>
                <p>Community Preferences</p>
            </div>
            <button class="close-modal-btn" onclick="closeUserSettingsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <form id="group-preferences-form" class="settings-form">
                @php $member = auth()->check() ? $group->members()->where('user_id', auth()->id())->first() : null; @endphp
                
                <div class="settings-section">
                    <label class="section-label">General</label>
                    
                    <div class="setting-item">
                        <div class="item-info">
                            <span class="item-title">Notifications</span>
                        </div>
                        <div class="item-action">
                            <select name="notification_preference" class="select">
                                <option value="all" {{ ($member && $member->notification_preference === 'all') ? 'selected' : '' }}>All</option>
                                <option value="highlights" {{ ($member && $member->notification_preference === 'highlights') ? 'selected' : '' }}>Highlights</option>
                                <option value="none" {{ ($member && $member->notification_preference === 'none') ? 'selected' : '' }}>Off</option>
                            </select>
                        </div>
                    </div>

                    <div class="setting-item">
                        <div class="item-info">
                            <span class="item-title">Auto-Anonymous</span>
                        </div>
                        <div class="item-action">
                            <label class="nexus-switch">
                                <input type="checkbox" name="is_anonymous_default" {{ ($member && $member->is_anonymous_default) ? 'checked' : '' }}>
                                <span class="switch-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="setting-item column-style">
                        <div class="item-info">
                            <span class="item-title">Anonymous Alias</span>
                            <span class="item-desc" style="font-size: 11px;">Assigned by community (Read-only)</span>
                        </div>
                        <div class="item-action" style="width: 100%;">
                            <input type="text" name="anonymous_username" class="input" 
                                   value="{{ $member->anonymous_username ?? 'Anonymous' }}" 
                                   readonly style="background: var(--bg); opacity: 0.7; cursor: not-allowed;">
                        </div>
                    </div>
                </div>

                <div class="settings-footer">
                    <button type="button" onclick="updateGroupPreferences()" class="btn-save">
                        Save Changes
                    </button>
                </div>
            </form>

            <div class="danger-zone">
                <button onclick="leaveGroup()" class="btn-leave">
                    Leave Community
                </button>
            </div>
            
            {{-- Mobile Close Button --}}
            <button class="mobile-close-btn" onclick="closeUserSettingsModal()">Close</button>
        </div>
    </div>
</div>

<style>
    .settings-modal-box {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        width: 100%;
        max-width: 400px;
        overflow: hidden;
    }

    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-text h3 { margin: 0; font-size: 16px; font-weight: 700; }
    .header-text p { margin: 0; font-size: 12px; color: var(--text-muted); }

    .close-modal-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 18px;
    }

    .modal-body { padding: 20px; }

    .settings-section { margin-bottom: 20px; }
    .section-label { 
        display: block; 
        font-size: 10px; 
        font-weight: 800; 
        text-transform: uppercase; 
        color: var(--text-muted); 
        margin-bottom: 12px;
    }

    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
    }

    .setting-item:not(:last-child) { border-bottom: 1px solid var(--border); }
    .setting-item.column-style { flex-direction: column; align-items: flex-start; gap: 8px; border-bottom: none; }

    .item-title { font-weight: 600; font-size: 14px; color: var(--text); }
    .item-desc { font-size: 12px; color: var(--text-muted); }

    .select {
        background: var(--surface-hover);
        border: 1px solid var(--border);
        color: var(--text);
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
    }

    .input {
        width: 100%;
        background: var(--surface-hover);
        border: 1px solid var(--border);
        color: var(--text);
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 13px;
    }

    .nexus-switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }

    .nexus-switch input { opacity: 0; width: 0; height: 0; }

    .switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: var(--border);
        transition: .2s;
        border-radius: 20px;
    }

    .switch-slider:before {
        position: absolute;
        content: "";
        height: 14px; width: 14px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .2s;
        border-radius: 50%;
    }

    input:checked + .switch-slider { background-color: var(--community-primary); }
    input:checked + .switch-slider:before { transform: translateX(20px); }

    .btn-save {
        width: 100%;
        height: 44px;
        background: var(--community-primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
    }

    .danger-zone { margin-top: 16px; }

    .btn-leave {
        width: 100%;
        padding: 10px;
        background: rgba(244, 63, 94, 0.05);
        border: 1px solid rgba(244, 63, 94, 0.1);
        color: #f43f5e;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
    }
    
    .mobile-close-btn {
        display: none;
        width: 100%;
        margin-top: 12px;
        padding: 12px;
        background: var(--surface-hover);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-weight: 600;
        color: var(--text);
        cursor: pointer;
    }

    @media (max-width: 600px) {
        .settings-modal-box { border-radius: 16px 16px 0 0; align-self: flex-end; }
        .mobile-close-btn { display: block; }
        .close-modal-btn { display: none; }
    }
</style>

@push('scripts')
<script>
function openUserSettingsModal() {
    const modal = document.getElementById('user-settings-modal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeUserSettingsModal() {
    const modal = document.getElementById('user-settings-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function updateGroupPreferences() {
    const form = document.getElementById('group-preferences-form');
    if (!form) return;
    
    const formData = new FormData(form);
    const slug = '{{ $group->slug }}';
    
    const payload = {
        notification_preference: formData.get('notification_preference'),
        is_anonymous_default: form.querySelector('[name="is_anonymous_default"]').checked ? 1 : 0
    };

    fetch(`/communities/${slug}/preferences`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        showToast('Changes saved successfully!', 'success');
        closeUserSettingsModal();
    })
    .catch(err => {
        console.error(err);
        showToast('Failed to save changes', 'error');
    });
}

function leaveGroup() {
    if (!confirm('Are you sure you want to leave this community?')) return;
    
    const slug = '{{ $group->slug }}';

    fetch(`/communities/${slug}/leave`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(() => {
        window.location.href = '/communities';
    });
}
</script>
@endpush
