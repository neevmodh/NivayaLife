@props([
    'heightCm' => null,
    'weightKg' => null,
    'editable' => true,
    'heightName' => 'height_cm',
    'weightName' => 'weight_kg',
    'dynamicErrors' => false,
    'trend' => null, // 'up' | 'down' | 'stable' | null
    'size' => 220,
])

<div x-data="bmiGauge({ heightCm: @js($heightCm), weightKg: @js($weightKg), editable: @js($editable) })">
    @if($editable)
    <div class="mb-5 grid grid-cols-2 gap-3">
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-novix-muted">Height (cm) *</label>
            <input type="number" step="0.1" min="30" max="280" name="{{ $heightName }}" x-model.number="heightCm" required
                @if($dynamicErrors) :class="errorFor('{{ $heightName }}') ? 'border-novix-pink-dark ring-2 ring-novix-pink-dark/20' : 'border-gray-200 focus:border-novix-green'" @endif
                class="w-full rounded-xl border bg-novix-cream/40 px-4 py-3 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30 {{ $dynamicErrors ? '' : 'border-gray-200 focus:border-novix-green' }}">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-novix-muted">Weight (kg) *</label>
            <input type="number" step="0.1" min="2" max="400" name="{{ $weightName }}" x-model.number="weightKg" required
                @if($dynamicErrors) :class="errorFor('{{ $weightName }}') ? 'border-novix-pink-dark ring-2 ring-novix-pink-dark/20' : 'border-gray-200 focus:border-novix-green'" @endif
                class="w-full rounded-xl border bg-novix-cream/40 px-4 py-3 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30 {{ $dynamicErrors ? '' : 'border-gray-200 focus:border-novix-green' }}">
        </div>
    </div>
    @endif

    <div class="relative mx-auto" style="width: {{ $size }}px; height: {{ $size * 0.53 }}px;">
        <div class="absolute inset-x-0 top-0 overflow-hidden" style="height: {{ $size * 0.5 }}px;">
            <div class="absolute rounded-full" style="width:{{ $size }}px; height:{{ $size }}px; left:0; top:0; background: conic-gradient(from 270deg, #8FB8E0 0deg 43.71deg, #1E5A45 43.71deg 77.14deg, #F5C879 77.14deg 102.86deg, #E8615A 102.86deg 180deg, transparent 180deg 360deg);"></div>
            <div class="absolute rounded-full bg-novix-cream dark:bg-novix-ink" style="width:{{ $size * 0.68 }}px; height:{{ $size * 0.68 }}px; left:{{ $size * 0.16 }}px; top:{{ $size * 0.16 }}px;"></div>
        </div>
        <div class="absolute bottom-2 left-1/2 origin-bottom rounded-full bg-novix-ink transition-transform duration-700 ease-out dark:bg-white"
            style="width:3px; height:{{ $size * 0.39 }}px;"
            :style="`transform: translateX(-50%) rotate(${angle}deg)`"></div>
        <div class="absolute bottom-0 left-1/2 h-3.5 w-3.5 -translate-x-1/2 rounded-full bg-novix-ink dark:bg-white"></div>
    </div>

    <div class="mt-2 text-center">
        <div class="flex items-center justify-center gap-2">
            <span class="text-3xl font-extrabold text-novix-ink" x-text="bmi > 0 ? bmi : '--'"></span>
            @if($trend)
                @if($trend === 'up')
                    <span class="text-lg text-novix-pink-dark" title="Up from previous reading">&#8593;</span>
                @elseif($trend === 'down')
                    <span class="text-lg text-novix-blue" title="Down from previous reading">&#8595;</span>
                @else
                    <span class="text-lg text-novix-muted" title="Stable">&#8594;</span>
                @endif
            @endif
        </div>
        <span x-cloak x-show="bmi > 0" class="mt-1 inline-block rounded-full px-3 py-1 text-xs font-bold text-white" :style="`background:${categoryColor}`" x-text="categoryLabel"></span>
    </div>
</div>
