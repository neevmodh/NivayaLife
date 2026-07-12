<div x-data="ajaxForm({ url: @js(route('profile.emergency-contact')), csrfToken: @js(csrf_token()) })">
    <h3 class="text-lg font-bold text-novix-ink dark:text-white">Emergency contact</h3>
    <p class="mt-1 text-sm text-novix-muted">Shown on your emergency ID card.</p>

    <form @submit.prevent="submit($event)" class="mt-6 space-y-4">
        <x-floating-input name="emergency_contact_name" label="Contact name" :value="$member->emergency_contact_name" :required="true" dynamic-errors />
        <x-phone-input name="emergency_contact_phone" label="Contact phone" :value="$member->emergency_contact_phone" dynamic-errors />
        <x-floating-select name="emergency_contact_relation" label="Relation to you" :value="$member->emergency_contact_relation" :required="true" dynamic-errors :options="[
            'spouse' => 'Spouse', 'parent' => 'Parent', 'sibling' => 'Sibling', 'child' => 'Child', 'friend' => 'Friend', 'other' => 'Other',
        ]" />

        <x-save-button />
    </form>
</div>
