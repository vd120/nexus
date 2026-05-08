@extends('layouts.app')

@section('content')
<div class="community-page-container">
    @include('communities.partials.header')

    <div class="community-body-wrap">
        <div class="settings-container">
            <div class="settings-page-header">
                <h2 class="settings-title">{{ __('messages.my_preferences') }}</h2>
                <p class="settings-subtitle">{{ __('messages.manage_interaction_with', ['name' => $group->name]) }}</p>
            </div>

            <form id="preferences-form" class="settings-form">
                @csrf
                @method('PATCH')

                {{-- Notification Preferences --}}
                <div class="settings-card">
                    <div class="card-header">
                        <div class="header-icon"><i class="fas fa-bell"></i></div>
                        <div class="header-text">
                            <h3>{{ __('messages.notifications') }}</h3>
                            <p>{{ __('messages.choose_updates_desc') }}</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ __('messages.notification_level') }}</label>
                            <div class="custom-select-wrap">
                                <select name="notification_preference" class="form-input">
                                    <option value="all" {{ $member->notification_preference === 'all' ? 'selected' : '' }}>{{ __('messages.all_activity_posts_comments') }}</option>
                                    <option value="highlights" {{ $member->notification_preference === 'highlights' ? 'selected' : '' }}>{{ __('messages.highlights_only') }}</option>
                                    <option value="none" {{ $member->notification_preference === 'none' ? 'selected' : '' }}>{{ __('messages.muted_no_notifications') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Identity & Privacy --}}
                <div class="settings-card">
                    <div class="card-header">
                        <div class="header-icon"><i class="fas fa-user-secret"></i></div>
                        <div class="header-text">
                            <h3>{{ __('messages.identity_privacy') }}</h3>
                            <p>{{ __('messages.identity_privacy_desc') }}</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="toggles-list">
                            <div class="toggle-item">
                                <div class="toggle-info">
                                    <strong>{{ __('messages.global_anonymity') }}</strong>
                                    <p>{{ __('messages.global_anonymity_desc') }}</p>
                                </div>
                                <label class="nexus-switch">
                                    <input type="checkbox" name="is_anonymous_default" {{ $member->is_anonymous_default ? 'checked' : '' }}>
                                    <span class="switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="button" onclick="savePreferences()" class="action-btn save-btn">
                        <i class="fas fa-save"></i>
                        <span>{{ __('messages.save_preferences') }}</span>
                    </button>
                </div>
            </form>

            {{-- Danger Zone --}}
            <div class="danger-section">
                <div class="danger-header">
                    <h3>{{ __('messages.danger_zone') }}</h3>
                    <p>{{ __('messages.danger_zone_actions_desc') }}</p>
                </div>
                <div class="danger-card">
                    <div class="danger-info">
                        <strong>{{ __('messages.leave_community') }}</strong>
                        <p>{{ __('messages.leave_community_desc') }}</p>
                    </div>
                    <button type="button" onclick="confirmLeaveGroup()" class="action-btn leave-btn">
                        {{ __('messages.leave_group') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-container { max-width: 800px; margin: 0 auto; padding-bottom: 80px; }
    
    .settings-page-header { text-align: center; margin-bottom: 40px; padding-top: 20px; }
    .settings-title { font-size: 32px; font-weight: 900; color: var(--text); margin: 0 0 8px; letter-spacing: -1px; }
    .settings-subtitle { font-size: 15px; color: var(--text-muted); margin: 0; font-weight: 500; }

    .settings-card { background: var(--surface); border: 1px solid var(--border); border-radius: 28px; margin-bottom: 24px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: 0.3s; }
    .settings-card:hover { border-color: var(--community-accent); transform: translateY(-2px); }
    
    .card-header { padding: 28px 32px; background: linear-gradient(to right, var(--surface-hover), transparent); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 24px; }
    .header-icon { width: 52px; height: 52px; border-radius: 16px; background: var(--community-accent); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 8px 16px var(--community-accent-soft); }
    .header-text h3 { font-size: 19px; font-weight: 800; color: var(--text); margin: 0 0 4px; }
    .header-text p { font-size: 13.5px; color: var(--text-muted); margin: 0; line-height: 1.5; }

    .card-body { padding: 32px; }

    .form-group { margin-bottom: 24px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 10px; padding-left: 4px; }
    
    .form-input { width: 100%; background: var(--surface-hover); border: 1px solid var(--border); padding: 14px 18px; border-radius: 16px; color: var(--text); font-size: 15px; font-weight: 600; outline: none; transition: 0.2s; }
    .form-input:focus { border-color: var(--community-accent); background: var(--surface); box-shadow: 0 0 0 4px var(--community-accent-soft); }
    .form-input:disabled { opacity: 0.6; cursor: not-allowed; }

    .input-with-icon { position: relative; }
    .input-with-icon i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
    .input-with-icon .form-input { padding-left: 48px; }

    .input-help { font-size: 12px; color: var(--text-muted); margin: 8px 0 0 4px; }

    /* Toggles */
    .toggles-list { display: flex; flex-direction: column; gap: 16px; }
    .toggle-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: var(--surface-hover); border-radius: 16px; border: 1px solid transparent; transition: 0.2s; }
    .toggle-item:hover { background: var(--surface); border-color: var(--border); }
    .toggle-info strong { display: block; font-size: 15px; color: var(--text); margin-bottom: 2px; }
    .toggle-info p { font-size: 13px; color: var(--text-muted); margin: 0; }

    /* Nexus Switch */
    .nexus-switch { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
    .nexus-switch input { opacity: 0; width: 0; height: 0; }
    .switch-slider { position: absolute; cursor: pointer; inset: 0; background-color: var(--border); transition: .4s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 26px; }
    .switch-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 50%; }
    input:checked + .switch-slider { background-color: var(--community-accent); }
    input:checked + .switch-slider:before { transform: translateX(22px); }

    .settings-actions { display: flex; justify-content: center; margin-top: 32px; }
    .action-btn { height: 52px; padding: 0 32px; border-radius: 16px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 15px; font-weight: 800; cursor: pointer; transition: 0.3s; border: none; }
    
    .save-btn { background: var(--community-accent); color: white; }
    .save-btn:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3); }

    .danger-section { margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--border); }
    .danger-header { margin-bottom: 20px; }
    .danger-header h3 { font-size: 18px; font-weight: 800; color: #ef4444; margin: 0 0 4px; }
    .danger-header p { font-size: 14px; color: var(--text-muted); margin: 0; }

    .danger-card { display: flex; justify-content: space-between; align-items: center; padding: 24px 32px; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); border-radius: 24px; gap: 20px; }
    .danger-info strong { display: block; font-size: 16px; color: var(--text); margin-bottom: 4px; }
    .danger-info p { font-size: 13px; color: var(--text-muted); margin: 0; }
    
    .leave-btn { background: white; color: #ef4444; border: 1px solid #ef4444; }
    .leave-btn:hover { background: #ef4444; color: white; }

    .mt-24 { margin-top: 24px; }

    @media (max-width: 600px) {
        .settings-container { padding: 0 4px 60px; }
        .settings-page-header { text-align: center; margin-bottom: 24px; padding: 0 16px; }
        .settings-title { font-size: 20px; }
        .settings-subtitle { font-size: 13px; }

        .settings-card { border-radius: 16px; margin-bottom: 16px; }
        .card-header { padding: 16px 20px; gap: 14px; }
        .header-icon { width: 40px; height: 40px; font-size: 16px; border-radius: 10px; }
        .header-text h3 { font-size: 16px; }
        .header-text p { font-size: 12px; }
        .card-body { padding: 20px; }

        .toggle-item { padding: 16px; border-radius: 12px; }
        .toggle-info strong { font-size: 14px; }
        .toggle-info p { font-size: 12px; }

        .save-btn { width: 100%; border-radius: 14px; }

        .danger-section { margin-top: 32px; padding-top: 24px; }
        .danger-card { flex-direction: column; text-align: center; padding: 20px; border-radius: 16px; }
        .leave-btn { width: 100%; height: 46px; }
    }
</style>

<script>
    function savePreferences() {
        const form = document.getElementById('preferences-form');
        const formData = new FormData(form);
        const slug = '{{ $group->slug }}';
        
        const payload = {
            notification_preference: formData.get('notification_preference'),
            is_anonymous_default: form.querySelector('[name="is_anonymous_default"]').checked ? 1 : 0
        };

        const btn = document.querySelector('.save-btn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

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
            showToast('{{ __("messages.preferences_updated_success") }}', 'success');
        })
        .catch(err => {
            console.error(err);
            showToast('{{ __("messages.failed_save_preferences") }}', 'error');
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }

    function confirmLeaveGroup() {
        if (confirm('{{ __("messages.leave_community_confirm") }}')) {
            const slug = '{{ $group->slug }}';
            
            fetch(`/communities/${slug}/leave`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(() => {
                window.location.href = '{{ route("communities.index") }}';
            });
        }
    }
</script>
@endsection
