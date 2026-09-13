<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">Medications</h2>
                <p class="mt-1 text-sm text-nivayalife-muted">For {{ $active->full_name }}</p>
            </div>
            @if($canEdit)
                <a href="{{ route('medications.create') }}?member={{ $active->id }}" class="flex items-center gap-2 rounded-xl bg-nivayalife-green px-5 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Add Medication
                </a>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                class="mb-4 rounded-xl bg-nivayalife-mint px-4 py-3 text-sm font-semibold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">
                {{ session('status') }}
            </div>
        @endif

        @if($medications->isNotEmpty())
            @php
                $activeCount = $medications->where('active', true)->count();
                $remindersCount = $medications->where('reminder_enabled', true)->count();
            @endphp
            <div class="mb-4 grid grid-cols-3 gap-3">
                <div class="rounded-nivayalife bg-white p-3.5 text-center shadow-nivayalife-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold text-nivayalife-ink dark:text-white">{{ $medications->count() }}</p>
                    <p class="text-[11px] font-semibold text-nivayalife-muted">Total</p>
                </div>
                <div class="rounded-nivayalife bg-white p-3.5 text-center shadow-nivayalife-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold text-nivayalife-green dark:text-nivayalife-mint">{{ $activeCount }}</p>
                    <p class="text-[11px] font-semibold text-nivayalife-muted">Active</p>
                </div>
                <div class="rounded-nivayalife bg-white p-3.5 text-center shadow-nivayalife-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold text-nivayalife-ink dark:text-white">{{ $remindersCount }}</p>
                    <p class="text-[11px] font-semibold text-nivayalife-muted">Reminders on</p>
                </div>
            </div>
        @endif

        @if($medications->isEmpty())
            <div class="rounded-nivayalife bg-white shadow-nivayalife-sm dark:bg-white/5">
                <x-empty-state
                    icon="pill"
                    title="No medications recorded yet."
                    :action-label="$canEdit ? 'Add the first one' : null"
                    :action-url="$canEdit ? route('medications.create').'?member='.$active->id : null"
                    class="py-10" />
            </div>
        @else
            <div class="space-y-3">
                @foreach($medications as $medication)
                    <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-nivayalife-ink dark:text-white">{{ $medication->medicine_name }}</p>
                                    @if(!$medication->active)
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500 dark:bg-white/10 dark:text-white/60">Inactive</span>
                                    @endif
                                    @if($medication->reminder_enabled)
                                        <span class="flex items-center gap-1 rounded-full bg-nivayalife-mint px-2 py-0.5 text-[10px] font-bold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint" title="Reminders on">
                                            <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6v-5a7 7 0 0 0-5.5-6.84V3a1.5 1.5 0 0 0-3 0v1.16A7 7 0 0 0 5 11v5l-1.5 2v1h17v-1L19 16Z"/></svg>
                                            Reminders on
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-nivayalife-muted">
                                    {{ $medication->dosage ?: 'No dosage set' }}
                                    @if($medication->frequency) &middot; {{ $medication->frequency }} @endif
                                </p>
                                @if(!empty($medication->schedule_times))
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($medication->schedule_times as $time)
                                            <span class="rounded-full bg-nivayalife-cream px-2 py-0.5 text-[11px] font-semibold text-nivayalife-ink dark:bg-white/10 dark:text-white">{{ $time }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($medication->start_date || $medication->end_date)
                                    <p class="mt-2 text-[11px] text-nivayalife-muted">
                                        {{ $medication->start_date?->format('M j, Y') ?? 'No start date' }}
                                        &rarr;
                                        {{ $medication->end_date?->format('M j, Y') ?? 'ongoing' }}
                                    </p>
                                @endif
                            </div>
                            @if($canEdit)
                                <div class="flex flex-shrink-0 items-start gap-1">
                                    <a href="{{ route('medications.edit', $medication) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-nivayalife-green hover:bg-nivayalife-mint/40 dark:hover:bg-white/10">Edit</a>
                                    <form method="POST" action="{{ route('medications.destroy', $medication) }}" onsubmit="return confirm('Remove {{ addslashes($medication->medicine_name) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg px-3 py-2 text-xs font-semibold text-nivayalife-pink-dark hover:bg-nivayalife-pink/10">Remove</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
