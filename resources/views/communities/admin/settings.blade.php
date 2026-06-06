@extends('layouts.app')

@section('content')
<div class="admin-wrapper">
    @include('communities.admin.partials.sidebar')
    <main class="admin-main">
        <div class="admin-content-inner">

<div class="settings-page">
    <div class="admin-page-header">
        <h1 class="admin-page-title">{{ __('community_admin.community_settings') }}</h1>
        <p class="admin-page-subtitle">{{ __('community_admin.community_settings_subtitle') }}</p>
    </div>

    <form action="{{ route('communities.admin.settings.update', $group->slug) }}" method="POST" enctype="multipart/form-data" onsubmit="this.querySelector('[type=submit]').disabled=true;this.querySelector('[type=submit]').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Saving...';">
        @csrf
        @method('PATCH')

        @if($errors->any())
        <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:12px;padding:14px 18px;margin-bottom:20px;">
            <div style="font-weight:700;color:#ef4444;margin-bottom:6px;"><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</div>
            <ul style="margin:0;padding-left:20px;color:#ef4444;font-size:13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Branding Section --}}
        <div class="panel settings-card">
            <div class="settings-card-header">
                <div class="header-icon"><i class="fas fa-palette"></i></div>
                <div class="header-text">
                    <h3>{{ __('community_admin.visual_branding') }}</h3>
                    <p>{{ __('community_admin.visual_branding_desc') }}</p>
                </div>
            </div>
            <div class="panel-body branding-grid">
                <div class="branding-upload-box avatar-box">
                    <label class="branding-label">{{ __('community_admin.community_avatar') }}</label>
                    <div class="avatar-edit-wrap">
                        <div class="avatar-preview-container">
                            <img src="{{ $group->avatar_url }}" id="avatar-preview" alt="Community Avatar">
                            <div class="preview-overlay" onclick="document.getElementById('avatar-input').click()">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <input type="file" name="avatar" id="avatar-input" onchange="previewImage(this, 'avatar-preview')" hidden accept="image/*">
                        <button type="button" onclick="document.getElementById('avatar-input').click()" class="mod-btn-outline">
                            <span>{{ __('community_admin.update_avatar') }}</span>
                        </button>
                    </div>
                </div>

                <div class="branding-upload-box cover-box">
                    <label class="branding-label">{{ __('community_admin.cover_photo') }}</label>
                    <div class="cover-edit-wrap">
                        <div class="cover-preview-container">
                            <img src="{{ $group->cover_photo ? asset('storage/' . $group->cover_photo) : 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2070' }}" id="cover-preview" alt="Cover Photo">
                            <div class="preview-overlay" onclick="document.getElementById('cover-input').click()">
                                <i class="fas fa-image"></i>
                            </div>
                        </div>
                        <input type="file" name="cover_photo" id="cover-input" onchange="previewImage(this, 'cover-preview')" hidden accept="image/*">
                        <button type="button" onclick="document.getElementById('cover-input').click()" class="mod-btn-outline">
                            <span>{{ __('community_admin.update_cover') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Basic Information --}}
        <div class="panel settings-card">
            <div class="settings-card-header">
                <div class="header-icon"><i class="fas fa-info-circle"></i></div>
                <div class="header-text">
                    <h3>{{ __('community_admin.essential_identity') }}</h3>
                    <p>{{ __('community_admin.essential_identity_desc') }}</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>{{ __('community_admin.community_name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $group->name) }}" class="form-input" required placeholder="{{ __('community_admin.enter_community_name') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('community_admin.description') }}</label>
                    <textarea name="description" rows="3" class="form-input" placeholder="{{ __('community_admin.description_placeholder') }}">{{ old('description', $group->description) }}</textarea>
                </div>
                <div class="form-group">
                    <label>{{ __('community_admin.welcome_message') }}</label>
                    <textarea name="welcome_message" rows="2" class="form-input" placeholder="{{ __('community_admin.welcome_message_placeholder') }}">{{ old('welcome_message', $group->welcome_message) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Privacy & Permissions --}}
        <div class="panel settings-card">
            <div class="settings-card-header">
                <div class="header-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="header-text">
                    <h3>{{ __('community_admin.privacy_access') }}</h3>
                    <p>{{ __('community_admin.privacy_access_desc') }}</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="grid-2-settings">
                    <div class="form-group">
                        <label>{{ __('community_admin.join_privacy') }}</label>
                        <div class="select-wrap">
                            <select name="privacy_level" class="form-input">
                                <option value="public" {{ $group->privacy_level === 'public' ? 'selected' : '' }}>{{ __('community_admin.public_auto_join') }}</option>
                                <option value="private" {{ $group->privacy_level === 'private' ? 'selected' : '' }}>{{ __('community_admin.private_approval_required') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('community_admin.posting_permissions') }}</label>
                        <div class="select-wrap">
                            <select name="posting_permission" class="form-input">
                                <option value="anyone" {{ $group->posting_permission === 'anyone' ? 'selected' : '' }}>{{ __('community_admin.everyone_can_post') }}</option>
                                <option value="admins_only" {{ $group->posting_permission === 'admins_only' ? 'selected' : '' }}>{{ __('community_admin.admins_mods_only') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="toggles-list">
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <strong>{{ __('community_admin.global_discovery') }}</strong>
                            <p>{{ __('community_admin.global_discovery_desc') }}</p>
                        </div>
                        <label class="nexus-switch">
                            <input type="hidden" name="is_discoverable" value="0">
                            <input type="checkbox" name="is_discoverable" value="1" {{ $group->is_discoverable ? 'checked' : '' }}>
                            <span class="switch-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-card">
                        <div class="toggle-info">
                            <strong>{{ __('community_admin.anonymous_contributions') }}</strong>
                            <p>{{ __('community_admin.anonymous_contributions_desc') }}</p>
                        </div>
                        <label class="nexus-switch">
                            <input type="hidden" name="allow_anonymous_posts" value="0">
                            <input type="checkbox" name="allow_anonymous_posts" value="1" {{ $group->allow_anonymous_posts ? 'checked' : '' }}>
                            <span class="switch-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-card">
                        <div class="toggle-info">
                            <strong>{{ __('community_admin.strict_moderation') }}</strong>
                            <p>{{ __('community_admin.strict_moderation_desc') }}</p>
                        </div>
                        <label class="nexus-switch">
                            <input type="hidden" name="require_post_approval" value="0">
                            <input type="checkbox" name="require_post_approval" value="1" {{ $group->require_post_approval ? 'checked' : '' }}>
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-actions-footer">
            <button type="submit" class="mod-btn approve-btn large-btn">
                <i class="fas fa-save"></i>
                <span>{{ __('community_admin.publish_settings') }}</span>
            </button>
        </div>
    </form>

    {{-- Danger Zone --}}
    <div class="panel settings-card danger-zone-card" style="margin-top: 60px; border-color: rgba(239, 68, 68, 0.2);">
        <div class="settings-card-header" style="background: rgba(239, 68, 68, 0.03);">
            <div class="header-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="header-text">
                <h3 style="color: #ef4444;">{{ __('community_admin.danger_zone') }}</h3>
                <p>{{ __('community_admin.danger_zone_desc') }}</p>
            </div>
        </div>
        <div class="panel-body">
            <div class="toggle-card delete-community-card" style="background: rgba(239, 68, 68, 0.02); border: 1px solid rgba(239, 68, 68, 0.1);">
                <div class="toggle-info">
                    <strong>{{ __('community_admin.delete_community') }}</strong>
                    <p>{{ __('community_admin.delete_community_desc') }}</p>
                </div>
                <button type="button" class="mod-btn-outline danger-btn" onclick="confirmDeleteCommunity()">
                    <i class="fas fa-trash-alt"></i>
                    <span>{{ __('community_admin.delete_community') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

<form id="delete-community-form" action="{{ route('communities.admin.delete', $group->slug) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function previewImage(input, targetId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(targetId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function confirmDeleteCommunity() {
        if (confirm('{{ __('community_admin.delete_confirm') }}')) {
            document.getElementById('delete-community-form').submit();
        }
    }
</script>
        </div>
    </main>
</div>
@endsection
