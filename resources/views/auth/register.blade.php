<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create your account — Novix</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

    @include('partials.pwa-meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-novix-cream font-sans text-novix-ink antialiased dark:bg-novix-ink dark:text-novix-cream">

<x-confetti />

<div class="mx-auto max-w-lg px-4 py-8 sm:py-12">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ url('/') }}"><x-novix-logo /></a>
        <div class="flex items-center gap-3">
            <span class="hidden text-sm text-novix-muted sm:inline">Already have an account?</span>
            <a href="{{ route('login') }}" class="rounded-lg border border-novix-green/20 bg-white px-4 py-2 text-sm font-semibold text-novix-green shadow-novix-sm hover:bg-novix-mint/40">Log in</a>
            <x-dark-mode-toggle />
        </div>
    </div>

    <div
        x-data="registerForm({
            csrfToken: @js(csrf_token()),
            checkEmailUrl: @js(route('register.check-email')),
            registerUrl: @js(route('register.store')),
        })"
        class="rounded-novix bg-white p-5 shadow-novix sm:p-8 dark:bg-white/5"
    >
        <h2 class="text-xl font-bold text-novix-ink dark:text-white">Create your account</h2>
        <p class="mt-1 text-sm text-novix-muted">Just the basics for now — you can add your photo, address, health info, and more from your profile anytime.</p>

        <form x-ref="registerForm" class="mt-6 space-y-4" :class="{ 'animate-novix-shake': shake }" @submit.prevent="submit()">
            @if($google)
                <div class="flex items-center gap-3 rounded-xl border border-novix-green/20 bg-novix-mint/40 px-4 py-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-novix-green" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29A11.96 11.96 0 0 0 0 12c0 1.94.46 3.77 1.29 5.38l3.98-3.09z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/></svg>
                    <p class="text-sm text-novix-ink">Signed in as <strong>{{ $google['email'] }}</strong> via Google — no password needed.</p>
                </div>
            @endif

            <x-floating-input name="full_name" label="Full name" :value="$google['name'] ?? ''" :required="true" autocomplete="name" dynamic-errors />

            @if($google)
                <input type="hidden" name="email" value="{{ $google['email'] }}">
            @else
                <div x-data="{ email: '' }" class="relative">
                    <input type="email" name="email" id="email" x-model="email" @input="checkEmail(email)" placeholder=" " required autocomplete="username"
                        :class="errorFor('email') || emailStatus === 'taken' ? 'border-novix-pink-dark ring-2 ring-novix-pink-dark/20' : 'border-gray-200 focus:border-novix-green'"
                        class="peer w-full rounded-xl border bg-novix-cream/40 px-4 pt-5 pb-2 pr-10 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                    <label for="email" class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">Email *</label>

                    <svg x-cloak x-show="emailStatus === 'checking'" class="absolute right-3 top-3.5 h-5 w-5 animate-spin text-novix-muted" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    <svg x-cloak x-show="emailStatus === 'available'" class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-novix-green" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg x-cloak x-show="emailStatus === 'taken'" class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-novix-pink-dark" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>

                    <p x-cloak x-show="emailStatus === 'taken'" class="mt-1 text-xs text-novix-pink-dark">This email is already registered.</p>
                    <p x-cloak x-show="errorFor('email') && emailStatus !== 'taken'" x-text="errorFor('email')" class="mt-1 text-xs text-novix-pink-dark"></p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-data="passwordStrength()">
                    <div>
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
                </div>

                <a href="{{ route('auth.google.redirect') }}"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-medium text-novix-ink hover:bg-novix-cream/60">
                    <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29A11.96 11.96 0 0 0 0 12c0 1.94.46 3.77 1.29 5.38l3.98-3.09z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/></svg>
                    Or sign up with Google instead
                </a>
            @endif

            <x-phone-input name="phone" label="Phone number" dynamic-errors />

            <x-floating-select name="gender" label="Gender" :required="true" dynamic-errors :options="[
                'male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say',
            ]" />

            {{-- Optional — nothing in the app requires these to use uploads/AI features; check any you're comfortable with now, or skip and they're never asked again unless you opt in later. --}}
            <div class="space-y-2.5 rounded-xl border border-gray-100 p-4 dark:border-white/10">
                <label class="flex items-start gap-3 text-sm text-novix-ink/80 dark:text-white/70">
                    <input type="checkbox" name="consent_account_creation" value="1" class="mt-0.5 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    <span>I agree to the <a href="{{ url('/terms') }}" target="_blank" class="text-novix-green underline">Terms of Service</a>.</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-novix-ink/80 dark:text-white/70">
                    <input type="checkbox" name="consent_upload" value="1" class="mt-0.5 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    <span>I consent to uploading and storing my family's medical reports per the <a href="{{ url('/privacy') }}" target="_blank" class="text-novix-green underline">Privacy Policy</a>.</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-novix-ink/80 dark:text-white/70">
                    <input type="checkbox" name="consent_ai_processing" value="1" class="mt-0.5 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    <span>I consent to AI processing of uploaded reports to generate plain-language summaries.</span>
                </label>
            </div>

            <p x-cloak x-show="errorFor('_general')" x-text="errorFor('_general')" class="text-center text-sm text-novix-pink-dark"></p>

            <button type="submit" :disabled="loading"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-novix-green px-7 py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark disabled:opacity-60">
                <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <span x-text="loading ? 'Creating your account…' : 'Create my account'"></span>
            </button>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-novix-muted">
        You can fill in your date of birth, blood group, photo, address, and emergency contact anytime from your profile.
    </p>
</div>

</body>
</html>
