<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">
                Edit <span class="font-mono">{{ $table }}</span> #{{ $id }}
            </h2>
            <a href="{{ route('admin.tables.show', $table) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-nivayalife-ink hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('admin_error'))
            <div class="mb-4 rounded-xl bg-nivayalife-pink/20 px-4 py-3 text-sm font-semibold text-nivayalife-pink-dark">{{ session('admin_error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.tables.update', [$table, $id]) }}" class="space-y-4 rounded-nivayalife bg-white p-6 shadow-nivayalife-sm dark:bg-white/5">
            @csrf
            @method('PUT')
            @include('admin.tables._form-fields', ['columns' => $columns, 'values' => $row])

            <div class="flex justify-end pt-2">
                <button type="submit" class="rounded-xl bg-nivayalife-green px-6 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm hover:bg-nivayalife-green-dark">Save changes</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.tables.destroy', [$table, $id]) }}" onsubmit="return confirm('Delete this row? This cannot be undone from the admin panel.');" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-xl border border-nivayalife-pink-dark/30 px-5 py-2.5 text-sm font-semibold text-nivayalife-pink-dark hover:bg-nivayalife-pink/10">Delete row</button>
        </form>
    </div>
</x-app-layout>
