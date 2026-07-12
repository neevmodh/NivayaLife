<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">{{ $feature }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-novix-mint text-novix-green">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h3 class="mt-5 text-xl font-bold text-novix-ink dark:text-white">{{ $feature }} is coming soon</h3>
        <p class="mt-2 text-sm text-novix-muted">We're still building this part of Novix. Check back shortly.</p>
        <a href="{{ route('dashboard') }}" class="mt-6 inline-block rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">
            Back to dashboard
        </a>
    </div>
</x-app-layout>
