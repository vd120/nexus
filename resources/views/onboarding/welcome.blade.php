@extends('layouts.app')

@section('title', __('messages.onboarding_welcome_title', ['name' => auth()->user()->name]))

@section('content')
<style>
@media (max-width: 480px) {
    .ob-follow-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
</style>
<div id="onboarding-root" style="max-width:560px; margin:3rem auto; padding:0 1rem;">

    {{-- Step indicators --}}
    <div style="display:flex; gap:.5rem; justify-content:center; margin-bottom:2.5rem;" aria-label="{{ __('messages.onboarding_welcome_subtitle') }}">
        @foreach([0, 1, 2] as $i)
        <div class="step-dot" data-step="{{ $i }}" style="
            height:4px; flex:1; border-radius:2px; max-width:80px;
            background:{{ $i === 0 ? 'var(--accent,#6366f1)' : 'var(--border-color,#2a2a3e)' }};
            transition:background .3s;
        "></div>
        @endforeach
    </div>

    {{-- Step 1: Profile --}}
    <div id="step-0" class="onboarding-step">
        <div style="text-align:center; margin-bottom:2rem;">
            <div style="font-size:2.5rem; margin-bottom:1rem;" aria-hidden="true">👋</div>
            <h1 style="font-size:1.5rem; font-weight:800; margin:0 0 .5rem;">{{ __('messages.onboarding_welcome_title', ['name' => auth()->user()->name]) }}</h1>
            <p style="opacity:.6; margin:0; font-size:.9375rem;">{{ __('messages.onboarding_welcome_subtitle') }}</p>
        </div>
        <div style="background:var(--card-bg,#1e1e2e); border:1px solid var(--border-color,#2a2a3e); border-radius:16px; padding:1.5rem; margin-bottom:1.5rem;">
            <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                <div style="position:relative;">
                    <img id="ob-avatar-preview" src="{{ auth()->user()->avatar_url }}" alt="{{ __('users.profile_picture') }}" style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
                    <label for="ob-avatar-input" aria-label="{{ __('users.change_avatar') }}" style="position:absolute; bottom:0; right:0; background:var(--accent,#6366f1); border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                        <i class="fas fa-camera" style="font-size:.7rem; color:#fff;" aria-hidden="true"></i>
                    </label>
                    <input type="file" id="ob-avatar-input" accept="image/*" style="display:none;" onchange="previewOBAvatar(this)">
                </div>
                <div style="width:100%;">
                    <label for="ob-bio" style="display:block; font-size:.8rem; opacity:.6; margin-bottom:.375rem;">{{ __('messages.onboarding_bio_label') }} <span style="opacity:.5;">({{ __('messages.onboarding_bio_optional') }})</span></label>
                    <textarea id="ob-bio" placeholder="{{ __('messages.onboarding_bio_placeholder') }}" maxlength="500" style="width:100%; box-sizing:border-box; padding:.625rem .875rem; border-radius:8px; border:1px solid var(--border-color,#2a2a3e); background:var(--input-bg,#12121f); color:inherit; font-size:.9375rem; resize:vertical; min-height:80px;"></textarea>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:.75rem;">
            <button type="button" class="btn btn-primary" style="flex:1;" onclick="saveProfileStep()">
                {{ __('messages.onboarding_save_continue') }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </button>
            <button type="button" class="btn btn-ghost" onclick="goToStep(1)">{{ __('messages.onboarding_skip') }}</button>
        </div>
    </div>

    {{-- Step 2: Follow suggestions --}}
    <div id="step-1" class="onboarding-step" style="display:none;">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div style="font-size:2.5rem; margin-bottom:.75rem;" aria-hidden="true">🤝</div>
            <h2 style="font-size:1.375rem; font-weight:800; margin:0 0 .5rem;">{{ __('messages.onboarding_who_to_follow') }}</h2>
            <p style="opacity:.6; margin:0; font-size:.875rem;">{{ __('messages.onboarding_follow_subtitle') }}</p>
        </div>
        <div class="ob-follow-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:.75rem; margin-bottom:1.5rem;">
            @foreach($suggestions as $person)
            <div style="background:var(--card-bg,#1e1e2e); border:1px solid var(--border-color,#2a2a3e); border-radius:12px; padding:1rem; text-align:center;">
                <img src="{{ $person->avatar_url }}" alt="{{ $person->username }}" style="width:48px; height:48px; border-radius:50%; object-fit:cover; margin-bottom:.5rem;">
                <div style="font-size:.875rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $person->name ?: $person->username }}</div>
                <div style="font-size:.75rem; opacity:.5; margin-bottom:.75rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">&#64;{{ $person->username }}</div>
                <button type="button"
                    class="btn ob-follow-btn"
                    data-username="{{ $person->username }}"
                    data-label-follow="{{ __('users.follow') }}"
                    data-label-following="{{ __('users.following') }}"
                    onclick="obFollow(this)"
                    style="width:100%; font-size:.8rem; padding:.375rem .5rem; background:var(--accent,#6366f1)20; color:var(--accent,#6366f1); border:1px solid var(--accent,#6366f1)40;">
                    {{ __('users.follow') }}
                </button>
            </div>
            @endforeach
            @if($suggestions->isEmpty())
            <p style="opacity:.5; text-align:center; grid-column:1/-1;">{{ __('messages.onboarding_no_suggestions') }}</p>
            @endif
        </div>
        <div style="display:flex; gap:.75rem;">
            <button type="button" class="btn btn-primary" style="flex:1;" onclick="goToStep(2)">
                {{ __('messages.onboarding_continue') }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </button>
            <button type="button" class="btn btn-ghost" onclick="goToStep(2)">{{ __('messages.onboarding_skip') }}</button>
        </div>
    </div>

    {{-- Step 3: Done --}}
    <div id="step-2" class="onboarding-step" style="display:none; text-align:center;">
        <div style="font-size:3rem; margin-bottom:1rem;" aria-hidden="true">🎉</div>
        <h2 style="font-size:1.5rem; font-weight:800; margin:0 0 .5rem;">{{ __('messages.onboarding_all_set') }}</h2>
        <p style="opacity:.6; margin:0 0 2rem; font-size:.9375rem;">{{ __('messages.onboarding_all_set_desc') }}</p>
        <div style="background:var(--card-bg,#1e1e2e); border:1px solid #8b5cf660; border-radius:12px; padding:1.25rem; margin-bottom:2rem; text-align:left;">
            <strong style="display:block; margin-bottom:.5rem; font-size:.9375rem;">🔒 {{ __('messages.onboarding_security_tip') }}</strong>
            <p style="font-size:.875rem; opacity:.7; margin:0 0 .75rem;">{{ __('messages.onboarding_2fa_prompt') }}</p>
            <a href="{{ route('profile.edit', auth()->user()) }}#2fa-card" class="btn" style="font-size:.8rem; padding:.375rem .75rem; background:var(--accent,#6366f1)20; color:var(--accent,#6366f1); border:1px solid var(--accent,#6366f1)40;">{{ __('messages.onboarding_setup_2fa') }}</a>
        </div>
        <button type="button" class="btn btn-primary" style="width:100%; font-size:1rem; padding:.875rem;" onclick="finishOnboarding()">
            {{ __('messages.onboarding_go_to_feed') }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </button>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function goToStep(n) {
    document.querySelectorAll('.onboarding-step').forEach((el, i) => el.style.display = i === n ? 'block' : 'none');
    document.querySelectorAll('.step-dot').forEach((dot, i) => {
        dot.style.background = i <= n ? 'var(--accent,#6366f1)' : 'var(--border-color,#2a2a3e)';
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function previewOBAvatar(input) {
    if (input.files?.[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('ob-avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

async function saveProfileStep() {
    const bio = document.getElementById('ob-bio').value;
    const avatarInput = document.getElementById('ob-avatar-input');
    const formData = new FormData();
    formData.append('_token', csrf);
    if (bio) formData.append('bio', bio);
    if (avatarInput.files[0]) formData.append('avatar', avatarInput.files[0]);

    // Patch via profile update endpoint (existing)
    if (bio || avatarInput.files[0]) {
        // Build a minimal profile update — reuse existing updateProfile route
        const nameInput = '{{ auth()->user()->name }}';
        const usernameInput = '{{ auth()->user()->username }}';
        const emailInput = '{{ auth()->user()->email }}';
        formData.append('name', nameInput);
        formData.append('username', usernameInput);
        formData.append('email', emailInput);

        await fetch('{{ route("profile.update", auth()->user()->username) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            body: formData,
        }).catch(() => {});
    }
    goToStep(1);
}

async function obFollow(btn) {
    const username = btn.dataset.username;
    const labelFollow = btn.dataset.labelFollow;
    const labelFollowing = btn.dataset.labelFollowing;
    try {
        const res = await fetch(`/users/${username}/follow`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.is_following) {
            btn.dataset.following = '1';
            btn.textContent = labelFollowing;
            btn.style.background = 'var(--accent,#6366f1)';
            btn.style.color = '#fff';
        } else {
            btn.dataset.following = '0';
            btn.textContent = labelFollow;
            btn.style.background = 'var(--accent,#6366f1)20';
            btn.style.color = 'var(--accent,#6366f1)';
        }
    } catch(e) {}
}

async function finishOnboarding() {
    await fetch('{{ route("onboarding.complete") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    }).catch(() => {});
    window.location.href = '/';
}
</script>
@endsection
