@extends('layouts.app')

@section('title', __('users.edit_profile'))

@section('content')
<style>
.edit-profile-container { max-width: 680px; margin: 0 auto; }
.edit-header { margin-bottom: 32px; }
.page-header-top { display: flex; align-items: center; gap: 12px; }
.back-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 50%; color: var(--text);
    text-decoration: none; transition: all var(--transition); flex-shrink: 0;
}
.back-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
.edit-header h1 { font-size: 24px; font-weight: 800; color: var(--text); margin: 0; }
.edit-header p { color: var(--text-muted); font-size: 14px; margin: 0; }

.edit-card { 
    background: var(--surface); border: 1px solid var(--border); 
    border-radius: var(--radius-lg); padding: 32px; margin-bottom: 24px;
}
.edit-card h3 { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.edit-card h3 i { color: var(--primary); }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: var(--text); }
.form-group label span { color: var(--text-muted); font-weight: 400; }
.form-input, .form-textarea, .form-select {
    width: 100%; padding: 12px 16px; font-size: 15px;
    border: 1px solid var(--border); border-radius: var(--radius);
    background: var(--bg); color: var(--text); transition: all var(--transition);
}
.form-input:focus, .form-textarea:focus, .form-select:focus {
    outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}
.form-textarea { min-height: 100px; resize: vertical; }
.form-select { cursor: pointer; }
.form-checkbox { 
    display: flex; align-items: center; gap: 12px; padding: 16px;
    background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);
    cursor: pointer; transition: all var(--transition);
}
.form-checkbox:hover { border-color: var(--primary); }
.form-checkbox input { width: 20px; height: 20px; accent-color: var(--primary); }
.form-checkbox-text { flex: 1; }
.form-checkbox-text strong { display: block; color: var(--text); font-size: 14px; }
.form-checkbox-text span { color: var(--text-muted); font-size: 13px; }

.image-upload { display: flex; align-items: center; gap: 20px; }
.image-preview { 
    width: 100px; height: 100px; border-radius: 50%; overflow: hidden;
    background: var(--bg); border: 2px solid var(--border); display: flex; align-items: center; justify-content: center;
}
.image-preview.cover { width: 200px; height: 120px; border-radius: var(--radius); }
.image-preview img { width: 100%; height: 100%; object-fit: cover; }
.image-preview .placeholder { font-size: 32px; color: var(--text-muted); }
.image-upload-actions { flex: 1; }
.upload-btn { 
    display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
    border: 1px solid var(--border); border-radius: var(--radius);
    background: var(--bg); color: var(--text); font-size: 14px; cursor: pointer;
    transition: all var(--transition);
}
.upload-btn:hover { border-color: var(--primary); color: var(--primary); }
.file-input { display: none; }

.form-actions { 
    display: flex; justify-content: space-between; align-items: center; 
    padding-top: 24px; border-top: 1px solid var(--border); margin-top: 32px;
}
.danger-zone { 
    background: rgba(244, 63, 94, 0.05); border: 1px solid rgba(244, 63, 94, 0.2);
    border-radius: var(--radius); padding: 24px; margin-top: 32px;
}
.danger-zone h4 { color: var(--accent); font-size: 16px; font-weight: 700; margin-bottom: 12px; }
.danger-zone p { color: var(--text-muted); font-size: 14px; margin-bottom: 16px; }

@media (max-width: 640px) {
    .form-row { grid-template-columns: 1fr; }
    .edit-card { padding: 20px; }
    .image-upload { flex-direction: column; text-align: center; }
    .form-actions { flex-direction: column; gap: 16px; }
}
</style>

