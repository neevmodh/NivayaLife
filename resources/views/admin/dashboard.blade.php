@php
    $formatBytes = function (?int $bytes) {
        $bytes = $bytes ?? 0;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2).' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2).' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2).' KB';
        return $bytes.' B';
    };

    $ranges = ['7' => '7 days', '30' => '30 days', '90' => '90 days', '365' => '1 year', 'all' => 'All time'];

    $novixPalette = ['#1E5A45', '#8FB8E0', '#F4A9A0', '#F5C879', '#2E7A5D', '#E8615A'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Admin overview</h2>
            <a href="{{ route('admin.tables') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Browse database tables
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        {{-- Top-line stats --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Users</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($userCount) }}</p>
                <p class="mt-1 text-xs text-novix-muted">{{ $verifiedUserCount }} verified &middot; {{ $newUsersLast7Days }} new (7d)</p>
            </div>
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Family members</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($familyMemberCount) }}</p>
                <p class="mt-1 text-xs text-novix-muted">
                    @foreach($familyMembersByAccessType as $type => $count)
                        {{ Str::headline($type) }}: {{ $count }}@if(!$loop->last) &middot; @endif
                    @endforeach
                </p>
            </div>
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Reports</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($reportCount) }}</p>
                <p class="mt-1 text-xs text-novix-muted">{{ $formatBytes($totalStorageBytes) }} stored</p>
            </div>
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Background jobs</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($pendingJobCount) }}</p>
                <p class="mt-1 text-xs {{ $failedJobCount > 0 ? 'text-novix-pink-dark font-semibold' : 'text-novix-muted' }}">
                    {{ $failedJobCount }} failed
                </p>
            </div>
        </div>

        {{-- Range selector --}}
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Trends over</span>
            <div class="flex gap-1 rounded-full bg-white p-1 shadow-novix-sm dark:bg-white/5">
                @foreach($ranges as $value => $label)
                    {{-- $ranges' numeric-looking keys (7, 30, 90, 365) get silently cast to int by PHP's array-key normalization, while $range from the query string is always a string — cast both sides the same way rather than relying on == --}}
                    <a href="{{ route('admin.dashboard', ['range' => $value]) }}"
                        class="rounded-full px-3 py-1 text-xs font-semibold transition {{ (string) $value === $range ? 'bg-novix-green text-white' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Landing page traffic --}}
        <div>
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-novix-muted">Landing page traffic</h3>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Total views</p>
                    <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($pageViewsTotal) }}</p>
                    <p class="mt-1 text-xs text-novix-muted">{{ number_format($pageViewsToday) }} today</p>
                </div>
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Views in range</p>
                    <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($pageViewsInRange) }}</p>
                    <p class="mt-1 text-xs text-novix-muted">for the selected period</p>
                </div>
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Unique visitors</p>
                    <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($uniqueVisitorsInRange) }}</p>
                    <p class="mt-1 text-xs text-novix-muted">by IP, in range</p>
                </div>
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Visitor → signup</p>
                    <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ $conversionRate !== null ? $conversionRate.'%' : '—' }}</p>
                    <p class="mt-1 text-xs text-novix-muted">signups ÷ views, in range</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5 lg:col-span-2">
                    <h3 class="text-sm font-bold text-novix-ink dark:text-white">Views over time</h3>
                    @if(collect($pageViewSeries)->sum('count') === 0)
                        <p class="mt-3 text-sm text-novix-muted">No landing page views recorded yet.</p>
                    @else
                        <div class="mt-2" x-data="adminChart({
                            type: 'area',
                            series: [{ name: 'Views', data: @js(collect($pageViewSeries)->pluck('count')) }],
                            options: {
                                colors: ['{{ $novixPalette[3] }}'],
                                stroke: { curve: 'smooth', width: 2 },
                                fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.05 } },
                                dataLabels: { enabled: false },
                                xaxis: { categories: @js(collect($pageViewSeries)->pluck('date')), labels: { show: false }, axisTicks: { show: false } },
                                grid: { borderColor: 'rgba(148,163,184,0.2)' },
                            },
                        })"></div>
                    @endif
                </div>

                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <h3 class="text-sm font-bold text-novix-ink dark:text-white">Top referrers</h3>
                    <div class="mt-3 space-y-2">
                        @forelse($topReferrers as $host => $count)
                            <div class="flex items-center justify-between text-sm">
                                <span class="truncate text-novix-muted">{{ $host }}</span>
                                <span class="flex-shrink-0 font-semibold text-novix-ink dark:text-white">{{ number_format($count) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-novix-muted">No referrer data yet — most visits are likely direct.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Views by hour of day</h3>
                <div class="mt-2" x-data="adminChart({
                    type: 'bar',
                    series: [{ name: 'Views', data: @js($pageViewsByHour) }],
                    options: {
                        colors: ['{{ $novixPalette[2] }}'],
                        plotOptions: { bar: { borderRadius: 4, columnWidth: '70%' } },
                        xaxis: { categories: @js(collect(range(0, 23))->map(fn($h) => sprintf('%02d:00', $h))) },
                        dataLabels: { enabled: false },
                        grid: { borderColor: 'rgba(148,163,184,0.2)' },
                        height: 220,
                    },
                })"></div>
            </div>
        </div>

        {{-- Time-series charts --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Signups</h3>
                <div class="mt-2" x-data="adminChart({
                    type: 'area',
                    series: [{ name: 'Signups', data: @js(collect($signupSeries)->pluck('count')) }],
                    options: {
                        colors: ['{{ $novixPalette[0] }}'],
                        stroke: { curve: 'smooth', width: 2 },
                        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: @js(collect($signupSeries)->pluck('date')), labels: { show: false }, axisTicks: { show: false } },
                        grid: { borderColor: 'rgba(148,163,184,0.2)' },
                    },
                })"></div>
            </div>

            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Reports uploaded</h3>
                <div class="mt-2" x-data="adminChart({
                    type: 'area',
                    series: [{ name: 'Reports', data: @js(collect($reportSeries)->pluck('count')) }],
                    options: {
                        colors: ['{{ $novixPalette[1] }}'],
                        stroke: { curve: 'smooth', width: 2 },
                        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: @js(collect($reportSeries)->pluck('date')), labels: { show: false }, axisTicks: { show: false } },
                        grid: { borderColor: 'rgba(148,163,184,0.2)' },
                    },
                })"></div>
            </div>
        </div>

        {{-- Breakdown donuts/bars --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Reports by type</h3>
                @if($reportsByType->isEmpty())
                    <p class="mt-3 text-sm text-novix-muted">No reports yet.</p>
                @else
                    <div class="mt-2" x-data="adminChart({
                        type: 'donut',
                        series: @js($reportsByType->values()),
                        options: {
                            colors: @js($novixPalette),
                            labels: @js($reportsByType->keys()->map(fn($k) => Str::headline($k))),
                            legend: { position: 'bottom', fontSize: '11px' },
                            dataLabels: { enabled: false },
                        },
                    })"></div>
                @endif
            </div>

            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">OCR status</h3>
                @if($reportsByOcrStatus->isEmpty())
                    <p class="mt-3 text-sm text-novix-muted">No reports yet.</p>
                @else
                    <div class="mt-2" x-data="adminChart({
                        type: 'donut',
                        series: @js($reportsByOcrStatus->values()),
                        options: {
                            colors: @js($novixPalette),
                            labels: @js($reportsByOcrStatus->keys()->map(fn($k) => Str::headline($k))),
                            legend: { position: 'bottom', fontSize: '11px' },
                            dataLabels: { enabled: false },
                        },
                    })"></div>
                @endif
            </div>

            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">AI jobs</h3>
                @if($aiJobsByStatus->isEmpty())
                    <p class="mt-3 text-sm text-novix-muted">No AI jobs yet.</p>
                @else
                    <div class="mt-2" x-data="adminChart({
                        type: 'bar',
                        series: [{ name: 'Jobs', data: @js($aiJobsByStatus->values()) }],
                        options: {
                            colors: ['{{ $novixPalette[0] }}'],
                            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                            xaxis: { categories: @js($aiJobsByStatus->keys()->map(fn($k) => Str::headline($k))) },
                            dataLabels: { enabled: false },
                            grid: { borderColor: 'rgba(148,163,184,0.2)' },
                        },
                    })"></div>
                @endif
            </div>

            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Blood groups</h3>
                @if($bloodGroupDistribution->isEmpty())
                    <p class="mt-3 text-sm text-novix-muted">No data yet.</p>
                @else
                    <div class="mt-2" x-data="adminChart({
                        type: 'donut',
                        series: @js($bloodGroupDistribution->values()),
                        options: {
                            colors: @js($novixPalette),
                            labels: @js($bloodGroupDistribution->keys()),
                            legend: { position: 'bottom', fontSize: '11px' },
                            dataLabels: { enabled: false },
                        },
                    })"></div>
                @endif
            </div>
        </div>

        {{-- Age distribution --}}
        <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
            <h3 class="text-sm font-bold text-novix-ink dark:text-white">Age distribution</h3>
            <div class="mt-2" x-data="adminChart({
                type: 'bar',
                series: [{ name: 'Family members', data: @js(array_values($ageBuckets)) }],
                options: {
                    colors: ['{{ $novixPalette[4] }}'],
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                    xaxis: { categories: @js(array_keys($ageBuckets)) },
                    dataLabels: { enabled: false },
                    grid: { borderColor: 'rgba(148,163,184,0.2)' },
                    height: 220,
                },
            })"></div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Recent users --}}
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Recent signups</h3>
                <div class="mt-3 space-y-3">
                    @forelse($recentUsers as $user)
                        <div class="flex items-center gap-3 text-sm">
                            <x-avatar :photo-path="$user->avatar_path" :full-name="$user->name" size="h-9 w-9" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-novix-ink dark:text-white">{{ $user->name }}</p>
                                <p class="truncate text-xs text-novix-muted">{{ $user->email }}</p>
                            </div>
                            <span class="flex-shrink-0 text-xs text-novix-muted">{{ $user->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-novix-muted">No users yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent activity --}}
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Recent activity</h3>
                <div class="mt-3 max-h-72 overflow-y-auto overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs font-semibold uppercase tracking-wide text-novix-muted">
                                <th class="pb-2 pr-4">When</th>
                                <th class="pb-2 pr-4">Who</th>
                                <th class="pb-2 pr-4">Action</th>
                                <th class="pb-2">Target</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @forelse($recentAuditLog as $entry)
                                <tr>
                                    <td class="whitespace-nowrap py-2 pr-4 text-novix-muted">{{ $entry->created_at->diffForHumans() }}</td>
                                    <td class="whitespace-nowrap py-2 pr-4">{{ $entry->user?->name ?? 'System' }}</td>
                                    <td class="whitespace-nowrap py-2 pr-4 font-medium text-novix-ink dark:text-white">{{ Str::headline($entry->action) }}</td>
                                    <td class="whitespace-nowrap py-2 text-novix-muted">{{ $entry->target_type }} #{{ $entry->target_id }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-3 text-novix-muted">No activity recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
