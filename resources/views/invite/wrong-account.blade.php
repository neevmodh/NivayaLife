<x-message-page icon-bg="bg-nivayalife-yellow/30" icon-color="text-nivayalife-yellow">
    <x-slot:icon>
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </x-slot:icon>

    <h1 class="mt-5 text-xl font-bold text-nivayalife-ink">This invite is for a different email</h1>
    <p class="mt-2 text-sm text-nivayalife-muted">
        You're signed in as <strong>{{ auth()->user()->email }}</strong>, but this invitation was sent to
        <strong>{{ $invitation->invited_email }}</strong>. Log out and try again with that account.
    </p>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="rounded-xl bg-nivayalife-green px-6 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm hover:bg-nivayalife-green-dark">Log out</button>
    </form>
</x-message-page>
