@props([
    'title',
    'hint' => null,
    'actionLabel' => null,
    'actionUrl' => null,
    'icon' => 'check',
    'tone' => 'neutral',
])

@php
    // One icon set, one layout, one set of spacing values — every card that
    // has nothing to show now looks the same instead of each inventing its own.
    $icons = [
        'check' => '<path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'report' => '<path d="M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'clock' => '<path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'pill' => '<path d="M10.5 20.5 3.5 13.5a4.95 4.95 0 1 1 7-7l1 1 1-1a4.95 4.95 0 0 1 7 7l-7 7-1.5-1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3 4 6v6c0 4.4 3.4 8.2 8 9 4.6-.8 8-4.6 8-9V6l-8-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M4 20h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    // "positive" is for the all-clear case (no medications is good news);
    // "neutral" is for the nothing-here-yet case (no reports uploaded).
    $tones = [
        'neutral' => 'bg-nivayalife-cream text-nivayalife-muted dark:bg-white/10',
        'positive' => 'bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 px-4 py-8 text-center']) }}>
    <span class="flex h-11 w-11 items-center justify-center rounded-full {{ $tones[$tone] ?? $tones['neutral'] }}" aria-hidden="true">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">{!! $icons[$icon] ?? $icons['check'] !!}</svg>
    </span>
    <p class="text-sm font-medium text-nivayalife-ink dark:text-white">{{ $title }}</p>
    @if($hint)
        <p class="-mt-1 max-w-[28ch] text-xs text-nivayalife-muted">{{ $hint }}</p>
    @endif
    @if($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="mt-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-nivayalife-green transition hover:bg-nivayalife-mint/50 dark:text-nivayalife-mint dark:hover:bg-white/10">
            {{ $actionLabel }}
        </a>
    @endif
</div>
