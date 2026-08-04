@php
    $reportTypeIcons = [
        'blood_test' => '&#129656;', 'prescription' => '&#128138;', 'xray' => '&#129460;',
        'sonography' => '&#128266;', 'mri_ct' => '&#129504;', 'insurance' => '&#128737;', 'bill' => '&#129534;',
        'ecg' => '&#128147;', 'dental' => '&#129463;', 'discharge_summary' => '&#127973;',
        'pathology' => '&#129514;', 'eye_care' => '&#128065;', 'other' => '&#128196;',
    ];
    $ocrBadge = [
        'pending' => ['label' => 'Queued', 'class' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60'],
        'processing' => ['label' => 'Reading…', 'class' => 'bg-novix-yellow/30 text-amber-700 dark:text-novix-yellow'],
        'completed' => ['label' => 'Ready', 'class' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint'],
        'failed' => ['label' => 'Failed', 'class' => 'bg-novix-pink/30 text-novix-pink-dark'],
    ];

    // Reports arrive newest-first; grouping by month gives the list a spine
    // without needing a separate timeline page.
    $grouped = $reports->groupBy(fn ($r) => ($r->report_date ?? $r->uploaded_at)?->format('F Y') ?? 'Undated');

    $readyCount = $reports->where('ocr_status', 'completed')->count();
    $pendingCount = $reports->whereIn('ocr_status', ['pending', 'processing'])->count();

    $activeFilters = array_values(array_filter([
        $search !== '' ? "“{$search}”" : null,
        $type !== '' ? (\App\Models\Report::TYPE_LABELS[$type] ?? $type) : null,
        $from !== '' ? "From {$from}" : null,
        $to !== '' ? "To {$to}" : null,
    ]));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold leading-tight tracking-tight text-novix-ink dark:text-white">Reports</h2>
                <p class="mt-1 text-sm text-novix-muted">{{ $active->full_name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('shares.history') }}?member={{ $active->id }}"
                    class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-novix-ink transition hover:-translate-y-0.5 hover:bg-novix-cream active:translate-y-0 dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    Share history
                </a>
                <a href="{{ route('reports.upload') }}"
                    class="flex items-center gap-2 rounded-xl bg-novix-green px-5 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:-translate-y-0.5 hover:bg-novix-green-dark active:translate-y-0 active:scale-95">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Upload
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8"
        x-data="{
            view: 'list',
            filtersOpen: {{ $hasFilters ? 'true' : 'false' }},
            init() {
                try { this.view = localStorage.getItem('nivaya.reports.view') || 'list'; } catch (e) {}
            },
            setView(v) {
                this.view = v;
                try { localStorage.setItem('nivaya.reports.view', v); } catch (e) {}
            },
        }">

        {{-- Counts double as a summary and a processing check --}}
        @if($reports->isNotEmpty() || $hasFilters)
            <div class="mb-4 grid grid-cols-3 gap-3">
                <div class="rounded-novix bg-white p-3.5 text-center shadow-novix-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold text-novix-ink dark:text-white">{{ $reports->count() }}</p>
                    <p class="text-[11px] font-semibold text-novix-muted">{{ $hasFilters ? 'Matching' : 'Total' }}</p>
                </div>
                <div class="rounded-novix bg-white p-3.5 text-center shadow-novix-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold text-novix-green dark:text-novix-mint">{{ $readyCount }}</p>
                    <p class="text-[11px] font-semibold text-novix-muted">Explained</p>
                </div>
                <div class="rounded-novix bg-white p-3.5 text-center shadow-novix-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold {{ $pendingCount > 0 ? 'text-amber-600 dark:text-novix-yellow' : 'text-novix-ink dark:text-white' }}">{{ $pendingCount }}</p>
                    <p class="text-[11px] font-semibold text-novix-muted">Processing</p>
                </div>
            </div>
        @endif

        {{-- Search stays visible; the rest folds away until wanted --}}
        <form method="GET" action="{{ route('reports.index') }}" class="mb-4 rounded-novix bg-white p-3 shadow-novix-sm dark:bg-white/5">
            @if(request('member'))
                <input type="hidden" name="member" value="{{ request('member') }}">
            @endif

            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-novix-muted" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search text, hospital, doctor&hellip;"
                        x-data @input.debounce.600ms="$event.target.form.requestSubmit()"
                        class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3 text-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>

                <button type="button" @click="filtersOpen = !filtersOpen"
                    class="flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold text-novix-ink transition hover:bg-novix-cream active:scale-95 dark:border-white/10 dark:text-white dark:hover:bg-white/10"
                    :aria-expanded="filtersOpen.toString()">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <span class="hidden sm:inline">Filters</span>
                    @if(count($activeFilters))
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-novix-gold text-[10px] font-bold text-white">{{ count($activeFilters) }}</span>
                    @endif
                </button>

                <div class="hidden rounded-xl border border-gray-200 p-0.5 dark:border-white/10 sm:flex" role="group" aria-label="View">
                    <button type="button" @click="setView('list')" aria-label="List view"
                        class="rounded-lg p-1.5 transition" :class="view === 'list' ? 'bg-novix-mint text-novix-green dark:bg-white/10 dark:text-novix-mint' : 'text-novix-muted hover:text-novix-green'">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                    <button type="button" @click="setView('grid')" aria-label="Grid view"
                        class="rounded-lg p-1.5 transition" :class="view === 'grid' ? 'bg-novix-mint text-novix-green dark:bg-white/10 dark:text-novix-mint' : 'text-novix-muted hover:text-novix-green'">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg>
                    </button>
                </div>
            </div>

            <div x-show="filtersOpen" x-cloak x-transition class="mt-3 grid grid-cols-1 gap-3 border-t border-gray-100 pt-3 dark:border-white/10 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Type</label>
                    <select name="type" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="">All types</option>
                        @foreach(\App\Models\Report::TYPE_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">From</label>
                    <input type="date" name="from" value="{{ $from }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">To</label>
                    <input type="date" name="to" value="{{ $to }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
            </div>

            @if(count($activeFilters))
                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 dark:border-white/10">
                    @foreach($activeFilters as $filterLabel)
                        <span class="inline-flex items-center rounded-full bg-novix-mint px-2.5 py-1 text-[11px] font-semibold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">
                            {{ $filterLabel }}
                        </span>
                    @endforeach
                    <a href="{{ route('reports.index') }}{{ request('member') ? '?member='.request('member') : '' }}"
                        class="text-[11px] font-semibold text-novix-muted transition hover:text-novix-pink-dark">Clear all</a>
                </div>
            @endif
        </form>

        @if($reports->isEmpty())
            <div class="rounded-novix bg-white shadow-novix-sm dark:bg-white/5">
                <x-empty-state
                    :title="$hasFilters ? 'No reports match those filters' : 'No reports yet'"
                    :hint="$hasFilters ? 'Try widening the date range or clearing the search.' : 'Upload a prescription, scan or lab result and it gets read and explained automatically.'"
                    icon="report"
                    :action-label="$hasFilters ? 'Clear filters' : 'Upload your first report'"
                    :action-url="$hasFilters ? route('reports.index') : route('reports.upload')"
                    class="py-14" />
            </div>
        @else
            @foreach($grouped as $month => $monthReports)
                <div class="mb-2 mt-6 flex items-center gap-3 first:mt-0">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-novix-muted">{{ $month }}</h3>
                    <span class="h-px flex-1 bg-gray-100 dark:bg-white/10" aria-hidden="true"></span>
                    <span class="text-[11px] font-semibold text-novix-muted">{{ $monthReports->count() }}</span>
                </div>

                {{-- List and grid are the same cards under different layout rules --}}
                <div :class="view === 'grid' ? 'grid grid-cols-1 gap-3 sm:grid-cols-2' : 'space-y-3'">
                    @foreach($monthReports as $report)
                        @php($badge = $ocrBadge[$report->ocr_status] ?? $ocrBadge['pending'])
                        <a href="{{ route('reports.show', $report) }}"
                            class="group flex gap-4 rounded-novix border-t-2 border-transparent bg-white p-4 shadow-novix-sm transition hover:-translate-y-0.5 hover:border-novix-gold/50 hover:shadow-novix active:translate-y-0 dark:bg-white/5"
                            :class="view === 'grid' ? 'h-full items-start' : 'items-center'">
                            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-novix-cream text-xl transition group-hover:scale-110 dark:bg-white/10">{!! $reportTypeIcons[$report->type] ?? '&#128196;' !!}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-bold text-novix-ink dark:text-white">{{ $report->typeLabel() }}</p>
                                    <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-novix-muted">
                                    {{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}
                                    @if($report->hospital_or_clinic_name) &middot; {{ $report->hospital_or_clinic_name }} @endif
                                </p>
                                @if($report->ai_summary)
                                    <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-novix-muted">{{ Str::limit(Str::of($report->ai_summary)->before("\n\n"), 150) }}</p>
                                @endif
                            </div>
                            <svg class="h-4 w-4 flex-shrink-0 self-center text-novix-muted transition group-hover:translate-x-0.5 group-hover:text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</x-app-layout>
