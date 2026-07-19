<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">
                New row in <span class="font-mono">{{ $table }}</span>
            </h2>
            <a href="{{ route('admin.tables.show', $table) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('admin_error'))
            <div class="mb-4 rounded-xl bg-novix-pink/20 px-4 py-3 text-sm font-semibold text-novix-pink-dark">{{ session('admin_error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.tables.store', $table) }}" class="space-y-4 rounded-novix bg-white p-6 shadow-novix-sm dark:bg-white/5">
            @csrf
            @include('admin.tables._form-fields', ['columns' => $columns, 'values' => null])

            <div class="flex justify-end pt-2">
                <button type="submit" class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">Create row</button>
            </div>
        </form>
    </div>
</x-app-layout>
