<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Share Link Created</h2>
    </x-slot>

    <div class="mx-auto max-w-lg px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-novix bg-white p-6 text-center shadow-novix-sm dark:bg-white/5">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <h3 class="mt-3 text-lg font-bold text-novix-ink dark:text-white">Ready to share</h3>
            <p class="mt-1 text-sm text-novix-muted">
                {{ $share->access_type === 'single_report' ? 'This report' : 'Full report history' }} for {{ $familyMember->full_name }}
                @if($share->shared_with_label) — <strong>{{ $share->shared_with_label }}</strong> @endif
            </p>
            <p class="mt-1 text-xs text-novix-muted">
                Expires {{ $share->expires_at->format('M j, Y g:i A') }}
                @if($share->requiresPin()) &middot; PIN protected @endif
                @if($share->is_one_time) &middot; One-time view @endif
            </p>

            <img src="{{ $qrDataUri }}" class="mx-auto mt-5 h-48 w-48 rounded-xl bg-white p-2 shadow-sm" alt="Share QR code">
            <a href="{{ $qrDataUri }}" download="share-qr-{{ $share->token }}.svg" class="mt-2 inline-block text-xs font-semibold text-novix-green hover:underline">Download QR image</a>

            <div class="mt-5 rounded-lg bg-novix-cream/60 px-3 py-2 text-xs text-novix-ink dark:bg-white/10 dark:text-white" style="word-break: break-all;">
                {{ $publicUrl }}
            </div>

            <div class="mt-4 flex flex-wrap justify-center gap-3" x-data="{ ...copyLink({ url: @js($publicUrl) }), ...shareActions({ url: @js($publicUrl), title: @js('Health report shared via Nivaya Life') }) }">
                <button type="button" @click="copy()" class="rounded-xl bg-novix-green px-5 py-2.5 text-sm font-semibold text-white hover:bg-novix-green-dark">
                    <span x-show="!copied">Copy link</span>
                    <span x-show="copied">Copied!</span>
                </button>
                <button type="button" x-show="supportsNativeShare" x-cloak @click="nativeShare()" class="rounded-xl border-2 border-novix-green px-5 py-2.5 text-sm font-semibold text-novix-green hover:bg-novix-mint/40">
                    Share via WhatsApp / Email
                </button>
            </div>
        </div>

        <div class="mt-4 flex justify-center gap-4 text-sm">
            <a href="{{ route('shares.history') }}?member={{ $familyMember->id }}" class="font-semibold text-novix-green hover:underline">View Share History</a>
            <a href="{{ route('reports.index') }}" class="font-semibold text-novix-muted hover:underline">Back to Reports</a>
        </div>
    </div>
</x-app-layout>
