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
