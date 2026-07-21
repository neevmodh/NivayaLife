<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 34px; }
    body { font-family: DejaVu Sans, sans-serif; color: #12211F; font-size: 11px; }
    .header { background: #0F6A61; color: #fff; padding: 14px 18px; border-radius: 8px; }
    .header .brand { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #9FDDD2; }
    .header .name { font-size: 17px; font-weight: bold; margin-top: 3px; }
    .header .meta { font-size: 10px; color: #CFEFEA; margin-top: 3px; }
    .report { margin-top: 18px; page-break-inside: avoid; }
    .report-title { font-size: 13px; font-weight: bold; }
    .report-meta { font-size: 10px; color: #5C7876; margin-top: 2px; }
    .preview { margin-top: 8px; max-width: 100%; max-height: 260px; border: 1px solid #DCE8E6; border-radius: 6px; }
    .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #5C7876; font-weight: bold; margin-top: 10px; }
    .section-body { margin-top: 4px; font-size: 10px; white-space: pre-line; }
    .divider { border-top: 1px solid #DCE8E6; margin-top: 16px; }
    .footer { margin-top: 16px; font-size: 9px; color: #5C7876; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">Novix &middot; Shared Health Report</div>
    <div class="name">{{ $familyMember->full_name }}</div>
    <div class="meta">
        {{ $familyMember->age() !== null ? $familyMember->age().' years' : '' }}
        &middot; Blood group {{ $familyMember->blood_group ?? '—' }}
    </div>
</div>

@foreach($reports as $report)
    <div class="report">
        <div class="report-title">{{ $report->typeLabel() }}</div>
        <div class="report-meta">
            {{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}
            @if($report->hospital_or_clinic_name) &middot; {{ $report->hospital_or_clinic_name }} @endif
            @if($report->doctor_name) &middot; Dr. {{ $report->doctor_name }} @endif
        </div>

        @if($previewDataUris[$report->id] ?? null)
            <img class="preview" src="{{ $previewDataUris[$report->id] }}">
        @endif

        @if($report->ai_summary)
            <div class="section-title">Summary</div>
            <div class="section-body">{{ $report->ai_summary }}</div>
        @endif

        @if($detailedExplanations[$report->id] ?? null)
            <div class="section-title">Detailed Explanation</div>
            <div class="section-body">{{ $detailedExplanations[$report->id] }}</div>
        @endif

        @if($report->ocr_text)
            <div class="section-title">Extracted Text</div>
            <div class="section-body">{{ $report->ocr_text }}</div>
        @endif
    </div>
    @unless($loop->last)
        <div class="divider"></div>
    @endunless
@endforeach

<div class="footer">
    Generated {{ now()->format('M j, Y, g:i a') }} via Novix &middot; Shared link expires {{ $share->expires_at->format('M j, Y g:i A') }}
</div>

</body>
</html>
