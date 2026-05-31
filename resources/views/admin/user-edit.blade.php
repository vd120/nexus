@extends('layouts.app')

@section('title', __('admin.edit_user_title') . ' - Admin Panel')

@push('styles')
@vite('resources/css/admin-user-edit.css')
@endpush

@section('content')
<div class="admin-page">
    {{-- Header --}}
    <div class="admin-header">
        <div class="header-left">
            <a href="{{ route('admin.users.show', $user) }}" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>{{ __('admin.edit_user_title') }}</h1>
                <p>{{ __('admin.edit_user_subtitle') }}</p>
            </div>
        </div>
    </div>

    <div class="edit-form">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Basic Info Section --}}
            <div class="form-card">
                <h2><i class="fas fa-user"></i> {{ __('admin.basic_info') }}</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>{{ __('admin.full_name_label') }} *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required minlength="1" maxlength="255" autocomplete="name">
                        @error('name')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.username_label') }} *</label>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_\-]+" autocomplete="username">
                        @error('username')
                            <span class="error">{{ $message }}</span>
                        @enderror
                        <small class="help-text">{{ __('admin.username_help') }}</small>
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.email_label') }} *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                        @error('email')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.new_password_label') }} <small>({{ __('admin.leave_blank_keep_current') }})</small></label>
                        <input type="password" name="password" minlength="8" autocomplete="new-password">
                        @error('password')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.account_status') }}</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_admin" id="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                            <label for="is_admin">{{ __('admin.grant_admin') }}</label>
                        </div>
                    </div>

                    <div class="form-group full">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_suspended" id="is_suspended" value="1" {{ old('is_suspended', $user->is_suspended) ? 'checked' : '' }}>
                            <label for="is_suspended">{{ __('admin.suspend_account') }}</label>
                        </div>
                        <small class="help-text">{{ __('admin.suspend_help') }}</small>
                    </div>
                </div>
            </div>

            {{-- Profile Section --}}
            <div class="form-card">
                <h2><i class="fas fa-id-card"></i> {{ __('admin.profile_info') }}</h2>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>{{ __('admin.bio') }}</label>
                        <textarea name="bio" rows="3" maxlength="500" placeholder="{{ __('admin.bio_placeholder') }}">{{ old('bio', $user->profile->bio ?? '') }}</textarea>
                    </div>

                    <div class="form-group full">
                        <label>{{ __('admin.about') }}</label>
                        <textarea name="about" rows="3" maxlength="1000" placeholder="{{ __('admin.about_placeholder') }}">{{ old('about', $user->profile->about ?? '') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.website') }}</label>
                        <input type="url" name="website" value="{{ old('website', $user->profile->website ?? '') }}" placeholder="{{ __('admin.website_placeholder') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.location') }}</label>
                        <input type="text" name="location" value="{{ old('location', $user->profile->location ?? '') }}" placeholder="{{ __('admin.location_placeholder') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.occupation') }}</label>
                        <input type="text" name="occupation" value="{{ old('occupation', $user->profile->occupation ?? '') }}" placeholder="{{ __('admin.occupation_placeholder') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.phone') }}</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->profile->phone ?? '') }}" placeholder="{{ __('admin.phone_placeholder') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.gender') }}</label>
                        <select name="gender">
                            <option value="">{{ __('admin.not_specified') }}</option>
                            <option value="male" {{ old('gender', $user->profile->gender ?? '') === 'male' ? 'selected' : '' }}>{{ __('admin.male') }}</option>
                            <option value="female" {{ old('gender', $user->profile->gender ?? '') === 'female' ? 'selected' : '' }}>{{ __('admin.female') }}</option>
                            <option value="other" {{ old('gender', $user->profile->gender ?? '') === 'other' ? 'selected' : '' }}>{{ __('admin.other') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ __('admin.birth_date') }}</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date', $user->profile->birth_date ?? '') }}">
                    </div>

                    <div class="form-group full">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_private" id="is_private" value="1" {{ old('is_private', $user->profile->is_private ?? false) ? 'checked' : '' }}>
                            <label for="is_private">{{ __('admin.make_private') }}</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Images Section --}}
            <div class="form-card">
                <h2><i class="fas fa-images"></i> {{ __('admin.profile_images') }}</h2>
                <div class="images-grid">
                    <div class="image-upload">
                        <label>{{ __('admin.avatar') }}</label>
                        <div class="image-preview">
                            <img src="{{ $user->avatar_url }}" alt="{{ __('admin.avatar') }}" id="avatar-preview">
                        </div>
                        <div class="image-actions">
                            <label for="avatar" class="btn-upload">
                                <i class="fas fa-upload"></i> {{ __('admin.upload') }}
                            </label>
                            <button type="button" onclick="removeImage('avatar')" class="btn-remove">
                                <i class="fas fa-trash"></i> {{ __('admin.remove') }}
                            </button>
                        </div>
                        <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;" onchange="previewImage(this, 'avatar-preview')">
                        <input type="hidden" name="remove_avatar" id="remove-avatar" value="0">
                    </div>

                    <div class="image-upload">
                        <label>{{ __('admin.cover_image') }}</label>
                        <div class="image-preview cover">
                            @if($user->profile && $user->profile->cover_image)
                                <img src="{{ asset('storage/' . $user->profile->cover_image) }}" alt="{{ __('admin.cover_image') }}" id="cover-preview">
                            @else
                                <div class="no-image" id="cover-preview">
                                    <i class="fas fa-image"></i>
                                </div>
                            @endif
                        </div>
                        <div class="image-actions">
                            <label for="cover_image" class="btn-upload">
                                <i class="fas fa-upload"></i> {{ __('admin.upload') }}
                            </label>
                            @if($user->profile && $user->profile->cover_image)
                            <button type="button" onclick="removeImage('cover')" class="btn-remove">
                                <i class="fas fa-trash"></i> {{ __('admin.remove') }}
                            </button>
                            @endif
                        </div>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*" style="display: none;" onchange="previewImage(this, 'cover-preview')">
                        <input type="hidden" name="remove_cover" id="remove-cover" value="0">
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="form-actions">
                <a href="{{ route('admin.users.show', $user) }}" class="btn-cancel">
                    <i class="fas fa-times"></i> {{ __('admin.cancel') }}
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> {{ __('admin.save_changes') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage(type) {
    const message = type === 'avatar' 
        ? "{{ str_replace(':type', 'avatar', __('admin.remove_image_confirm')) }}"
        : "{{ str_replace(':type', 'cover_image', __('admin.remove_image_confirm')) }}";
    if (confirm(message)) {
        document.getElementById('remove-' + type).value = '1';
        const preview = document.getElementById(type + '-preview');
        preview.innerHTML = '<i class="fas fa-' + (type === 'avatar' ? 'user' : 'image') + '"></i>';
    }
}
</script>
@endsection
