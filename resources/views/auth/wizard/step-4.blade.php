@php
    $existing = $wizard['step4'] ?? [];
@endphp

<div x-ref="step4">
    <h2 class="text-xl font-bold text-novix-ink dark:text-white">A few health basics</h2>
    <p class="mt-1 text-sm text-novix-muted">We'll track your BMI over time and flag anything worth knowing in an emergency.</p>

    <form x-ref="step4Form" class="mt-6 space-y-5" @submit.prevent>
        <x-bmi-gauge
            :height-cm="$existing['height_cm'] ?? null"
            :weight-kg="$existing['weight_kg'] ?? null"
            dynamic-errors
        />

        <x-tag-input name="allergies" label="Known allergies" placeholder="e.g. Peanuts, Penicillin..." :initial="$existing['allergies'] ?? []" />
        <x-tag-input name="medicines" label="Current medicines" placeholder="e.g. Metformin 500mg..." :initial="$existing['medicines'] ?? []" />
    </form>
</div>
