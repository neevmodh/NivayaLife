@php
    $dismissedInvites = session('dismissed_invitations', []);
    $pendingInvite = auth()->check()
        ? \App\Models\FamilyInvitation::where('invited_email', auth()->user()->email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->whereNotIn('token', $dismissedInvites)
            ->with(['primaryAccount', 'familyMember'])
            ->latest()
            ->first()
        : null;
@endphp

@if($pendingInvite)
    <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4">
        <div x-show="open" x-transition class="w-full max-w-md rounded-nivayalife bg-white p-6 text-center shadow-nivayalife dark:bg-nivayalife-night">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">
                <x-relation-icon :relation="$pendingInvite->familyMember->relation" class="h-7 w-7" />
            </span>
            <h3 class="mt-4 text-lg font-bold text-nivayalife-ink dark:text-white">
                {{ $pendingInvite->primaryAccount->name }} invited you to join their family
            </h3>
            <p class="mt-1 text-sm text-nivayalife-muted">
                As their {{ Str::headline($pendingInvite->familyMember->relation) }} on Nivaya Life. You'll choose exactly what to share with them.
            </p>

            <div class="mt-6 flex justify-center gap-3">
                <form method="POST" action="{{ route('invite.dismiss', $pendingInvite->token) }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-gray-200 px-6 py-2.5 text-sm font-semibold text-nivayalife-ink transition hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                        No, not now
                    </button>
                </form>
                <a href="{{ route('invite.show', $pendingInvite->token) }}"
                    class="rounded-xl bg-nivayalife-green px-6 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark">
                    Yes, accept
                </a>
            </div>
        </div>
    </div>
@endif
