@php
    // Displayed even on editable tables — no reason to show password hashes
    // / secrets on screen at all, in case of a shared screen. The write
    // forms (create/edit) exclude these columns entirely, not just mask them.
    $sensitivePattern = '/password|secret|token|recovery_codes/i';

    // photo_path/avatar_path live on the public disk (same as everywhere
    // else in the app, e.g. <x-avatar>) so Storage::url() resolves them
    // directly. "avatar" is a raw external URL (Google's profile photo),
    // used as-is. Deliberately NOT extended to reports.file_path — those
    // live on the private disk behind their own authorization check, and
    // are sensitive medical documents rather than profile photos.
    $publicDiskImageColumns = ['photo_path', 'avatar_path'];
    $externalUrlColumns = ['avatar'];

    $sortUrl = fn ($column) => route('admin.tables.show', array_filter([
        'table' => $table, 'q' => $q ?: null, 'sort' => $column,
        'dir' => ($sortColumn === $column && $sortDir === 'asc') ? 'desc' : 'asc',
    ]));

    $exportUrl = route('admin.tables.export', array_filter([
        'table' => $table, 'q' => $q ?: null, 'sort' => $sortColumn, 'dir' => $sortDir,
    ]));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">
                <span class="font-mono">{{ $table }}</span>
            </h2>
            <div class="flex gap-2">
                <a href="{{ $exportUrl }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    Export CSV
                </a>
                @if($isEditable)
                    <a href="{{ route('admin.tables.create', $table) }}" class="rounded-lg bg-novix-green px-4 py-2 text-sm font-semibold text-white hover:bg-novix-green-dark">
                        Add row
                    </a>
                @endif
                <a href="{{ route('admin.tables') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    All tables
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('admin_status'))
            <div class="mb-4 rounded-xl bg-novix-mint px-4 py-3 text-sm font-semibold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">{{ session('admin_status') }}</div>
        @endif
        @if(session('admin_error'))
            <div class="mb-4 rounded-xl bg-novix-pink/20 px-4 py-3 text-sm font-semibold text-novix-pink-dark">{{ session('admin_error') }}</div>
        @endif

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-novix-muted">{{ number_format($rows->total()) }} rows{{ $isEditable ? '' : ' · read-only' }}</p>

            <form method="GET" action="{{ route('admin.tables.show', $table) }}" class="flex items-center gap-2">
                @if($sortColumn !== $primaryKey || $sortDir !== 'desc')
                    <input type="hidden" name="sort" value="{{ $sortColumn }}">
                    <input type="hidden" name="dir" value="{{ $sortDir }}">
                @endif
                <input type="search" name="q" value="{{ $q }}" placeholder="Search {{ count($searchableColumns) }} text column{{ count($searchableColumns) === 1 ? '' : 's' }}…"
                    class="w-64 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-novix-ink shadow-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                @if($q)
                    <a href="{{ route('admin.tables.show', $table) }}" class="text-xs font-semibold text-novix-muted hover:text-novix-ink">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-hidden rounded-novix bg-white shadow-novix-sm dark:bg-white/5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-white/10">
                            @foreach($columns as $column)
                                <th class="whitespace-nowrap px-3 py-2 font-semibold uppercase tracking-wide text-novix-muted">
                                    <a href="{{ $sortUrl($column) }}" class="inline-flex items-center gap-1 hover:text-novix-ink dark:hover:text-white">
                                        {{ $column }}
                                        @if($sortColumn === $column)
                                            <span aria-hidden="true">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @endif
                                    </a>
                                </th>
                            @endforeach
                            @if($isEditable)
                                <th class="whitespace-nowrap px-3 py-2"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse($rows as $row)
                            <tr>
                                @foreach($columns as $column)
                                    @php($value = $row->$column ?? null)
                                    <td class="whitespace-nowrap px-3 py-2 text-novix-ink dark:text-white">
                                        @if(preg_match($sensitivePattern, $column) && $value !== null)
                                            <span class="text-novix-muted">••••••••</span>
                                        @elseif($value === null)
                                            <span class="text-novix-muted">—</span>
                                        @elseif(in_array($column, $publicDiskImageColumns))
                                            <a href="{{ Storage::url($value) }}" target="_blank">
                                                <img src="{{ Storage::url($value) }}" alt="" class="h-8 w-8 rounded-full object-cover" loading="lazy">
                                            </a>
                                        @elseif(in_array($column, $externalUrlColumns))
                                            <a href="{{ $value }}" target="_blank">
                                                <img src="{{ $value }}" alt="" class="h-8 w-8 rounded-full object-cover" loading="lazy">
                                            </a>
                                        @else
                                            {{ Str::limit((string) $value, 60) }}
                                        @endif
                                    </td>
                                @endforeach
                                @if($isEditable)
                                    <td class="whitespace-nowrap px-3 py-2">
                                        <a href="{{ route('admin.tables.edit', [$table, $row->$primaryKey]) }}" class="font-semibold text-novix-green hover:underline">Edit</a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($columns) + ($isEditable ? 1 : 0) }}" class="px-3 py-4 text-novix-muted">No rows{{ $q ? ' match that search' : '' }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </div>
</x-app-layout>
