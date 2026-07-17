@php($isEdit = $vaccination->exists)

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">{{ $isEdit ? 'Edit Vaccination' : 'Add Vaccination' }}</h2>
        <p class="mt-1 text-sm text-novix-muted">For {{ $active->full_name }}</p>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $isEdit ? route('vaccinations.update', $vaccination) : route('vaccinations.store') }}"
            class="space-y-5 rounded-novix bg-white p-6 shadow-novix-sm dark:bg-white/5">
            @csrf
            @if($isEdit) @method('PATCH') @endif
            <input type="hidden" name="family_member_id" value="{{ $active->id }}">

            @if($errors->any())
                <div class="rounded-xl bg-novix-pink/20 p-3 text-sm text-novix-pink-dark">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-xs font-semibold text-novix-muted">Vaccine name</label>
                <input type="text" name="vaccine_name" value="{{ old('vaccine_name', $vaccination->vaccine_name) }}" required
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Dose number</label>
                    <input type="number" min="1" name="dose_number" value="{{ old('dose_number', $vaccination->dose_number ?? 1) }}" required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Date given</label>
                    <input type="date" name="date_administered" value="{{ old('date_administered', $vaccination->date_administered?->toDateString()) }}" max="{{ now()->toDateString() }}" required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-novix-muted">Next dose due (optional)</label>
                <input type="date" name="next_due_date" value="{{ old('next_due_date', $vaccination->next_due_date?->toDateString()) }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                <p class="mt-1 text-[11px] text-novix-muted">We'll email a reminder as this date approaches.</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-novix-muted">Location</label>
                <input type="text" name="location" value="{{ old('location', $vaccination->location) }}" placeholder="e.g. City Clinic"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('vaccinations.index') }}?member={{ $active->id }}" class="text-sm font-semibold text-novix-muted hover:text-novix-ink dark:hover:text-white">Cancel</a>
                <button type="submit" class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark">
                    {{ $isEdit ? 'Save changes' : 'Add vaccination' }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
