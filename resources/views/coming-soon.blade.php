<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">{{ $feature }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-nivayalife-mint text-nivayalife-green">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h3 class="mt-5 text-xl font-bold text-nivayalife-ink dark:text-white">{{ $feature }} is coming soon</h3>
        <p class="mt-2 text-sm text-nivayalife-muted">We're still building this part of Nivaya Life. Check back shortly.</p>
        <a href="{{ route('dashboard') }}" class="mt-6 inline-block rounded-xl bg-nivayalife-green px-6 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm hover:bg-nivayalife-green-dark">
            Back to dashboard
        </a>
    </div>
</x-app-layout>
