@extends('layouts.app')

@section('title', __('messages.feature_flags_title') . ' — Admin')

@section('content')
<div class="container" style="max-width:800px; margin:2rem auto; padding:0 1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:.75rem;">
        <h1 style="font-size:1.5rem; font-weight:700; margin:0;">{{ __('messages.feature_flags_title') }}</h1>
        <a href="{{ route('admin.dashboard') }}" style="font-size:.875rem; opacity:.7;">&larr; Admin</a>
    </div>

    <div style="background:var(--card-bg,#1e1e2e); border:1px solid var(--border-color,#2a2a3e); border-radius:12px; overflow:hidden; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; min-width:480px;">
            <thead>
                <tr style="border-bottom:1px solid var(--border-color,#2a2a3e);">
                    <th style="padding:.875rem 1rem; text-align:left; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; opacity:.6;">Flag</th>
                    <th style="padding:.875rem 1rem; text-align:left; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; opacity:.6;">Notes</th>
                    <th style="padding:.875rem 1rem; text-align:right; font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; opacity:.6;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flags as $flag)
                <tr class="flag-row" style="border-bottom:1px solid var(--border-color,#2a2a3e);">
                    <td style="padding:.875rem 1rem;">
                        <code style="font-size:.875rem; background:var(--code-bg,#12121f); padding:.15rem .4rem; border-radius:4px;">{{ $flag->name }}</code>
                    </td>
                    <td style="padding:.875rem 1rem; font-size:.875rem; opacity:.7;">{{ $flag->notes }}</td>
                    <td style="padding:.875rem 1rem; text-align:right;">
                        <button
                            class="flag-toggle-btn"
                            data-name="{{ $flag->name }}"
                            data-enabled="{{ $flag->enabled ? '1' : '0' }}"
                            data-label-enabled="{{ __('messages.feature_flag_enabled') }}"
                            data-label-disabled="{{ __('messages.feature_flag_disabled') }}"
                            style="
                                padding:.375rem .875rem;
                                border-radius:20px;
                                border:none;
                                cursor:pointer;
                                font-size:.8rem;
                                font-weight:600;
                                transition:all .2s;
                                background: {{ $flag->enabled ? '#22c55e20' : '#ef444420' }};
                                color: {{ $flag->enabled ? '#22c55e' : '#ef4444' }};
                                outline: 1px solid {{ $flag->enabled ? '#22c55e40' : '#ef444440' }};
                            ">
                            {{ $flag->enabled ? __('messages.feature_flag_enabled') : __('messages.feature_flag_disabled') }}
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.flag-toggle-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const name = this.dataset.name;
        const labelEnabled = this.dataset.labelEnabled;
        const labelDisabled = this.dataset.labelDisabled;
        this.style.opacity = '.5';
        this.style.pointerEvents = 'none';

        try {
            const res = await fetch(`/admin/feature-flags/${name}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            this.dataset.enabled = data.enabled ? '1' : '0';
            this.textContent = data.enabled ? labelEnabled : labelDisabled;
            this.style.background = data.enabled ? '#22c55e20' : '#ef444420';
            this.style.color = data.enabled ? '#22c55e' : '#ef4444';
            this.style.outline = `1px solid ${data.enabled ? '#22c55e40' : '#ef444440'}`;
        } catch(e) {
            alert('{{ __("messages.feature_flag_toggle_failed") }}');
        }

        this.style.opacity = '1';
        this.style.pointerEvents = 'auto';
    });
});
</script>
@endpush
@endsection
