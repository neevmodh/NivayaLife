<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 34px; }
    body { font-family: DejaVu Sans, sans-serif; color: #12211F; font-size: 12px; }
    .header { background: #0F6A61; color: #fff; padding: 16px 20px; border-radius: 10px; }
    .header .brand { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #9FDDD2; }
    .header .title { font-size: 20px; font-weight: bold; margin-top: 4px; }
    .identity { width: 100%; margin-top: 16px; }
    .identity td { vertical-align: top; }
    .photo { width: 90px; height: 90px; border-radius: 12px; object-fit: cover; }
    .photo-placeholder { width: 90px; height: 90px; border-radius: 12px; background: #CFEFEA; color: #0F6A61; font-size: 32px; font-weight: bold; text-align: center; line-height: 90px; }
    .name { font-size: 18px; font-weight: bold; }
    .meta { color: #5C7876; font-size: 12px; margin-top: 3px; }
    .blood-box { background: #FDE6DD; border-radius: 10px; padding: 14px 18px; margin-top: 16px; }
    .blood-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #B8452A; font-weight: bold; }
    .blood-value { font-size: 30px; font-weight: bold; color: #B8452A; float: right; margin-top: -22px; }
    .section { margin-top: 16px; }
    .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #5C7876; font-weight: bold; border-bottom: 1px solid #DCE8E6; padding-bottom: 4px; }
    .section-body { margin-top: 6px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; margin-right: 6px; }
    .badge-severe { background: #B8452A; color: #fff; }
    .badge-moderate { background: #E8A544; color: #12211F; }
    .badge-mild { background: #DCEFEA; color: #12211F; }
    .contact-box { background: #CFEFEA; border-radius: 10px; padding: 12px 16px; margin-top: 6px; }
    .footer { margin-top: 22px; border-top: 1px solid #DCE8E6; padding-top: 10px; }
    .footer table { width: 100%; }
    .qr { width: 80px; height: 80px; }
    .muted { color: #5C7876; }
    ul { margin: 0; padding-left: 16px; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">Novix &middot; Emergency Medical Card</div>
    <div class="title">{{ $member->full_name }}</div>
</div>

<table class="identity">
    <tr>
        <td style="width: 100px;">
            @if($photoDataUri)
                <img class="photo" src="{{ $photoDataUri }}">
            @else
                <div class="photo-placeholder">{{ strtoupper(substr($member->full_name, 0, 1)) }}</div>
            @endif
        </td>
        <td>
            <div class="name">{{ $member->full_name }}</div>
            <div class="meta">
                Age: {{ $member->age() ?? '—' }} {{ $member->age() !== null ? 'years' : '' }}
                &middot; {{ $member->gender ? \Illuminate\Support\Str::headline($member->gender) : '—' }}
                &middot; ID: {{ $member->unique_health_id }}
            </div>
        </td>
    </tr>
</table>

<div class="blood-box">
    <span class="blood-label">Blood Group</span>
    <span class="blood-value">{{ $member->blood_group ?? '—' }}</span>
</div>

<div class="section">
    <div class="section-title">Allergies</div>
    <div class="section-body">
        @if($allergies->isEmpty())
            <span class="muted">No known allergies</span>
        @else
            @foreach($allergies as $allergy)
                <div style="margin-bottom: 4px;">
                    <span class="badge badge-{{ $allergy->severity }}">{{ $allergy->severity }}</span>
                    {{ $allergy->allergen_name }}
                </div>
            @endforeach
        @endif
    </div>
</div>

<div class="section">
    <div class="section-title">Chronic Conditions</div>
    <div class="section-body">
        @if($conditions->isEmpty())
            <span class="muted">No chronic conditions recorded</span>
        @else
            <ul>
                @foreach($conditions as $condition)
                    <li>{{ $condition->condition_name }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="section">
    <div class="section-title">Current Medications</div>
    <div class="section-body">
        @if($medications->isEmpty())
            <span class="muted">No active medications</span>
        @else
            <ul>
                @foreach($medications as $medication)
                    <li>{{ $medication->medicine_name }} @if($medication->dosage) — {{ $medication->dosage }} @endif</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="section">
    <div class="section-title">Emergency Contact</div>
    <div class="contact-box">
        @if($member->emergency_contact_phone)
            <strong>{{ $member->emergency_contact_name ?? '—' }}</strong> — {{ $member->emergency_contact_phone }}
            @if($member->emergency_contact_relation) ({{ \Illuminate\Support\Str::headline($member->emergency_contact_relation) }}) @endif
        @else
            <span class="muted">No emergency contact on file</span>
        @endif
    </div>
</div>

@if($doctor)
<div class="section">
    <div class="section-title">Family Doctor</div>
    <div class="section-body">
        {{ $doctor->name }} @if($doctor->specialization) — {{ $doctor->specialization }} @endif
        @if($doctor->phone) &middot; {{ $doctor->phone }} @endif
    </div>
</div>
@endif

<div class="footer">
    <table>
        <tr>
            <td class="muted">
                Card {{ $card->card_number }}<br>
                Issued {{ $card->issued_at->format('M j, Y') }}
            </td>
            <td style="width: 90px; text-align: right;">
                <img class="qr" src="{{ $qrPngDataUri }}">
            </td>
        </tr>
    </table>
</div>

</body>
</html>
