@php
    $statusBadge = [
        'active' => ['label' => 'Active', 'class' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint'],
        'expired' => ['label' => 'Expired', 'class' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60'],
        'revoked' => ['label' => 'Revoked', 'class' => 'bg-novix-pink/30 text-novix-pink-dark'],
        'viewed' => ['label' => 'Viewed (one-time)', 'class' => 'bg-novix-yellow/30 text-novix-yellow'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Share History</h2>
        <p class="mt-1 text-sm text-novix-muted">For {{ $active->full_name }}</p>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">

        @if(session('status') === 'share-revoked')
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                class="mb-6 rounded-xl bg-novix-mint px-4 py-3 text-sm font-semibold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">
                Share revoked. The link will no longer work.
            </div>
        @endif

        @if($shares->isEmpty())
            <div class="rounded-novix bg-white p-10 text-center shadow-novix-sm dark:bg-white/5">
                <p class="text-sm text-novix-muted">No shares created yet.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($shares as $share)
                    @php($badge = $statusBadge[$share->statusLabel()])
                    <div class="rounded-novix bg-white p-4 shadow-novix-sm dark:bg-white/5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-bold text-novix-ink dark:text-white">
                                        {{ $share->access_type === 'single_report' ? ($share->report?->typeLabel() ?? 'Report') : 'Full report history' }}
                                    </p>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                    @if($share->requiresPin())
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500 dark:bg-white/10 dark:text-white/60">PIN</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-novix-muted">
                                    {{ $share->shared_with_label ?? 'No label' }}
                                    &middot; Created {{ $share->created_at->format('M j, Y') }}
                                    &middot; Expires {{ $share->expires_at->format('M j, Y g:i A') }}
                                </p>
                                <p class="mt-1 text-xs text-novix-muted">
                                    {{ $share->view_count }} view{{ $share->view_count === 1 ? '' : 's' }}
                                    @if($share->view_count > 0) &middot; Last viewed {{ $share->updated_at->diffForHumans() }} @endif
                                </p>
                            </div>

                            @if($share->isActive())
                                <form method="POST" action="{{ route('shares.revoke', $share) }}" onsubmit="return confirm('Revoke this share? The link will stop working immediately.');">
                                    @csrf
                                    <button type="submit" class="flex-shrink-0 rounded-lg border border-novix-pink-dark/30 px-3 py-1.5 text-xs font-semibold text-novix-pink-dark hover:bg-novix-pink/10">Revoke</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
