@props([
    'label',
    'value' => null,
    'fallbackLabel' => 'Add',
    'fallbackUrl' => null,
    'accent' => false,
])

{{-- A labelled value on the identity card. When the value is missing this
     renders a quiet "Add" link rather than a bare em-dash, so an incomplete
     profile reads as an invitation instead of a rendering bug. --}}
@php
    $base = 'flex min-w-[5.5rem] flex-col gap-0.5 rounded-xl px-3 py-2 text-left backdrop-blur-sm transition';
    $filled = $accent ? 'bg-white/20' : 'bg-white/10';
@endphp

@if(filled($value))
    <div class="{{ $base }} {{ $filled }}">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-white/60">{{ $label }}</span>
        <span class="text-base font-bold leading-tight text-white">{{ $value }}</span>
    </div>
@elseif($fallbackUrl)
    <a href="{{ $fallbackUrl }}" class="{{ $base }} border border-dashed border-white/25 hover:border-white/50 hover:bg-white/10">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-white/60">{{ $label }}</span>
        <span class="flex items-center gap-1 text-sm font-semibold leading-tight text-white/75">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            {{ $fallbackLabel }}
        </span>
    </a>
@else
    <div class="{{ $base }} {{ $filled }}">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-white/60">{{ $label }}</span>
        <span class="text-base font-bold leading-tight text-white/50">&mdash;</span>
    </div>
@endif
