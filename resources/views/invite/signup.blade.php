<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accept your invite — Nivaya Life</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-novix-cream font-sans antialiased">
<div class="mx-auto max-w-xl px-4 py-10 sm:py-14">
    <a href="{{ url('/') }}" class="mb-6 flex justify-center"><x-novix-logo /></a>

    <div
        x-data="inviteSignupForm({ registerUrl: @js(route('invite.register', $invitation->token)), csrfToken: @js(csrf_token()) })"
        :class="{ 'animate-novix-shake': shake }"
        class="rounded-novix bg-white p-6 shadow-novix sm:p-8"
    >
        <span class="inline-flex items-center gap-1.5 rounded-full bg-novix-mint px-3 py-1 text-xs font-semibold text-novix-green">
            <x-relation-icon :relation="$invitation->familyMember->relation" class="h-3.5 w-3.5" />
            Invited as {{ Str::headline($invitation->familyMember->relation) }}
        </span>
        <h1 class="mt-4 text-2xl font-bold text-novix-ink">Join {{ $invitation->primaryAccount->name }}'s family on Nivaya Life</h1>
        <p class="mt-1 text-sm text-novix-muted">Create your own login — you'll choose exactly what to share.</p>

        <form @submit.prevent="submit($event)" class="mt-6 space-y-4">
            <input type="hidden" name="email" value="{{ $invitation->invited_email }}">
            <div class="rounded-xl bg-novix-cream/60 px-4 py-2.5 text-sm text-novix-ink">{{ $invitation->invited_email }}</div>

            <x-floating-input name="full_name" label="Your full name" :value="$invitation->familyMember->full_name" :required="true" autocomplete="name" dynamic-errors />
            <x-phone-input name="phone" label="Phone number" with-country-code dynamic-errors />

            <div x-data="passwordStrength()">
                <div class="relative">
                    <input type="password" name="password" id="password" x-model="password" placeholder=" " required autocomplete="new-password" minlength="8"
                        :class="errorFor('password') ? 'border-novix-pink-dark ring-2 ring-novix-pink-dark/20' : 'border-gray-200 focus:border-novix-green'"
                        class="peer w-full rounded-xl border bg-novix-cream/40 px-4 pt-5 pb-2 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                    <label for="password" class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">Password *</label>
                </div>
                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full transition-all duration-300" :style="`width: ${widthPercent}%; background: ${color}`"></div>
                </div>
                <p x-cloak x-show="password" class="mt-1 text-xs" :style="`color: ${color}`" x-text="label"></p>
                <p x-cloak x-show="errorFor('password')" x-text="errorFor('password')" class="mt-1 text-xs text-novix-pink-dark"></p>
            </div>

            <x-floating-input type="password" name="password_confirmation" label="Confirm password" :required="true" autocomplete="new-password" />

            <div>
                <p class="mb-2 text-sm font-semibold text-novix-ink">Your photo</p>
                <x-camera-capture :upload-url="'#'" element-id="novix-invite-camera" />
                <p x-cloak x-show="errorFor('photo')" x-text="errorFor('photo')" class="mt-2 text-center text-sm text-novix-pink-dark"></p>
            </div>

            <p x-cloak x-show="errorFor('_general')" x-text="errorFor('_general')" class="text-center text-sm text-novix-pink-dark"></p>

            <button type="submit" :disabled="loading"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-novix-green py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark disabled:opacity-60">
                <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                Create my account
            </button>
        </form>
    </div>
</div>
</body>
</html>
