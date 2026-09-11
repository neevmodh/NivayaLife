@php
    $inviteUrl = route('invite.show', $invitation->token);
@endphp

<div
    x-data="inviteCountdown({ expiresAt: @js($invitation->expires_at->toIso8601String()), lastSentAt: @js($invitation->updated_at->toIso8601String()) })"
    class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5"
>
    <div class="flex items-start gap-3">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-nivayalife-yellow/25 text-nivayalife-yellow">
            <x-relation-icon :relation="$member->relation" class="h-5 w-5" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-nivayalife-ink dark:text-white">{{ $member->full_name }}</p>
            <p class="truncate text-xs text-nivayalife-muted">{{ $invitation->invited_email }}</p>
        </div>
    </div>

    <div class="mt-3 flex items-center gap-1.5 text-xs">
        @if($isExpired)
            <span class="rounded-full bg-nivayalife-pink/25 px-2 py-0.5 font-semibold text-nivayalife-pink-dark">Expired</span>
        @else
            <svg class="h-3.5 w-3.5 text-nivayalife-muted" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="text-nivayalife-muted" x-text="expiresInLabel"></span>
        @endif
    </div>

    <p class="mt-1 text-xs text-nivayalife-muted">Sent {{ $invitation->created_at->diffForHumans() }}</p>

    <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-3 text-xs font-semibold dark:border-white/10">
        <form method="POST" action="{{ route('family.invite.resend', $invitation) }}">
            @csrf
            <button type="submit" :disabled="!canResend" :class="canResend ? 'text-nivayalife-green hover:underline' : 'cursor-not-allowed text-gray-300 dark:text-white/20'">
                <span x-show="canResend">Resend</span>
                <span x-show="!canResend" x-text="`Resend (${resendCooldownRemaining}s)`"></span>
            </button>
        </form>

        <form method="POST" action="{{ route('family.invite.cancel', $invitation) }}" onsubmit="return confirm('Cancel this invitation?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-nivayalife-pink-dark hover:underline">Cancel</button>
        </form>

        <div x-data="copyLink({ url: @js($inviteUrl) })">
            <button type="button" @click="copy()" class="text-nivayalife-ink hover:underline dark:text-white/80">
                <span x-show="!copied">Copy link</span>
                <span x-show="copied" class="text-nivayalife-green">Copied!</span>
            </button>
        </div>

        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = !open" class="text-nivayalife-ink hover:underline dark:text-white/80">QR code</button>
            <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute left-0 top-full z-10 mt-2">
                <x-invite-qr :url="$inviteUrl" :size="160" />
            </div>
        </div>
    </div>
</div>
