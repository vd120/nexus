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
                </div>
                <div class="form-group">
                    <label>{{ __('users.phone') }}</label>
                    <input type="text" name="phone" class="form-input" value="{{ old('phone', $user->profile->phone ?? '') }}">
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
                </div>
                <div class="form-group">
                    <label>{{ __('users.birth_date') }}</label>
                    <input type="date" name="birth_date" class="form-input" value="{{ old('birth_date', $user->profile->birth_date ?? '') }}">
                </div>
            </div>
        </div>

        {{-- Security --}}
        <div class="edit-card">
            <h3><i class="fas fa-shield-alt"></i> {{ __('users.security_settings') }}</h3>
            <div class="form-group">
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                    {{ __('users.change_password_desc') }}
                </p>
                <a href="{{ route('password.change') }}" class="btn" style="background: var(--surface); border: 1px solid var(--border); color: var(--text); padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; border-radius: var(--radius); font-weight: 600; transition: all var(--transition);">
                    <i class="fas fa-key" style="color: var(--primary);"></i> {{ __('messages.change_password') }}
                </a>
            </div>
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
        </div>

        {{-- Actions --}}
        <div class="form-actions">
            <a href="{{ route('users.show', $user) }}" class="btn btn-ghost">{{ __('users.cancel') }}</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('users.save_changes') }}</button>
        </div>
    </form>

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

// Start the timer when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateCooldownTimer();
    initUsernameCheck();
});

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
</script>
@endsection
