@php($isEdit = $vaccination->exists)

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">{{ $isEdit ? 'Edit Vaccination' : 'Add Vaccination' }}</h2>
        <p class="mt-1 text-sm text-nivayalife-muted">For {{ $active->full_name }}</p>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $isEdit ? route('vaccinations.update', $vaccination) : route('vaccinations.store') }}"
            class="space-y-5 rounded-nivayalife bg-white p-6 shadow-nivayalife-sm dark:bg-white/5">
            @csrf
            @if($isEdit) @method('PATCH') @endif
            <input type="hidden" name="family_member_id" value="{{ $active->id }}">

            @if($errors->any())
                <div class="rounded-xl bg-nivayalife-pink/20 p-3 text-sm text-nivayalife-pink-dark">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-floating-input name="vaccine_name" label="Vaccine name" :value="old('vaccine_name', $vaccination->vaccine_name)" :required="true" />

            <div class="grid grid-cols-2 gap-4">
                <x-floating-input type="number" name="dose_number" label="Dose number" :value="old('dose_number', $vaccination->dose_number ?? 1)" :required="true" min="1" />
                <x-floating-input type="date" name="date_administered" label="Date given" :value="old('date_administered', $vaccination->date_administered?->toDateString())" :required="true" :max="now()->toDateString()" />
            </div>

            <div>
                <x-floating-input type="date" name="next_due_date" label="Next dose due (optional)" :value="old('next_due_date', $vaccination->next_due_date?->toDateString())" />
                <p class="mt-1 text-[11px] text-nivayalife-muted">We'll email a reminder as this date approaches.</p>
            </div>

            <x-floating-input name="location" label="Location" :value="old('location', $vaccination->location)" />

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('vaccinations.index') }}?member={{ $active->id }}" class="text-sm font-semibold text-nivayalife-muted hover:text-nivayalife-ink dark:hover:text-white">Cancel</a>
                <button type="submit" class="rounded-xl bg-nivayalife-green px-6 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark">
                    {{ $isEdit ? 'Save changes' : 'Add vaccination' }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
