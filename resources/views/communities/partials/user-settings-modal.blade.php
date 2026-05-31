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
