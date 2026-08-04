@php
    // Single source for the FAQ — rendered on the page and emitted as
    // JSON-LD so search engines can show these as rich results.
    $faqs = [
        ['q' => 'Is my medical data secure?', 'a' => 'Yes. Every report is encrypted, stored securely, and only visible to you and the family members or doctors you explicitly choose to share it with.'],
        ['q' => 'Does the AI replace my doctor?', 'a' => 'No. Nivaya Life\'s AI explains and organizes your reports in plain language, but it never diagnoses conditions or prescribes treatment. Always consult a qualified doctor for medical decisions.'],
        ['q' => 'What languages are supported?', 'a' => 'Report explanations are available in multiple languages, including English, Hindi, and Gujarati, so every family member can understand their own records in the language they\'re most comfortable with.'],
        ['q' => 'Is Nivaya Life free to use?', 'a' => 'Yes — Nivaya Life is free to get started, so your whole family can build a digital health record without any upfront cost.'],
        ['q' => 'Can I manage my parents\' or children\'s records too?', 'a' => 'Yes. Nivaya Life is built for families — create a profile for each parent, child, or grandparent, and manage all of their reports and medications from a single account.'],
        ['q' => 'What happens to the paper reports I already have?', 'a' => 'Just take a photo or upload a PDF. Nivaya Life\'s scanner reads printed reports and prescriptions automatically, so you don\'t have to type anything in by hand.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Nivaya Life replaces paper medical records with one secure online health record for your whole family — explained in plain language by AI.">

    <title>Nivaya Life — No more paper. No more confusion.</title>

    {{-- Social share cards --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Nivaya Life">
    <meta property="og:title" content="Nivaya Life — No more paper. No more confusion.">
    <meta property="og:description" content="One secure online health record for your whole family — organized automatically and explained in plain language by AI.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ url('/icons/icon-512.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Nivaya Life — No more paper. No more confusion.">
    <meta name="twitter:description" content="One secure online health record for your whole family — organized automatically and explained in plain language by AI.">
    <meta name="twitter:image" content="{{ url('/icons/icon-512.png') }}">

    {{-- FAQ rich-result structured data --}}
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ])->all(),
    ], JSON_UNESCAPED_SLASHES) !!}</script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @include('partials.pwa-meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="novix-surface bg-novix-cream font-sans text-novix-ink antialiased">

    {{-- ============ HEADER ============ --}}
    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-50 border-b border-novix-green/10 bg-novix-cream/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a href="/" aria-label="Nivaya Life home"><x-novix-logo size="sm" /></a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-novix-ink/80 md:flex" aria-label="Primary">
                <a href="#features" class="hover:text-novix-green">Features</a>
                <a href="#how-it-works" class="hover:text-novix-green">How it works</a>
                <a href="#privacy" class="hover:text-novix-green">Privacy</a>
                <a href="#faq" class="hover:text-novix-green">FAQ</a>
            </nav>

            <div class="hidden items-center gap-3 md:flex">
                <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-novix-ink hover:text-novix-green focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-novix-green">
                    Log in
                </a>
                <a href="{{ route('register') }}" class="rounded-lg bg-novix-green px-5 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-novix-green">
                    Get Started
                </a>
            </div>

            <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-controls="mobile-menu"
                class="inline-flex items-center justify-center rounded-lg p-2 text-novix-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-novix-green md:hidden">
                <span class="sr-only">Toggle menu</span>
                <svg x-show="!mobileOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="mobileOpen" x-cloak class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div id="mobile-menu" x-show="mobileOpen" x-cloak x-transition
            class="border-t border-novix-green/10 bg-novix-cream px-6 py-4 md:hidden">
            <nav class="flex flex-col gap-4 text-sm font-medium" aria-label="Mobile">
                <a href="#features" @click="mobileOpen = false" class="hover:text-novix-green">Features</a>
                <a href="#how-it-works" @click="mobileOpen = false" class="hover:text-novix-green">How it works</a>
                <a href="#privacy" @click="mobileOpen = false" class="hover:text-novix-green">Privacy</a>
                <a href="#faq" @click="mobileOpen = false" class="hover:text-novix-green">FAQ</a>
                <div class="mt-2 flex flex-col gap-3 border-t border-novix-green/10 pt-4">
                    <a href="{{ route('login') }}" class="text-center font-semibold text-novix-ink">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-novix-green px-5 py-2.5 text-center font-semibold text-white">Get Started</a>
                </div>
            </nav>
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-novix-mint/60 blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/3 -left-32 h-80 w-80 rounded-full bg-novix-gold/15 blur-3xl"></div>

        {{-- On phones the hero fills the viewport and centres like an app's
             opening screen (the preview card sits below the fold); on lg the
             web layout takes over with the two-column grid. --}}
        <div class="relative mx-auto grid min-h-[calc(100svh-4.75rem)] max-w-7xl content-center items-center gap-12 px-6 py-16 lg:min-h-0 lg:grid-cols-2 lg:content-normal lg:gap-16 lg:px-8 lg:py-24">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-novix-green shadow-novix-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-novix-gold" aria-hidden="true"></span>
                    100% digital, zero paper
                </span>

                <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-novix-ink sm:text-5xl">
                    No more paper.<br>
                    No more confusion.<br>
                    Just your family's health,<br>
                    <span class="relative inline-block text-novix-green italic">
                        organized online.
                        <svg class="absolute -bottom-2 left-0 w-full text-novix-gold" viewBox="0 0 200 9" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M2 7c50-5 148-5 196-2" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </span>
                </h1>

                <p class="mt-6 max-w-md text-lg text-novix-ink/70">
                    Nivaya Life stores every family member's medical reports online and uses AI to explain
                    them in plain language — so nothing is scattered, and nothing is confusing.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a href="{{ route('register') }}"
                        class="rounded-xl bg-novix-green px-6 py-3 text-sm font-semibold text-white shadow-novix transition hover:bg-novix-green-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-novix-green">
                        Get Started
                    </a>
                    <a href="#how-it-works"
                        class="rounded-xl border border-novix-green/20 bg-white px-6 py-3 text-sm font-semibold text-novix-green transition hover:bg-novix-mint/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-novix-green">
                        See how it works
                    </a>
                </div>

                {{-- App-style scroll hint — phones only, where the hero fills the screen. --}}
                <div class="mt-12 flex justify-center lg:hidden" aria-hidden="true">
                    <svg class="h-5 w-5 animate-bounce text-novix-green/50" viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>

            {{-- Miniature of the real dashboard — sample data, not a real user --}}
            <div class="relative">
                <p class="mb-2 text-center text-xs font-medium uppercase tracking-wide text-novix-muted lg:text-left">
                    Preview with sample data
                </p>
                <div class="rounded-novix bg-white p-4 shadow-novix sm:p-5" aria-hidden="true">
                    {{-- identity card --}}
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-novix-green to-novix-green-dark p-4 text-white">
                        <div class="pointer-events-none absolute -right-10 -top-12 h-32 w-32 rounded-full bg-white/5"></div>
                        <div class="relative flex items-center gap-3">
                            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full border-2 border-white/25 bg-white/10">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/><path d="M5 21v-1a7 7 0 0 1 14 0v1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">Aarav Shah</p>
                                <p class="font-mono text-[10px] tracking-tight text-novix-gold-light">NVX-8FK2M &middot; Self</p>
                            </div>
                            <span class="rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-bold text-novix-green">Emergency card</span>
                        </div>
                        <div class="relative mt-3 grid grid-cols-4 gap-1.5 text-center">
                            @foreach ([['Age', '34 yrs'], ['Blood', 'O+'], ['BMI', '22.4'], ['Sex', 'Male']] as [$label, $value])
                                <div class="rounded-lg bg-white/10 px-1 py-1.5">
                                    <p class="text-[8px] font-semibold uppercase tracking-wider text-white/60">{{ $label }}</p>
                                    <p class="text-[11px] font-bold">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- today's doses --}}
                    <div class="mt-3 rounded-2xl border border-novix-green/10 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-novix-muted">Today's doses</p>
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-novix-ink">Metformin <span class="font-normal text-novix-muted">· 500 mg</span></p>
                            </div>
                            <div class="flex gap-1">
                                <span class="rounded-full bg-novix-mint px-2 py-0.5 text-[9px] font-bold text-novix-green line-through">08:00</span>
                                <span class="rounded-full bg-novix-cream px-2 py-0.5 text-[9px] font-bold text-novix-muted">14:00</span>
                                <span class="rounded-full bg-novix-cream px-2 py-0.5 text-[9px] font-bold text-novix-muted">21:00</span>
                            </div>
                        </div>
                    </div>

                    {{-- report + AI summary --}}
                    <div class="mt-3 rounded-2xl border border-novix-green/10 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-novix-cream text-xs">🩸</span>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-semibold text-novix-ink">Blood Test</p>
                                    <p class="text-[10px] text-novix-muted">Explained in plain language by AI</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-novix-mint px-2 py-0.5 text-[9px] font-semibold text-novix-green">Ready</span>
                        </div>
                        <p class="mt-2 rounded-lg bg-novix-green/5 px-2.5 py-2 text-[10px] leading-relaxed text-novix-green">
                            "Haemoglobin is in the normal range. Vitamin D is slightly low — worth discussing supplements with your doctor."
                        </p>
                    </div>
                </div>

                <div class="pointer-events-none absolute -bottom-6 -left-6 h-24 w-24 rounded-full bg-novix-gold/25 blur-2xl"></div>
            </div>
        </div>
    </section>

    {{-- ============ PROBLEM ============ --}}
    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-8" aria-labelledby="problem-heading">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="problem-heading" class="text-3xl font-extrabold text-novix-ink">Paper records don't work anymore</h2>
            <p class="mt-3 text-novix-ink/70">Every family runs into the same problems managing health records the old way.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['icon' => 'M9 13h6m-6 4h4m1-15H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-6-6Z', 'title' => 'Reports scattered everywhere', 'body' => 'Hospital folders, WhatsApp downloads, old envelopes — records end up spread across a dozen places, none of them searchable.'],
                ['icon' => 'M9 12h.01M15 12h.01M9.5 16a3.5 3.5 0 0 0 5 0M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z', 'title' => 'Doctors missing your history', 'body' => 'Without past reports in hand, a new doctor starts from zero — even if the same test was done six months ago.'],
                ['icon' => 'M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'title' => 'Managing the whole family manually', 'body' => 'Parents, kids, grandparents — everyone\'s reports pile up in different places, with no single view of who has what.'],
                ['icon' => 'M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'title' => 'Repeating tests you\'ve already done', 'body' => 'When an old report can\'t be found in time, the easiest fix is often just redoing the test — more cost, more waiting.'],
            ] as $problem)
                <div class="rounded-novix bg-white p-6 shadow-novix-sm">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-novix-mint text-novix-green" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $problem['icon'] }}"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-novix-ink">{{ $problem['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-novix-ink/70">{{ $problem['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ HOW IT WORKS ============ --}}
    <section id="how-it-works" class="scroll-mt-20 py-20" aria-labelledby="how-heading">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 id="how-heading" class="text-3xl font-extrabold text-novix-ink">How Nivaya Life works</h2>
                <p class="mt-3 text-novix-ink/70">Four steps from a drawer full of paper to a health record everyone can actually use.</p>
            </div>

            <ol class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['step' => '1', 'title' => 'Create your family health account', 'body' => 'Set up one account and add every family member — parents, spouse, kids — each with their own record.'],
                    ['step' => '2', 'title' => 'Upload reports and prescriptions', 'body' => 'Snap a photo or upload a PDF of any report, prescription, or bill. Nivaya Life files it under the right person.'],
                    ['step' => '3', 'title' => 'AI organizes and explains them', 'body' => 'Nivaya Life sorts each report into the right category and explains what it means in plain language.'],
                    ['step' => '4', 'title' => 'Share a summary with your doctor', 'body' => 'Generate a secure link or a clean summary you can hand to any doctor in one tap.'],
                ] as $step)
                    <li class="relative rounded-novix bg-white p-6 shadow-novix-sm">
                        <span class="text-4xl font-extrabold text-novix-gold/60" aria-hidden="true">{{ $step['step'] }}</span>
                        <h3 class="mt-3 font-semibold text-novix-ink">{{ $step['title'] }}</h3>
                        <p class="mt-1.5 text-sm text-novix-ink/70">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============ FEATURES ============ --}}
    <section id="features" class="scroll-mt-20 mx-auto max-w-7xl px-6 py-20 lg:px-8" aria-labelledby="features-heading">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="features-heading" class="text-3xl font-extrabold text-novix-ink">Everything your family's health needs</h2>
            <p class="mt-3 text-novix-ink/70">Built to replace the folder of paperwork every family accumulates.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['icon' => 'M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'title' => 'Login & Family Profiles', 'body' => 'One account for the whole family. Add parents, spouse, and children as separate profiles, each with their own health history.'],
                ['icon' => 'M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 1 0-8 0v4h8Z', 'title' => 'Secure Health Locker', 'body' => 'Every report, prescription, and bill stored securely online, organized by family member.'],
                ['icon' => 'M9 12h6m-6 4h6m1 5H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l4.414 4.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z', 'title' => 'Report Upload & Auto Category', 'body' => 'Upload a photo or PDF and Nivaya Life automatically sorts it — blood test, prescription, X-ray, insurance, or bill.'],
                ['icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a4 4 0 1 1 5.657 0A4 4 0 0 1 12 18a4 4 0 0 1-2.828-1.464Z', 'title' => 'AI Report Explanation', 'body' => 'Get a plain-language summary of any report, with abnormal values called out clearly.'],
                ['icon' => 'M4 7V5a2 2 0 0 1 2-2h2M4 17v2a2 2 0 0 0 2 2h2m8-16h2a2 2 0 0 1 2 2v2m-4 12h2a2 2 0 0 0 2-2v-2M8 12h8', 'title' => 'AI Scanner / OCR', 'body' => 'Scan a printed report or prescription and Nivaya Life extracts the text automatically — no manual typing.'],
                ['icon' => 'M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'title' => 'Personal Health Timeline', 'body' => 'See every family member\'s reports and medications laid out chronologically, so nothing gets lost.'],
                ['icon' => 'M8.7 10.7 15.3 7.3M8.7 13.3l6.6 3.4M18 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z', 'title' => 'Secure Report Sharing', 'body' => 'Generate a time-limited link to share one report with a doctor — no account or login required on their end.'],
                ['icon' => 'M12 21s-7-4.35-9.5-8.5C.83 9.1 2.3 5.5 6 5c2-.27 3.5 1 4 2 .5-1 2-2.27 4-2 3.7.5 5.17 4.1 3.5 7.5C19 16.65 12 21 12 21Z', 'title' => 'Emergency Medical Card', 'body' => 'A public, no-login page with blood group, allergies, and emergency contacts, ready the moment it\'s needed.'],
                ['icon' => 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.66V5a2 2 0 1 0-4 0v.34C7.67 6.17 6 8.39 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9', 'title' => 'Medicine Reminders', 'body' => 'Scheduled reminders for every medication, for every family member, so doses don\'t get missed.'],
                ['icon' => 'M3 12h18M12 3a15 15 0 0 1 4 9 15 15 0 0 1-4 9 15 15 0 0 1-4-9 15 15 0 0 1 4-9ZM3 12a9 9 0 0 1 9-9M21 12a9 9 0 0 1-9 9', 'title' => 'Multilingual Support', 'body' => 'Report explanations available in multiple languages, so every family member can actually understand them.'],
            ] as $feature)
                <div class="rounded-novix bg-white p-6 shadow-novix-sm transition hover:shadow-novix">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-novix-green text-white" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $feature['icon'] }}"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-novix-ink">{{ $feature['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-novix-ink/70">{{ $feature['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ TRUST & PRIVACY ============ --}}
    <section id="privacy" class="scroll-mt-20 py-20" aria-labelledby="privacy-heading">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 id="privacy-heading" class="text-3xl font-extrabold text-novix-ink">Your data, protected and controlled by you</h2>
                <p class="mt-3 text-novix-ink/70">Nivaya Life is built to earn trust with real families managing real medical information.</p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 1 0-8 0v4h8Z', 'title' => 'Encrypted storage', 'body' => 'Every report is encrypted at rest and in transit — only you and the people you choose can see it.'],
                    ['icon' => 'M8.7 10.7 15.3 7.3M8.7 13.3l6.6 3.4M18 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z', 'title' => 'You control sharing', 'body' => 'Share a report for a set time window — you decide who sees what, and for how long. Revoke access anytime.'],
                    ['icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a4 4 0 1 1 5.657 0A4 4 0 0 1 12 18a4 4 0 0 1-2.828-1.464Z', 'title' => 'AI explains, it doesn\'t diagnose', 'body' => 'Nivaya Life\'s AI reads and organizes your reports and explains them in plain language. It never makes a diagnosis or prescribes treatment.'],
                ] as $item)
                    <div class="rounded-novix bg-white p-6 shadow-novix-sm">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-novix-mint text-novix-green" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $item['icon'] }}"/></svg>
                        </span>
                        <h3 class="mt-4 font-semibold text-novix-ink">{{ $item['title'] }}</h3>
                        <p class="mt-1.5 text-sm text-novix-ink/70">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-novix border-2 border-novix-green/15 bg-white px-6 py-5 text-center shadow-novix-sm">
                <p class="font-medium text-novix-ink">
                    "Nivaya Life is a personal health companion. It explains and organizes your records —
                    it does not diagnose, prescribe, or replace your doctor."
                </p>
            </div>
        </div>
    </section>

    {{-- ============ HEALTH BREAK (interactive) ============ --}}
    <section class="mx-auto max-w-5xl px-6 py-20 lg:px-8" aria-labelledby="break-heading">
        <x-confetti />
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="break-heading" class="text-3xl font-extrabold text-novix-ink">Take a 10-second health break</h2>
            <p class="mt-3 text-novix-ink/70">Two little things to try before you scroll on.</p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            {{-- Tap-the-heart: a synthesized lub-dub heartbeat on every tap,
                 and a confetti burst at ten beats. Pure WebAudio — no audio
                 file, nothing to download, silent until the visitor asks. --}}
            <div class="flex flex-col items-center justify-center rounded-novix border-t-2 border-novix-gold/50 bg-white p-8 text-center shadow-novix-sm"
                x-data="{
                    beats: 0,
                    bumping: false,
                    tap() {
                        this.beats++;
                        this.bumping = true;
                        setTimeout(() => this.bumping = false, 200);
                        try {
                            const C = window.AudioContext || window.webkitAudioContext;
                            this._ctx = this._ctx || new C();
                            const c = this._ctx, t = c.currentTime;
                            const thump = (at, freq, gain) => {
                                const o = c.createOscillator(), g = c.createGain();
                                o.type = 'sine';
                                o.frequency.setValueAtTime(freq, at);
                                g.gain.setValueAtTime(0.0001, at);
                                g.gain.exponentialRampToValueAtTime(gain, at + 0.02);
                                g.gain.exponentialRampToValueAtTime(0.0001, at + 0.25);
                                o.connect(g); g.connect(c.destination);
                                o.start(at); o.stop(at + 0.3);
                            };
                            thump(t, 60, 0.5);
                            thump(t + 0.22, 48, 0.35);
                        } catch (e) { /* audio blocked — the visual bump still lands */ }
                        if (this.beats === 10) window.dispatchEvent(new CustomEvent('novix:confetti'));
                    },
                }">
                <button type="button" @click="tap()" aria-label="Tap to hear a heartbeat"
                    class="group relative flex h-24 w-24 items-center justify-center rounded-full bg-novix-pink/20 transition hover:bg-novix-pink/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-novix-green"
                    :class="bumping ? 'scale-110' : 'scale-100'" style="transition: transform 0.15s ease">
                    <svg class="h-12 w-12 text-novix-pink-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 21s-7-4.35-9.5-8.5C.83 9.1 2.3 5.5 6 5c2-.27 3.5 1 4 2 .5-1 2-2.27 4-2 3.7.5 5.17 4.1 3.5 7.5C19 16.65 12 21 12 21Z"/>
                    </svg>
                </button>
                <h3 class="mt-5 font-semibold text-novix-ink">Tap the heart</h3>
                <p class="mt-1 text-sm text-novix-ink/60">Hear a real lub-dub. Ten beats earns a small celebration.</p>
                <p class="mt-3 h-5 text-xs font-bold text-novix-gold" x-cloak x-show="beats > 0"
                    x-text="beats < 10 ? beats + (beats === 1 ? ' beat' : ' beats') + ' with you' : 'Your heart, our priority 💛'"></p>
            </div>

            {{-- Live BMI check — the exact gauge component from inside the app,
                 so the landing page demos the real product, not a mockup. --}}
            <div class="rounded-novix border-t-2 border-novix-gold/50 bg-white p-8 shadow-novix-sm">
                <h3 class="text-center font-semibold text-novix-ink">Try a live BMI check</h3>
                <p class="mt-1 text-center text-sm text-novix-ink/60">This is the same gauge you'll see on your dashboard.</p>
                <div class="mt-5">
                    <x-bmi-gauge :size="170" hole-class="bg-white" />
                </div>
                <p class="mt-4 text-center text-xs text-novix-ink/50">Stays on this page — nothing is saved or sent anywhere.</p>
            </div>
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section id="faq" class="scroll-mt-20 mx-auto max-w-3xl px-6 py-20 lg:px-8" aria-labelledby="faq-heading">
        <div class="text-center">
            <h2 id="faq-heading" class="text-3xl font-extrabold text-novix-ink">Frequently asked questions</h2>
        </div>

        <div class="mt-10 divide-y divide-novix-green/10 rounded-novix border border-novix-green/10 bg-white px-6 shadow-novix-sm">
            @foreach ($faqs as $index => $faq)
                <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" class="py-2">
                    <h3>
                        <button type="button" @click="open = !open" :aria-expanded="open.toString()" :aria-controls="'faq-panel-{{ $index }}'"
                            class="flex w-full items-center justify-between gap-4 py-4 text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-novix-green">
                            <span class="font-semibold text-novix-ink">{{ $faq['q'] }}</span>
                            <svg class="h-5 w-5 shrink-0 text-novix-green transition-transform" :class="open ? 'rotate-45' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                            </svg>
                        </button>
                    </h3>
                    <div :id="'faq-panel-{{ $index }}'" x-show="open" x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0">
                        <p class="pb-5 pr-8 text-sm text-novix-ink/70">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ FINAL CTA ============ --}}
    <section class="px-6 pb-20 lg:px-8">
        <div class="relative mx-auto max-w-4xl overflow-hidden rounded-novix bg-novix-green px-8 py-14 text-center shadow-novix sm:px-16">
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-novix-gold/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-10 h-48 w-48 rounded-full bg-white/5" aria-hidden="true"></div>
            <h2 class="relative text-3xl font-extrabold text-white">Ready to leave the paper behind?</h2>
            <p class="mx-auto mt-3 max-w-md text-white/80">
                Create your family's secure health record today — free to get started.
            </p>
            <a href="{{ route('register') }}"
                class="mt-8 inline-block rounded-xl bg-white px-8 py-3 text-sm font-semibold text-novix-green shadow-novix-sm transition hover:bg-novix-cream focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                Get Started
            </a>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
        <div class="flex flex-col items-center gap-8 border-b border-novix-green/10 pb-8 sm:flex-row sm:items-start sm:justify-between">
            <div class="max-w-xs text-center sm:text-left">
                <a href="/" class="flex justify-center sm:justify-start" aria-label="Nivaya Life home"><x-novix-logo size="sm" /></a>
                <p class="mt-2 text-sm text-novix-ink/60">Your family's health records, online — no more paper.</p>
            </div>

            <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-novix-ink/70 sm:justify-end" aria-label="Footer">
                <a href="#features" class="hover:text-novix-green">Features</a>
                <a href="#how-it-works" class="hover:text-novix-green">How it works</a>
                <a href="#privacy" class="hover:text-novix-green">Privacy</a>
                <a href="#faq" class="hover:text-novix-green">FAQ</a>
                <a href="mailto:hello@nivayalife.example" class="hover:text-novix-green">hello@nivayalife.example</a>
            </nav>
        </div>

        <div class="flex flex-col items-center justify-between gap-3 pt-6 text-center text-xs text-novix-ink/50 sm:flex-row sm:text-left">
            <p>&copy; {{ date('Y') }} Nivaya Life. Not a diagnostic tool — always consult a qualified doctor.</p>
            <p class="flex gap-4">
                <a href="{{ url('/terms') }}" class="hover:text-novix-green">Terms of Service</a>
                <a href="{{ url('/privacy') }}" class="hover:text-novix-green">Privacy Policy</a>
            </p>
        </div>
    </footer>

</body>
</html>
