@php
    // Displayed even on editable tables — no reason to show password hashes
    // / secrets on screen at all, in case of a shared screen. The write
    // forms (create/edit) exclude these columns entirely, not just mask them.
    $sensitivePattern = '/password|secret|token|recovery_codes/i';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">
                <span class="font-mono">{{ $table }}</span>
            </h2>
            <div class="flex gap-2">
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

        <p class="mb-4 text-sm text-novix-muted">{{ number_format($rows->total()) }} rows{{ $isEditable ? '' : ' · read-only' }}</p>

        <div class="overflow-hidden rounded-novix bg-white shadow-novix-sm dark:bg-white/5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-white/10">
                            @foreach($columns as $column)
                                <th class="whitespace-nowrap px-3 py-2 font-semibold uppercase tracking-wide text-novix-muted">{{ $column }}</th>
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
                            <tr><td colspan="{{ count($columns) + ($isEditable ? 1 : 0) }}" class="px-3 py-4 text-novix-muted">No rows.</td></tr>
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
