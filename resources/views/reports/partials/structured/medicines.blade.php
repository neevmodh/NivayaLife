@props(['data'])
@php($rows = $data['medicines'] ?? [])
@if($rows)
    <x-report-card>
        <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Medicines on this prescription</h3>
        <div class="mt-3 space-y-2.5">
            @foreach($rows as $med)
                <div class="rounded-xl bg-nivayalife-cream/60 p-3.5 dark:bg-white/5">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-bold text-nivayalife-ink dark:text-white">{{ $med['name'] ?? 'Unnamed' }}</p>
                        @if($med['dosage'] ?? null)
                            <span class="rounded-full bg-nivayalife-mint px-2 py-0.5 text-[11px] font-bold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">{{ $med['dosage'] }}</span>
                        @endif
                        @if($med['uncertain'] ?? false)
                            {{-- The model was unsure of this handwriting; say so rather than presenting a guess as fact. --}}
                            <span class="rounded-full bg-nivayalife-yellow/30 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:text-nivayalife-yellow">Handwriting unclear — check the original</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-nivayalife-muted">
                        {{ collect([$med['frequency'] ?? null, $med['duration'] ?? null, $med['instructions'] ?? null])->filter()->implode(' · ') ?: 'No schedule recorded' }}
                    </p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-nivayalife-muted">Read automatically from the document — always confirm against the original above before taking anything.</p>
    </x-report-card>
@endif
