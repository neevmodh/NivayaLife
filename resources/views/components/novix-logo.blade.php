@props(['size' => 'md', 'dark' => false])

@php
$sizes = [
    'sm' => ['tile' => 'h-8 w-8 rounded-[9px]', 'text' => 'text-lg'],
    'md' => ['tile' => 'h-10 w-10 rounded-xl', 'text' => 'text-xl'],
    'lg' => ['tile' => 'h-12 w-12 rounded-2xl', 'text' => 'text-2xl'],
];
$s = $sizes[$size] ?? $sizes['md'];

// The mark always sits on its own green tile, so it reads identically on the
// white desktop nav, the green mobile header, and the cream landing page —
// only the wordmark beside it needs to adapt to the background.
$wordColor = match (true) {
    $dark === 'responsive' => 'text-white sm:text-novix-ink dark:sm:text-white',
    (bool) $dark => 'text-white',
    default => 'text-novix-ink dark:text-white',
};
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    {{-- Inline recreation of the Nivaya Life mark: white N whose right
         stroke becomes the gold "i", dotted in gold. --}}
    <span class="{{ $s['tile'] }} flex flex-shrink-0 items-center justify-center bg-novix-green shadow-novix-sm">
        <svg class="h-[62%] w-[62%]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6.5 18.5v-13l9 13" stroke="#FFFFFF" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17.5 18.5V9.4" stroke="#C9941A" stroke-width="2.7" stroke-linecap="round"/>
            <circle cx="17.5" cy="5.4" r="1.85" fill="#C9941A"/>
        </svg>
    </span>
    <span class="{{ $s['text'] }} font-bold tracking-tight {{ $wordColor }}">Nivaya<span class="text-novix-gold"> Life</span></span>
</div>
