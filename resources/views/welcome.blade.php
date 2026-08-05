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
                {{-- The mark leads the hero, so the brand registers before the headline. --}}
                <img src="/icons/icon-192.png?v=2" alt="" width="56" height="56"
                    class="mb-5 h-14 w-14 rounded-2xl shadow-novix-sm" aria-hidden="true">

                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-novix-green shadow-novix-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-novix-gold" aria-hidden="true"></span>
                    100% digital, zero paper
                </span>

                <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-novix-ink sm:text-5xl lg:text-6xl">
                    No more paper.<br>
                    No more confusion.<br>
                    Just your family's health,<br>
                    <span class="relative inline-block novix-text-gold italic">
                        organized online.
                        <svg class="absolute -bottom-2 left-0 w-full text-novix-gold/70" viewBox="0 0 200 9" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M2 7c50-5 148-5 196-2" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
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

    {{-- ============ CAPABILITY BAND ============ --}}
    {{-- Sits directly under the hero on desktop: the four things the product
         actually does, stated plainly, before any argument is made. --}}
    <section class="relative px-6 lg:px-8" aria-label="What Nivaya Life does">
        <div class="mx-auto max-w-7xl">
            <div class="novix-rule-gold" aria-hidden="true"></div>
            <div class="grid gap-px overflow-hidden bg-novix-green/10 py-px sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['n' => '10', 'suffix' => '+', 'label' => 'Report types recognized', 'sub' => 'Blood work to X-rays, sorted automatically'],
                    ['n' => '3', 'suffix' => '', 'label' => 'Languages explained in', 'sub' => 'English, Hindi and Gujarati'],
                    ['n' => '1', 'suffix' => '', 'label' => 'Account for the family', 'sub' => 'Parents, partner, children, grandparents'],
                    ['n' => '0', 'suffix' => '', 'label' => 'Paper to keep', 'sub' => 'Photograph it once and let it go'],
                ] as $stat)
                    <div class="bg-novix-cream px-6 py-8 text-center" data-reveal style="transition-delay:{{ $loop->index * 70 }}ms">
                        <p class="text-4xl font-extrabold tracking-tight text-novix-green">
                            <span data-count-to="{{ $stat['n'] }}" data-count-suffix="{{ $stat['suffix'] }}">0</span>
                        </p>
                        <p class="mt-2 text-sm font-bold text-novix-ink">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-novix-ink/60">{{ $stat['sub'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="novix-rule-gold" aria-hidden="true"></div>
        </div>
    </section>

    {{-- ============ PROBLEM ============ --}}
    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-8" aria-labelledby="problem-heading">
        <div class="mx-auto max-w-2xl text-center">
            <h2 data-reveal id="problem-heading" class="text-3xl font-extrabold text-novix-ink">Paper records don't work anymore</h2>
            <p class="mt-3 text-novix-ink/70">Every family runs into the same problems managing health records the old way.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['icon' => 'M9 13h6m-6 4h4m1-15H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-6-6Z', 'title' => 'Reports scattered everywhere', 'body' => 'Hospital folders, WhatsApp downloads, old envelopes — records end up spread across a dozen places, none of them searchable.'],
                ['icon' => 'M9 12h.01M15 12h.01M9.5 16a3.5 3.5 0 0 0 5 0M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z', 'title' => 'Doctors missing your history', 'body' => 'Without past reports in hand, a new doctor starts from zero — even if the same test was done six months ago.'],
                ['icon' => 'M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'title' => 'Managing the whole family manually', 'body' => 'Parents, kids, grandparents — everyone\'s reports pile up in different places, with no single view of who has what.'],
                ['icon' => 'M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'title' => 'Repeating tests you\'ve already done', 'body' => 'When an old report can\'t be found in time, the easiest fix is often just redoing the test — more cost, more waiting.'],
            ] as $problem)
                <div class="rounded-novix bg-white p-6 shadow-novix-sm transition hover:-translate-y-0.5 hover:shadow-novix" data-reveal style="transition-delay:{{ $loop->index * 70 }}ms">
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
                <h2 data-reveal id="how-heading" class="text-3xl font-extrabold text-novix-ink">How Nivaya Life works</h2>
                <p class="mt-3 text-novix-ink/70">Four steps from a drawer full of paper to a health record everyone can actually use.</p>
            </div>

            <ol class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['step' => '1', 'title' => 'Create your family health account', 'body' => 'Set up one account and add every family member — parents, spouse, kids — each with their own record.'],
                    ['step' => '2', 'title' => 'Upload reports and prescriptions', 'body' => 'Snap a photo or upload a PDF of any report, prescription, or bill. Nivaya Life files it under the right person.'],
                    ['step' => '3', 'title' => 'AI organizes and explains them', 'body' => 'Nivaya Life sorts each report into the right category and explains what it means in plain language.'],
                    ['step' => '4', 'title' => 'Share a summary with your doctor', 'body' => 'Generate a secure link or a clean summary you can hand to any doctor in one tap.'],
                ] as $step)
                    <li class="relative rounded-novix bg-white p-6 shadow-novix-sm transition hover:-translate-y-0.5 hover:shadow-novix" data-reveal style="transition-delay:{{ $loop->index * 70 }}ms">
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
            <h2 data-reveal id="features-heading" class="text-3xl font-extrabold text-novix-ink">Everything your family's health needs</h2>
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
                <div class="novix-gold-edge group rounded-novix bg-white p-6 shadow-novix-sm transition hover:-translate-y-1 hover:shadow-novix" data-reveal style="transition-delay:{{ $loop->index * 55 }}ms">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-novix-green text-white transition group-hover:scale-110" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $feature['icon'] }}"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-novix-ink">{{ $feature['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-novix-ink/70">{{ $feature['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ MULTILINGUAL DEMO ============ --}}
    <section class="mx-auto max-w-5xl px-6 py-20 lg:px-8" aria-labelledby="lang-heading">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <h2 id="lang-heading" class="text-3xl font-extrabold text-novix-ink">Read in the language you think in</h2>
            <p class="mt-3 text-novix-ink/70">
                The same report, explained plainly — switch languages and watch it change.
            </p>
        </div>

        <div class="mx-auto mt-10 max-w-2xl overflow-hidden rounded-novix border-t-2 border-novix-gold/50 bg-white shadow-novix"
            data-reveal
            x-data="{
                lang: 'en',
                langs: {
                    en: { label: 'English', text: 'Your haemoglobin is in the normal range. Vitamin D is a little low — worth discussing supplements with your doctor.' },
                    hi: { label: 'हिंदी', text: 'आपका हीमोग्लोबिन सामान्य सीमा में है। विटामिन डी थोड़ा कम है — अपने डॉक्टर से सप्लीमेंट के बारे में बात करें।' },
                    gu: { label: 'ગુજરાતી', text: 'તમારું હીમોગ્લોબિન સામાન્ય શ્રેણીમાં છે. વિટામિન ડી થોડું ઓછું છે — તમારા ડૉક્ટર સાથે સપ્લિમેન્ટ વિશે વાત કરો.' },
                },
            }">
            <div class="flex items-center justify-between gap-3 border-b border-novix-green/10 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-novix-cream text-base" aria-hidden="true">🩸</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-novix-ink">Blood Test &middot; AI summary</p>
                        <p class="text-xs text-novix-muted">Sample report</p>
                    </div>
                </div>
                <div class="flex flex-shrink-0 gap-1 rounded-full bg-novix-cream p-1" role="group" aria-label="Summary language">
                    <template x-for="(meta, code) in langs" :key="code">
                        <button type="button" @click="lang = code"
                            class="rounded-full px-3 py-1 text-xs font-bold transition"
                            :class="lang === code ? 'bg-novix-green text-white shadow-novix-sm' : 'text-novix-muted hover:text-novix-green'"
                            :aria-pressed="(lang === code).toString()"
                            x-text="meta.label"></button>
                    </template>
                </div>
            </div>
            <div class="p-6">
                <p class="min-h-[3.5rem] text-[15px] leading-relaxed text-novix-ink" x-text="langs[lang].text"></p>
                <p class="mt-4 border-t border-novix-green/10 pt-3 text-xs text-novix-ink/50">
                    Illustrative example. Nivaya Life explains reports — it never diagnoses.
                </p>
            </div>
        </div>
    </section>

    {{-- ============ PAPER VS NIVAYA ============ --}}
    <section class="mx-auto max-w-5xl px-6 pb-20 lg:px-8" aria-labelledby="compare-heading">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <h2 id="compare-heading" class="text-3xl font-extrabold text-novix-ink">The difference in practice</h2>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            <div class="rounded-novix border border-novix-ink/10 bg-white/60 p-7" data-reveal>
                <h3 class="flex items-center gap-2 font-bold text-novix-ink/70">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-novix-ink/5 text-novix-ink/50" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M9 13h6m-6 4h4m1-15H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    </span>
                    With paper
                </h3>
                <ul class="mt-4 space-y-3 text-sm text-novix-ink/60">
                    @foreach ([
                        'Hunting through folders before every appointment',
                        'Reports fading, tearing, or going missing',
                        'Medical terms you have to look up yourself',
                        'Repeating a test because the old one cannot be found',
                        'Nothing at hand in an emergency',
                    ] as $line)
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-novix-ink/25" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                            {{ $line }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-novix border-t-2 border-novix-gold/50 bg-white p-7 shadow-novix" data-reveal style="transition-delay:120ms">
                <h3 class="flex items-center gap-2 font-bold text-novix-green">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-novix-mint text-novix-green" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 5v6c0 5 3.4 9 8 11 4.6-2 8-6 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    </span>
                    With Nivaya Life
                </h3>
                <ul class="mt-4 space-y-3 text-sm text-novix-ink/80">
                    @foreach ([
                        'Every report for every family member, in one place',
                        'Stored securely online — nothing to lose or damage',
                        'Plain-language explanations in your own language',
                        'Search your whole history in seconds',
                        'An emergency card ready before you need it',
                    ] as $line)
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $line }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ============ TRUST & PRIVACY ============ --}}
    <section id="privacy" class="scroll-mt-20 py-20" aria-labelledby="privacy-heading">
        <div class="mx-auto max-w-5xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 data-reveal id="privacy-heading" class="text-3xl font-extrabold text-novix-ink">Your data, protected and controlled by you</h2>
                <p class="mt-3 text-novix-ink/70">Nivaya Life is built to earn trust with real families managing real medical information.</p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 1 0-8 0v4h8Z', 'title' => 'Encrypted storage', 'body' => 'Every report is encrypted at rest and in transit — only you and the people you choose can see it.'],
                    ['icon' => 'M8.7 10.7 15.3 7.3M8.7 13.3l6.6 3.4M18 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z', 'title' => 'You control sharing', 'body' => 'Share a report for a set time window — you decide who sees what, and for how long. Revoke access anytime.'],
                    ['icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a4 4 0 1 1 5.657 0A4 4 0 0 1 12 18a4 4 0 0 1-2.828-1.464Z', 'title' => 'AI explains, it doesn\'t diagnose', 'body' => 'Nivaya Life\'s AI reads and organizes your reports and explains them in plain language. It never makes a diagnosis or prescribes treatment.'],
                ] as $item)
                    <div class="rounded-novix bg-white p-6 shadow-novix-sm transition hover:-translate-y-0.5 hover:shadow-novix" data-reveal style="transition-delay:{{ $loop->index * 70 }}ms">
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
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <h2 id="break-heading" class="text-3xl font-extrabold text-novix-ink">Take a 10-second health break</h2>
            <p class="mt-3 text-novix-ink/70">Try the real thing before you sign up for anything.</p>
        </div>

        {{-- Live BMI check leads: it is the actual gauge component from inside
             the app, so the strongest demo gets the most prominent slot. --}}
        <div class="mt-10 overflow-hidden rounded-novix border-t-2 border-novix-gold/50 bg-white shadow-novix-sm" data-reveal>
            <div class="grid items-center gap-8 p-8 md:grid-cols-2 md:p-10">
                <div class="order-2 md:order-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-novix-mint px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-novix-green">
                        <span class="h-1.5 w-1.5 rounded-full bg-novix-gold" aria-hidden="true"></span>
                        Live demo
                    </span>
                    <h3 class="mt-3 text-2xl font-extrabold text-novix-ink">Check your BMI right now</h3>
                    <p class="mt-2 text-sm leading-relaxed text-novix-ink/70">
                        Enter a height and weight and the needle moves instantly. This is the very
                        same gauge that sits on your dashboard and tracks every reading over time
                        once you have an account.
                    </p>
                    <p class="mt-4 flex items-start gap-2 text-xs text-novix-ink/50">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 1 0-8 0v4h8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                        Runs entirely in your browser — nothing is saved, sent, or shared.
                    </p>
                </div>
                <div class="order-1 md:order-2">
                    <x-bmi-gauge :size="190" hole-class="bg-white" />
                </div>
            </div>
        </div>

        {{-- Tap-the-heart: a synthesized lub-dub on every tap, confetti at ten.
             Pure WebAudio — no audio file, and silent until the visitor asks. --}}
        <div class="mt-6 flex flex-col items-center gap-6 rounded-novix border-t-2 border-novix-gold/50 bg-white p-8 text-center shadow-novix-sm sm:flex-row sm:text-left"
            data-reveal
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
                class="relative flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-novix-pink/20 transition hover:bg-novix-pink/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-novix-green"
                :class="bumping ? 'scale-110' : 'scale-100'" style="transition: transform 0.15s ease">
                <svg class="h-10 w-10 text-novix-pink-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 21s-7-4.35-9.5-8.5C.83 9.1 2.3 5.5 6 5c2-.27 3.5 1 4 2 .5-1 2-2.27 4-2 3.7.5 5.17 4.1 3.5 7.5C19 16.65 12 21 12 21Z"/>
                </svg>
            </button>
            <div class="min-w-0">
                <h3 class="font-semibold text-novix-ink">Tap the heart</h3>
                <p class="mt-1 text-sm text-novix-ink/60">Hear a real lub-dub — sound on. Ten beats earns a small celebration.</p>
                <p class="mt-2 h-5 text-xs font-bold text-novix-gold" x-cloak x-show="beats > 0"
                    x-text="beats < 10 ? beats + (beats === 1 ? ' beat' : ' beats') + ' with you' : 'Your heart, our priority 💛'"></p>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-3xl px-6 lg:px-8"><div class="novix-rule-gold" aria-hidden="true"></div></div>

    {{-- ============ FAQ ============ --}}
    <section id="faq" class="scroll-mt-20 mx-auto max-w-3xl px-6 py-20 lg:px-8" aria-labelledby="faq-heading">
        <div class="text-center">
            <h2 data-reveal id="faq-heading" class="text-3xl font-extrabold text-novix-ink">Frequently asked questions</h2>
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
        <div class="mx-auto mb-16 max-w-4xl novix-rule-gold" aria-hidden="true"></div>
        <div class="novix-sheen relative mx-auto max-w-4xl overflow-hidden rounded-novix bg-novix-green px-8 py-14 text-center shadow-novix ring-1 ring-novix-gold/30 sm:px-16" data-reveal>
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-novix-gold/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-10 h-48 w-48 rounded-full bg-white/5" aria-hidden="true"></div>
            <h2 class="relative text-3xl font-extrabold text-white">Ready to leave the paper behind?</h2>
            <p class="relative mx-auto mt-3 max-w-md text-white/80">
                Create your family's secure health record today — free to get started.
            </p>
            <a href="{{ route('register') }}"
                class="relative mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 text-sm font-semibold text-novix-green shadow-novix-sm transition hover:-translate-y-0.5 hover:bg-novix-cream focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                Get Started
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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

    {{-- Thumb-reachable signup bar, phones only. It stays out of the way until
         the hero has scrolled past, so it never covers the opening screen. --}}
    <div x-data="{ shown: false }"
        @scroll.window="shown = window.scrollY > window.innerHeight * 0.9"
        x-show="shown" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-novix-green/10 bg-novix-cream/95 px-4 py-3 backdrop-blur md:hidden"
        style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
        <div class="flex items-center gap-3">
            <a href="{{ route('register') }}" class="flex-1 rounded-xl bg-novix-green py-3 text-center text-sm font-bold text-white shadow-novix-sm">
                Get Started — free
            </a>
            <a href="{{ route('login') }}" class="rounded-xl border border-novix-green/20 bg-white px-4 py-3 text-sm font-semibold text-novix-green">
                Log in
            </a>
        </div>
    </div>

    {{-- Back to top, desktop only — the mobile bar owns that corner. --}}
    <button type="button" x-data="{ shown: false }"
        @scroll.window="shown = window.scrollY > 900"
        x-show="shown" x-cloak x-transition
        @click="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
        aria-label="Back to top"
        class="fixed bottom-6 right-6 z-40 hidden h-11 w-11 items-center justify-center rounded-full bg-white text-novix-green shadow-novix ring-1 ring-novix-green/10 transition hover:-translate-y-0.5 hover:bg-novix-mint md:flex">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 15 6-6 6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

</body>
</html>
