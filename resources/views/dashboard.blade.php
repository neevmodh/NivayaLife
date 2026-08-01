@php
    $reportTypeIcons = [
        'blood_test' => '&#129656;', 'prescription' => '&#128138;', 'xray' => '&#129460;',
        'sonography' => '&#128266;', 'mri_ct' => '&#129504;', 'insurance' => '&#128737;', 'bill' => '&#129534;',
        'ecg' => '&#128147;', 'other' => '&#128196;',
    ];
    $ocrBadge = [
        'pending' => ['label' => 'Pending', 'class' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60'],
        'processing' => ['label' => 'Processing', 'class' => 'bg-novix-yellow/30 text-novix-yellow'],
        'completed' => ['label' => 'Completed', 'class' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint'],
        'failed' => ['label' => 'Failed', 'class' => 'bg-novix-pink/30 text-novix-pink-dark'],
    ];
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = Str::of(auth()->user()->name)->words(1, '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">{{ $greeting }}, {{ $firstName }} 👋</h2>
            <p class="text-sm text-novix-muted">Here's what's happening with {{ $active->full_name === auth()->user()->name ? 'your' : "{$active->full_name}'s" }} health today.</p>
        </div>
    </x-slot>

    @if(session('just_registered'))
        <div x-data x-init="document.dispatchEvent(new CustomEvent('novix:confetti'))"></div>
        <x-confetti />
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
            class="fixed right-6 top-6 z-50 rounded-xl bg-novix-green px-5 py-3 text-sm font-semibold text-white shadow-novix">
            Welcome, {{ session('just_registered') }}! Your account is ready.
        </div>
    @endif

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        {{-- First-run checklist --}}
        @if($onboarding)
            <div x-data="{ show: true }" x-show="show" x-transition
                class="animate-novix-fade-up rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-novix-ink dark:text-white">Get the most out of Novix</p>
                        <p class="mt-0.5 text-xs text-novix-muted">A few quick things to try:</p>
                    </div>
                    <button type="button"
                        @click="show = false; fetch('{{ route('dashboard.dismiss-onboarding') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), Accept: 'application/json' } })"
                        class="flex-shrink-0 text-novix-muted hover:text-novix-ink dark:hover:text-white" aria-label="Dismiss">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    @php
                        $checklistItems = [
                            'family' => ['label' => 'Add a family member', 'url' => route('family.add')],
                            'report' => ['label' => 'Upload a report', 'url' => route('reports.upload')],
                            'assistant' => ['label' => 'Try the AI assistant', 'url' => route('assistant')],
                        ];
                    @endphp
                    @foreach($checklistItems as $key => $item)
                        <a href="{{ $item['url'] }}" class="flex items-center gap-2 rounded-xl border {{ $onboarding[$key] ? 'border-novix-green/30 bg-novix-mint/30' : 'border-gray-200 dark:border-white/10' }} px-3 py-2.5 text-sm font-medium transition hover:border-novix-green/40">
                            <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full {{ $onboarding[$key] ? 'bg-novix-green text-white' : 'border-2 border-gray-300 dark:border-white/20' }}">
                                @if($onboarding[$key])
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @endif
                            </span>
                            <span class="{{ $onboarding[$key] ? 'text-novix-green line-through dark:text-novix-mint' : 'text-novix-ink dark:text-white' }}">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Hero card --}}
        <div class="animate-novix-fade-up relative overflow-hidden rounded-novix bg-gradient-to-br from-novix-green to-novix-green-dark shadow-novix" style="animation-delay:0ms">
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/5"></div>
            <div class="pointer-events-none absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-white/5"></div>

            <div class="relative flex flex-col items-center gap-6 p-6 text-white sm:flex-row sm:items-center sm:p-8">
                <div class="relative flex-shrink-0">
                    <x-avatar :photo-path="$active->photo_path" :full-name="$active->full_name" :gender="$active->gender" :age="$active->age()"
                        size="h-24 w-24" color-class="bg-white/10 text-white" class="border-4 border-white/25 shadow-lg" />
                    <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-novix-mint text-novix-green shadow" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </div>

                <div class="flex-1 text-center sm:text-left">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $active->full_name }}</h1>
                    <p class="mt-1 flex items-center justify-center gap-1.5 text-sm text-white/70 sm:justify-start">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ $active->unique_health_id }} &middot; {{ Str::headline($active->relation) }}
                    </p>
                    <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                        @if($active->date_of_birth)
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-sm">{{ $active->date_of_birth->age }} yrs</span>
                        @endif
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold capitalize backdrop-blur-sm">{{ $active->gender ? str_replace('_', ' ', $active->gender) : 'Gender not set' }}</span>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-1 rounded-2xl bg-white/10 px-6 py-4 backdrop-blur-sm">
                    <span class="text-xs font-semibold uppercase tracking-wide text-white/70">Blood group</span>
                    <span class="text-3xl font-extrabold">{{ $active->blood_group ?? '—' }}</span>
                </div>

                <a href="{{ route('id-card.show') }}"
                    class="flex flex-shrink-0 items-center gap-2 rounded-xl border-2 border-novix-pink-dark/40 bg-novix-pink-dark/90 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-black/10 transition hover:border-novix-pink-dark hover:bg-novix-pink-dark">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8" cy="12" r="1.5" fill="currentColor"/><path d="M13 10h5M13 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Emergency Card
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- BMI gauge --}}
            <div class="animate-novix-fade-up rounded-novix bg-white p-6 shadow-novix-sm transition hover:shadow-novix dark:bg-white/5" style="animation-delay:60ms">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <h3 class="text-sm font-bold text-novix-ink dark:text-white">BMI</h3>
                </div>
                @if($latestBmi)
                    <div class="mt-4">
                        <x-bmi-gauge :height-cm="$latestBmi->height_cm" :weight-kg="$latestBmi->weight_kg" :editable="false" :trend="$trend" :size="180" />
                    </div>
                    <p class="mt-2 text-center text-xs text-novix-muted">Last recorded {{ $latestBmi->recorded_date->diffForHumans() }}</p>
                @else
                    <div class="mt-6 flex flex-col items-center gap-2 py-4 text-center">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-cream text-novix-muted dark:bg-white/10" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <p class="text-sm text-novix-muted">No BMI recorded yet.</p>
                        <a href="{{ route('profile.edit') }}" class="text-xs font-semibold text-novix-green hover:underline">Add height &amp; weight</a>
                    </div>
                @endif
            </div>

            {{-- Quick actions --}}
            <div class="lg:col-span-2">
                <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                    <svg class="h-4 w-4 text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Quick actions
                </h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <a href="{{ route('reports.upload') }}" class="animate-novix-fade-up group flex flex-col items-center gap-2 rounded-novix bg-white p-5 text-center shadow-novix-sm transition hover:-translate-y-0.5 hover:shadow-novix dark:bg-white/5" style="animation-delay:120ms">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-mint text-novix-green transition group-hover:scale-110 dark:bg-novix-green/20 dark:text-novix-mint" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 4v12m0-12 4 4m-4-4-4 4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">Upload Report</span>
                    </a>
                    <a href="{{ route('timeline') }}" class="animate-novix-fade-up group flex flex-col items-center gap-2 rounded-novix bg-white p-5 text-center shadow-novix-sm transition hover:-translate-y-0.5 hover:shadow-novix dark:bg-white/5" style="animation-delay:150ms">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-blue/20 text-novix-blue transition group-hover:scale-110" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h10M4 18h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">View Timeline</span>
                    </a>
                    <a href="{{ route('id-card.show') }}" class="animate-novix-fade-up group flex flex-col items-center gap-2 rounded-novix border-2 border-novix-pink-dark/20 bg-white p-5 text-center shadow-novix-sm transition hover:-translate-y-0.5 hover:border-novix-pink-dark/40 hover:shadow-novix dark:bg-white/5" style="animation-delay:180ms">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-pink/25 text-novix-pink-dark transition group-hover:scale-110" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8" cy="12" r="1.5" fill="currentColor"/><path d="M13 10h5M13 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">Emergency Card</span>
                    </a>
                    <a href="{{ route('shares.history') }}" class="animate-novix-fade-up group flex flex-col items-center gap-2 rounded-novix bg-white p-5 text-center shadow-novix-sm transition hover:-translate-y-0.5 hover:shadow-novix dark:bg-white/5" style="animation-delay:210ms">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-yellow/30 text-novix-yellow transition group-hover:scale-110" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M18 8a3 3 0 1 0-2.83-4H15a3 3 0 0 0 .09 4.26L8.9 11.7a3 3 0 1 0 0 4.6l6.19 3.44A3 3 0 1 0 15 17.7l-6.19-3.44a3 3 0 0 0 0-.52L15 10.3c.52.44 1.19.7 1.91.7A3 3 0 0 0 18 8Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">Share Report</span>
                    </a>
                </div>

                {{-- Family overview --}}
                <div class="mb-3 mt-6 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                        <svg class="h-4 w-4 text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Family
                    </h3>
                    <a href="{{ route('family.index') }}" class="text-xs font-semibold text-novix-green hover:underline">Manage family</a>
                </div>
                <div class="animate-novix-fade-up flex flex-wrap gap-4 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5" style="animation-delay:240ms">
                    @foreach($familyMembers as $member)
                        <form method="POST" action="{{ route('dashboard.switch', $member) }}" class="group flex flex-col items-center gap-1.5">
                            @csrf
                            <button type="submit" class="relative transition group-hover:-translate-y-0.5" aria-label="Switch to {{ $member->full_name }}">
                                <x-avatar :photo-path="$member->photo_path" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()"
                                    size="h-14 w-14" class="ring-2 ring-offset-2 dark:ring-offset-novix-ink {{ $member->id === $active->id ? 'ring-novix-green' : 'ring-gray-200 group-hover:ring-novix-mint' }}" />
                                @if($member->id === $active->id)
                                    <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-novix-green ring-2 ring-white dark:ring-novix-ink" aria-hidden="true">
                                        <svg class="h-2.5 w-2.5 text-white" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                @endif
                            </button>
                            <span class="text-xs font-medium text-novix-ink dark:text-white">{{ Str::of($member->full_name)->words(1, '') }}</span>
                        </form>
                    @endforeach

                    <a href="{{ route('family.add') }}" class="group flex flex-col items-center gap-1.5" aria-label="Add family member">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-dashed border-novix-green/30 text-novix-green transition group-hover:-translate-y-0.5 group-hover:border-novix-green group-hover:bg-novix-mint/40 dark:text-novix-mint">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <span class="text-xs font-medium text-novix-ink dark:text-white">Add</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Recent reports --}}
            <div class="animate-novix-fade-up rounded-novix bg-white p-6 shadow-novix-sm transition hover:shadow-novix dark:bg-white/5" style="animation-delay:300ms">
                <div class="flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                        <svg class="h-4 w-4 text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8h6M9 12h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        Recent reports
                    </h3>
                    <a href="{{ route('reports.upload') }}" class="text-xs font-semibold text-novix-green hover:underline">Upload new</a>
                </div>

                @if($recentReports->isEmpty())
                    <div class="mt-4 flex flex-col items-center gap-2 rounded-xl bg-novix-cream/60 py-8 text-center dark:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-novix-muted shadow-sm dark:bg-white/10" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                        </span>
                        <p class="text-sm text-novix-muted">No reports yet — upload your first one.</p>
                        <a href="{{ route('reports.upload') }}" class="text-xs font-semibold text-novix-green hover:underline">Upload a report</a>
                    </div>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($recentReports as $report)
                            @php
                                $badge = $ocrBadge[$report->ocr_status] ?? $ocrBadge['pending'];
                            @endphp
                            <li>
                                <a href="{{ route('reports.show', $report) }}" class="flex items-center gap-3 rounded-lg px-2 py-3 transition hover:bg-novix-cream/60 dark:hover:bg-white/5">
                                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-novix-cream text-lg dark:bg-white/10">{!! $reportTypeIcons[$report->type] ?? '&#128196;' !!}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-novix-ink dark:text-white">{{ Str::headline($report->type) }}</p>
                                        <p class="text-xs text-novix-muted">{{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}</p>
                                        @if($report->ai_summary)
                                            <p class="mt-0.5 truncate text-xs text-novix-muted">{{ Str::limit(Str::of($report->ai_summary)->before("\n\n"), 90) }}</p>
                                        @endif
                                    </div>
                                    <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Upcoming medications --}}
            <div class="animate-novix-fade-up rounded-novix bg-white p-6 shadow-novix-sm transition hover:shadow-novix dark:bg-white/5" style="animation-delay:340ms"
                x-data="doseTracker({ csrfToken: @js(csrf_token()), initialStatuses: @js($doseStatuses) })">
                <div class="flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                        <svg class="h-4 w-4 text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10.5 20.5 3.5 13.5a4.95 4.95 0 1 1 7-7l1 1 1-1a4.95 4.95 0 0 1 7 7l-7 7-1.5-1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Today's medications
                    </h3>
                    <a href="{{ route('medications.index') }}?member={{ $active->id }}" class="text-xs font-semibold text-novix-green hover:underline">Manage</a>
                </div>

                @if($activeMedications->isEmpty())
                    <div class="mt-4 flex flex-col items-center gap-2 rounded-xl bg-novix-cream/60 py-8 text-center dark:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-novix-muted shadow-sm dark:bg-white/10" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <p class="text-sm text-novix-muted">No active medications — you're all clear.</p>
                    </div>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($activeMedications as $medication)
                            <li class="rounded-lg px-2 py-3 transition hover:bg-novix-cream/60 dark:hover:bg-white/5">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-novix-ink dark:text-white">{{ $medication->medicine_name }}</p>
                                    <p class="text-xs text-novix-muted">{{ $medication->dosage }}</p>
                                </div>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @forelse($medication->schedule_times ?? [] as $time)
                                        <button type="button" @click="toggle({{ $medication->id }}, '{{ $time }}')"
                                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold transition"
                                            :class="{
                                                'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint': statusFor({{ $medication->id }}, '{{ $time }}') === 'taken',
                                                'bg-novix-pink/30 text-novix-pink-dark': statusFor({{ $medication->id }}, '{{ $time }}') === 'missed',
                                                'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60': !['taken', 'missed'].includes(statusFor({{ $medication->id }}, '{{ $time }}')),
                                            }">{{ $time }}</button>
                                    @empty
                                        <span class="text-xs text-novix-muted">{{ $medication->frequency ?? 'As needed' }}</span>
                                    @endforelse
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-[11px] text-novix-muted">Tap a time to mark that dose taken.</p>
                @endif
            </div>

            {{-- Vaccinations --}}
            <div class="animate-novix-fade-up rounded-novix bg-white p-6 shadow-novix-sm transition hover:shadow-novix dark:bg-white/5" style="animation-delay:380ms">
                <div class="flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                        <svg class="h-4 w-4 text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 8 8 19l-5-5M14 3l7 7-3 3-7-7 3-3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Vaccinations
                    </h3>
                    <a href="{{ route('vaccinations.index') }}?member={{ $active->id }}" class="text-xs font-semibold text-novix-green hover:underline">Manage</a>
                </div>

                @if($upcomingVaccinations->isEmpty())
                    <div class="mt-4 flex flex-col items-center gap-2 rounded-xl bg-novix-cream/60 py-8 text-center dark:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-novix-muted shadow-sm dark:bg-white/10" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <p class="text-sm text-novix-muted">No upcoming doses tracked.</p>
                        <a href="{{ route('vaccinations.create') }}?member={{ $active->id }}" class="text-xs font-semibold text-novix-green hover:underline">Add a vaccination</a>
                    </div>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($upcomingVaccinations as $vaccination)
                            @php
                                $overdue = $vaccination->next_due_date->toDateString() < now()->toDateString();
                                $dueSoon = ! $overdue && $vaccination->next_due_date->toDateString() <= now()->addDays(7)->toDateString();
                            @endphp
                            <li class="flex items-center justify-between rounded-lg px-2 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-novix-ink dark:text-white">{{ $vaccination->vaccine_name }} &middot; Dose {{ $vaccination->dose_number + 1 }}</p>
                                    <p class="text-xs text-novix-muted">Due {{ $vaccination->next_due_date->format('M j, Y') }}</p>
                                </div>
                                @if($overdue || $dueSoon)
                                    <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $overdue ? 'bg-novix-pink/30 text-novix-pink-dark' : 'bg-novix-yellow/30 text-novix-yellow' }}">
                                        {{ $overdue ? 'Overdue' : 'Due soon' }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
