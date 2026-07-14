@props(['relation', 'class' => 'h-4 w-4'])

@php
    $icons = [
        'self' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4 0-7 2-7 5v1h14v-1c0-3-3-5-7-5Z" fill="currentColor"/>',
        'spouse' => '<path d="M8 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM2 20c0-3 2.5-5 6-5s6 2 6 5M10 20c0-3 2.5-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        'father' => '<circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        'mother' => '<circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        'son' => '<circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M6 21v-1.5A4.5 4.5 0 0 1 10.5 15h3A4.5 4.5 0 0 1 18 19.5V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        'daughter' => '<circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M6 21v-1.5A4.5 4.5 0 0 1 10.5 15h3A4.5 4.5 0 0 1 18 19.5V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        'grandfather' => '<circle cx="12" cy="6.5" r="3.5" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M5 21v-1a7 7 0 0 1 14 0v1M9 21v-3M15 21v-3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        'grandmother' => '<circle cx="12" cy="6.5" r="3.5" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M5 21v-1a7 7 0 0 1 14 0v1M9 21v-3M15 21v-3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        'other' => '<circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M5 21v-1a7 7 0 0 1 14 0v1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
    ];
    $path = $icons[$relation] ?? $icons['other'];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $path !!}</svg>
