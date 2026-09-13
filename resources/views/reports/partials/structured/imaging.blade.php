@props(['data'])
@php($findings = $data['findings'] ?? [])
@if($findings || ($data['impression'] ?? null))
    <div class="mt-6 rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
        <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">
            Findings @if($data['body_part'] ?? null)<span class="font-normal text-nivayalife-muted">· {{ $data['body_part'] }}</span>@endif
        </h3>
        @if($findings)
            <ul class="mt-3 space-y-1.5">
                @foreach($findings as $f)
                    <li class="flex gap-2 text-sm text-nivayalife-ink dark:text-white">
                        <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-nivayalife-green" aria-hidden="true"></span>
                        <span>{{ is_array($f) ? json_encode($f) : $f }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        @if($data['impression'] ?? null)
            <div class="mt-4 rounded-xl bg-nivayalife-cream/60 p-3.5 dark:bg-white/5">
                <p class="text-[11px] font-bold uppercase tracking-wide text-nivayalife-muted">Impression</p>
                <p class="mt-1 text-sm text-nivayalife-ink dark:text-white">{{ $data['impression'] }}</p>
            </div>
        @endif
    </div>
@endif
