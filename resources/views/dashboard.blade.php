@php
    $ocrBadge = [
        'pending' => ['label' => 'Pending', 'class' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60'],
        'processing' => ['label' => 'Processing', 'class' => 'bg-nivayalife-yellow/30 text-amber-700 dark:text-nivayalife-yellow'],
        'completed' => ['label' => 'Ready', 'class' => 'bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint'],
        'failed' => ['label' => 'Failed', 'class' => 'bg-nivayalife-pink/30 text-nivayalife-pink-dark'],
    ];

    // Server-rendered fallback only. The real greeting is computed from the
    // visitor's own clock (see the x-data below) — the server sits in a single
    // fixed timezone, so it can't know whether it's morning where they are.
    $hour = now()->hour;
    $greeting = match (true) {
        $hour < 5 => 'Good night',
        $hour < 12 => 'Good morning',
        $hour < 17 => 'Good afternoon',
        $hour < 22 => 'Good evening',
        default => 'Good night',
    };
    $firstName = Str::of(auth()->user()->name)->words(1, '');
    $isSelf = $active->full_name === auth()->user()->name;

    // Editing a member's vitals goes through the profile screen for yourself and
    // the family-member screen for everyone else.
    $editUrl = $active->relation === 'self' ? route('profile.edit') : route('family.member.edit', $active);

    $nextVaccination = $upcomingVaccinations->first();
    $nextVaccinationDays = $nextVaccination ? now()->startOfDay()->diffInDays($nextVaccination->next_due_date->startOfDay(), false) : null;

    // "Needs attention" is assembled once here so the banner can decide whether
    // it has anything worth interrupting the page for. An empty list renders
    // nothing at all rather than a card that says "nothing to see".
    $attention = [];

    $overdueVaccinations = $upcomingVaccinations->filter(fn ($v) => $v->next_due_date->toDateString() < now()->toDateString());
    if ($overdueVaccinations->isNotEmpty()) {
        $attention[] = [
            'tone' => 'urgent',
            'text' => $overdueVaccinations->count() === 1
                ? "{$overdueVaccinations->first()->vaccine_name} dose is overdue"
                : "{$overdueVaccinations->count()} vaccination doses are overdue",
            'label' => 'Review',
            'url' => route('vaccinations.index') . '?member=' . $active->id,
        ];
    }

    $pendingDoseCount = collect($doseStatuses)->filter(fn ($s) => $s === 'pending')->count();
    if ($pendingDoseCount > 0) {
        $attention[] = [
            'tone' => 'warn',
            'text' => $pendingDoseCount === 1 ? '1 dose still to take today' : "{$pendingDoseCount} doses still to take today",
            'label' => 'Mark taken',
            'url' => '#todays-medications',
        ];
    }

    $readyReportCount = $recentReports->where('ocr_status', 'completed')->count();
    $processingReports = $recentReports->whereIn('ocr_status', ['pending', 'processing']);
    if ($processingReports->isNotEmpty()) {
        $attention[] = [
            'tone' => 'info',
            'text' => $processingReports->count() === 1 ? '1 report is still being read' : "{$processingReports->count()} reports are still being read",
            'label' => 'View',
            'url' => route('reports.index'),
        ];
    }

    $attentionTones = [
        'urgent' => 'bg-nivayalife-pink-dark',
        'warn' => 'bg-nivayalife-yellow',
        'info' => 'bg-nivayalife-blue',
    ];

    $quickActions = [
        ['label' => 'Upload a report', 'hint' => 'PDF, photo or scan', 'url' => route('reports.upload'),
         'icon' => '<path d="M12 4v12m0-12 4 4m-4-4-4 4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>'],
        ['label' => 'View timeline', 'hint' => 'Everything in order', 'url' => route('timeline'),
         'icon' => '<path d="M4 6h16M4 12h10M4 18h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'],
        ['label' => 'Emergency card', 'hint' => 'Offline-ready ID', 'url' => route('id-card.show'),
         'icon' => '<path d="M3 7h18v10H3V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8" cy="12" r="1.5" fill="currentColor"/><path d="M13 10h5M13 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>'],
        ['label' => 'Share a report', 'hint' => 'Time-limited link', 'url' => route('shares.history'),
         'icon' => '<path d="M18 8a3 3 0 1 0-2.83-4H15a3 3 0 0 0 .09 4.26L8.9 11.7a3 3 0 1 0 0 4.6l6.19 3.44A3 3 0 1 0 15 17.7l-6.19-3.44a3 3 0 0 0 0-.52L15 10.3c.52.44 1.19.7 1.91.7A3 3 0 0 0 18 8Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>'],
    ];
@endphp

<x-app-layout>
    {{-- Greeting and the family switcher share one row. They used to be two
         stacked blocks saying roughly the same thing about who you're viewing. --}}
    <x-slot name="header">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div x-data="{
                greeting: @js($greeting),
                init() {
                    // Recomputed from the browser's clock, so the greeting is
                    // right wherever the person actually is — and midnight
                    // reads as night rather than morning.
                    const h = new Date().getHours();
                    this.greeting = h < 5 ? 'Good night'
                        : h < 12 ? 'Good morning'
                        : h < 17 ? 'Good afternoon'
                        : h < 22 ? 'Good evening'
                        : 'Good night';
                },
            }">
                <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white"><span x-text="greeting">{{ $greeting }}</span>, {{ $firstName }} 👋</h2>
                <p class="mt-1 text-sm text-nivayalife-muted">
                    {{ $isSelf ? "Here's what's happening with your health today." : "You're viewing {$active->full_name}'s records." }}
                </p>
            </div>

            <div class="flex flex-col items-stretch gap-3 lg:items-end">
                <div class="flex flex-shrink-0 items-center justify-end gap-2">
                    <a href="{{ route('id-card.show') }}"
                        class="flex items-center gap-2 rounded-xl border border-nivayalife-green/20 bg-white px-4 py-2.5 text-sm font-semibold text-nivayalife-green shadow-nivayalife-sm transition hover:bg-nivayalife-mint/40 dark:border-white/10 dark:bg-white/5 dark:text-nivayalife-mint">
                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8" cy="12" r="1.5" fill="currentColor"/><path d="M13 10h5M13 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        <span class="hidden sm:inline">Emergency card</span>
                    </a>
                    <a href="{{ route('reports.upload') }}"
                        class="flex items-center gap-2 rounded-xl bg-nivayalife-green px-4 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark">
                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <span class="hidden sm:inline">Add report</span>
                    </a>
                </div>

                <div class="-mx-4 flex snap-x items-center gap-3 overflow-x-auto px-4 pb-1 lg:mx-0 lg:px-0">
                    @foreach($familyMembers as $member)
                        <form method="POST" action="{{ route('dashboard.switch', $member) }}" class="group flex-shrink-0 snap-start">
                            @csrf
                            <button type="submit" aria-label="Switch to {{ $member->full_name }}"
                                @class([
                                    'flex items-center gap-2 rounded-full border py-1 pl-1 pr-3.5 transition',
                                    'border-nivayalife-green bg-white shadow-nivayalife-sm dark:bg-white/10' => $member->id === $active->id,
                                    'border-transparent bg-white/60 hover:border-nivayalife-green/30 hover:bg-white dark:bg-white/5' => $member->id !== $active->id,
                                ])>
                                <x-avatar :photo-path="$member->photo_path" :preset="$member->avatar_preset ?? null" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-8 w-8" />
                                <span @class([
                                    'whitespace-nowrap text-sm font-semibold',
                                    'text-nivayalife-green dark:text-nivayalife-mint' => $member->id === $active->id,
                                    'text-nivayalife-muted' => $member->id !== $active->id,
                                ])>{{ Str::of($member->full_name)->words(1, '') }}</span>
                            </button>
                        </form>
                    @endforeach

                    <a href="{{ route('family.add') }}" aria-label="Add family member"
                        class="flex h-10 w-10 flex-shrink-0 snap-start items-center justify-center rounded-full border border-dashed border-nivayalife-green/40 text-nivayalife-green transition hover:border-nivayalife-green hover:bg-nivayalife-mint/40 dark:text-nivayalife-mint">
                        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @if(session('just_registered'))
        <div x-data x-init="window.dispatchEvent(new CustomEvent('nivayalife:confetti'))"></div>
        <x-confetti />
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
            class="fixed right-6 top-20 z-50 rounded-xl bg-nivayalife-green px-5 py-3 text-sm font-semibold text-white shadow-nivayalife">
            Welcome, {{ session('just_registered') }}! Your account is ready.
        </div>
    @endif

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        {{-- First-run checklist --}}
        @if($onboarding)
            <div x-data="{ show: true }" x-show="show" x-transition
                class="animate-nivayalife-fade-up rounded-nivayalife border border-nivayalife-green/15 bg-white p-5 shadow-nivayalife-sm dark:border-white/10 dark:bg-white/5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-nivayalife-ink dark:text-white">Get the most out of Nivaya Life</p>
                        <p class="mt-0.5 text-xs text-nivayalife-muted">A few quick things to try:</p>
                    </div>
                    <button type="button"
                        @click="show = false; fetch('{{ route('dashboard.dismiss-onboarding') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), Accept: 'application/json' } })"
                        class="flex-shrink-0 text-nivayalife-muted hover:text-nivayalife-ink dark:hover:text-white" aria-label="Dismiss">
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
                        <a href="{{ $item['url'] }}" class="flex items-center gap-2 rounded-xl border {{ $onboarding[$key] ? 'border-nivayalife-green/30 bg-nivayalife-mint/30' : 'border-gray-200 dark:border-white/10' }} px-3 py-2.5 text-sm font-medium transition hover:border-nivayalife-green/40">
                            <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full {{ $onboarding[$key] ? 'bg-nivayalife-green text-white' : 'border-2 border-gray-300 dark:border-white/20' }}">
                                @if($onboarding[$key])
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @endif
                            </span>
                            <span class="{{ $onboarding[$key] ? 'text-nivayalife-green line-through dark:text-nivayalife-mint' : 'text-nivayalife-ink dark:text-white' }}">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Vitals strip. Same four fields the old identity card carried, without
             the card chrome around them — a plain row on the page background,
             plus the health ID as a small aside rather than a headline. --}}
        <div class="animate-nivayalife-fade-up flex flex-wrap items-center justify-between gap-3" style="animation-delay:40ms">
            <div class="grid flex-1 grid-cols-2 gap-2 sm:grid-cols-4">
                <x-data-chip label="Age"
                    :value="$active->date_of_birth ? $active->date_of_birth->age . ' yrs' : null"
                    :fallback-url="$editUrl" fallback-label="Set DOB" />
                <x-data-chip label="Blood group" :value="$active->blood_group" :fallback-url="$editUrl" accent />
                <x-data-chip label="BMI"
                    :value="$latestBmi ? number_format($latestBmi->bmi_value, 1) : null"
                    :fallback-url="$editUrl" fallback-label="Measure" />
                <x-data-chip label="Sex"
                    :value="$active->gender ? Str::headline(str_replace('_', ' ', $active->gender)) : null"
                    :fallback-url="$editUrl" fallback-label="Set" />
            </div>
            <p class="hidden flex-shrink-0 font-mono text-xs tracking-tight text-nivayalife-muted lg:block">{{ $active->unique_health_id }}</p>
        </div>

        {{-- Needs attention — renders only when something actually needs it.
             On wide screens the items sit side by side instead of stacking
             into a tall column that pushes the real content off-screen. --}}
        @if(count($attention))
            <div class="animate-nivayalife-fade-up overflow-hidden rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white shadow-nivayalife-sm dark:bg-white/5" style="animation-delay:60ms">
                <ul class="divide-y divide-gray-100 dark:divide-white/10 lg:flex lg:divide-x lg:divide-y-0">
                    @foreach($attention as $item)
                        <li class="flex flex-1 items-center gap-3 px-5 py-3.5">
                            <span class="relative flex h-2.5 w-2.5 flex-shrink-0" aria-hidden="true">
                                @if($item['tone'] === 'urgent')
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $attentionTones[$item['tone']] }} opacity-60"></span>
                                @endif
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $attentionTones[$item['tone']] }}"></span>
                            </span>
                            <p class="min-w-0 flex-1 text-sm font-medium text-nivayalife-ink dark:text-white">{{ $item['text'] }}</p>
                            <a href="{{ $item['url'] }}" class="flex-shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold text-nivayalife-green transition hover:bg-nivayalife-mint/50 active:scale-95 dark:text-nivayalife-mint dark:hover:bg-white/10">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Desktop-only stat rail. On a phone these numbers are already one
             swipe away in the cards below, so it would only add scrolling. --}}
        <div class="hidden animate-nivayalife-fade-up gap-4 lg:grid lg:grid-cols-4" style="animation-delay:80ms">
            @php
                $railStats = [
                    ['value' => $recentReports->count(), 'label' => 'Recent reports', 'sub' => $readyReportCount.' explained', 'url' => route('reports.index')],
                    ['value' => $activeMedications->count(), 'label' => 'Active medications', 'sub' => $pendingDoseCount > 0 ? $pendingDoseCount.' dose'.($pendingDoseCount === 1 ? '' : 's').' left today' : 'All taken today', 'url' => route('medications.index').'?member='.$active->id],
                    ['value' => $upcomingVaccinations->count(), 'label' => 'Vaccinations tracked', 'sub' => $nextVaccinationDays === null ? 'None scheduled' : ($nextVaccinationDays < 0 ? 'One overdue' : 'Next in '.$nextVaccinationDays.' days'), 'url' => route('vaccinations.index').'?member='.$active->id],
                    ['value' => $familyMembers->count(), 'label' => 'Family members', 'sub' => 'One account, one history', 'url' => route('family.index')],
                ];
            @endphp
            @foreach($railStats as $stat)
                <a href="{{ $stat['url'] }}" class="nivayalife-gold-edge group rounded-nivayalife bg-white p-5 shadow-nivayalife-sm transition hover:-translate-y-0.5 hover:shadow-nivayalife dark:bg-white/5">
                    <p class="text-3xl font-extrabold tracking-tight text-nivayalife-ink dark:text-white">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-sm font-bold text-nivayalife-ink dark:text-white">{{ $stat['label'] }}</p>
                    <p class="mt-0.5 flex items-center gap-1 text-xs text-nivayalife-muted">
                        {{ $stat['sub'] }}
                        <svg class="h-3 w-3 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </p>
                </a>
            @endforeach
        </div>

        {{-- Main grid: what's happening on the left, tools and vitals on the right. --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            <div class="space-y-5 lg:col-span-2">

                {{-- Today's medications --}}
                <section id="todays-medications" class="animate-nivayalife-fade-up scroll-mt-24 rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5" style="animation-delay:100ms"
                    x-data="doseTracker({ csrfToken: @js(csrf_token()), initialStatuses: @js($doseStatuses) })">
                    <x-section-header title="Today's doses" icon="pill" action-label="Manage" :action-url="route('medications.index') . '?member=' . $active->id" />

                    @if($activeMedications->isEmpty())
                        <x-empty-state
                            title="No active medications"
                            hint="Nothing scheduled for {{ $isSelf ? 'you' : Str::of($active->full_name)->words(1, '') }} right now."
                            tone="positive"
                            action-label="Add a medication"
                            :action-url="route('medications.create') . '?member=' . $active->id" />
                    @else
                        <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                            @foreach($activeMedications as $medication)
                                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-nivayalife-ink dark:text-white">{{ $medication->medicine_name }}</p>
                                        <p class="text-xs text-nivayalife-muted">{{ $medication->dosage }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($medication->schedule_times ?? [] as $time)
                                            <button type="button" @click="toggle({{ $medication->id }}, '{{ $time }}')"
                                                class="rounded-full px-2.5 py-1 text-[11px] font-bold tabular-nums transition"
                                                :class="{
                                                    'bg-nivayalife-mint text-nivayalife-green line-through dark:bg-nivayalife-green/20 dark:text-nivayalife-mint': statusFor({{ $medication->id }}, '{{ $time }}') === 'taken',
                                                    'bg-nivayalife-pink/30 text-nivayalife-pink-dark': statusFor({{ $medication->id }}, '{{ $time }}') === 'missed',
                                                    'bg-nivayalife-cream text-nivayalife-muted hover:bg-nivayalife-mint/60 dark:bg-white/10 dark:text-white/60': !['taken', 'missed'].includes(statusFor({{ $medication->id }}, '{{ $time }}')),
                                                }">{{ $time }}</button>
                                        @empty
                                            <span class="text-xs text-nivayalife-muted">{{ $medication->frequency ?? 'As needed' }}</span>
                                        @endforelse
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-[11px] text-nivayalife-muted">Tap a time to mark that dose taken.</p>
                    @endif
                </section>

                {{-- Recent reports --}}
                <section class="animate-nivayalife-fade-up rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5" style="animation-delay:140ms">
                    <x-section-header title="Recent reports" icon="report" action-label="Upload new" :action-url="route('reports.upload')" />

                    @if($recentReports->isEmpty())
                        <x-empty-state
                            title="No reports yet"
                            hint="Upload a prescription, scan or lab result and Nivaya Life will read it for you."
                            icon="report"
                            action-label="Upload a report"
                            :action-url="route('reports.upload')" />
                    @else
                        <ul class="relative mt-3">
                            @foreach($recentReports as $report)
                                @php $badge = $ocrBadge[$report->ocr_status] ?? $ocrBadge['pending']; @endphp
                                <li class="relative flex gap-3 pb-4 last:pb-0">
                                    @unless($loop->last)
                                        <span class="absolute left-[18px] top-9 bottom-0 w-px bg-gray-100 dark:bg-white/10" aria-hidden="true"></span>
                                    @endunless
                                    <span class="relative z-10 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-nivayalife-cream text-nivayalife-green dark:bg-white/10 dark:text-nivayalife-mint">
                                        <x-report-type-icon :type="$report->type" class="h-4.5 w-4.5" />
                                    </span>
                                    <a href="{{ route('reports.show', $report) }}" class="-mt-1 min-w-0 flex-1 rounded-xl px-3 py-2 transition hover:bg-nivayalife-cream/70 dark:hover:bg-white/5">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-nivayalife-ink dark:text-white">{{ Str::headline($report->type) }}</p>
                                                <p class="text-xs text-nivayalife-muted">{{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}</p>
                                            </div>
                                            <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                        </div>
                                        @if($report->ai_summary)
                                            <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-nivayalife-muted">{{ Str::limit(Str::of($report->ai_summary)->before("\n\n"), 120) }}</p>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <div class="space-y-5">

                {{-- Quick actions — one accent colour, differentiated by icon and
                     label rather than four unrelated gradients. --}}
                <section class="animate-nivayalife-fade-up rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5" style="animation-delay:180ms">
                    <x-section-header title="Quick actions" icon="bolt" />
                    <div class="mt-3 space-y-1">
                        @foreach($quickActions as $action)
                            <a href="{{ $action['url'] }}" class="group flex items-center gap-3 rounded-xl p-2.5 transition hover:bg-nivayalife-cream/70 dark:hover:bg-white/5">
                                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-nivayalife-mint text-nivayalife-green transition group-hover:bg-nivayalife-green group-hover:text-white dark:bg-nivayalife-green/20 dark:text-nivayalife-mint" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">{!! $action['icon'] !!}</svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-nivayalife-ink dark:text-white">{{ $action['label'] }}</span>
                                    <span class="block truncate text-xs text-nivayalife-muted">{{ $action['hint'] }}</span>
                                </span>
                                <svg class="h-4 w-4 flex-shrink-0 text-nivayalife-muted transition group-hover:translate-x-0.5 group-hover:text-nivayalife-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Vitals --}}
                <section class="animate-nivayalife-fade-up rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5" style="animation-delay:220ms">
                    <x-section-header title="Body mass index" icon="pulse" :action-label="$latestBmi ? 'Update' : null" :action-url="$latestBmi ? $editUrl : null" />

                    @if($latestBmi)
                        <div class="mt-3">
                            <x-bmi-gauge :height-cm="$latestBmi->height_cm" :weight-kg="$latestBmi->weight_kg" :editable="false" :trend="$trend" :size="150" hole-class="bg-white dark:bg-nivayalife-night" />
                        </div>
                        <p class="mt-2 text-center text-xs text-nivayalife-muted">Last recorded {{ $latestBmi->recorded_date->diffForHumans() }}</p>
                    @else
                        <x-empty-state
                            title="No measurement yet"
                            hint="Add a height and weight to track BMI over time."
                            icon="clock"
                            action-label="Add height & weight"
                            :action-url="$editUrl" />
                    @endif

                    @if($bmiHistory->count() > 1)
                        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10" x-data="adminChart({
                            type: 'line',
                            series: [{ name: 'BMI', data: @js($bmiHistory->map(fn ($log) => ['x' => $log->recorded_date->toDateString(), 'y' => $log->bmi_value])) }],
                            options: {
                                height: 160,
                                colors: ['#14503F'],
                                stroke: { curve: 'smooth', width: 2 },
                                dataLabels: { enabled: false },
                                xaxis: { type: 'datetime', labels: { format: 'MMM d' } },
                                yaxis: { title: { text: 'BMI' } },
                                grid: { borderColor: 'rgba(148,163,184,0.2)' },
                            },
                        })"></div>
                    @endif
                </section>

                {{-- Vaccinations --}}
                <section class="animate-nivayalife-fade-up rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5" style="animation-delay:260ms">
                    <x-section-header title="Vaccinations" icon="syringe" action-label="Manage" :action-url="route('vaccinations.index') . '?member=' . $active->id" />

                    @if($upcomingVaccinations->isEmpty())
                        <x-empty-state
                            title="No doses scheduled"
                            hint="Add a vaccination to get reminders before it's due."
                            icon="shield"
                            action-label="Add a vaccination"
                            :action-url="route('vaccinations.create') . '?member=' . $active->id" />
                    @else
                        <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                            @foreach($upcomingVaccinations as $vaccination)
                                @php
                                    $overdue = $vaccination->next_due_date->toDateString() < now()->toDateString();
                                    $dueSoon = ! $overdue && $vaccination->next_due_date->toDateString() <= now()->addDays(7)->toDateString();
                                @endphp
                                <li class="flex items-center justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-nivayalife-ink dark:text-white">{{ $vaccination->vaccine_name }}</p>
                                        <p class="text-xs text-nivayalife-muted">Dose {{ $vaccination->dose_number + 1 }} &middot; {{ $vaccination->next_due_date->format('M j, Y') }}</p>
                                    </div>
                                    @if($overdue || $dueSoon)
                                        <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $overdue ? 'bg-nivayalife-pink/30 text-nivayalife-pink-dark' : 'bg-nivayalife-yellow/30 text-amber-700 dark:text-nivayalife-yellow' }}">
                                            {{ $overdue ? 'Overdue' : 'Due soon' }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
