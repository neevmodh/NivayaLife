@props(['labels' => []])

<div>
    <div class="mb-2 flex items-center justify-between">
        <span class="text-xs font-semibold text-novix-muted">Step <span x-text="currentStep"></span> of <span x-text="totalSteps"></span></span>
        <span class="text-xs font-semibold text-novix-green" x-text="progressPercent + '% complete'"></span>
    </div>

    <div class="flex items-center gap-1.5">
        @foreach($labels as $i => $label)
            @php($n = $i + 1)
            <div class="flex flex-1 flex-col items-center gap-1.5">
                <button
                    type="button"
                    @click="goToStep({{ $n }})"
                    :disabled="{{ $n }} > furthestStep"
                    :class="{
                        'bg-novix-green text-white shadow-novix-sm': currentStep === {{ $n }},
                        'bg-novix-mint text-novix-green cursor-pointer': currentStep !== {{ $n }} && {{ $n }} <= furthestStep,
                        'bg-gray-100 text-gray-400 cursor-not-allowed': {{ $n }} > furthestStep,
                    }"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition"
                >
                    <svg x-show="{{ $n }} < furthestStep" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span x-show="{{ $n }} >= furthestStep" x-cloak>{{ $n }}</span>
                </button>
                <span class="hidden text-center text-[11px] font-medium text-novix-muted sm:block" :class="currentStep === {{ $n }} ? 'text-novix-green' : ''">{{ $label }}</span>
            </div>
            @if(!$loop->last)
                <div class="h-0.5 flex-1 rounded bg-gray-200" :class="{{ $n }} < furthestStep ? 'bg-novix-green' : ''" style="margin-bottom: 1.1rem;"></div>
            @endif
        @endforeach
    </div>

    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
        <div class="h-full rounded-full bg-novix-green transition-all duration-500 ease-out" :style="`width: ${progressPercent}%`"></div>
    </div>
</div>
