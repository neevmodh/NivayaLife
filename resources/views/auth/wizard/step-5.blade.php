<div x-ref="step5">
    <h2 class="text-xl font-bold text-novix-ink dark:text-white">Almost done — review &amp; confirm</h2>
    <p class="mt-1 text-sm text-novix-muted">Add an emergency contact, confirm your details, and you're in.</p>

    <form x-ref="step5Form" class="mt-6 space-y-5" @submit.prevent>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-floating-input name="emergency_contact_name" label="Emergency contact name" :required="true" dynamic-errors />
            <x-phone-input name="emergency_contact_phone" label="Emergency contact phone" dynamic-errors />
        </div>
        <x-floating-select name="emergency_contact_relation" label="Relation to you" :required="true" dynamic-errors :options="[
            'spouse' => 'Spouse', 'parent' => 'Parent', 'sibling' => 'Sibling', 'child' => 'Child', 'friend' => 'Friend', 'other' => 'Other',
        ]" />

        {{-- Summary — reads the live `summary` object (fetched via AJAX when this
             step becomes active), not server-rendered PHP: the wizard never
             reloads the page between steps, so a Blade snapshot taken at the
             initial GET /register would always be stale/empty here. --}}
        <div class="rounded-xl border border-gray-100 bg-novix-cream/60 p-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Account</h3>
                <button type="button" @click="goToStep(1)" class="text-xs font-semibold text-novix-green hover:underline">Edit</button>
            </div>
            <dl class="mt-2 grid grid-cols-2 gap-y-1 text-sm text-novix-ink/80 dark:text-white/70">
                <dt class="text-novix-muted">Name</dt><dd x-text="summary.step1?.full_name || '—'"></dd>
                <dt class="text-novix-muted">Email</dt><dd x-text="summary.step1?.email || '—'"></dd>
                <dt class="text-novix-muted">Phone</dt><dd x-text="summary.step1?.phone || '—'"></dd>
                <dt class="text-novix-muted">Date of birth</dt><dd x-text="summary.step1?.date_of_birth || '—'"></dd>
                <dt class="text-novix-muted">Blood group</dt><dd x-text="summary.step1?.blood_group || '—'"></dd>
            </dl>
        </div>

        <div class="rounded-xl border border-gray-100 bg-novix-cream/60 p-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Address</h3>
                <button type="button" @click="goToStep(3)" class="text-xs font-semibold text-novix-green hover:underline">Edit</button>
            </div>
            <p class="mt-2 text-sm text-novix-ink/80 dark:text-white/70"
                x-text="[summary.step3?.address_line1, summary.step3?.address_line2, summary.step3?.city, summary.step3?.state, summary.step3?.country, summary.step3?.pincode].filter(Boolean).join(', ') || '—'">
            </p>
        </div>

        <div class="rounded-xl border border-gray-100 bg-novix-cream/60 p-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Health</h3>
                <button type="button" @click="goToStep(4)" class="text-xs font-semibold text-novix-green hover:underline">Edit</button>
            </div>
            <dl class="mt-2 grid grid-cols-2 gap-y-1 text-sm text-novix-ink/80 dark:text-white/70">
                <dt class="text-novix-muted">Height</dt><dd x-text="summary.step4?.height_cm ? summary.step4.height_cm + ' cm' : '—'"></dd>
                <dt class="text-novix-muted">Weight</dt><dd x-text="summary.step4?.weight_kg ? summary.step4.weight_kg + ' kg' : '—'"></dd>
                <dt class="text-novix-muted">Allergies</dt><dd x-text="summary.step4?.allergies?.length ? summary.step4.allergies.join(', ') : 'None listed'"></dd>
                <dt class="text-novix-muted">Medicines</dt><dd x-text="summary.step4?.medicines?.length ? summary.step4.medicines.join(', ') : 'None listed'"></dd>
            </dl>
        </div>

        {{-- Consents --}}
        <div class="space-y-3 rounded-xl border border-gray-100 p-4 dark:border-white/10">
            <h3 class="text-sm font-bold text-novix-ink dark:text-white">Before you continue</h3>

            <label class="flex items-start gap-3 text-sm text-novix-ink/80 dark:text-white/70">
                <input type="checkbox" name="consent_account_creation" required class="mt-0.5 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                <span>I agree to the <a href="{{ url('/terms') }}" target="_blank" class="text-novix-green underline">Terms of Service</a> and confirm the information I've provided is accurate.</span>
            </label>
            <label class="flex items-start gap-3 text-sm text-novix-ink/80 dark:text-white/70">
                <input type="checkbox" name="consent_upload" required class="mt-0.5 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                <span>I consent to uploading and storing my family's medical reports per the <a href="{{ url('/privacy') }}" target="_blank" class="text-novix-green underline">Privacy Policy</a>.</span>
            </label>
            <label class="flex items-start gap-3 text-sm text-novix-ink/80 dark:text-white/70">
                <input type="checkbox" name="consent_ai_processing" required class="mt-0.5 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                <span>I consent to AI processing of uploaded reports to generate plain-language summaries.</span>
            </label>
        </div>
    </form>
</div>
