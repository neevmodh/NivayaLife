<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 30px 36px; }
    body { font-family: DejaVu Sans, sans-serif; color: #1F2A24; font-size: 11px; }
    .header { background: #14503F; color: #fff; padding: 14px 18px; border-radius: 8px; }
    .header .brand { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #B7E4CF; }
    .header .title { font-size: 17px; font-weight: bold; margin-top: 3px; }
    .header .range { font-size: 10px; color: #DDF3E6; margin-top: 3px; }
    .section { margin-top: 16px; page-break-inside: avoid; }
    .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #6B7A72; font-weight: bold; border-bottom: 1px solid #E7E1D4; padding-bottom: 4px; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.data th { text-align: left; font-size: 9px; text-transform: uppercase; color: #6B7A72; padding: 4px 6px; border-bottom: 1px solid #E7E1D4; }
    table.data td { padding: 5px 6px; border-bottom: 1px solid #F3EFE4; font-size: 10px; }
    .muted { color: #6B7A72; }
    .footer { margin-top: 20px; border-top: 1px solid #E7E1D4; padding-top: 8px; font-size: 9px; color: #6B7A72; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">Nivaya Life &middot; Health Summary</div>
    <div class="title">{{ $active->full_name }}</div>
    <div class="range">{{ $active->age() !== null ? $active->age().' years' : '' }} &middot; Blood group {{ $active->blood_group ?? '—' }} &middot; {{ $rangeLabel }}</div>
</div>

<div class="section">
    <div class="section-title">Reports ({{ $reports->count() }})</div>
    @if($reports->isEmpty())
        <p class="muted">No reports in this range.</p>
    @else
        <table class="data">
            <tr><th>Date</th><th>Type</th><th>Facility</th><th>Summary</th></tr>
            @foreach($reports as $report)
                <tr>
                    <td>{{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}</td>
                    <td>{{ $report->typeLabel() }}</td>
                    <td>{{ $report->hospital_or_clinic_name ?? '—' }}</td>
                    <td>{{ $report->ai_summary ? \Illuminate\Support\Str::limit(\Illuminate\Support\Str::of($report->ai_summary)->before("\n\n"), 140) : '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

<div class="section">
    <div class="section-title">Vitals &amp; Lab Values</div>
    @if($metrics->isEmpty())
        <p class="muted">No recorded vitals in this range.</p>
    @else
        @foreach($metrics as $metricType => $rows)
            <table class="data">
                <tr><th colspan="2">{{ str_replace('_', ' ', $metricType) }}</th></tr>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row->recorded_date->format('M j, Y') }}</td>
                        <td>{{ rtrim(rtrim(number_format($row->value, 2), '0'), '.') }} {{ $row->unit }}</td>
                    </tr>
                @endforeach
            </table>
        @endforeach
    @endif
</div>

<div class="section">
    <div class="section-title">Medications</div>
    @if($medications->isEmpty())
        <p class="muted">No medications on file.</p>
    @else
        <table class="data">
            <tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Status</th></tr>
            @foreach($medications as $medication)
                <tr>
                    <td>{{ $medication->medicine_name }}</td>
                    <td>{{ $medication->dosage ?? '—' }}</td>
                    <td>{{ $medication->frequency ?? '—' }}</td>
                    <td>{{ $medication->active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

<div class="section">
    <div class="section-title">Vaccinations</div>
    @if($vaccinations->isEmpty())
        <p class="muted">No vaccinations in this range.</p>
    @else
        <table class="data">
            <tr><th>Vaccine</th><th>Dose</th><th>Date</th></tr>
            @foreach($vaccinations as $vaccination)
                <tr>
                    <td>{{ $vaccination->vaccine_name }}</td>
                    <td>{{ $vaccination->dose_number }}</td>
                    <td>{{ $vaccination->date_administered->format('M j, Y') }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

<div class="footer">
    Generated {{ now()->format('M j, Y, g:i a') }} via Nivaya Life. This summary is compiled from the patient's own records and is not a substitute for clinical judgment.
</div>

</body>
</html>
