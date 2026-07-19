<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create your account — Novix</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-novix-cream font-sans text-novix-ink antialiased dark:bg-novix-ink dark:text-novix-cream">

<x-confetti />

<div class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ url('/') }}"><x-novix-logo /></a>
        <div class="flex items-center gap-3">
            <span class="hidden text-sm text-novix-muted sm:inline">Already have an account?</span>
            <a href="{{ route('login') }}" class="rounded-lg border border-novix-green/20 bg-white px-4 py-2 text-sm font-semibold text-novix-green shadow-novix-sm hover:bg-novix-mint/40">Log in</a>
            <x-dark-mode-toggle />
        </div>
    </div>

    <div
        x-data="registrationWizard({
            resumeStep: {{ $resumeStep }},
            csrfToken: @js(csrf_token()),
            checkEmailUrl: @js(route('register.check-email')),
            summaryUrl: @js(route('register.summary')),
            stepUrls: {
                1: @js(route('register.step1')),
                2: @js(route('register.step2')),
                3: @js(route('register.step3')),
                4: @js(route('register.step4')),
                5: @js(route('register.complete')),
            },
        })"
        class="rounded-novix bg-white p-5 shadow-novix sm:p-8 dark:bg-white/5"
    >
        <x-wizard-progress :labels="['Account', 'Photo', 'Address', 'Health', 'Review']" />

        <div :class="{ 'animate-novix-shake': shake }" class="mt-8">

            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                @include('auth.wizard.step-1')
            </div>

            <div x-show="currentStep === 2" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                @include('auth.wizard.step-2')
            </div>

            <div x-show="currentStep === 3" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                @include('auth.wizard.step-3')
            </div>

            <div x-show="currentStep === 4" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                @include('auth.wizard.step-4')
            </div>

            <div x-show="currentStep === 5" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                @include('auth.wizard.step-5')
            </div>

            <p x-cloak x-show="errorFor('_general')" x-text="errorFor('_general')" class="mt-4 text-center text-sm text-novix-pink-dark"></p>

            <div class="mt-8 flex items-center justify-between border-t border-gray-100 pt-6 dark:border-white/10">
                <button type="button" x-show="currentStep > 1" x-cloak @click="goToStep(currentStep - 1)"
                    class="rounded-xl px-5 py-2.5 text-sm font-semibold text-novix-muted transition hover:text-novix-ink">
                    &larr; Back
                </button>
                <span x-show="currentStep === 1"></span>

                <button type="button" x-show="currentStep === 2" x-cloak @click="skipPhoto()" :disabled="loading"
                    class="ml-auto mr-3 rounded-xl px-4 py-2.5 text-sm font-semibold text-novix-muted transition hover:text-novix-ink disabled:opacity-60">
                    Skip for now
                </button>

                <button type="button" @click="submitStep(currentStep)" :disabled="loading"
                    class="ml-auto flex items-center gap-2 rounded-xl bg-novix-green px-7 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark disabled:opacity-60">
                    <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    <span x-text="currentStep === 5 ? 'Create my account' : 'Next'"></span>
                    <span x-show="currentStep < 5" aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-novix-muted">
        Your progress is saved automatically — refreshing this page won't lose your answers.
    </p>
</div>

</body>
</html>
