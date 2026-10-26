@props(['name', 'size' => 22])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('add-column')<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M9 3v18m7-12v6m-3-3h6"/>@break
        @case('grip')<path d="M8 5h.01M16 5h.01M8 12h.01M16 12h.01M8 19h.01M16 19h.01" stroke-width="3"/>@break
        @case('chevron')<path d="m9 5 7 7-7 7"/>@break
        @case('arrow-left')<path d="M20 12H4m7-7-7 7 7 7"/>@break
        @case('sun')<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>@break
        @case('moon')<path d="M21 13a9 9 0 0 1-10-10A9 9 0 1 0 21 13Z"/>@break
        @case('menu')<path d="M5 6h14M5 12h14M5 18h14"/>@break
        @case('chart')<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 15v-3m4 5V7m4 8V9"/>@break
        @case('archive')<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>@break
        @case('people')<circle cx="9" cy="7" r="3"/><path d="M2 21v-3a7 7 0 0 1 14 0v3m0-17a3 3 0 0 1 0 6m4 11v-3a7 7 0 0 0-2-5"/>@break
        @case('settings')<path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2"/><circle cx="16" cy="12" r="2"/><circle cx="10" cy="18" r="2"/>@break
        @case('filter')<path d="M4 6h16M7 12h10m-7 6h4"/>@break
        @case('heart')<path d="m12 21-8.5-8.5a5.3 5.3 0 0 1 7.5-7.5l1 1 1-1a5.3 5.3 0 0 1 7.5 7.5Z"/>@break
        @case('message')<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-2 2V11.5a9.5 9.5 0 0 1 19 0Z"/>@break
        @case('repost')<path d="m17 2 4 4-4 4M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4m14-1v2a3 3 0 0 1-3 3H3"/>@break
        @case('image')<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 5-5 4 4 5-6 4 5"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @case('close')<path d="m6 6 12 12M6 18 18 6"/>@break
        @case('more')<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>@break
        @case('home')<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>@break
        @case('search')<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>@break
        @case('book')<path d="M12 5v16M12 5C8 2 4 3 2 4v15c4-2 7-1 10 2 3-3 6-4 10-2V4c-2-1-6-2-10 1Z"/>@break
        @case('bookmark')<path d="M6 3h12v18l-6-4-6 4Z"/>@break
        @case('person')<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>@break
        @case('mail')<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 6 9 7 9-7"/>@break
        @case('write')<path d="m16 3 5 5-12 12-6 1 1-6ZM13 6l5 5"/>@break
        @case('arrow')<path d="M5 12h14m-6-6 6 6-6 6"/>@break
        @default<circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/>
    @endswitch
</svg>
