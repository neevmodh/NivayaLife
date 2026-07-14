@php
    $typeIcons = ['report' => '&#128196;', 'medication' => '&#128138;', 'vaccination' => '&#128137;', 'metric' => '&#128200;'];
    $years = $grouped->keys()->sortDesc()->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Health Timeline</h2>
                <p class="mt-1 text-sm text-novix-muted">For {{ $active->full_name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('shares.create') }}?family_member_id={{ $active->id }}&access_type=full_summary"
                    class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    Share full history
                </a>
                <a href="{{ route('shares.history') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    Share history
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8" x-data="metricCompare()">

        {{-- Filters --}}
        <form method="GET" action="{{ route('timeline') }}" class="rounded-novix bg-white p-4 shadow-novix-sm dark:bg-white/5" x-data="{ range: @js($range) }">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Search reports</label>
                    <input type="text" name="q" value="{{ $search }}" placeholder="e.g. cholesterol"
                        @input.debounce.600ms="$event.target.form.requestSubmit()"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Category</label>
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
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Date range</label>
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
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">From</label>
                    <input type="date" name="from" value="{{ $customFrom }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">To</label>
                    <input type="date" name="to" value="{{ $customTo }}" onchange="this.form.requestSubmit()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
            </div>
        </form>

        {{-- Export PDF --}}
        <form method="GET" action="{{ route('timeline.export') }}" class="mt-3 flex flex-wrap items-center gap-2 rounded-novix bg-novix-mint/40 p-3 text-sm dark:bg-novix-green/10">
            <span class="font-semibold text-novix-ink dark:text-white">Export doctor-ready PDF for:</span>
            <select name="range" class="rounded-lg border border-gray-200 px-2 py-1.5 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="30d">Last 30 days</option>
                <option value="6m" selected>Last 6 months</option>
                <option value="1y">Last year</option>
                <option value="all">All time</option>
            </select>
            <button type="submit" class="rounded-lg bg-novix-green px-4 py-1.5 text-xs font-bold text-white hover:bg-novix-green-dark">Export PDF</button>
        </form>

        {{-- Compare panel --}}
        <div x-show="selected.length > 0" x-cloak class="sticky top-2 z-10 mt-4 rounded-novix border-2 border-novix-green bg-white p-4 shadow-novix dark:bg-novix-ink">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-novix-ink dark:text-white">Comparing <span x-text="selected.length"></span> value(s)</span>
                <button type="button" @click="clear()" class="text-xs font-semibold text-novix-muted hover:text-novix-ink">Clear</button>
            </div>
            <template x-if="comparableGroups.length === 0">
                <p class="mt-2 text-xs text-novix-muted">Select two or more values of the <strong>same</strong> metric to compare them.</p>
            </template>
            <template x-for="group in comparableGroups" :key="group.metricType">
                <div class="mt-3 rounded-xl bg-novix-cream/60 p-3 dark:bg-white/5">
                    <p class="text-xs font-bold uppercase tracking-wide text-novix-muted" x-text="group.label"></p>
                    <p class="mt-1 text-sm text-novix-ink dark:text-white">
                        <span x-text="group.oldest.value + ' ' + group.unit"></span>
                        (<span x-text="group.oldest.dateLabel"></span>)
                        &rarr;
                        <span x-text="group.newest.value + ' ' + group.unit"></span>
                        (<span x-text="group.newest.dateLabel"></span>)
                    </p>
                    <p class="mt-1 text-sm font-semibold"
                        :class="group.direction === 'up' ? 'text-novix-pink-dark' : (group.direction === 'down' ? 'text-novix-blue' : 'text-novix-muted')">
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
                <div class="rounded-novix bg-white p-10 text-center shadow-novix-sm dark:bg-white/5">
                    <p class="text-sm text-novix-muted">Nothing found for these filters.</p>
                </div>
            @endif

            @foreach($years as $year)
                @php($isRecentYear = $loop->first)
                <div x-data="{ open: {{ $isRecentYear ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open" class="flex w-full items-center gap-2 text-left">
                        <svg class="h-4 w-4 text-novix-muted transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <h3 class="text-lg font-bold text-novix-ink dark:text-white">{{ $year }}</h3>
                        <span class="text-xs text-novix-muted">({{ $grouped[$year]->flatten(1)->count() }})</span>
                    </button>

                    <div x-show="open" x-cloak class="mt-3 space-y-5 border-l-2 border-gray-100 pl-5 dark:border-white/10">
                        @foreach($grouped[$year] as $month => $monthEntries)
                            <div>
                                <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-novix-muted">{{ $month }}</h4>
                                <div class="space-y-2">
                                    @foreach($monthEntries->sortByDesc('date') as $entry)
                                        <div class="rounded-novix bg-white shadow-novix-sm dark:bg-white/5" x-data="{ open: false }">
                                            <div class="flex items-start gap-3 p-4">
                                                @if(isset($entry['compare']))
                                                    <input type="checkbox"
                                                        @change="toggle(@js($entry['compare']))"
                                                        :checked="isSelected({{ $entry['compare']['id'] }})"
                                                        class="mt-1.5 h-4 w-4 flex-shrink-0 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                                                @endif

                                                <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-novix-cream text-sm dark:bg-white/10">{!! $typeIcons[$entry['type']] !!}</span>

                                                <button type="button" @click="open = !open" class="min-w-0 flex-1 text-left">
                                                    <p class="truncate text-sm font-semibold capitalize text-novix-ink dark:text-white">{{ $entry['title'] }}</p>
                                                    <p class="text-xs text-novix-muted">
                                                        {{ $entry['date']->format('M j, Y') }}
                                                        @if($entry['subtitle']) &middot; {{ $entry['subtitle'] }} @endif
                                                    </p>
                                                </button>

                                                @if($entry['type'] === 'report')
                                                    <a href="{{ route('reports.show', $entry['model']) }}" class="flex-shrink-0 text-xs font-semibold text-novix-green hover:underline">Open</a>
                                                @endif
                                            </div>

                                            <div x-show="open" x-cloak x-transition class="border-t border-gray-100 px-4 py-3 text-sm text-novix-ink dark:border-white/10 dark:text-white">
                                                @if($entry['type'] === 'report' && $entry['detail'])
                                                    <p class="whitespace-pre-line text-xs text-novix-muted">{{ Str::of($entry['detail'])->before("\n\nThis is not medical advice") }}</p>
                                                @elseif($entry['type'] === 'report')
                                                    <p class="text-xs text-novix-muted">No summary available yet.</p>
                                                @else
                                                    <p class="text-xs text-novix-muted">{{ $entry['detail'] ?: 'No further details.' }}</p>
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
