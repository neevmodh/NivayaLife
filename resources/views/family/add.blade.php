@php
    $user = auth()->user();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Add Family Member</h2>
    </x-slot>

    <x-confetti />

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8"
        x-data="familyAddPage({
            inviteUrl: @js(route('family.invite.store')),
            dependentUrl: @js(route('family.dependent.store')),
            csrfToken: @js(csrf_token()),
            primaryCountry: @js($primaryMember->country ?? 'India'),
            primaryState: @js($primaryMember->state ?? ''),
            primaryCity: @js($primaryMember->city ?? ''),
            primaryAddressLine1: @js($primaryMember->address_line1 ?? ''),
            primaryAddressLine2: @js($primaryMember->address_line2 ?? ''),
            primaryPincode: @js($primaryMember->pincode ?? ''),
            primaryPhone: @js($user->phone ?? ''),
        })"
    >
        {{-- Step 1: choose connection type --}}
        <div x-show="mode === null" x-transition class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <button type="button" @click="chooseMode('linked')"
                class="group flex flex-col items-start gap-4 rounded-novix border-2 border-gray-100 bg-white p-6 text-left shadow-novix-sm transition hover:-translate-y-0.5 hover:border-novix-green/40 hover:shadow-novix dark:border-white/10 dark:bg-white/5">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-novix-blue/20 text-novix-blue transition group-hover:scale-105">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M4 4h16v12H7l-3 3V4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 9h8M8 12h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3 class="text-base font-bold text-novix-ink dark:text-white">They have their own email</h3>
                    <p class="mt-1 text-sm text-novix-muted">They'll get their own Novix account and choose what to share with you.</p>
                </div>
            </button>

            <button type="button" @click="chooseMode('dependent')"
                class="group flex flex-col items-start gap-4 rounded-novix border-2 border-gray-100 bg-white p-6 text-left shadow-novix-sm transition hover:-translate-y-0.5 hover:border-novix-green/40 hover:shadow-novix dark:border-white/10 dark:bg-white/5">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-novix-mint text-novix-green transition group-hover:scale-105 dark:bg-novix-green/20 dark:text-novix-mint">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <h3 class="text-base font-bold text-novix-ink dark:text-white">They don't have an email</h3>
                    <p class="mt-1 text-sm text-novix-muted">You'll manage their full profile, reports, and reminders directly.</p>
                </div>
            </button>
        </div>

        {{-- Back link (shown once a mode is chosen and before success) --}}
        <button type="button" x-show="mode !== null && !inviteSuccess" @click="mode = null" x-cloak
            class="mb-4 flex items-center gap-1 text-sm font-semibold text-novix-green hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Back
        </button>

        {{-- Path A: invite a linked member --}}
        <div x-show="mode === 'linked' && !inviteSuccess" x-cloak x-transition
            :class="{ 'animate-novix-shake': shake }"
            class="rounded-novix bg-white p-6 shadow-novix-sm sm:p-8 dark:bg-white/5">
            <h3 class="text-lg font-bold text-novix-ink dark:text-white">Invite a family member</h3>
            <p class="mt-1 text-sm text-novix-muted">They'll get an email with a link to create their own account.</p>

            <form @submit.prevent="submitInvite($event)" class="mt-6 space-y-4">
                <x-floating-select name="relation" label="Relation to you" :required="true" dynamic-errors :options="[
                    'spouse' => 'Spouse', 'father' => 'Father', 'mother' => 'Mother', 'son' => 'Son',
                    'daughter' => 'Daughter', 'grandfather' => 'Grandfather', 'grandmother' => 'Grandmother', 'other' => 'Other',
                ]" />
                <x-floating-input name="full_name" label="Their name" :required="true" dynamic-errors />
                <x-floating-input type="email" name="email" label="Their email address" :required="true" dynamic-errors />

                <button type="submit" :disabled="loading"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-novix-green py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark disabled:opacity-60">
                    <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    Send invitation
                </button>
            </form>
        </div>

        {{-- Path A success --}}
        <div x-show="inviteSuccess" x-cloak x-transition class="rounded-novix bg-white p-6 text-center shadow-novix-sm sm:p-8 dark:bg-white/5">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <h3 class="mt-4 text-lg font-bold text-novix-ink dark:text-white">Invitation sent to <span x-text="inviteSuccess?.name"></span>!</h3>
            <p class="mt-1 text-sm text-novix-muted">If the email doesn't arrive, share this link directly:</p>

            <div class="mt-4 flex items-center gap-2 rounded-xl border border-gray-200 bg-novix-cream/60 px-3 py-2 dark:border-white/10 dark:bg-white/5" x-data="copyLink()">
                <span class="flex-1 truncate text-left text-xs text-novix-ink dark:text-white/80" x-text="inviteSuccess?.inviteUrl"></span>
                <button type="button" @click="copy(inviteSuccess?.inviteUrl)" class="flex-shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-novix-green shadow-sm dark:bg-white/10">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied">Copied!</span>
                </button>
            </div>

            <div class="mt-5 flex justify-center" x-show="inviteSuccess">
                <div class="inline-block rounded-xl bg-white p-3 shadow-novix-sm">
                    <img :src="inviteSuccess?.qrDataUri" width="160" height="160" alt="QR code to accept the invitation">
                </div>
            </div>

            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('family.index') }}" class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">Go to Family</a>
                <button type="button" @click="mode = null; inviteSuccess = null" class="rounded-xl border border-gray-200 px-6 py-2.5 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white">Invite another</button>
            </div>
        </div>

        {{-- Path B: add a dependent --}}
        <div x-show="mode === 'dependent'" x-cloak x-transition
            :class="{ 'animate-novix-shake': shake }"
            class="rounded-novix bg-white p-6 shadow-novix-sm sm:p-8 dark:bg-white/5">
            <h3 class="text-lg font-bold text-novix-ink dark:text-white">Add a dependent</h3>
            <p class="mt-1 text-sm text-novix-muted">You'll manage their profile directly — no invite needed.</p>

            <form @submit.prevent="submitDependent($event)" class="mt-6 space-y-5">
                <x-floating-select id="dependent_relation" name="relation" label="Relation to you" :required="true" dynamic-errors :options="[
                    'spouse' => 'Spouse', 'father' => 'Father', 'mother' => 'Mother', 'son' => 'Son',
                    'daughter' => 'Daughter', 'grandfather' => 'Grandfather', 'grandmother' => 'Grandmother', 'other' => 'Other',
                ]" />
                <x-floating-input id="dependent_full_name" name="full_name" label="Their full name" :required="true" dynamic-errors />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-floating-input type="date" name="date_of_birth" label="Date of birth" :required="true" dynamic-errors />
                    <x-floating-select name="gender" label="Gender" :required="true" dynamic-errors :options="[
                        'male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say',
                    ]" />
                </div>

                <x-blood-group-select dynamic-errors />

                <div>
                    <p class="mb-2 text-sm font-semibold text-novix-ink dark:text-white">Take or upload their photo</p>
                    <x-camera-capture :upload-url="'#'" element-id="novix-dependent-camera" />
                    <p x-cloak x-show="errorFor('photo')" x-text="errorFor('photo')" class="mt-2 text-center text-sm text-novix-pink-dark"></p>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-semibold text-novix-ink dark:text-white">Address</p>
                        <label class="flex items-center gap-2 text-xs font-medium text-novix-muted">
                            <input type="checkbox" @change="applySameAddress($event.target.checked)" class="rounded border-gray-300 text-novix-green focus:ring-novix-green">
                            Same as my address
                        </label>
                    </div>

                    <x-location-select element-id="novix-dependent-location" dynamic-errors />

                    <div class="mt-4 grid grid-cols-1 gap-4">
                        <div class="relative">
                            <input type="text" name="address_line1" x-model="form_address_line1" placeholder=" " required
                                class="peer w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 pt-5 pb-2 text-sm text-novix-ink shadow-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                            <label class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">Address line 1 *</label>
                        </div>
                        <div class="relative">
                            <input type="text" name="address_line2" x-model="form_address_line2" placeholder=" "
                                class="peer w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 pt-5 pb-2 text-sm text-novix-ink shadow-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                            <label class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">Address line 2 (optional)</label>
                        </div>
                        <div class="relative">
                            <input type="text" name="pincode" x-model="form_pincode" inputmode="numeric" maxlength="12" placeholder=" " required
                                class="peer w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 pt-5 pb-2 text-sm text-novix-ink shadow-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                            <label class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">Pincode / postal code *</label>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-novix-ink dark:text-white">Health</p>
                    <x-bmi-gauge dynamic-errors />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-semibold text-novix-ink dark:text-white">Emergency contact</p>
                        <label class="flex items-center gap-2 text-xs font-medium text-novix-muted">
                            <input type="checkbox" @change="applyMyPhone($event.target.checked)" class="rounded border-gray-300 text-novix-green focus:ring-novix-green">
                            Use my phone number
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-floating-input name="emergency_contact_name" label="Contact name" :value="$user->name" :required="true" dynamic-errors />
                        <div class="relative">
                            <input type="tel" inputmode="numeric" name="emergency_contact_phone" x-model="form_emergency_phone"
                                @input="form_emergency_phone = form_emergency_phone.replace(/\D/g, '').slice(0, 10)"
                                placeholder=" " required
                                class="peer w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 pt-5 pb-2 text-sm text-novix-ink shadow-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                            <label class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">Contact phone *</label>
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-floating-select name="emergency_contact_relation" label="Relation to dependent" :required="true" dynamic-errors :options="[
                            'parent' => 'Parent', 'spouse' => 'Spouse', 'sibling' => 'Sibling', 'child' => 'Child', 'guardian' => 'Guardian', 'other' => 'Other',
                        ]" />
                    </div>
                </div>

                <p x-cloak x-show="errorFor('_general')" x-text="errorFor('_general')" class="text-center text-sm text-novix-pink-dark"></p>

                <button type="submit" :disabled="loading"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-novix-green py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark disabled:opacity-60">
                    <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    Add family member
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
