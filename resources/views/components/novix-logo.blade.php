@props(['size' => 'md', 'dark' => false])

@php
$sizes = [
    'sm' => ['icon' => 'h-6 w-6', 'text' => 'text-lg'],
    'md' => ['icon' => 'h-8 w-8', 'text' => 'text-xl'],
    'lg' => ['icon' => 'h-10 w-10', 'text' => 'text-2xl'],
];
$s = $sizes[$size] ?? $sizes['md'];
$textColor = $dark ? 'text-white' : 'text-novix-green';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <svg class="{{ $s['icon'] }} {{ $textColor }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2C7 2 3 6 3 11c0 5.523 4.477 10 9 10 .552 0 1-.448 1-1V12c0-5-3-8-1-10Z" fill="currentColor" fill-opacity="0.15"/>
        <path d="M12 2C7 2 3 6 3 11c0 5.523 4.477 10 9 10M12 2c5 0 9 4 9 9 0 5.523-4.477 10-9 10M12 2v19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span class="{{ $s['text'] }} font-bold {{ $textColor }} tracking-tight">Novix</span>
</div>
