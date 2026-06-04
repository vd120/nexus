@props(['user', 'size' => '1em'])
@if($user->is_verified)
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
     width="{{ $size }}" height="{{ $size }}"
     style="display:inline-block;vertical-align:middle;margin-left:.2em;flex-shrink:0;"
     aria-label="Verified account" role="img">
    <circle cx="12" cy="12" r="10.5" fill="#1d9bf0"/>
    <path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
@endif
