@props(['name', 'size' => 20])
<svg {{ $attributes->class(['icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('home') <path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/> @break
        @case('book') <path d="M4 3h13a3 3 0 0 1 3 3v15H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3"/><path d="M3 17h17M8 7h7M8 11h5"/> @break
        @case('grid') <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/> @break
        @case('bookmark') <path d="M6 3h12v18l-6-4-6 4Z"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/> @break
        @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/> @break
        @case('arrow') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
        @case('back') <path d="M20 12H4m6-6-6 6 6 6"/> @break
        @case('edit') <path d="m15 4 5 5-10 10-6 1 1-6Z"/><path d="m13 6 5 5"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('mail') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/> @break
        @case('code') <path d="m8 6-6 6 6 6m8-12 6 6-6 6m-3-15-2 18"/> @break
        @case('layers') <path d="m12 3 10 6-10 6L2 9Zm-10 12 10 6 10-6M2 15l10 6 10-6"/> @break
        @case('coffee') <path d="M3 8h14v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4ZM17 8h2a3 3 0 0 1 0 6h-2M7 2v3m5-3v3M2 23h17"/> @break
        @case('check') <path d="m4 12 5 5L20 6"/> @break
        @case('link') <path d="m10 14 4-4m-6 6-2 2a4 4 0 0 1-6-6l5-5a4 4 0 0 1 6 0m-1 1 2-2a4 4 0 0 1 6 6l-5 5a4 4 0 0 1-6 0" transform="translate(2 0)"/> @break
        @case('plus') <path d="M12 4v16M4 12h16"/> @break
    @endswitch
</svg>
