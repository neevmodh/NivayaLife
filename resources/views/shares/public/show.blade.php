<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shared Health Report — Nivaya Life</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-nivayalife-cream font-sans antialiased">
<div class="mx-auto max-w-2xl px-4 py-8 sm:py-12">
    <a href="{{ url('/') }}" class="mb-6 flex justify-center"><x-nivayalife-logo /></a>

    {{-- Minimal identity — never full address or emergency contact in a share --}}
    <div class="overflow-hidden rounded-nivayalife bg-nivayalife-green shadow-nivayalife">
        <div class="flex items-center justify-between px-6 py-4 text-white">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-white/70">Shared via Nivaya Life</p>
                <h1 class="mt-1 text-lg font-bold">{{ $familyMember->full_name }}</h1>
                <p class="text-sm text-white/80">
                    {{ $familyMember->age() !== null ? $familyMember->age().' years' : '' }}
                    &middot; Blood group {{ $familyMember->blood_group ?? '—' }}
                </p>
            </div>
            <a href="{{ route('share.public.pdf', $share->token) }}" class="flex-shrink-0 rounded-lg bg-white/15 px-3 py-2 text-xs font-bold text-white hover:bg-white/25">Download PDF</a>
        </div>
    </div>

    @if($share->shared_with_label)
        <p class="mt-3 text-center text-xs text-nivayalife-muted">Shared with: {{ $share->shared_with_label }}</p>
    @endif

    {{-- Reports --}}
    <div class="mt-6 space-y-5">
        @foreach($reports as $report)
            @php($isImage = str_starts_with($report->mime_type ?? '', 'image/'))
            @php($isPdf = ($report->mime_type ?? '') === 'application/pdf')
            <div class="overflow-hidden rounded-nivayalife bg-white shadow-nivayalife-sm">
                <div class="border-b border-gray-100 p-5">
                    <p class="text-sm font-bold text-nivayalife-ink">{{ $report->typeLabel() }}</p>
                    <p class="text-xs text-nivayalife-muted">
                        {{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}
                        @if($report->hospital_or_clinic_name) &middot; {{ $report->hospital_or_clinic_name }} @endif
                        @if($report->doctor_name) &middot; Dr. {{ $report->doctor_name }} @endif
                    </p>
                </div>

                @if($isImage)
                    <img src="{{ route('share.public.file', [$share->token, $report]) }}" class="max-h-96 w-full bg-nivayalife-cream object-contain" alt="{{ $report->original_filename }}">
                @elseif($isPdf)
                    <iframe src="{{ route('share.public.file', [$share->token, $report]) }}" class="h-96 w-full" title="{{ $report->original_filename }}"></iframe>
                @endif

                @if($report->ai_summary)
                    <div class="border-t border-gray-100 p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-nivayalife-muted">Summary</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-nivayalife-ink">{{ $report->ai_summary }}</p>
                    </div>
                @endif

                @if($detailedExplanations[$report->id] ?? null)
                    <div class="border-t border-gray-100 p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-nivayalife-muted">Detailed Explanation</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-nivayalife-ink">{{ $detailedExplanations[$report->id] }}</p>
                    </div>
                @endif

                @if($report->ocr_text)
                    <div class="border-t border-gray-100 p-5" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="text-xs font-semibold text-nivayalife-green hover:underline">
                            <span x-text="open ? 'Hide extracted text' : 'Show extracted text'"></span>
                        </button>
                        <p x-show="open" x-cloak class="mt-2 whitespace-pre-line text-xs text-nivayalife-muted">{{ $report->ocr_text }}</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <p class="mt-6 text-center text-xs text-nivayalife-muted">This link expires {{ $share->expires_at->format('M j, Y g:i A') }}.</p>
</div>
</body>
</html>
