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

    <form action="{{ route('communities.admin.settings.update', $group->slug) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

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

<style>
    .settings-page { max-width: 900px; margin: 0 auto; padding-bottom: 60px; }
    
    .settings-card { border-radius: 32px; border: 1px solid var(--border); background: var(--surface); margin-bottom: 32px; overflow: hidden; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .settings-card:hover { border-color: var(--admin-accent-glow); }
    
    .settings-card-header { padding: 32px; background: var(--surface-hover); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 20px; }
    .header-icon { width: 56px; height: 56px; border-radius: 18px; background: var(--admin-accent-glow); color: var(--admin-accent); display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
    .header-text h3 { font-size: 20px; font-weight: 800; color: var(--text); margin: 0 0 4px; }
    .header-text p { font-size: 14px; color: var(--text-muted); margin: 0; line-height: 1.4; }

    .panel-body { padding: 32px; }

    /* Branding Grid */
    .branding-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
    .branding-label { display: block; font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
    
    .avatar-edit-wrap { display: flex; flex-direction: column; align-items: center; gap: 20px; }
    .avatar-preview-container { width: 140px; height: 140px; border-radius: 40px; position: relative; overflow: hidden; border: 4px solid var(--surface-hover); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    .avatar-preview-container img { width: 100%; height: 100%; object-fit: cover; }
    
    .cover-edit-wrap { display: flex; flex-direction: column; gap: 20px; }
    .cover-preview-container { width: 100%; height: 140px; border-radius: 24px; position: relative; overflow: hidden; border: 4px solid var(--surface-hover); }
    .cover-preview-container img { width: 100%; height: 100%; object-fit: cover; }
    
    .preview-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; opacity: 0; transition: 0.3s; cursor: pointer; backdrop-filter: blur(4px); }
    .avatar-preview-container:hover .preview-overlay, .cover-preview-container:hover .preview-overlay { opacity: 1; }

    /* Forms */
    .form-group { margin-bottom: 24px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: var(--text-muted); margin-bottom: 10px; padding-left: 4px; }
    .form-input { width: 100%; background: var(--surface-hover); border: 1px solid var(--border); padding: 14px 18px; border-radius: 16px; color: var(--text); font-size: 15px; font-weight: 600; transition: 0.2s; outline: none; }
    .form-input:focus { border-color: var(--admin-accent); background: var(--surface); box-shadow: 0 0 0 4px var(--admin-accent-glow); }
    
    .grid-2-settings { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }

    /* Toggles */
    .toggles-list { display: flex; flex-direction: column; gap: 16px; border-top: 1px solid var(--border); padding-top: 32px; }
    .toggle-card { display: flex; justify-content: space-between; align-items: center; padding: 24px; background: var(--surface-hover); border-radius: 20px; transition: 0.2s; border: 1px solid transparent; }
    .toggle-card:hover { border-color: var(--border); background: var(--surface); }
    .toggle-info strong { display: block; font-size: 16px; color: var(--text); margin-bottom: 4px; }
    .toggle-info p { font-size: 13px; color: var(--text-muted); margin: 0; line-height: 1.4; }

    /* Switch Style */
    .nexus-switch { position: relative; display: inline-block; width: 52px; height: 28px; flex-shrink: 0; }
    .nexus-switch input { opacity: 0; width: 0; height: 0; }
    .switch-slider { position: absolute; cursor: pointer; inset: 0; background-color: var(--border); transition: .4s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 28px; }
    .switch-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    input:checked + .switch-slider { background-color: var(--admin-accent); }
    input:checked + .switch-slider:before { transform: translateX(24px); }

    /* Buttons */
    .settings-actions-footer { display: flex; justify-content: center; padding-top: 20px; }
    .mod-btn { height: 56px; padding: 0 40px; border-radius: 18px; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.3s; border: none; }
    .mod-btn-outline { background: var(--surface-hover); color: var(--text); border: 1px solid var(--border); padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .mod-btn-outline:hover { background: var(--border); transform: translateY(-2px); }
    .approve-btn { background: var(--admin-accent); color: white; }
    .approve-btn:hover { background: #4f46e5; transform: scale(1.02); box-shadow: 0 10px 20px rgba(94, 96, 206, 0.3); }
    .danger-btn { color: #ef4444 !important; border-color: rgba(239, 68, 68, 0.3) !important; }
    .danger-btn:hover { background: #ef4444 !important; color: white !important; border-color: #ef4444 !important; }

    @media (max-width: 850px) {
        .branding-grid { grid-template-columns: 1fr; gap: 32px; }
        .grid-2-settings { grid-template-columns: 1fr; }
        .settings-card-header { padding: 24px; }
        .panel-body { padding: 24px; }
    }
</style>

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
