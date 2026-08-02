@php($isEdit = $medication->exists)

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">{{ $isEdit ? 'Edit Medication' : 'Add Medication' }}</h2>
        <p class="mt-1 text-sm text-novix-muted">For {{ $active->full_name }}</p>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $isEdit ? route('medications.update', $medication) : route('medications.store') }}"
            class="space-y-5 rounded-novix bg-white p-6 shadow-novix-sm dark:bg-white/5"
            x-data="{ times: @js($medication->schedule_times ?? []), newTime: '' }"
        >
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
                <label class="mb-1 block text-xs font-semibold text-novix-muted">Medicine name</label>
                <input type="text" name="medicine_name" value="{{ old('medicine_name', $medication->medicine_name) }}" required
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Dosage</label>
                    <input type="text" name="dosage" value="{{ old('dosage', $medication->dosage) }}" placeholder="e.g. 500mg"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Frequency</label>
                    <input type="text" name="frequency" value="{{ old('frequency', $medication->frequency) }}" placeholder="e.g. Twice daily"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-novix-muted">Reminder times</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(time, index) in times" :key="time">
                        <span class="flex items-center gap-1.5 rounded-full bg-novix-cream px-3 py-1.5 text-xs font-semibold text-novix-ink dark:bg-white/10 dark:text-white">
                            <span x-text="time"></span>
                            <input type="hidden" name="schedule_times[]" :value="time">
                            <button type="button" @click="times.splice(index, 1)" class="text-novix-muted hover:text-novix-pink-dark" aria-label="Remove time">&times;</button>
                        </span>
                    </template>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <input type="time" x-model="newTime" class="rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                    <button type="button" @click="if (newTime && !times.includes(newTime)) { times.push(newTime); newTime = ''; }"
                        class="rounded-lg border border-novix-green px-3 py-2 text-xs font-semibold text-novix-green hover:bg-novix-mint/40">Add time</button>
                </div>
                <p class="mt-1 text-[11px] text-novix-muted">Add as many times a day as needed — a reminder is sent around each one below, if reminders are turned on.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">Start date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $medication->start_date?->toDateString()) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-novix-muted">End date</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $medication->end_date?->toDateString()) }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-novix-muted">Prescribing doctor</label>
                <input type="text" name="prescribing_doctor" value="{{ old('prescribing_doctor', $medication->prescribing_doctor) }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 text-sm font-medium text-novix-ink dark:text-white">
                    <input type="checkbox" name="active" value="1" {{ old('active', $medication->exists ? $medication->active : true) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    Currently taking
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-novix-ink dark:text-white">
                    <input type="checkbox" name="reminder_enabled" value="1" {{ old('reminder_enabled', $medication->exists ? $medication->reminder_enabled : true) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    Remind me (email + push)
                </label>
            </div>
            <p class="-mt-3 text-[11px] text-novix-muted">
                Push notifications arrive on this device like an alarm, with "Mark as taken" and "Snooze" buttons — turn them on from Profile &rarr; Notifications.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('medications.index') }}?member={{ $active->id }}" class="text-sm font-semibold text-novix-muted hover:text-novix-ink dark:hover:text-white">Cancel</a>
                <button type="submit" class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark">
                    {{ $isEdit ? 'Save changes' : 'Add medication' }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
