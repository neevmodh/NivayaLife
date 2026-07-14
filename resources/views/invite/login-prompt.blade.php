<x-message-page>
    <x-slot:icon>
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </x-slot:icon>

    <h1 class="mt-5 text-xl font-bold text-novix-ink">You already have a Novix account</h1>
    <p class="mt-2 text-sm text-novix-muted">
        <strong>{{ $invitation->primaryAccount->name }}</strong> invited <strong>{{ $invitation->invited_email }}</strong>
        to their family. Log in to accept and choose what you'd like to share with them.
    </p>
    <a href="{{ route('login') }}" class="mt-6 inline-block rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">Log in to accept</a>
</x-message-page>
