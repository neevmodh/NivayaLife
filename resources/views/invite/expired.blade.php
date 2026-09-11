<x-message-page icon-bg="bg-nivayalife-pink/25" icon-color="text-nivayalife-pink-dark">
    <x-slot:icon>
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </x-slot:icon>

    <h1 class="mt-5 text-xl font-bold text-nivayalife-ink">This invite has expired</h1>
    <p class="mt-2 text-sm text-nivayalife-muted">
        Ask <strong>{{ $invitation->primaryAccount->name }}</strong> to resend the invitation from their Family page —
        invites are valid for 7 days.
    </p>
</x-message-page>
