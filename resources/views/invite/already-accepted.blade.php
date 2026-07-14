<x-message-page icon-bg="bg-novix-mint" icon-color="text-novix-green">
    <x-slot:icon>
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </x-slot:icon>

    <h1 class="mt-5 text-xl font-bold text-novix-ink">This invite has already been accepted</h1>
    <p class="mt-2 text-sm text-novix-muted">If this was you, just log in to see your dashboard.</p>
    <a href="{{ route('login') }}" class="mt-6 inline-block rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">Log in</a>
</x-message-page>
