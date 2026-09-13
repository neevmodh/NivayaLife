<x-auth-card :show-tabs="false">
    <div x-data="{ shake: {{ $errors->any() ? 'true' : 'false' }} }" x-init="if (shake) setTimeout(() => shake = false, 500)" :class="{ 'animate-nivayalife-shake': shake }">
        <h1 class="text-2xl font-bold text-nivayalife-ink dark:text-white">Choose a new password</h1>
        <p class="mt-1 text-sm text-nivayalife-muted">Make it something you haven't used before.</p>

        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-floating-input type="email" name="email" label="Email" :value="old('email', $request->email)" :required="true" autocomplete="username" :error="$errors->first('email')" />
            <x-floating-input type="password" name="password" label="Password" :required="true" autocomplete="new-password" :error="$errors->first('password')" />
            <x-floating-input type="password" name="password_confirmation" label="Confirm password" :required="true" autocomplete="new-password" :error="$errors->first('password_confirmation')" />

            <button type="submit"
                class="w-full rounded-xl bg-nivayalife-green py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark focus:outline-none focus:ring-2 focus:ring-nivayalife-green focus:ring-offset-2">
                Reset password
            </button>
        </form>
    </div>
</x-auth-card>
