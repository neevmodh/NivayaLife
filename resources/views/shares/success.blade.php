<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">Share Link Created</h2>
    </x-slot>

    <div class="mx-auto max-w-lg px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-nivayalife bg-white p-6 text-center shadow-nivayalife-sm dark:bg-white/5">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <h3 class="mt-3 text-lg font-bold text-nivayalife-ink dark:text-white">Ready to share</h3>
            <p class="mt-1 text-sm text-nivayalife-muted">
                {{ $share->access_type === 'single_report' ? 'This report' : 'Full report history' }} for {{ $familyMember->full_name }}
                @if($share->shared_with_label) — <strong>{{ $share->shared_with_label }}</strong> @endif
            </p>
            <p class="mt-1 text-xs text-nivayalife-muted">
                Expires {{ $share->expires_at->format('M j, Y g:i A') }}
                @if($share->requiresPin()) &middot; PIN protected @endif
                @if($share->is_one_time) &middot; One-time view @endif
            </p>

            <img src="{{ $qrDataUri }}" class="mx-auto mt-5 h-48 w-48 rounded-xl bg-white p-2 shadow-sm" alt="Share QR code">
            <a href="{{ $qrDataUri }}" download="share-qr-{{ $share->token }}.svg" class="mt-2 inline-block text-xs font-semibold text-nivayalife-green hover:underline">Download QR image</a>

            <div class="mt-5 rounded-lg bg-nivayalife-cream/60 px-3 py-2 text-xs text-nivayalife-ink dark:bg-white/10 dark:text-white" style="word-break: break-all;">
                {{ $publicUrl }}
            </div>

            <div class="mt-4 flex flex-wrap justify-center gap-3" x-data="{ ...copyLink({ url: @js($publicUrl) }), ...shareActions({ url: @js($publicUrl), title: @js('Health report shared via Nivaya Life') }) }">
                <button type="button" @click="copy()" class="rounded-xl bg-nivayalife-green px-5 py-2.5 text-sm font-semibold text-white hover:bg-nivayalife-green-dark">
                    <span x-show="!copied">Copy link</span>
                    <span x-show="copied">Copied!</span>
                </button>
                <button type="button" x-show="supportsNativeShare" x-cloak @click="nativeShare()" class="rounded-xl border-2 border-nivayalife-green px-5 py-2.5 text-sm font-semibold text-nivayalife-green hover:bg-nivayalife-mint/40">
                    Share via WhatsApp / Email
                </button>
            </div>
        </div>

        <div class="mt-4 flex justify-center gap-4 text-sm">
            <a href="{{ route('shares.history') }}?member={{ $familyMember->id }}" class="font-semibold text-nivayalife-green hover:underline">View Share History</a>
            <a href="{{ route('reports.index') }}" class="font-semibold text-nivayalife-muted hover:underline">Back to Reports</a>
        </div>
    </div>
</x-app-layout>
