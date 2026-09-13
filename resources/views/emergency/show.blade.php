<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Emergency Medical Card — Nivaya Life</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-nivayalife-cream font-sans antialiased" x-data="{ lang: 'en', labels: @js($labels) }">
<div class="mx-auto max-w-xl px-4 py-8 sm:py-12">
    <a href="{{ url('/') }}" class="mb-6 flex justify-center"><x-nivayalife-logo /></a>

    <div class="mb-4 flex justify-center gap-2" role="group" aria-label="Language">
        <button type="button" @click="lang = 'en'" :class="lang === 'en' ? 'bg-nivayalife-green text-white' : 'bg-white text-nivayalife-ink'" :aria-pressed="lang === 'en'" aria-label="English" class="rounded-full px-4 py-1.5 text-xs font-semibold shadow-sm transition">English</button>
        <button type="button" @click="lang = 'hi'" :class="lang === 'hi' ? 'bg-nivayalife-green text-white' : 'bg-white text-nivayalife-ink'" :aria-pressed="lang === 'hi'" aria-label="Hindi" class="rounded-full px-4 py-1.5 text-xs font-semibold shadow-sm transition">हिंदी</button>
        <button type="button" @click="lang = 'gu'" :class="lang === 'gu' ? 'bg-nivayalife-green text-white' : 'bg-white text-nivayalife-ink'" :aria-pressed="lang === 'gu'" aria-label="Gujarati" class="rounded-full px-4 py-1.5 text-xs font-semibold shadow-sm transition">ગુજરાતી</button>
    </div>

    @php
        $severeAllergies = $allergies->where('severity', 'severe');
    @endphp

    <div class="overflow-hidden rounded-nivayalife bg-white shadow-nivayalife">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 bg-nivayalife-green px-6 py-4">
            <div class="text-white">
                <p class="text-xs font-semibold uppercase tracking-widest text-white/70" x-text="labels[lang].title"></p>
                <p class="mt-0.5 text-[11px] text-white/60" x-text="(labels[lang].card_number) + ' ' + @js($card->card_number)"></p>
            </div>
            @if($qrDataUri)
                <img src="{{ $qrDataUri }}" class="h-16 w-16 flex-shrink-0 rounded-lg bg-white p-1.5" alt="QR code">
            @endif
        </div>

        {{-- Identity --}}
        <div class="flex items-center gap-4 border-b border-gray-100 p-6">
            <x-avatar :photo-path="$member->photo_path" :preset="$member->avatar_preset ?? null" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()"
                size="h-20 w-20" rounded="rounded-2xl" class="flex-shrink-0" />
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold text-nivayalife-ink">{{ $member->full_name }}</h1>
                <p class="text-sm text-nivayalife-muted">
                    <span x-text="labels[lang].age"></span>:
                    {{ $member->age() !== null ? $member->age() : '—' }}
                    <span x-show="{{ $member->age() !== null ? 'true' : 'false' }}" x-text="labels[lang].years"></span>
                    &middot; {{ $member->gender ? Str::headline($member->gender) : '—' }}
                </p>
            </div>
        </div>

        {{-- Critical band. This page is read by a stranger, in a hurry, often
             in poor light: blood group and severe allergies come before
             everything else and are sized to be read at a glance. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2">
            <div class="flex flex-col items-center justify-center border-b border-gray-100 bg-nivayalife-pink/20 px-6 py-6 sm:border-b-0 sm:border-r">
                <span class="text-[11px] font-bold uppercase tracking-widest text-nivayalife-pink-dark" x-text="labels[lang].blood_group"></span>
                <span class="mt-1 text-5xl font-extrabold leading-none text-nivayalife-pink-dark">{{ $member->blood_group ?? '—' }}</span>
            </div>

            <div class="flex flex-col justify-center px-6 py-6 {{ $severeAllergies->isNotEmpty() ? 'bg-nivayalife-pink-dark' : 'bg-nivayalife-mint/40' }}">
                @if($severeAllergies->isNotEmpty())
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-widest text-white/80">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span x-text="labels[lang].severe"></span> <span x-text="labels[lang].allergies"></span>
                    </span>
                    <ul class="mt-1.5 space-y-0.5">
                        @foreach($severeAllergies as $allergy)
                            <li class="text-xl font-extrabold leading-tight text-white">{{ $allergy->allergen_name }}</li>
                        @endforeach
                    </ul>
                @else
                    <span class="text-[11px] font-bold uppercase tracking-widest text-nivayalife-green" x-text="labels[lang].allergies"></span>
                    <span class="mt-1 text-lg font-bold text-nivayalife-green" x-text="labels[lang].no_allergies"></span>
                @endif
            </div>
        </div>

        {{-- Call, immediately after the critical facts rather than buried. --}}
        @if($member->emergency_contact_phone)
            <a href="tel:{{ $member->emergency_contact_phone }}"
                class="flex items-center gap-3 border-y border-gray-100 bg-nivayalife-green px-6 py-4 text-white transition active:scale-[0.99]">
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-white/15" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-white/70" x-text="labels[lang].emergency_contact"></span>
                    <span class="block truncate text-base font-bold">{{ $member->emergency_contact_name ?? $member->emergency_contact_phone }}</span>
                    <span class="block truncate text-xs text-white/70">{{ $member->emergency_contact_phone }}@if($member->emergency_contact_relation) &middot; {{ Str::headline($member->emergency_contact_relation) }}@endif</span>
                </span>
                <span class="flex-shrink-0 rounded-full bg-white px-4 py-2 text-sm font-extrabold text-nivayalife-green" x-text="labels[lang].call"></span>
            </a>
        @endif

        {{-- Allergies --}}
        <div class="border-t border-gray-100 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-nivayalife-muted" x-text="labels[lang].allergies"></h3>
            @php($otherAllergies = $allergies->where('severity', '!=', 'severe'))
            @if($otherAllergies->isEmpty())
                <p class="mt-2 text-sm text-nivayalife-muted">{{ $allergies->isEmpty() ? '' : '' }}<span x-text="labels[lang].no_allergies"></span></p>
            @else
                <ul class="mt-2 space-y-1.5">
                    @foreach($otherAllergies as $allergy)
                        @php($severityClass = match($allergy->severity) {
                            'severe' => 'bg-nivayalife-pink-dark text-white',
                            'moderate' => 'bg-nivayalife-yellow/40 text-nivayalife-ink',
                            default => 'bg-gray-100 text-nivayalife-ink',
                        })
                        <li class="flex items-center gap-2 text-sm">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $severityClass }}" x-text="labels[lang].{{ $allergy->severity }}"></span>
                            <span class="font-medium text-nivayalife-ink">{{ $allergy->allergen_name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Chronic conditions --}}
        <div class="border-t border-gray-100 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-nivayalife-muted" x-text="labels[lang].chronic_conditions"></h3>
            @if($conditions->isEmpty())
                <p class="mt-2 text-sm text-nivayalife-muted" x-text="labels[lang].no_conditions"></p>
            @else
                <ul class="mt-2 list-inside list-disc text-sm text-nivayalife-ink">
                    @foreach($conditions as $condition)
                        <li>{{ $condition->condition_name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Medications --}}
        <div class="border-t border-gray-100 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-nivayalife-muted" x-text="labels[lang].medications"></h3>
            @if($medications->isEmpty())
                <p class="mt-2 text-sm text-nivayalife-muted" x-text="labels[lang].no_medications"></p>
            @else
                <ul class="mt-2 space-y-1 text-sm text-nivayalife-ink">
                    @foreach($medications as $medication)
                        <li>{{ $medication->medicine_name }} @if($medication->dosage) <span class="text-nivayalife-muted">— {{ $medication->dosage }}</span> @endif</li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Family doctor --}}
        @if($doctor)
            <div class="border-t border-gray-100 p-6">
                <h3 class="text-xs font-bold uppercase tracking-wide text-nivayalife-muted" x-text="labels[lang].family_doctor"></h3>
                <p class="mt-2 text-sm font-medium text-nivayalife-ink">
                    {{ $doctor->name }}
                    @if($doctor->specialization) <span class="text-nivayalife-muted">— {{ $doctor->specialization }}</span> @endif
                </p>
                @if($doctor->phone)
                    <a href="tel:{{ $doctor->phone }}" class="mt-1 inline-block text-xs font-semibold text-nivayalife-green hover:underline">{{ $doctor->phone }}</a>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between border-t border-gray-100 bg-black/5 px-6 py-3 text-[11px] text-nivayalife-muted">
            <span x-text="labels[lang].issued + ' ' + @js($card->issued_at->format('M j, Y'))"></span>
            <a href="{{ route('emergency.pdf.full', $card->card_number) }}" class="font-semibold text-nivayalife-green hover:underline" x-text="labels[lang].download_pdf"></a>
        </div>
    </div>
</div>
</body>
</html>
