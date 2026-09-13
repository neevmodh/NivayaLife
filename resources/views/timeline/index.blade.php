@php
    $typeIcons = [
        'report' => 'M9 12h6m-6 4h6m1 5H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l4.414 4.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z',
        'medication' => 'M10.5 20.5 3.5 13.5a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7ZM7 10l7 7',
        'vaccination' => 'M19 8 8 19l-5-5M14 3l7 7-3 3-7-7 3-3Z',
        'metric' => 'M4 20V10m5 10V4m5 16v-7m5 7V8',
    ];
    $years = $grouped->keys()->sortDesc()->values();

    // Each kind of event gets its own colour, so a year's worth of history is
    // scannable by shape and colour before any of it is read.
    $typeTone = [
        'report' => 'bg-nivayalife-green',
        'medication' => 'bg-nivayalife-blue',
        'vaccination' => 'bg-nivayalife-gold',
        'metric' => 'bg-nivayalife-pink-dark',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">Health Timeline</h2>
                <p class="mt-1 text-sm text-nivayalife-muted">For {{ $active->full_name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('shares.create') }}?family_member_id={{ $active->id }}&access_type=full_summary"
                    class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-nivayalife-ink hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    Share full history
                </a>
                <a href="{{ route('shares.history') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-nivayalife-ink hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    Share history
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8" x-data="metricCompare()">

        {{-- Filters --}}
        <form method="GET" action="{{ route('timeline') }}" class="rounded-nivayalife bg-white p-4 shadow-nivayalife-sm dark:bg-white/5" x-data="{ range: @js($range) }">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">Search reports</label>
                    <input type="text" name="q" value="{{ $search }}" placeholder="e.g. cholesterol"
                        @input.debounce.600ms="$event.target.form.requestSubmit()"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">Category</label>
                    <select name="type" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="" {{ $type === '' ? 'selected' : '' }}>All types</option>
                        <optgroup label="Reports">
                            <option value="report" {{ $type === 'report' ? 'selected' : '' }}>All reports</option>
                            @foreach(\App\Models\Report::TYPE_LABELS as $key => $label)
                                <option value="report:{{ $key }}" {{ $type === "report:{$key}" ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                        <option value="medication" {{ $type === 'medication' ? 'selected' : '' }}>Medications</option>
                        <option value="vaccination" {{ $type === 'vaccination' ? 'selected' : '' }}>Vaccinations</option>
                        <option value="vitals" {{ $type === 'vitals' ? 'selected' : '' }}>Vitals</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">Date range</label>
                    <select name="range" x-model="range" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                        <option value="all">All time</option>
                        <option value="30d">Last 30 days</option>
                        <option value="6m">Last 6 months</option>
                        <option value="1y">Last year</option>
                        <option value="custom">Custom range</option>
                    </select>
                </div>
            </div>

            <div x-show="range === 'custom'" x-cloak class="mt-3 grid grid-cols-2 gap-3 sm:w-1/2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">From</label>
                    <input type="date" name="from" value="{{ $customFrom }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">To</label>
                    <input type="date" name="to" value="{{ $customTo }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
            </div>
        </form>

        {{-- Export PDF --}}
        <form method="GET" action="{{ route('timeline.export') }}" class="mt-3 flex flex-wrap items-center gap-2 rounded-nivayalife bg-nivayalife-mint/40 p-3 text-sm dark:bg-nivayalife-green/10">
            <span class="font-semibold text-nivayalife-ink dark:text-white">Export doctor-ready PDF for:</span>
            <select name="range" class="flex-shrink-0 rounded-lg border border-gray-200 py-1.5 pl-3 pr-8 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="30d">Last 30 days</option>
                <option value="6m" selected>Last 6 months</option>
                <option value="1y">Last year</option>
                <option value="all">All time</option>
            </select>
            <button type="submit" class="rounded-lg bg-nivayalife-green px-4 py-1.5 text-xs font-bold text-white hover:bg-nivayalife-green-dark">Export PDF</button>
        </form>

        {{-- Compare panel --}}
        <div x-show="selected.length > 0" x-cloak class="sticky top-2 z-10 mt-4 rounded-nivayalife border-2 border-nivayalife-green bg-white p-4 shadow-nivayalife dark:bg-nivayalife-night">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-nivayalife-ink dark:text-white">Comparing <span x-text="selected.length"></span> value(s)</span>
                <button type="button" @click="clear()" class="text-xs font-semibold text-nivayalife-muted hover:text-nivayalife-ink">Clear</button>
            </div>
            <template x-if="comparableGroups.length === 0">
                <p class="mt-2 text-xs text-nivayalife-muted">Select two or more values of the <strong>same</strong> metric to compare them.</p>
            </template>
            <template x-for="group in comparableGroups" :key="group.metricType">
                <div class="mt-3 rounded-xl bg-nivayalife-cream/60 p-3 dark:bg-white/5">
                    <p class="text-xs font-bold uppercase tracking-wide text-nivayalife-muted" x-text="group.label"></p>
                    <p class="mt-1 text-sm text-nivayalife-ink dark:text-white">
                        <span x-text="group.oldest.value + ' ' + group.unit"></span>
                        (<span x-text="group.oldest.dateLabel"></span>)
                        &rarr;
                        <span x-text="group.newest.value + ' ' + group.unit"></span>
                        (<span x-text="group.newest.dateLabel"></span>)
                    </p>
                    <p class="mt-1 text-sm font-semibold"
                        :class="group.direction === 'up' ? 'text-nivayalife-pink-dark' : (group.direction === 'down' ? 'text-nivayalife-blue' : 'text-nivayalife-muted')">
                        <span x-show="group.direction === 'up'">&#8593; Up</span>
                        <span x-show="group.direction === 'down'">&#8595; Down</span>
                        <span x-show="group.direction === 'unchanged'">No change</span>
                        <span x-show="group.direction !== 'unchanged'" x-text="Math.abs(group.pctChange) + '%'"></span>
                        since <span x-text="group.oldest.dateLabel"></span>
                    </p>
                </div>
            </template>
        </div>

        {{-- Feed --}}
        <div class="mt-6 space-y-6">
            @if($entryCount === 0)
                <div class="rounded-nivayalife bg-white shadow-nivayalife-sm dark:bg-white/5">
                    <x-empty-state
                        title="Nothing in this range"
                        hint="Try widening the dates or clearing the search — everything uploaded shows up here in order."
                        icon="clock"
                        class="py-14" />
                </div>
            @endif

            @foreach($years as $year)
                @php($isRecentYear = $loop->first)
                <div x-data="{ open: {{ $isRecentYear ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="flex w-full items-center gap-2 rounded-lg text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-nivayalife-green">
                        <svg class="h-4 w-4 text-nivayalife-muted transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <h3 class="text-lg font-bold text-nivayalife-ink dark:text-white">{{ $year }}</h3>
                        <span class="rounded-full bg-nivayalife-mint px-2 py-0.5 text-[11px] font-bold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">{{ $grouped[$year]->flatten(1)->count() }}</span>
                        <span class="h-px flex-1 bg-gray-100 dark:bg-white/10" aria-hidden="true"></span>
                    </button>

                    <div x-show="open" x-cloak class="ml-1 mt-3 space-y-5 border-l-2 border-nivayalife-green/15 pl-7 dark:border-white/10">
                        @foreach($grouped[$year] as $month => $monthEntries)
                            <div>
                                <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-nivayalife-muted">{{ $month }}</h4>
                                <div class="space-y-2">
                                    @foreach($monthEntries->sortByDesc('date') as $entry)
                                        {{-- The node sits in the left gutter, on the spine. --}}
                                        <div class="nivayalife-gold-edge relative rounded-nivayalife bg-white shadow-nivayalife-sm transition hover:-translate-y-0.5 hover:shadow-nivayalife dark:bg-white/5" x-data="{ open: false }">
                                            <span class="absolute -left-[27px] top-6 h-3 w-3 rounded-full {{ $typeTone[$entry['type']] ?? 'bg-nivayalife-muted' }} ring-4 ring-nivayalife-cream dark:ring-nivayalife-night" aria-hidden="true"></span>
                                            <div class="flex items-start gap-3 p-4">
                                                @if(isset($entry['compare']))
                                                    <input type="checkbox"
                                                        @change="toggle(@js($entry['compare']))"
                                                        :checked="isSelected({{ $entry['compare']['id'] }})"
                                                        title="Compare this value with another of the same metric"
                                                        aria-label="Compare this value"
                                                        class="mt-1.5 h-4 w-4 flex-shrink-0 rounded border-gray-300 text-nivayalife-green focus:ring-nivayalife-green">
                                                @endif

                                                <span class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-nivayalife-cream text-nivayalife-green transition group-hover:scale-110 dark:bg-white/10 dark:text-nivayalife-mint">
                                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $typeIcons[$entry['type']] }}"/></svg>
                                                </span>

                                                <button type="button" @click="open = !open" class="min-w-0 flex-1 text-left">
                                                    <p class="truncate text-sm font-semibold capitalize text-nivayalife-ink dark:text-white">{{ $entry['title'] }}</p>
                                                    <p class="text-xs text-nivayalife-muted">
                                                        {{ $entry['date']->format('M j, Y') }}
                                                        @if($entry['subtitle']) &middot; {{ $entry['subtitle'] }} @endif
                                                    </p>
                                                </button>

                                                @if($entry['type'] === 'report')
                                                    <a href="{{ route('reports.show', $entry['model']) }}" class="group/open flex flex-shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-nivayalife-green transition hover:bg-nivayalife-mint/50 dark:hover:bg-white/10">Open<svg class="h-3 w-3 transition group-hover/open:translate-x-0.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                                                @endif
                                            </div>

                                            <div x-show="open" x-cloak x-transition class="border-t border-gray-100 px-4 py-3 text-sm text-nivayalife-ink dark:border-white/10 dark:text-white">
                                                @if($entry['type'] === 'report' && $entry['detail'])
                                                    <p class="whitespace-pre-line text-xs text-nivayalife-muted">{{ Str::of($entry['detail'])->before("\n\nThis is not medical advice") }}</p>
                                                @elseif($entry['type'] === 'report')
                                                    <p class="text-xs text-nivayalife-muted">No summary available yet.</p>
                                                @else
                                                    <p class="text-xs text-nivayalife-muted">{{ $entry['detail'] ?: 'No further details.' }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
