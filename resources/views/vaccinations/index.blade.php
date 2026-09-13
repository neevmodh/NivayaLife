<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">Vaccinations</h2>
                <p class="mt-1 text-sm text-nivayalife-muted">For {{ $active->full_name }}</p>
            </div>
            @if($canEdit)
                <a href="{{ route('vaccinations.create') }}?member={{ $active->id }}" class="flex items-center gap-2 rounded-xl bg-nivayalife-green px-5 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Add Vaccination
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

        @if($vaccinations->isNotEmpty())
            @php
                $overdueCount = $vaccinations->filter(fn ($v) => $v->isOverdue())->count();
                $dueSoonCount = $vaccinations->filter(fn ($v) => $v->isDueSoon())->count();
            @endphp
            <div class="mb-4 grid grid-cols-3 gap-3">
                <div class="rounded-nivayalife bg-white p-3.5 text-center shadow-nivayalife-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold text-nivayalife-ink dark:text-white">{{ $vaccinations->count() }}</p>
                    <p class="text-[11px] font-semibold text-nivayalife-muted">Doses recorded</p>
                </div>
                <div class="rounded-nivayalife bg-white p-3.5 text-center shadow-nivayalife-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold {{ $dueSoonCount > 0 ? 'text-amber-600 dark:text-nivayalife-yellow' : 'text-nivayalife-ink dark:text-white' }}">{{ $dueSoonCount }}</p>
                    <p class="text-[11px] font-semibold text-nivayalife-muted">Due within 7 days</p>
                </div>
                <div class="rounded-nivayalife bg-white p-3.5 text-center shadow-nivayalife-sm dark:bg-white/5">
                    <p class="text-xl font-extrabold {{ $overdueCount > 0 ? 'text-nivayalife-pink-dark' : 'text-nivayalife-ink dark:text-white' }}">{{ $overdueCount }}</p>
                    <p class="text-[11px] font-semibold text-nivayalife-muted">Overdue</p>
                </div>
            </div>
        @endif

        @if($vaccinations->isEmpty())
            <div class="rounded-nivayalife bg-white shadow-nivayalife-sm dark:bg-white/5">
                <x-empty-state
                    icon="shield"
                    title="No vaccinations recorded yet."
                    :action-label="$canEdit ? 'Add the first one' : null"
                    :action-url="$canEdit ? route('vaccinations.create').'?member='.$active->id : null"
                    class="py-10" />
            </div>
        @else
            <div class="space-y-3">
                @foreach($vaccinations as $vaccination)
                    @php
                        $overdue = $vaccination->isOverdue();
                        $dueSoon = $vaccination->isDueSoon();
                    @endphp
                    <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-nivayalife-ink dark:text-white">{{ $vaccination->vaccine_name }} &middot; Dose {{ $vaccination->dose_number }}</p>
                                <p class="mt-1 text-xs text-nivayalife-muted">
                                    Given {{ $vaccination->date_administered->format('M j, Y') }}
                                    @if($vaccination->location) &middot; {{ $vaccination->location }} @endif
                                </p>
                                @if($vaccination->next_due_date)
                                    <p class="mt-1.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $overdue ? 'bg-nivayalife-pink/30 text-nivayalife-pink-dark' : ($dueSoon ? 'bg-nivayalife-yellow/30 text-nivayalife-yellow' : 'bg-nivayalife-cream text-nivayalife-muted dark:bg-white/10') }}">
                                        {{ $overdue ? 'Overdue since' : 'Next dose due' }} {{ $vaccination->next_due_date->format('M j, Y') }}
                                    </p>
                                @endif
                            </div>
                            @if($canEdit)
                                <div class="flex flex-shrink-0 items-start gap-1">
                                    <a href="{{ route('vaccinations.edit', $vaccination) }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-nivayalife-green hover:bg-nivayalife-mint/40 dark:hover:bg-white/10">Edit</a>
                                    <form method="POST" action="{{ route('vaccinations.destroy', $vaccination) }}" onsubmit="return confirm('Remove this vaccination record?');">
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
