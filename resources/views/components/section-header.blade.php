@props([
    'title',
    'icon' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

@php
    $icons = [
        'bolt' => '<path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        'report' => '<path d="M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8h6M9 12h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        'pill' => '<path d="M10.5 20.5 3.5 13.5a4.95 4.95 0 1 1 7-7l1 1 1-1a4.95 4.95 0 0 1 7 7l-7 7-1.5-1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'syringe' => '<path d="M19 8 8 19l-5-5M14 3l7 7-3 3-7-7 3-3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'pulse' => '<path d="M3 12h4l2.5-6 4 12 2.5-6h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'target' => '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3']) }}>
    <h3 class="flex items-center gap-2 text-[13px] font-bold uppercase tracking-wide text-nivayalife-muted">
        @if($icon && isset($icons[$icon]))
            <svg class="h-4 w-4 text-nivayalife-green dark:text-nivayalife-mint" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $icons[$icon] !!}</svg>
        @endif
        {{ $title }}
    </h3>
    @if($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="flex-shrink-0 text-xs font-semibold text-nivayalife-green transition hover:underline dark:text-nivayalife-mint">{{ $actionLabel }}</a>
    @endif
</div>
