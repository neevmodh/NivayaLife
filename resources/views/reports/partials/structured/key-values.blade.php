@props(['data', 'title' => 'Details'])
@php($pairs = $data['pairs'] ?? [])
@if($pairs)
    <x-report-card>
        <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">{{ $title }}</h3>
        <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach($pairs as $pair)
                <div class="rounded-xl bg-nivayalife-cream/60 p-3 dark:bg-white/5">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-nivayalife-muted">{{ $pair['label'] }}</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-nivayalife-ink dark:text-white">{{ $pair['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </x-report-card>
@endif
