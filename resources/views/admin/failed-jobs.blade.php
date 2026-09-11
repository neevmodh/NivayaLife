<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-nivayalife-ink dark:text-white">Failed jobs</h2>
                <span class="mt-1.5 block h-0.5 w-10 rounded-full bg-nivayalife-gold" aria-hidden="true"></span>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-nivayalife-ink transition hover:-translate-y-0.5 hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Back to overview
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('admin_status'))
            <div class="mb-4 rounded-xl bg-nivayalife-mint px-4 py-3 text-sm font-semibold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">{{ session('admin_status') }}</div>
        @endif
        @if(session('admin_error'))
            <div class="mb-4 rounded-xl bg-nivayalife-pink/20 px-4 py-3 text-sm font-semibold text-nivayalife-pink-dark">{{ session('admin_error') }}</div>
        @endif

        @if($jobs->isEmpty())
            <div class="rounded-nivayalife bg-white shadow-nivayalife-sm dark:bg-white/5">
                <x-empty-state
                    title="Nothing has failed"
                    hint="Queued work that errors out lands here, where you can read the reason and retry it."
                    tone="positive"
                    class="py-14" />
            </div>
        @else
            <p class="mb-4 text-sm text-nivayalife-muted">
                Showing the {{ $jobs->count() }} most recent of {{ number_format($total) }}. Retrying puts a job back on the queue exactly as the framework would.
            </p>

            <div class="space-y-3">
                @foreach($jobs as $job)
                    <div class="rounded-nivayalife border-t-2 border-nivayalife-pink-dark/50 bg-white p-4 shadow-nivayalife-sm dark:bg-white/5" x-data="{ open: false }">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-nivayalife-ink dark:text-white">{{ $job->name }}</p>
                                <p class="mt-0.5 text-xs text-nivayalife-muted">
                                    Queue: {{ $job->queue }} &middot; {{ \Illuminate\Support\Carbon::parse($job->failed_at)->diffForHumans() }}
                                </p>
                                <p class="mt-1.5 break-words text-xs text-nivayalife-pink-dark">{{ Str::limit($job->reason, 200) }}</p>
                            </div>

                            <div class="flex flex-shrink-0 gap-2">
                                <form method="POST" action="{{ route('admin.failed-jobs.retry', $job->uuid) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-nivayalife-green px-3 py-1.5 text-xs font-bold text-white transition hover:bg-nivayalife-green-dark active:scale-95">Retry</button>
                                </form>
                                <form method="POST" action="{{ route('admin.failed-jobs.delete', $job->uuid) }}"
                                    onsubmit="return confirm('Remove this job from the failed queue? It will not run again.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-nivayalife-muted transition hover:border-nivayalife-pink-dark hover:text-nivayalife-pink-dark active:scale-95 dark:border-white/10">Discard</button>
                                </form>
                            </div>
                        </div>

                        <button type="button" @click="open = !open" class="mt-2 text-[11px] font-semibold text-nivayalife-muted transition hover:text-nivayalife-green" :aria-expanded="open.toString()">
                            <span x-text="open ? 'Hide stack trace' : 'Show stack trace'"></span>
                        </button>
                        <pre x-show="open" x-cloak class="mt-2 max-h-64 overflow-auto rounded-lg bg-nivayalife-cream/70 p-3 text-[10px] leading-relaxed text-nivayalife-ink dark:bg-black/30 dark:text-white/70">{{ $job->exception }}</pre>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