<div class="edit-profile-container">
    <div class="edit-header">
        <div class="page-header-top">
            <a href="{{ route('users.show', $user) }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>{{ __('users.edit_profile') }}</h1>
        </div>
        <p>{{ __('users.update_profile_desc') }}</p>
    </div>

    <form action="{{ route('profile.update', $user) }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Avatar & Cover --}}
        <div class="edit-card">
            <h3><i class="fas fa-images"></i> {{ __('users.profile_images') }}</h3>

            <div class="form-group">
                <label>{{ __('users.profile_picture') }}</label>
                <div class="image-upload">
                    <div class="image-preview">
                        <img src="{{ $user->avatar_url }}" alt="Avatar" id="avatar-preview">
                    </div>
                    <div class="image-upload-actions">
                        <label class="upload-btn">
                            <i class="fas fa-camera"></i> {{ __('users.change_avatar') }}
                            <input type="file" name="avatar" class="file-input" accept="image/*" onchange="previewImage(this, 'avatar-preview')">
                        </label>
                        <button type="button" class="btn btn-ghost" onclick="deleteAvatar()" style="margin-left: 8px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('users.cover_image') }}</label>
                <div class="image-upload">
                    <div class="image-preview cover">
                        @if($user->profile && $user->profile->cover_image)
                            <img src="{{ asset('storage/' . $user->profile->cover_image) }}" alt="Cover" id="cover-preview">
                        @else
                            <div class="placeholder"><i class="fas fa-image"></i></div>
                        @endif
                    </div>
                    <div class="image-upload-actions">
                        <label class="upload-btn">
                            <i class="fas fa-image"></i> {{ __('users.change_cover') }}
                            <input type="file" name="cover_image" class="file-input" accept="image/*" onchange="previewImage(this, 'cover-preview')">
                        </label>
                        @if($user->profile && $user->profile->cover_image)
                            <button type="button" class="btn btn-ghost" onclick="deleteCover()" style="margin-left: 8px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Basic Info --}}
        <div class="edit-card">
            <h3><i class="fas fa-user"></i> {{ __('users.basic_info') }}</h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="username">{{ __('users.username') }}</label>
                    @php
                        $canChangeUsername = auth()->user()->canChangeUsername();
                        $cooldownRemaining = auth()->user()->getUsernameChangeCooldownRemaining();
                    @endphp
                    @if(!$canChangeUsername && !auth()->user()->is_admin)
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-clock" style="color: var(--accent);"></i>
                            <span style="font-size: 13px; color: var(--accent);">
                                <span id="cooldown-timer" data-seconds="{{ $cooldownRemaining['total_seconds'] }}">Loading...</span>
                            </span>
                        </div>
                    @endif
                    <input type="text"
                           name="username"
                           id="username"
                           class="form-input"
                           value="{{ old('username', $user->username) }}"
                           required
                           minlength="3"
                           maxlength="50"
                           pattern="[a-zA-Z0-9_\-]+"
                           title="{{ __('messages.username_validation') }}"
                           {{ !$canChangeUsername && !auth()->user()->is_admin ? 'disabled' : '' }}>
                    @if(!$canChangeUsername && !auth()->user()->is_admin)
                        <input type="hidden" name="username" value="{{ $user->username }}">
                        <span style="font-size: 12px; color: var(--text-muted);">
                            <i class="fas fa-info-circle"></i> {{ __('users.username_cooldown_info') }}
                        </span>
                    @endif
                    @error('username')<span style="color: var(--accent); font-size: 13px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>{{ __('users.full_name') }}</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                    @error('name')<span style="color: var(--accent); font-size: 13px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>{{ __('users.email') }}</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                    @error('email')<span style="color: var(--accent); font-size: 13px;">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('users.bio_label') }} <span>({{ __('users.bio_max') }})</span></label>
                <textarea name="bio" class="form-textarea" maxlength="500">{{ old('bio', $user->profile->bio ?? '') }}</textarea>
            </div>

            <div class="form-group">
                <label>{{ __('users.about_label') }} <span>({{ __('users.about_max') }})</span></label>
                <textarea name="about" class="form-textarea" maxlength="1000">{{ old('about', $user->profile->about ?? '') }}</textarea>
            </div>
        </div>

        {{-- Additional Info --}}
        <div class="edit-card">
            <h3><i class="fas fa-info-circle"></i> {{ __('users.additional_details') }}</h3>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('users.location') }}</label>
                    <input type="text" name="location" class="form-input" value="{{ old('location', $user->profile->location ?? '') }}">
                    <label class="form-checkbox" style="margin-top:10px;">
                        <input type="checkbox" name="show_location" value="1" {{ (old('show_location', $user->profile->show_location ?? true)) ? 'checked' : '' }}>
                        <div class="form-checkbox-text">
                            <strong>{{ __('users.show_location_public') }}</strong>
                            <span>{{ __('users.show_location_public_desc') }}</span>
                        </div>
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ __('users.website') }}</label>
                    <input type="url" name="website" class="form-input" value="{{ old('website', $user->profile->website ?? '') }}" placeholder="https://example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('users.occupation') }}</label>
                    <input type="text" name="occupation" class="form-input" value="{{ old('occupation', $user->profile->occupation ?? '') }}">
                    <label class="form-checkbox" style="margin-top:10px;">
                        <input type="checkbox" name="show_occupation" value="1" {{ (old('show_occupation', $user->profile->show_occupation ?? true)) ? 'checked' : '' }}>
                        <div class="form-checkbox-text">
                            <strong>{{ __('users.show_occupation_public') }}</strong>
                            <span>{{ __('users.show_occupation_public_desc') }}</span>
                        </div>
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ __('users.phone') }}</label>
                    <input type="text" name="phone" class="form-input" value="{{ old('phone', $user->profile->phone ?? '') }}">
                    <label class="form-checkbox" style="margin-top:10px;">
                        <input type="checkbox" name="show_phone" value="1" {{ (old('show_phone', $user->profile->show_phone ?? false)) ? 'checked' : '' }}>
                        <div class="form-checkbox-text">
                            <strong>{{ __('users.show_phone_public') }}</strong>
                            <span>{{ __('users.show_phone_public_desc') }}</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('users.gender') }}</label>
                    <select name="gender" class="form-select">
                        <option value="">{{ __('users.select') }}</option>
                        <option value="male" {{ (old('gender', $user->profile->gender ?? '') == 'male') ? 'selected' : '' }}>{{ __('users.male') }}</option>
                        <option value="female" {{ (old('gender', $user->profile->gender ?? '') == 'female') ? 'selected' : '' }}>{{ __('users.female') }}</option>
                        <option value="other" {{ (old('gender', $user->profile->gender ?? '') == 'other') ? 'selected' : '' }}>{{ __('users.other') }}</option>
                    </select>
                    <label class="form-checkbox" style="margin-top:10px;">
                        <input type="checkbox" name="show_gender" value="1" {{ (old('show_gender', $user->profile->show_gender ?? true)) ? 'checked' : '' }}>
                        <div class="form-checkbox-text">
                            <strong>{{ __('users.show_gender_public') }}</strong>
                            <span>{{ __('users.show_gender_public_desc') }}</span>
                        </div>
                    </label>
                </div>
                <div class="form-group">
                    <label>{{ __('users.birth_date') }}</label>
                    <input type="date" name="birth_date" class="form-input" value="{{ old('birth_date', $user->profile->birth_date ?? '') }}">
                    <label class="form-checkbox" style="margin-top:10px;">
                        <input type="checkbox" name="show_birth_date" value="1" {{ (old('show_birth_date', $user->profile->show_birth_date ?? true)) ? 'checked' : '' }}>
                        <div class="form-checkbox-text">
                            <strong>{{ __('users.show_birth_date_public') }}</strong>
                            <span>{{ __('users.show_birth_date_public_desc') }}</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Security --}}
        <div class="edit-card">
            <h3><i class="fas fa-shield-alt"></i> {{ __('users.security_settings') }}</h3>
            <div class="form-group" style="margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                    {{ __('users.change_password_desc') }}
                </p>
                <a href="{{ route('password.change') }}" class="btn" style="background: var(--surface); border: 1px solid var(--border); color: var(--text); padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; border-radius: var(--radius); font-weight: 600; transition: all var(--transition);">
                    <i class="fas fa-key" style="color: var(--primary);"></i> {{ __('messages.change_password') }}
                </a>
            </div>
            @include('profile.security')
        </div>


        {{-- Privacy --}}
        <div class="edit-card">
            <h3><i class="fas fa-lock"></i> {{ __('users.privacy_settings') }}</h3>

            <label class="form-checkbox">
                <input type="checkbox" name="is_private" value="1" {{ (old('is_private', $user->profile->is_private ?? false)) ? 'checked' : '' }}>
                <div class="form-checkbox-text">
                    <strong>{{ __('users.private_account') }}</strong>
                    <span>{{ __('users.private_account_desc') }}</span>
                </div>
            </label>

            <label class="form-checkbox">
                <input type="checkbox" name="show_online_status" value="1" {{ (old('show_online_status', $user->profile->show_online_status ?? true)) ? 'checked' : '' }}>
                <div class="form-checkbox-text">
                    <strong>{{ __('users.show_online_status') }}</strong>
                    <span>{{ __('users.show_online_status_desc') }}</span>
                </div>
            </label>

            <label class="form-checkbox">
                <input type="checkbox" name="show_read_receipts" value="1" {{ (old('show_read_receipts', $user->profile->show_read_receipts ?? true)) ? 'checked' : '' }}>
                <div class="form-checkbox-text">
                    <strong>{{ __('users.show_read_receipts') }}</strong>
                    <span>{{ __('users.show_read_receipts_desc') }}</span>
                </div>
            </label>
        </div>

        {{-- Two-Factor Authentication --}}
        <div class="edit-card" id="2fa-card">
            <h3><i class="fas fa-shield-alt"></i> {{ __('users.two_factor_auth') }}</h3>

            @if(auth()->user()->two_factor_secret)
                <div id="2fa-enabled-state">
                    <div style="display:flex; align-items:center; gap:.75rem; padding:.875rem 1rem; background:#22c55e15; border:1px solid #22c55e30; border-radius:8px; margin-bottom:1rem;">
                        <i class="fas fa-check-circle" style="color:#22c55e; font-size:1.125rem;" aria-hidden="true"></i>
                        <div>
                            <strong style="display:block;">{{ __('users.two_factor_enabled') }}</strong>
                            <span style="font-size:.8rem; opacity:.65;">{{ __('users.two_factor_enabled_desc') }}</span>
                        </div>
                    </div>
                    <button type="button" class="btn" onclick="show2FADisableModal()" style="background:#ef444420; color:#ef4444; border:1px solid #ef444440;">
                        <i class="fas fa-times" aria-hidden="true"></i> {{ __('users.disable_2fa') }}
                    </button>
                </div>
            @else
                <div id="2fa-disabled-state">
                    <div style="display:flex; align-items:center; gap:.75rem; padding:.875rem 1rem; background:var(--input-bg,#12121f); border:1px solid var(--border-color,#2a2a3e); border-radius:8px; margin-bottom:1rem;">
                        <i class="fas fa-shield-alt" style="opacity:.4; font-size:1.125rem;" aria-hidden="true"></i>
                        <div>
                            <strong style="display:block;">{{ __('users.two_factor_not_enabled') }}</strong>
                            <span style="font-size:.8rem; opacity:.65;">{{ __('users.two_factor_not_enabled_desc') }}</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="setup2FA()">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i> {{ __('users.enable_2fa') }}
                    </button>
                </div>
                <div id="2fa-setup-state" style="display:none;">
                    <p style="font-size:.875rem; margin:0 0 1rem;">{{ __('users.two_factor_scan_desc') }}</p>
                    <div style="text-align:center; margin-bottom:1rem;">
                        <img id="2fa-qr" src="" alt="QR Code" style="width:160px; height:160px; border-radius:8px; background:#fff; padding:8px;">
                    </div>
                    <details style="margin-bottom:1rem;">
                        <summary style="font-size:.8rem; cursor:pointer; opacity:.6;">{{ __('users.two_factor_cant_scan') }}</summary>
                        <code id="2fa-secret" style="font-size:.8rem; display:block; margin-top:.5rem; padding:.5rem; background:var(--input-bg,#12121f); border-radius:4px; word-break:break-all;"></code>
                    </details>
                    <div style="display:flex; flex-wrap:wrap; gap:.75rem; align-items:center;">
                        <input type="text" id="2fa-code-input" inputmode="numeric" placeholder="000000" maxlength="6" aria-label="{{ __('users.two_factor_auth') }}" style="flex:1; min-width:120px; padding:.625rem .875rem; border-radius:8px; border:1px solid var(--border-color,#2a2a3e); background:var(--input-bg,#12121f); color:inherit; font-size:1rem; letter-spacing:.2em; text-align:center;">
                        <button type="button" class="btn btn-primary" onclick="confirm2FA()" id="2fa-confirm-btn">{{ __('users.two_factor_confirm') }}</button>
                        <button type="button" class="btn btn-ghost" onclick="cancel2FASetup()">{{ __('auth.cancel') }}</button>
                    </div>
                    <p id="2fa-error" style="color:#ef4444; font-size:.8rem; margin:.5rem 0 0; display:none;" role="alert"></p>
                </div>
                <div id="2fa-recovery-state" style="display:none;">
                    <div style="background:#f59e0b15; border:1px solid #f59e0b30; border-radius:8px; padding:1rem; margin-bottom:1rem;">
                        <strong style="display:block; margin-bottom:.5rem;"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;" aria-hidden="true"></i> {{ __('users.two_factor_save_codes_title') }}</strong>
                        <p style="font-size:.8rem; opacity:.8; margin:0 0 .75rem;">{{ __('users.two_factor_save_codes_desc') }}</p>
                        <div id="2fa-recovery-codes" style="display:grid; grid-template-columns:1fr 1fr; gap:.375rem; font-family:monospace; font-size:.875rem;"></div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="finish2FASetup()"><i class="fas fa-check" aria-hidden="true"></i> {{ __('users.two_factor_done') }}</button>
                </div>
            @endif
        </div>

        {{-- 2FA Disable Modal --}}
        <div id="2fa-disable-modal" role="dialog" aria-modal="true" aria-labelledby="2fa-disable-title" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:var(--card-bg,#1e1e2e); border:1px solid var(--border-color,#2a2a3e); border-radius:16px; padding:2rem; width:100%; max-width:400px; margin:1rem;">
                <h3 id="2fa-disable-title" style="margin:0 0 .5rem;"><i class="fas fa-shield-alt" aria-hidden="true"></i> {{ __('users.disable_2fa_title') }}</h3>
                <p style="font-size:.875rem; opacity:.65; margin:0 0 1rem;">{{ __('users.disable_2fa_desc') }}</p>
                <input type="password" id="2fa-disable-password" placeholder="{{ __('users.your_password') }}" autocomplete="current-password" style="width:100%; box-sizing:border-box; padding:.625rem .875rem; border-radius:8px; border:1px solid var(--border-color,#2a2a3e); background:var(--input-bg,#12121f); color:inherit; font-size:.9375rem; margin-bottom:.75rem;">
                <p id="2fa-disable-error" style="color:#ef4444; font-size:.8rem; margin:0 0 .75rem; display:none;" role="alert"></p>
                <div style="display:flex; gap:.75rem;">
                    <button type="button" class="btn" onclick="disable2FA()" style="flex:1; background:#ef444420; color:#ef4444; border:1px solid #ef444440;">{{ __('users.disable') }}</button>
                    <button type="button" class="btn btn-ghost" onclick="close2FAModal()" style="flex:1;">{{ __('auth.cancel') }}</button>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="form-actions">
            <a href="{{ route('users.show', $user) }}" class="btn btn-ghost">{{ __('users.cancel') }}</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('users.save_changes') }}</button>
        </div>
    </form>

    {{-- Data Export --}}
    <div class="edit-card" style="margin-top:1.5rem;">
        <h3><i class="fas fa-download" aria-hidden="true"></i> {{ __('users.your_data') }}</h3>
        <p style="font-size:.875rem; opacity:.7; margin:0 0 1rem;">{{ __('users.your_data_desc') }}</p>
        @php $pendingExport = App\Models\DataExportRequest::where('user_id', auth()->id())->whereIn('status', ['pending','processing'])->first(); @endphp
        @if($pendingExport)
            <div role="status" style="padding:.75rem 1rem; background:#f59e0b15; border:1px solid #f59e0b30; border-radius:8px; font-size:.875rem;">
                <i class="fas fa-clock" style="color:#f59e0b;" aria-hidden="true"></i> {{ __('users.export_pending') }}
            </div>
        @else
            <button type="button" id="export-data-btn" onclick="requestDataExport()" class="btn" style="background:var(--input-bg,#12121f); border:1px solid var(--border-color,#2a2a3e);">
                <i class="fas fa-download" aria-hidden="true"></i> {{ __('users.download_my_data') }}
            </button>
        @endif
    </div>

    {{-- Danger Zone --}}
    <div class="danger-zone">
        <h4><i class="fas fa-exclamation-triangle"></i> {{ __('users.danger_zone') }}</h4>
        <p>{{ __('users.danger_zone_desc') }}</p>
        <button type="button" class="btn" style="background: var(--accent); color: white;" onclick="confirmDeleteAccount()">
            <i class="fas fa-trash"></i> {{ __('users.delete_account') }}
        </button>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
            } else {
                // Create new image if placeholder exists
                const container = input.closest('.image-upload').querySelector('.image-preview');
                container.innerHTML = `<img src="${e.target.result}" id="${previewId}">`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function deleteAvatar() {
    if (!confirm({!! json_encode(__('users.delete_avatar_confirm')) !!})) return;

    fetch('{{ route("profile.delete-avatar") }}', {
        method: 'DELETE',
        headers: { 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const preview = document.getElementById('avatar-preview');
            if (preview) {
                preview.src = '{{ auth()->user()->default_avatar }}';
            }
            // Hide delete button
            const deleteBtn = document.querySelector('button[onclick="deleteAvatar()"]');
            if (deleteBtn) deleteBtn.style.display = 'none';
            showToast(data.message || 'Avatar deleted', 'success');
        } else {
            alert(data.message || {!! json_encode(__('users.failed_delete_avatar')) !!});
        }
    });
}

function deleteCover() {
    if (!confirm({!! json_encode(__('users.delete_cover_confirm')) !!})) return;

    fetch('{{ route("profile.delete-cover") }}', {
        method: 'DELETE',
        headers: { 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const preview = document.getElementById('cover-preview');
            if (preview) {
                preview.src = '';
                preview.parentElement.innerHTML = '<div class="placeholder"><i class="fas fa-image"></i></div>';
            }
            // Hide delete button
            const deleteBtn = document.querySelector('button[onclick="deleteCover()"]');
            if (deleteBtn) deleteBtn.style.display = 'none';
            showToast(data.message || 'Cover deleted', 'success');
        } else {
            alert(data.message || {!! json_encode(__('users.failed_delete_cover')) !!});
        }
    });
}

function confirmDeleteAccount() {
    // Layer 1: Warning confirmation
    if (!confirm({!! json_encode(__('users.delete_account_warning')) !!})) return;

    // Layer 2: Final confirmation
    if (!confirm({!! json_encode(__('users.delete_account_final')) !!})) return;

    // Layer 3: Password prompt
    const password = prompt({!! json_encode(__('users.delete_account_password_prompt')) !!});
    if (!password) return;

    fetch('{{ route("profile.delete-account") }}', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ password: password })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '{{ route("home") }}';
        } else {
            alert(data.message || {!! json_encode(__('users.failed_delete_account')) !!});
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert({!! json_encode(__('users.failed_delete_account')) !!});
    });
}

