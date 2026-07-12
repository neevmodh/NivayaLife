@php
    $currentAllergies = $member->allergies()->pluck('allergen_name')->all();
    $currentMedicines = $member->medications()->pluck('medicine_name')->all();
@endphp

<div x-data="ajaxForm({ url: @js(route('profile.health')), csrfToken: @js(csrf_token()) })">
    <h3 class="text-lg font-bold text-novix-ink dark:text-white">Health</h3>
    <p class="mt-1 text-sm text-novix-muted">Saving a new height/weight adds a fresh entry to your BMI history rather than overwriting the last one.</p>

    <form @submit.prevent="submit($event)" class="mt-6 space-y-5">
        <x-bmi-gauge :height-cm="$member->height_cm" :weight-kg="$member->weight_kg" dynamic-errors />

        <x-tag-input name="allergies" label="Known allergies" placeholder="e.g. Peanuts, Penicillin..." :initial="$currentAllergies" />
        <x-tag-input name="medicines" label="Current medicines" placeholder="e.g. Metformin 500mg..." :initial="$currentMedicines" />

        <x-save-button />
    </form>
</div>
