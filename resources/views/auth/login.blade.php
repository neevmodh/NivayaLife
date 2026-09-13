<x-auth-card active="login">
    <div x-data="{ shake: {{ $errors->any() ? 'true' : 'false' }} }" x-init="if (shake) setTimeout(() => shake = false, 500)" :class="{ 'animate-nivayalife-shake': shake }">
        <h1 class="text-2xl font-bold text-nivayalife-ink dark:text-white">Welcome back</h1>
        <p class="mt-1 text-sm text-nivayalife-muted">Log in to access your family's health records.</p>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        <a href="{{ route('auth.google.redirect') }}"
            class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-medium text-nivayalife-ink hover:bg-nivayalife-cream/60 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-nivayalife-green">
            <x-google-icon />
            Continue with Google
        </a>

        <div class="mt-6 flex items-center">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="mx-3 text-xs text-nivayalife-muted">or log in with email</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf

            <x-floating-input type="email" name="email" label="Email" :value="old('email')" :required="true" autocomplete="username" :error="$errors->first('email')" />
            <x-floating-input type="password" name="password" label="Password" :required="true" autocomplete="current-password" :error="$errors->first('password')" />

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-nivayalife-green shadow-sm focus:ring-nivayalife-green">
                    <span class="ms-2 text-sm text-nivayalife-muted">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm text-nivayalife-green hover:underline" href="{{ route('password.request') }}">
                        Forgot your password?
                    </a>
                @endif
            </div>

            <button type="submit"
                class="w-full rounded-xl bg-nivayalife-green py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark focus:outline-none focus:ring-2 focus:ring-nivayalife-green focus:ring-offset-2">
                Log in
            </button>
        </form>
    </div>
</x-auth-card>