// Username change cooldown countdown timer
function updateCooldownTimer() {
    const timerElement = document.getElementById('cooldown-timer');
    if (!timerElement) return;

    let seconds = parseInt(timerElement.getAttribute('data-seconds'));

    if (seconds <= 0) {
        timerElement.textContent = {!! json_encode(__('users.can_change_now')) !!};
        timerElement.style.color = '#22c55e'; // green
        // Enable the username field
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.disabled = false;
        }
        return;
    }

    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    let timeString = '';
    if (days > 0) {
        timeString = `${days}d ${hours}h ${minutes}m ${secs}s`;
    } else if (hours > 0) {
        timeString = `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
        timeString = `${minutes}m ${secs}s`;
    } else {
        timeString = `${secs}s`;
    }

    const remainingText = {!! json_encode(__('users.time_remaining')) !!}.replace(':time', timeString);
    timerElement.textContent = remainingText;

    // Update the data attribute
    timerElement.setAttribute('data-seconds', seconds - 1);

    // Update every second
    setTimeout(updateCooldownTimer, 1000);
}

// Initialization is handled at the bottom of the script.



// Real-time username availability check
function initUsernameCheck() {
    const usernameInput = document.getElementById('username');
    
    if (!usernameInput) return;
    
    // Create error display element if it doesn't exist
    let usernameError = document.getElementById('username-error');
    if (!usernameError) {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'username-error';
        errorDiv.style.cssText = 'font-size: 13px; margin-top: 6px; display: none; font-weight: 500;';
        usernameInput.parentNode.appendChild(errorDiv);
        usernameError = errorDiv;
    }
    
    // Create status indicator
    let statusSpan = document.getElementById('username-status');
    if (!statusSpan) {
        statusSpan = document.createElement('span');
        statusSpan.id = 'username-status';
        statusSpan.style.cssText = 'font-size: 14px; margin-left: 8px; display: none; vertical-align: middle;';
        usernameInput.parentNode.appendChild(statusSpan);
    }
    
    let debounceTimer;
    
    usernameInput.addEventListener('input', function() {
        const username = this.value.trim();
        const currentUsername = '{{ $user->username }}';
        
        clearTimeout(debounceTimer);
        
        // Hide status and error
        usernameError.style.display = 'none';
        statusSpan.style.display = 'none';
        
        // Don't check if username is the same as current
        if (username === currentUsername) {
            return;
        }
        
        // Don't check if username is too short
        if (username.length < 3) {
            return;
        }
        
        // Debounce the check
        debounceTimer = setTimeout(() => {
            checkUsernameAvailability(username);
        }, 500);
    });
    
    // Check on blur as well
    usernameInput.addEventListener('blur', function() {
        const username = this.value.trim();
        const currentUsername = '{{ $user->username }}';
        
        if (username !== currentUsername && username.length >= 3) {
            checkUsernameAvailability(username);
        }
    });
}

function checkUsernameAvailability(username) {
    const errorEl = document.getElementById('username-error');
    const statusEl = document.getElementById('username-status');
    
    // Show loading state with text
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span style="font-size: 12px; margin-left: 4px;">{{ __('messages.username_checking') }}</span>';
    statusEl.style.color = 'var(--text-muted)';
    statusEl.style.display = 'inline';
    
    fetch('/api/check-username?username=' + encodeURIComponent(username))
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                // Username is available - show success checkmark with text
                statusEl.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> <span style="font-size: 12px; margin-left: 4px; color: var(--success);">{{ __('messages.username_available') }}</span>';
                statusEl.style.display = 'inline';
                errorEl.style.display = 'none';
            } else {
                // Username is taken - show error with text
                errorEl.innerHTML = '<i class="fas fa-times-circle" style="color: var(--accent); margin-right: 4px;"></i> <span>{{ __('messages.username_taken') }}</span>';
                errorEl.style.color = 'var(--accent)';
                errorEl.style.display = 'block';
                
                // Also show X icon in status
                statusEl.innerHTML = '<i class="fas fa-times-circle" style="color: var(--accent);"></i>';
                statusEl.style.display = 'inline';
            }
        })
        .catch(error => {
            console.error('Error checking username:', error);
            statusEl.style.display = 'none';
            errorEl.style.display = 'none';
            // Silently fail - backend validation will catch it
        });
}

// Initialize profile page
    document.addEventListener('DOMContentLoaded', function() {
        updateCooldownTimer();
        initUsernameCheck();
    });

    // ── 2FA ──────────────────────────────────────────────────────────
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const i18n2fa = {
        enable: '{{ addslashes(__("users.enable_2fa")) }}',
        enabled: '{{ addslashes(__("users.two_factor_enabled")) }}',
        enabledDesc: '{{ addslashes(__("users.two_factor_enabled_desc")) }}',
        disable: '{{ addslashes(__("users.disable_2fa")) }}',
        confirm: '{{ addslashes(__("users.two_factor_confirm")) }}',
        enterCode: '{{ addslashes(__("auth.verification_code")) }}',
        invalidCode: '{{ addslashes(__("auth.two_factor_invalid_code")) }}',
        somethingWrong: '{{ addslashes(__("auth.error")) }}',
        toastEnabled: '{{ addslashes(__("auth.success")) }}',
        toastDisabled: '{{ addslashes(__("auth.success")) }}',
        enterPassword: '{{ addslashes(__("users.your_password")) }}',
    };

    async function setup2FA() {
        const btn = document.getElementById('2fa-disabled-state').querySelector('.btn-primary');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
        try {
            const res = await fetch('{{ route("2fa.setup") }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            document.getElementById('2fa-qr').src = data.qr_code_uri;
            document.getElementById('2fa-secret').textContent = data.secret;
            document.getElementById('2fa-disabled-state').style.display = 'none';
            document.getElementById('2fa-setup-state').style.display = 'block';
        } catch(e) {
            showToast(i18n2fa.somethingWrong, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shield-alt" aria-hidden="true"></i> ' + i18n2fa.enable;
    }

    function cancel2FASetup() {
        document.getElementById('2fa-setup-state').style.display = 'none';
        document.getElementById('2fa-disabled-state').style.display = 'block';
        document.getElementById('2fa-code-input').value = '';
        document.getElementById('2fa-error').style.display = 'none';
    }

    async function confirm2FA() {
        const code = document.getElementById('2fa-code-input').value.trim();
        const errEl = document.getElementById('2fa-error');
        const btn = document.getElementById('2fa-confirm-btn');
        if (!code) { errEl.textContent = i18n2fa.enterCode; errEl.style.display = 'block'; return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
        errEl.style.display = 'none';

        try {
            const res = await fetch('{{ route("2fa.confirm") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ code }),
            });
            const data = await res.json();
            if (!res.ok) { errEl.textContent = data.message || i18n2fa.invalidCode; errEl.style.display = 'block'; return; }

            document.getElementById('2fa-setup-state').style.display = 'none';
            const rcEl = document.getElementById('2fa-recovery-codes');
            rcEl.innerHTML = data.recovery_codes.map(c => `<code style="background:var(--input-bg,#12121f);padding:.25rem .5rem;border-radius:4px;">${c}</code>`).join('');
            document.getElementById('2fa-recovery-state').style.display = 'block';
        } catch(e) {
            errEl.textContent = i18n2fa.somethingWrong;
            errEl.style.display = 'block';
        }
        btn.disabled = false;
        btn.innerHTML = i18n2fa.confirm;
    }

    function finish2FASetup() {
        document.getElementById('2fa-recovery-state').style.display = 'none';
        document.getElementById('2fa-disabled-state').innerHTML = `
            <div style="display:flex;align-items:center;gap:.75rem;padding:.875rem 1rem;background:#22c55e15;border:1px solid #22c55e30;border-radius:8px;margin-bottom:1rem;">
                <i class="fas fa-check-circle" style="color:#22c55e;font-size:1.125rem;" aria-hidden="true"></i>
                <div><strong style="display:block;">${i18n2fa.enabled}</strong><span style="font-size:.8rem;opacity:.65;">${i18n2fa.enabledDesc}</span></div>
            </div>
            <button type="button" class="btn" onclick="show2FADisableModal()" style="background:#ef444420;color:#ef4444;border:1px solid #ef444440;"><i class="fas fa-times" aria-hidden="true"></i> ${i18n2fa.disable}</button>`;
        document.getElementById('2fa-disabled-state').style.display = 'block';
        showToast(i18n2fa.toastEnabled, 'success');
    }

    function show2FADisableModal() {
        const modal = document.getElementById('2fa-disable-modal');
        modal.style.display = 'flex';
        document.getElementById('2fa-disable-password').focus();
    }

    function close2FAModal() {
        document.getElementById('2fa-disable-modal').style.display = 'none';
        document.getElementById('2fa-disable-password').value = '';
        document.getElementById('2fa-disable-error').style.display = 'none';
    }

    async function disable2FA() {
        const password = document.getElementById('2fa-disable-password').value;
        const errEl = document.getElementById('2fa-disable-error');
        if (!password) { errEl.textContent = i18n2fa.enterPassword; errEl.style.display = 'block'; return; }

        try {
            const res = await fetch('{{ route("2fa.disable") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ password }),
            });
            const data = await res.json();
            if (!res.ok) { errEl.textContent = data.message || i18n2fa.invalidCode; errEl.style.display = 'block'; return; }
            close2FAModal();
            showToast(i18n2fa.toastDisabled, 'success');
            setTimeout(() => location.reload(), 1000);
        } catch(e) {
            errEl.textContent = i18n2fa.somethingWrong;
            errEl.style.display = 'block';
        }
    }

    // Close disable modal on overlay click
    document.getElementById('2fa-disable-modal')?.addEventListener('click', function(e) {
        if (e.target === this) close2FAModal();
    });

    // Data export
    const exportLabel = '{{ addslashes(__("users.download_my_data")) }}';
    const somethingWrong = '{{ addslashes(__("auth.error")) }}';

    async function requestDataExport() {
        const btn = document.getElementById('export-data-btn');
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
        try {
            const res = await fetch('{{ route("export.request") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok) {
                btn.parentElement.innerHTML = `<div role="status" style="padding:.75rem 1rem;background:#22c55e15;border:1px solid #22c55e30;border-radius:8px;font-size:.875rem;"><i class="fas fa-check-circle" style="color:#22c55e;" aria-hidden="true"></i> ${data.message}</div>`;
            } else {
                showToast(data.message || somethingWrong, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download" aria-hidden="true"></i> ' + exportLabel;
            }
        } catch(e) {
            showToast(somethingWrong, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download" aria-hidden="true"></i> ' + exportLabel;
        }
    }

    // Real-time privacy toggles — fire instantly on checkbox change, no form submit needed
    (function () {
        const TOGGLES = ['show_online_status', 'show_read_receipts'];
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        TOGGLES.forEach(name => {
            const checkbox = document.querySelector(`input[name="${name}"]`);
            if (!checkbox) return;

            checkbox.addEventListener('change', async function () {
                const value = this.checked ? 1 : 0;
                const label = this.closest('.form-checkbox');
                if (label) label.style.opacity = '0.6';
                const originalChecked = this.checked;

                try {
                    const res = await fetch('{{ route("settings.privacy-toggle") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ setting: name, value: value }),
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        if (typeof window.showToast === 'function') {
                            window.showToast('{{ __("messages.settings_saved") }}', 'success');
                        }
                    } else {
                        this.checked = !originalChecked;
                        const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to save');
                        if (typeof window.showToast === 'function') window.showToast(msg, 'error');
                    }
                } catch (e) {
                    this.checked = !originalChecked;
                    if (typeof window.showToast === 'function') window.showToast('Failed to save', 'error');
                } finally {
                    if (label) label.style.opacity = '';
                }
            });
        });
    })();

</script>
@endsection
