<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { font-family: DejaVu Sans, sans-serif; color: #fff; font-size: 7px; line-height: 1.15; margin: 0; padding: 6pt 9pt; background: #14503F; }
    div, span, td { line-height: 1.15; }
    .card { width: 222pt; }
    .brand { font-size: 6px; letter-spacing: 1px; text-transform: uppercase; color: #B7E4CF; }
    table.layout { width: 222pt; table-layout: fixed; border-collapse: collapse; }
    table.layout td { vertical-align: top; overflow: hidden; }
    table.layout .info-cell { width: 158pt; }
    table.layout .qr-cell { width: 64pt; }
    .name { font-size: 11px; font-weight: bold; margin-top: 1pt; }
    .meta { font-size: 7px; color: #DDF3E6; margin-top: 1pt; }
    .blood { margin-top: 4pt; }
    .blood-label { font-size: 6px; text-transform: uppercase; letter-spacing: 1px; color: #DDF3E6; }
    .blood-value { font-size: 17px; font-weight: bold; }
    .row { margin-top: 4pt; font-size: 7px; }
    .row .label { color: #B7E4CF; text-transform: uppercase; font-size: 6px; letter-spacing: 0.5px; }
    .qr-cell { text-align: right; }
    .qr { width: 48pt; height: 48pt; background: #fff; padding: 3pt; border-radius: 4pt; }
    .card-number { font-size: 6px; color: #B7E4CF; margin-top: 3pt; }
</style>
</head>
<body>
<div class="card">
    <table class="layout">
        <tr>
            <td class="info-cell">
                <div class="brand">Nivaya Life &middot; Emergency</div>
                <div class="name">{{ $member->full_name }}</div>
                <div class="meta">
                    {{ $member->age() ?? '—' }}{{ $member->age() !== null ? ' yrs' : '' }}
                    &middot; {{ $member->gender ? \Illuminate\Support\Str::headline($member->gender) : '—' }}
                </div>

                <div class="blood">
                    <span class="blood-label">Blood Group</span><br>
                    <span class="blood-value">{{ $member->blood_group ?? '—' }}</span>
                </div>
            </td>
            <td class="qr-cell">
                <img class="qr" src="{{ $qrPngDataUri }}">
                <div class="card-number">{{ $card->card_number }}</div>
            </td>
        </tr>
    </table>

    <div class="row">
        <span class="label">Emergency Contact</span><br>
        {{ $member->emergency_contact_name ?? '—' }}
        @if($member->emergency_contact_phone) &middot; {{ $member->emergency_contact_phone }} @endif
    </div>

    <div class="row">
        <span class="label">Allergies</span><br>
        @if($allergies->isEmpty())
            None known
        @else
            {{ $allergies->pluck('allergen_name')->take(3)->implode(', ') }}{{ $allergies->count() > 3 ? ', +'.($allergies->count() - 3).' more' : '' }}
        @endif
    </div>
</div>
</body>
</html>
