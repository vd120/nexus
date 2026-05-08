@extends('layouts.app')

@section('title', __('chat.create_chat_group') . ' - Nexus')

@section('content')
<div class="create-group-container" style="max-width: 500px; margin: 60px auto; padding: 0 20px;">
    <div class="card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 40px;">
        <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">{{ __('chat.new_chat_group') }}</h1>
        <p style="color: var(--text-muted); margin-bottom: 32px;">{{ __('chat.start_private_group_desc') }}</p>

        <form action="{{ route('groups.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div style="background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.2); color: #ef4444; padding: 12px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 700; margin-bottom: 8px;">{{ __('chat.group_name') }}</label>
                <input type="text" name="name" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-hover); color: var(--text);" placeholder="{{ __('chat.enter_group_name') }}">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 700; margin-bottom: 8px;">{{ __('chat.description') }} ({{ __('chat.optional') ?? 'Optional' }})</label>
                <textarea name="description" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border); background: var(--surface-hover); color: var(--text);" rows="2" placeholder="{{ __('chat.whats_this_group_about') }}"></textarea>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 700; margin-bottom: 12px;">{{ __('chat.select_members') }}</label>
                <div class="members-selector" style="background: var(--surface-hover); border: 1px solid var(--border); border-radius: 16px; padding: 16px;">
                    <input type="text" id="userSearch" placeholder="{{ __('chat.search_friends') }}" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text); margin-bottom: 16px;">
                    <div id="usersList" style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                        @forelse($users as $user)
                            <label class="user-option" style="display: flex; align-items: center; gap: 12px; padding: 8px; border-radius: 10px; cursor: pointer; transition: background 0.2s;">
                                <input type="checkbox" name="members[]" value="{{ $user->id }}" style="width: 18px; height: 18px; accent-color: var(--primary);">
                                <img src="{{ $user->avatar_url }}" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; font-size: 14px;">{{ $user->username }}</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">{{ $user->name }}</div>
                                </div>
                            </label>
                        @empty
                            <p style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 20px;">{{ __('chat.no_friends_found') }}</p>
                        @endforelse
                    </div>
                </div>
                @error('members')
                    <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 32px; text-align: center;">
                <label style="display: block; font-weight: 700; margin-bottom: 16px; text-align: left;">{{ __('chat.group_avatar') }}</label>
                <div id="avatarPreviewContainer" style="width: 120px; height: 120px; border-radius: 32px; background: var(--surface-hover); border: 2px dashed var(--border); margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; position: relative; transition: all 0.3s ease;">
                    <img id="avatarImage" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <div id="avatarPlaceholder" style="text-align: center; color: var(--text-muted);">
                        <i class="fas fa-camera" style="font-size: 24px; display: block; margin-bottom: 4px;"></i>
                        <span style="font-size: 12px;">{{ __('chat.upload') }}</span>
                    </div>
                </div>
                <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;" onchange="previewAvatar(event)">
                <button type="button" class="btn btn-ghost" style="font-size: 13px; padding: 4px 12px;" onclick="document.getElementById('avatarInput').click()">
                    {{ __('chat.choose_image') !== 'chat.choose_image' ? __('chat.choose_image') : 'Choose Image' }}
                </button>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; height: 52px; font-weight: 700; border-radius: 16px; box-shadow: 0 8px 20px rgba(0, 168, 132, 0.2);">
                {{ __('chat.create_group') }}
            </button>
        </form>
    </div>
</div>

<script>
    function previewAvatar(event) {
        const input = event.target;
        const preview = document.getElementById('avatarImage');
        const placeholder = document.getElementById('avatarPlaceholder');
        const container = document.getElementById('avatarPreviewContainer');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                container.style.borderStyle = 'solid';
                container.style.borderColor = 'var(--primary)';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Allow clicking the container to trigger file input
    document.getElementById('avatarPreviewContainer').onclick = function() {
        document.getElementById('avatarInput').click();
    };

    // Search functionality
    document.getElementById('userSearch').oninput = function() {
        const query = this.value.toLowerCase();
        const options = document.querySelectorAll('.user-option');
        
        options.forEach(option => {
            const username = option.querySelector('div[style*="font-weight: 700"]').textContent.toLowerCase();
            const name = option.querySelector('div[style*="font-size: 12px"]').textContent.toLowerCase();
            
            if (username.includes(query) || name.includes(query)) {
                option.style.display = 'flex';
            } else {
                option.style.display = 'none';
            }
        });
    };
</script>

<style>
    .user-option:hover {
        background: var(--surface) !important;
    }
    .user-option input:checked + img + div {
        color: var(--primary);
    }
    /* Custom scrollbar for members list */
    #usersList::-webkit-scrollbar {
        width: 6px;
    }
    #usersList::-webkit-scrollbar-track {
        background: transparent;
    }
    #usersList::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 10px;
    }
</style>
@endsection
