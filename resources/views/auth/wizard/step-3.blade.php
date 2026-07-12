@php
    $existing = $wizard['step3'] ?? [];
@endphp

<div x-ref="step3">
    <h2 class="text-xl font-bold text-novix-ink dark:text-white">Where can we reach you?</h2>
    <p class="mt-1 text-sm text-novix-muted">Used for your records and, if ever needed, emergency responders.</p>

    <form x-ref="step3Form" class="mt-6 space-y-4" @submit.prevent>
        <x-location-select
            :country="$existing['country'] ?? 'India'"
            :state="$existing['state'] ?? ''"
            :city="$existing['city'] ?? ''"
            dynamic-errors
        />

        <x-floating-input name="address_line1" label="Address line 1" :value="$existing['address_line1'] ?? ''" :required="true" dynamic-errors />
        <x-floating-input name="address_line2" label="Address line 2 (optional)" :value="$existing['address_line2'] ?? ''" />
        <x-floating-input name="pincode" label="Pincode / postal code" :value="$existing['pincode'] ?? ''" :required="true" inputmode="numeric" maxlength="12" dynamic-errors />
    </form>
</div>
