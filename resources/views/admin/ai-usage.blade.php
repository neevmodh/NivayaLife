@php
    $fmt = fn ($n) => number_format((int) $n);
    $peak = collect($dailySeries)->max('tokens') ?: 1;

    $statusTone = [
        'completed' => 'bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint',
        'failed' => 'bg-nivayalife-pink/30 text-nivayalife-pink-dark',
        'running' => 'bg-nivayalife-yellow/30 text-amber-700 dark:text-nivayalife-yellow',
        'queued' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">AI usage &amp; cost</h2>
                <span class="mt-1.5 block h-0.5 w-10 rounded-full bg-nivayalife-gold" aria-hidden="true"></span>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-nivayalife-ink transition hover:-translate-y-0.5 hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Back to overview
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-nivayalife-muted">Last</span>
            <div class="flex gap-1 rounded-full bg-white p-1 shadow-nivayalife-sm dark:bg-white/5">
                @foreach($ranges as $value)
                    <a href="{{ route('admin.ai-usage', ['range' => $value]) }}"
                        class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $range === $value ? 'bg-nivayalife-green text-white' : 'text-nivayalife-muted hover:text-nivayalife-ink dark:hover:text-white' }}">
                        {{ $value }} days
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Headline numbers --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-nivayalife-muted">Estimated spend</p>
                <p class="mt-1 text-2xl font-bold text-nivayalife-ink dark:text-white">${{ number_format($estimatedCost, 2) }}</p>
                <p class="mt-1 text-xs text-nivayalife-muted">over {{ $days }} days</p>
            </div>
            <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-nivayalife-muted">Tokens in</p>
                <p class="mt-1 text-2xl font-bold text-nivayalife-ink dark:text-white">{{ $fmt($inputTokens) }}</p>
                <p class="mt-1 text-xs text-nivayalife-muted">prompts sent</p>
            </div>
            <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-nivayalife-muted">Tokens out</p>
                <p class="mt-1 text-2xl font-bold text-nivayalife-ink dark:text-white">{{ $fmt($outputTokens) }}</p>
                <p class="mt-1 text-xs text-nivayalife-muted">replies generated</p>
            </div>
            <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-nivayalife-muted">Calls</p>
                <p class="mt-1 text-2xl font-bold text-nivayalife-ink dark:text-white">{{ $fmt($reportRuns + $chatRuns) }}</p>
                <p class="mt-1 text-xs text-nivayalife-muted">{{ $fmt($reportRuns) }} report &middot; {{ $fmt($chatRuns) }} chat</p>
            </div>
        </div>

        <p class="rounded-xl bg-nivayalife-cream/70 px-4 py-2.5 text-xs text-nivayalife-muted dark:bg-white/5">
            Cost is estimated from a configured per-million-token rate, not billed figures — treat it as an early warning, not an invoice.
        </p>

        {{-- Daily tokens, drawn from the series rather than a chart library --}}
        <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
            <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Tokens per day</h3>
            @if(collect($dailySeries)->sum('tokens') === 0)
                <p class="mt-3 text-sm text-nivayalife-muted">No AI activity in this period.</p>
            @else
                <div class="mt-4 flex h-40 items-end gap-px">
                    @foreach($dailySeries as $point)
                        <div class="group relative flex-1" style="height:100%">
                            <div class="absolute bottom-0 w-full rounded-t bg-nivayalife-green/80 transition group-hover:bg-nivayalife-green"
                                style="height: {{ max(2, round($point['tokens'] / $peak * 100)) }}%"></div>
                            <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-nivayalife-ink px-2 py-1 text-[10px] font-semibold text-white group-hover:block">
                                {{ $fmt($point['tokens']) }} · {{ \Illuminate\Support\Carbon::parse($point['date'])->format('M j') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Heaviest assistant users --}}
            <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Heaviest assistant users</h3>
                <p class="mt-0.5 text-xs text-nivayalife-muted">The assistant has no rate limit, so this is where a runaway would show first.</p>
                @if($topChatUsers->isEmpty())
                    <p class="mt-4 text-sm text-nivayalife-muted">No assistant activity in this period.</p>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($topChatUsers as $row)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-nivayalife-ink dark:text-white">{{ $row->name }}</p>
                                    <p class="truncate text-xs text-nivayalife-muted">{{ $row->email }}</p>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-sm font-bold text-nivayalife-ink dark:text-white">{{ $fmt($row->tokens) }}</p>
                                    <p class="text-xs text-nivayalife-muted">{{ $fmt($row->messages) }} msgs</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Where the tokens go --}}
            <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">By job type</h3>
                @if($byJobType->isEmpty())
                    <p class="mt-4 text-sm text-nivayalife-muted">No report jobs in this period.</p>
                @else
                    <ul class="mt-3 space-y-3">
                        @php($maxTokens = $byJobType->max('tokens') ?: 1)
                        @foreach($byJobType as $row)
                            <li>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-nivayalife-ink dark:text-white">{{ Str::headline($row->job_type) }}</span>
                                    <span class="text-nivayalife-muted">{{ $fmt($row->tokens) }} tokens · {{ $fmt($row->runs) }} runs</span>
                                </div>
                                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-nivayalife-cream dark:bg-white/10">
                                    <div class="h-full rounded-full bg-nivayalife-green" style="width: {{ max(2, round($row->tokens / $maxTokens * 100)) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($byStatus->isNotEmpty())
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
                        @foreach($byStatus as $status => $runs)
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusTone[$status] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ Str::headline($status) }}: {{ $fmt($runs) }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if($byProvider->isNotEmpty())
                    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-nivayalife-muted">By provider</p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach($byProvider as $row)
                                <li class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-nivayalife-ink dark:text-white">{{ $row->provider }}</span>
                                    <span class="text-nivayalife-muted">{{ $fmt($row->tokens) }} tokens · {{ $fmt($row->runs) }} runs</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        @if($recentFailures->isNotEmpty())
            <div class="rounded-nivayalife border-t-2 border-nivayalife-pink-dark/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Recent AI failures</h3>
                <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                    @foreach($recentFailures as $failure)
                        <li class="py-2.5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-semibold text-nivayalife-ink dark:text-white">
                                    {{ Str::headline($failure->job_type) }}@if($failure->provider) &middot; {{ $failure->provider }} @endif
                                </span>
                                <span class="flex-shrink-0 text-xs text-nivayalife-muted">{{ $failure->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 break-words text-xs text-nivayalife-pink-dark">{{ Str::limit($failure->error_message, 220) }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
