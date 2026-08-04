<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Site settings</h2>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Back to overview
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('admin_status'))
            <div class="mb-4 rounded-xl bg-novix-mint px-4 py-3 text-sm font-semibold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">{{ session('admin_status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6 rounded-novix bg-white p-6 shadow-novix-sm dark:bg-white/5">
            @csrf
            @method('PUT')

            <div>
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="maintenance_mode" value="1" @checked($setting->maintenance_mode)
                        class="h-5 w-5 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    <span class="text-sm font-semibold text-novix-ink dark:text-white">Under construction / maintenance mode</span>
                </label>
                <p class="mt-1 text-xs text-novix-muted">
                    When enabled, everyone except admins sees a maintenance page instead of the site.
                </p>
            </div>

            <div>
                <label for="maintenance_message" class="block text-sm font-semibold text-novix-ink dark:text-white">Maintenance message (optional)</label>
                <textarea id="maintenance_message" name="maintenance_message" rows="3"
                    class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-novix-green focus:ring-novix-green dark:border-white/10 dark:bg-white/5 dark:text-white"
                    placeholder="Novix is undergoing scheduled maintenance. Please check back shortly.">{{ old('maintenance_message', $setting->maintenance_message) }}</textarea>
                @error('maintenance_message')
                    <p class="mt-1 text-xs text-novix-pink-dark">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="rounded-lg bg-novix-green px-5 py-2.5 text-sm font-semibold text-white hover:bg-novix-green/90">
                Save settings
            </button>
        </form>
    </div>
</x-app-layout>
