<div x-data="ajaxForm({ url: @js(route('profile.basic-info')), csrfToken: @js(csrf_token()) })">
    <h3 class="text-lg font-bold text-novix-ink dark:text-white">Basic information</h3>
    <p class="mt-1 text-sm text-novix-muted">Your name, date of birth, and the details that appear on your emergency card.</p>

    <form @submit.prevent="submit($event)" class="mt-6 space-y-4">
        <x-floating-input name="full_name" label="Full name" :value="$member->full_name" :required="true" dynamic-errors />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-floating-input type="date" name="date_of_birth" label="Date of birth" :value="$member->date_of_birth?->format('Y-m-d')" :required="true" dynamic-errors />
            <x-floating-select name="gender" label="Gender" :value="$member->gender" :required="true" dynamic-errors :options="[
                'male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say',
            ]" />
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold text-novix-muted">Relation</label>
            <input type="text" value="{{ Str::headline($member->relation) }}" disabled
                class="w-full cursor-not-allowed rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 text-sm text-gray-500">
            <p class="mt-1 text-xs text-novix-muted">Set when your account was linked — not editable here.</p>
        </div>

        <x-blood-group-select :value="$member->blood_group" dynamic-errors />

        <x-save-button />
    </form>
</div>
