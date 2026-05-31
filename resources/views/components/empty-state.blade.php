@props([
    'icon' => 'fas fa-inbox',
    'title' => null,
    'description' => null,
    'cta' => null,        // 'Label' or null
    'ctaHref' => '#',
    'tone' => 'default',  // default | accent | success | warning | danger
])

@php
    $toneIconColor = [
        'default' => 'var(--brand-500)',
        'accent'  => 'var(--accent-500)',
        'success' => 'var(--success)',
        'warning' => 'var(--warning)',
        'danger'  => 'var(--danger)',
    ][$tone] ?? 'var(--brand-500)';
@endphp

<div {{ $attributes->merge(['class' => 'nx-empty']) }}>
    <div class="nx-empty-icon" style="color: {{ $toneIconColor }};">
        <i class="{{ $icon }}"></i>
    </div>
    @if($title)
        <h3 class="nx-empty-title">{{ $title }}</h3>
    @endif
    @if($description)
        <p class="nx-empty-desc">{{ $description }}</p>
    @endif
    @if($cta)
        <a href="{{ $ctaHref }}" class="nx-empty-cta">{{ $cta }}</a>
    @endif
    {{ $slot }}
</div>
