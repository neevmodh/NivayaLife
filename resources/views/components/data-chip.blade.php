@props([
    'label',
    'value' => null,
    'fallbackLabel' => 'Add',
    'fallbackUrl' => null,
    'accent' => false,
])

{{-- A labelled value chip. When the value is missing this renders a quiet
     "Add" link rather than a bare em-dash, so an incomplete profile reads
     as an invitation instead of a rendering bug. --}}
@php
    $base = 'flex min-w-[5.5rem] flex-col gap-0.5 rounded-xl px-3 py-2 text-left shadow-nivayalife-sm transition';
    $filled = $accent ? 'bg-nivayalife-mint dark:bg-nivayalife-green/20' : 'bg-white dark:bg-white/5';
@endphp

@if(filled($value))
    <div class="{{ $base }} {{ $filled }}">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-nivayalife-muted">{{ $label }}</span>
        <span class="text-base font-bold leading-tight text-nivayalife-ink dark:text-white">{{ $value }}</span>
    </div>
@elseif($fallbackUrl)
    <a href="{{ $fallbackUrl }}" class="{{ $base }} border border-dashed border-nivayalife-green/25 bg-white hover:border-nivayalife-green/50 hover:bg-nivayalife-cream dark:bg-white/5 dark:hover:bg-white/10">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-nivayalife-muted">{{ $label }}</span>
        <span class="flex items-center gap-1 text-sm font-semibold leading-tight text-nivayalife-green dark:text-nivayalife-mint">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            {{ $fallbackLabel }}
        </span>
    </a>
@else
    <div class="{{ $base }} {{ $filled }}">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-nivayalife-muted">{{ $label }}</span>
        <span class="text-base font-bold leading-tight text-nivayalife-muted">&mdash;</span>
    </div>
@endif
