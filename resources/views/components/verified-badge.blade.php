@props(['user', 'size' => 14])
@if($user?->is_verified)
<svg {{ $attributes->class(['verified-badge']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" role="img" aria-label="Tài khoản đã xác minh">
    <title>Tài khoản đã xác minh</title>
    <circle cx="12" cy="12" r="10" fill="#1d9bf0"/>
    <path d="m7.5 11.8 3 3 6-6" fill="none" stroke="white" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
@endif
