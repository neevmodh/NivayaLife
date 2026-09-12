@props(['type'])

{{-- Stroke-based icons matching the rest of the app, replacing the old
     per-file emoji lookup tables (novix-slop: emoji standing in for icons
     don't scale, recolor, or render consistently across platforms). --}}
@php
    $paths = [
        'blood_test' => 'M12 3s7 8.5 7 13a7 7 0 1 1-14 0c0-4.5 7-13 7-13Z',
        'prescription' => 'M10.5 20.5 3.5 13.5a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7ZM7 10l7 7',
        'xray' => 'M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2',
        'sonography' => 'M3 12h2m3-6v12m3-9v6m3-8v10m3-6v2',
        'mri_ct' => 'M12 3v2m0 14v2m9-9h-2M5 12H3m14.5-6.4-1.4 1.4M7.9 16.5l-1.4 1.4m11-0.1-1.4-1.4M7.9 7.5 6.5 6.1M17 12a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z',
        'insurance' => 'M12 3 5 6v6c0 5 3 8 7 9 4-1 7-4 7-9V6l-7-3Z',
        'bill' => 'M6 2h12v20l-3-2-3 2-3-2-3 2V2ZM9 7h6M9 11h6M9 15h4',
        'ecg' => 'M3 12h4l2-6 4 12 2-6h6',
        'dental' => 'M12 3h-.6C9.4 3 8 4.6 8 6.8c0 1 .3 1.7.6 2.6.5 1.4 1 3.1 1.1 5.5.1 2.2.6 3.9 1.4 3.9.8 0 .9-1.6.9-2.8 0-.7.6-.7 1 0 0 1.2.1 2.8.9 2.8.8 0 1.3-1.7 1.4-3.9.1-2.4.6-4.1 1.1-5.5.3-.9.6-1.6.6-2.6C16 4.6 14.6 3 12.6 3H12Z',
        'discharge_summary' => 'M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M7 5h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2ZM9 13l2 2 4-4',
        'pathology' => 'M9 2h6M10 2v6.5L5.6 17a2 2 0 0 0 1.8 3h9.2a2 2 0 0 0 1.8-3L14 8.5V2',
        'eye_care' => 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
    ];
    $default = 'M9 12h6m-6 4h6m1 5H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l4.414 4.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z';
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$type] ?? $default }}"/>
</svg>
