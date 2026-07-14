<div x-data="ajaxForm({ url: @js($isDependentEdit ? route('profile.address.member', $member) : route('profile.address')), csrfToken: @js(csrf_token()) })">
    <h3 class="text-lg font-bold text-novix-ink dark:text-white">Address</h3>
    <p class="mt-1 text-sm text-novix-muted">Kept on file for {{ $isDependentEdit ? 'their' : 'your' }} records and, if ever needed, emergency responders.</p>

    <form @submit.prevent="submit($event)" class="mt-6 space-y-4">
        <x-location-select :country="$member->country ?? 'India'" :state="$member->state ?? ''" :city="$member->city ?? ''" dynamic-errors />

        <x-floating-input name="address_line1" label="Address line 1" :value="$member->address_line1" :required="true" dynamic-errors />
        <x-floating-input name="address_line2" label="Address line 2 (optional)" :value="$member->address_line2" />
        <x-floating-input name="pincode" label="Pincode / postal code" :value="$member->pincode" :required="true" inputmode="numeric" maxlength="12" dynamic-errors />

        <x-save-button />
    </form>
</div>
