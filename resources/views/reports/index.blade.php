@php
    $reportTypeIcons = [
        'blood_test' => '&#129656;', 'prescription' => '&#128138;', 'xray' => '&#129460;',
        'mri_ct' => '&#129504;', 'insurance' => '&#128737;', 'bill' => '&#129534;',
        'ecg' => '&#128147;', 'other' => '&#128196;',
    ];
    $ocrBadge = [
        'pending' => ['label' => 'Pending', 'class' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60'],
        'processing' => ['label' => 'Processing', 'class' => 'bg-novix-yellow/30 text-novix-yellow'],
        'completed' => ['label' => 'Completed', 'class' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint'],
        'failed' => ['label' => 'Failed', 'class' => 'bg-novix-pink/30 text-novix-pink-dark'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Reports</h2>
                <p class="mt-1 text-sm text-novix-muted">For {{ $active->full_name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('shares.history') }}?member={{ $active->id }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    Share History
                </a>
                <a href="{{ route('reports.upload') }}" class="flex items-center gap-2 rounded-xl bg-novix-green px-5 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Upload Report
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Filters --}}
        <form method="GET" action="{{ route('reports.index') }}" class="mb-4 rounded-novix bg-white p-4 shadow-novix-sm dark:bg-white/5">
            @if(request('member'))
                <input type="hidden" name="member" value="{{ request('member') }}">
            @endif
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Search</label>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search text, hospital, doctor&hellip;"
                        x-data @input.debounce.600ms="$event.target.form.requestSubmit()"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Type</label>
                    <select name="type" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="">All types</option>
                        @foreach(\App\Models\Report::TYPE_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-novix-muted">From</label>
                        <input type="date" name="from" value="{{ $from }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-novix-muted">To</label>
                        <input type="date" name="to" value="{{ $to }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                    </div>
                </div>
            </div>
            @if($hasFilters)
                <a href="{{ route('reports.index') }}{{ request('member') ? '?member='.request('member') : '' }}" class="mt-2 inline-block text-xs font-semibold text-novix-muted hover:text-novix-ink dark:hover:text-white">Clear filters</a>
            @endif
        </form>

        @if($reports->isEmpty() && ! $hasFilters)
            <div class="rounded-novix bg-white p-10 text-center shadow-novix-sm dark:bg-white/5">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-novix-cream text-novix-muted dark:bg-white/10" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                </span>
                <p class="mt-3 text-sm text-novix-muted">No reports yet.</p>
                <a href="{{ route('reports.upload') }}" class="mt-2 inline-block text-xs font-semibold text-novix-green hover:underline">Upload your first report</a>
            </div>
        @elseif($reports->isEmpty())
            <div class="rounded-novix bg-white p-10 text-center shadow-novix-sm dark:bg-white/5">
                <p class="text-sm text-novix-muted">No reports match your filters.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($reports as $report)
                    @php($badge = $ocrBadge[$report->ocr_status] ?? $ocrBadge['pending'])
                    <a href="{{ route('reports.show', $report) }}" class="flex items-center gap-4 rounded-novix bg-white p-4 shadow-novix-sm transition hover:shadow-novix dark:bg-white/5">
                        <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-novix-cream text-xl dark:bg-white/10">{!! $reportTypeIcons[$report->type] ?? '&#128196;' !!}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-bold text-novix-ink dark:text-white">{{ $report->typeLabel() }}</p>
                                <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </div>
                            <p class="text-xs text-novix-muted">
                                {{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}
                                @if($report->hospital_or_clinic_name) &middot; {{ $report->hospital_or_clinic_name }} @endif
                            </p>
                            @if($report->ai_summary)
                                <p class="mt-1 truncate text-xs text-novix-muted">{{ Str::limit(Str::of($report->ai_summary)->before("\n\n"), 130) }}</p>
                            @endif
                        </div>
                        <svg class="h-4 w-4 flex-shrink-0 text-novix-muted" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
