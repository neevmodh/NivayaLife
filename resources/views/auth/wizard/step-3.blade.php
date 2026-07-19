@php
    $existing = $wizard['step3'] ?? [];
@endphp

<div x-ref="step3">
    <h2 class="text-xl font-bold text-novix-ink dark:text-white">Where can we reach you? <span class="text-sm font-normal text-novix-muted">(optional)</span></h2>
    <p class="mt-1 text-sm text-novix-muted">Used for your records and, if ever needed, emergency responders. You can leave this blank and add it later from your profile.</p>

    <form x-ref="step3Form" class="mt-6 space-y-4" @submit.prevent>
        <x-location-select
            :country="$existing['country'] ?? ''"
            :state="$existing['state'] ?? ''"
            :city="$existing['city'] ?? ''"
            :required="false"
            dynamic-errors
        />

        <x-floating-input name="address_line1" label="Address line 1 (optional)" :value="$existing['address_line1'] ?? ''" dynamic-errors />
        <x-floating-input name="address_line2" label="Address line 2 (optional)" :value="$existing['address_line2'] ?? ''" />
        <x-floating-input name="pincode" label="Pincode / postal code (optional)" :value="$existing['pincode'] ?? ''" inputmode="numeric" maxlength="12" dynamic-errors />
    </form>
</div>
