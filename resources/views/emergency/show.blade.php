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
<body class="min-h-screen bg-novix-cream font-sans antialiased" x-data="{ lang: 'en', labels: @js($labels) }">
<div class="mx-auto max-w-xl px-4 py-8 sm:py-12">
    <a href="{{ url('/') }}" class="mb-6 flex justify-center"><x-novix-logo /></a>

    <div class="mb-4 flex justify-center gap-2">
        <button type="button" @click="lang = 'en'" :class="lang === 'en' ? 'bg-novix-green text-white' : 'bg-white text-novix-ink'" class="rounded-full px-4 py-1.5 text-xs font-semibold shadow-sm transition">English</button>
        <button type="button" @click="lang = 'hi'" :class="lang === 'hi' ? 'bg-novix-green text-white' : 'bg-white text-novix-ink'" class="rounded-full px-4 py-1.5 text-xs font-semibold shadow-sm transition">हिंदी</button>
        <button type="button" @click="lang = 'gu'" :class="lang === 'gu' ? 'bg-novix-green text-white' : 'bg-white text-novix-ink'" class="rounded-full px-4 py-1.5 text-xs font-semibold shadow-sm transition">ગુજરાતી</button>
    </div>

    <div class="overflow-hidden rounded-novix bg-white shadow-novix">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 bg-novix-green px-6 py-4">
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
            <x-avatar :photo-path="$member->photo_path" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()"
                size="h-20 w-20" rounded="rounded-2xl" class="flex-shrink-0" />
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold text-novix-ink">{{ $member->full_name }}</h1>
                <p class="text-sm text-novix-muted">
                    <span x-text="labels[lang].age"></span>:
                    {{ $member->age() !== null ? $member->age() : '—' }}
                    <span x-show="{{ $member->age() !== null ? 'true' : 'false' }}" x-text="labels[lang].years"></span>
                    &middot; {{ $member->gender ? Str::headline($member->gender) : '—' }}
                </p>
            </div>
        </div>

        {{-- Blood group — the single most scanned-for fact --}}
        <div class="flex items-center justify-between bg-novix-pink/20 px-6 py-5">
            <span class="text-sm font-bold uppercase tracking-wide text-novix-pink-dark" x-text="labels[lang].blood_group"></span>
            <span class="text-4xl font-extrabold text-novix-pink-dark">{{ $member->blood_group ?? '—' }}</span>
        </div>

        {{-- Allergies --}}
        <div class="border-t border-gray-100 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted" x-text="labels[lang].allergies"></h3>
            @if($allergies->isEmpty())
                <p class="mt-2 text-sm text-novix-muted" x-text="labels[lang].no_allergies"></p>
            @else
                <ul class="mt-2 space-y-1.5">
                    @foreach($allergies as $allergy)
                        @php($severityClass = match($allergy->severity) {
                            'severe' => 'bg-novix-pink-dark text-white',
                            'moderate' => 'bg-novix-yellow/40 text-novix-ink',
                            default => 'bg-gray-100 text-novix-ink',
                        })
                        <li class="flex items-center gap-2 text-sm">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $severityClass }}" x-text="labels[lang].{{ $allergy->severity }}"></span>
                            <span class="font-medium text-novix-ink">{{ $allergy->allergen_name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Chronic conditions --}}
        <div class="border-t border-gray-100 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted" x-text="labels[lang].chronic_conditions"></h3>
            @if($conditions->isEmpty())
                <p class="mt-2 text-sm text-novix-muted" x-text="labels[lang].no_conditions"></p>
            @else
                <ul class="mt-2 list-inside list-disc text-sm text-novix-ink">
                    @foreach($conditions as $condition)
                        <li>{{ $condition->condition_name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Medications --}}
        <div class="border-t border-gray-100 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted" x-text="labels[lang].medications"></h3>
            @if($medications->isEmpty())
                <p class="mt-2 text-sm text-novix-muted" x-text="labels[lang].no_medications"></p>
            @else
                <ul class="mt-2 space-y-1 text-sm text-novix-ink">
                    @foreach($medications as $medication)
                        <li>{{ $medication->medicine_name }} @if($medication->dosage) <span class="text-novix-muted">— {{ $medication->dosage }}</span> @endif</li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Emergency contact --}}
        <div class="border-t border-gray-100 bg-novix-mint/30 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted" x-text="labels[lang].emergency_contact"></h3>
            @if($member->emergency_contact_phone)
                <a href="tel:{{ $member->emergency_contact_phone }}" class="mt-2 flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm transition hover:shadow-novix-sm">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-novix-green text-white" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-novix-ink">{{ $member->emergency_contact_name ?? '—' }}</span>
                        <span class="block text-xs text-novix-muted">{{ $member->emergency_contact_phone }} @if($member->emergency_contact_relation) &middot; {{ Str::headline($member->emergency_contact_relation) }} @endif</span>
                    </span>
                    <span class="flex-shrink-0 rounded-full bg-novix-green px-3 py-1.5 text-xs font-bold text-white" x-text="labels[lang].call"></span>
                </a>
            @else
                <p class="mt-2 text-sm text-novix-muted" x-text="labels[lang].no_contact"></p>
            @endif
        </div>

        {{-- Family doctor --}}
        @if($doctor)
            <div class="border-t border-gray-100 p-6">
                <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted" x-text="labels[lang].family_doctor"></h3>
                <p class="mt-2 text-sm font-medium text-novix-ink">
                    {{ $doctor->name }}
                    @if($doctor->specialization) <span class="text-novix-muted">— {{ $doctor->specialization }}</span> @endif
                </p>
                @if($doctor->phone)
                    <a href="tel:{{ $doctor->phone }}" class="mt-1 inline-block text-xs font-semibold text-novix-green hover:underline">{{ $doctor->phone }}</a>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-between border-t border-gray-100 bg-black/5 px-6 py-3 text-[11px] text-novix-muted">
            <span x-text="labels[lang].issued + ' ' + @js($card->issued_at->format('M j, Y'))"></span>
            <a href="{{ route('emergency.pdf.full', $card->card_number) }}" class="font-semibold text-novix-green hover:underline" x-text="labels[lang].download_pdf"></a>
        </div>
    </div>
</div>
</body>
</html>
