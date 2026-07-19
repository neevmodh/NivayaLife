<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Database tables</h2>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Back to overview
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <p class="mb-4 text-sm text-novix-muted">Read-only — for viewing and troubleshooting. Editing goes through a direct database client.</p>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($tables as $table)
                <a href="{{ route('admin.tables.show', $table['name']) }}" class="flex items-center justify-between rounded-novix bg-white p-4 shadow-novix-sm transition hover:shadow-novix dark:bg-white/5">
                    <span class="font-mono text-sm font-semibold text-novix-ink dark:text-white">{{ $table['name'] }}</span>
                    <span class="rounded-full bg-novix-mint px-2.5 py-1 text-xs font-bold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">{{ number_format($table['count']) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
