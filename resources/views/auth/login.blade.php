<x-auth-card active="login">
    <div x-data="{ shake: {{ $errors->any() ? 'true' : 'false' }} }" x-init="if (shake) setTimeout(() => shake = false, 500)" :class="{ 'animate-novix-shake': shake }">
        <h1 class="text-2xl font-bold text-novix-ink dark:text-white">Welcome back</h1>
        <p class="mt-1 text-sm text-novix-muted">Log in to access your family's health records.</p>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        <a href="{{ route('auth.google.redirect') }}"
            class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-medium text-novix-ink hover:bg-novix-cream/60 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-novix-green">
            <svg class="h-5 w-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/>
                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/>
                <path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29A11.96 11.96 0 0 0 0 12c0 1.94.46 3.77 1.29 5.38l3.98-3.09z"/>
                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/>
            </svg>
            Continue with Google
        </a>

        <div class="mt-6 flex items-center">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="mx-3 text-xs text-novix-muted">or log in with email</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf

            <x-floating-input type="email" name="email" label="Email" :value="old('email')" :required="true" autocomplete="username" :error="$errors->first('email')" />
            <x-floating-input type="password" name="password" label="Password" :required="true" autocomplete="current-password" :error="$errors->first('password')" />

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-novix-green shadow-sm focus:ring-novix-green">
                    <span class="ms-2 text-sm text-novix-muted">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm text-novix-green hover:underline" href="{{ route('password.request') }}">
                        Forgot your password?
                    </a>
                @endif
            </div>

            <button type="submit"
                class="w-full rounded-xl bg-novix-green py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark focus:outline-none focus:ring-2 focus:ring-novix-green focus:ring-offset-2">
                Log in
            </button>
        </form>
    </div>
</x-auth-card>
