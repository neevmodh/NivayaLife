<x-auth-card :show-tabs="false">
    <div x-data="{ shake: {{ $errors->any() ? 'true' : 'false' }} }" x-init="if (shake) setTimeout(() => shake = false, 500)" :class="{ 'animate-nivayalife-shake': shake }">
        <h1 class="text-2xl font-bold text-nivayalife-ink dark:text-white">Forgot your password?</h1>
        <p class="mt-1 text-sm text-nivayalife-muted">Tell us your email and we'll send a link to choose a new one.</p>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf

            <x-floating-input type="email" name="email" label="Email" :value="old('email')" :required="true" autocomplete="username" :error="$errors->first('email')" />

            <button type="submit"
                class="w-full rounded-xl bg-nivayalife-green py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark focus:outline-none focus:ring-2 focus:ring-nivayalife-green focus:ring-offset-2">
                Email password reset link
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-6 flex items-center justify-center gap-1 text-sm font-semibold text-nivayalife-green hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Back to login
        </a>
    </div>
</x-auth-card>
