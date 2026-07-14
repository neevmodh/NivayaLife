<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Share {{ $report ? 'Report' : 'Report History' }}</h2>
        <p class="mt-1 text-sm text-novix-muted">For {{ $familyMember->full_name }}</p>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('shares.store') }}"
            x-data="{
                accessType: @js($defaultAccessType),
                expiryOption: '24h',
                pinEnabled: false,
            }">
            @csrf
            <input type="hidden" name="family_member_id" value="{{ $familyMember->id }}">

            {{-- What to share --}}
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">What are you sharing?</h3>

                <div class="mt-3 space-y-2">
                    @if($report)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-3 transition" :class="accessType === 'single_report' ? 'border-novix-green bg-novix-mint/30' : 'border-gray-200'">
                            <input type="radio" name="access_type" value="single_report" x-model="accessType" class="mt-1">
                            <span>
                                <span class="block text-sm font-semibold text-novix-ink dark:text-white">This report only</span>
                                <span class="block text-xs text-novix-muted">{{ $report->typeLabel() }} &middot; {{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}</span>
                            </span>
                        </label>
                        <input type="hidden" name="report_id" value="{{ $report->id }}">
                    @endif

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 p-3 transition" :class="accessType === 'full_summary' ? 'border-novix-green bg-novix-mint/30' : 'border-gray-200'">
                        <input type="radio" name="access_type" value="full_summary" x-model="accessType" class="mt-1">
                        <span>
                            <span class="block text-sm font-semibold text-novix-ink dark:text-white">Full report history</span>
                            <span class="block text-xs text-novix-muted">All {{ $reportCount }} report{{ $reportCount === 1 ? '' : 's' }} on file for {{ $familyMember->full_name }}</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border-2 border-gray-200 p-3 transition hover:border-novix-green/40">
                        <input type="radio" name="access_type_emergency" value="emergency_card" @click="window.location.href = @js(route('id-card.show'))" class="mt-1">
                        <span>
                            <span class="block text-sm font-semibold text-novix-ink dark:text-white">Emergency card instead</span>
                            <span class="block text-xs text-novix-muted">For blood group / allergies / emergency contact, use the Emergency Card's permanent QR code instead of a report share.</span>
                        </span>
                    </label>
                </div>
            </div>

            {{-- Label --}}
            <div class="mt-4 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <label class="mb-1.5 block text-sm font-bold text-novix-ink dark:text-white">Who is this for? <span class="font-normal text-novix-muted">(optional)</span></label>
                <input type="text" name="shared_with_label" placeholder="e.g. Dr. Mehta — Cardiologist" maxlength="255"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <p class="mt-1 text-xs text-novix-muted">Shown later in your Share History so you always know who has access.</p>
            </div>

            {{-- Expiry --}}
            <div class="mt-4 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Link expires</h3>
                <p class="mt-1 text-xs text-novix-muted">Health shares always expire — there's no "never" option.</p>

                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach(['24h' => '24 hours', '48h' => '48 hours', '7d' => '7 days', 'custom' => 'Custom'] as $value => $label)
                        <label class="cursor-pointer rounded-lg border-2 px-3 py-2 text-center text-xs font-semibold transition" :class="expiryOption === '{{ $value }}' ? 'border-novix-green bg-novix-mint/30 text-novix-green' : 'border-gray-200 text-novix-ink dark:border-white/10 dark:text-white'">
                            <input type="radio" name="expiry_option" value="{{ $value }}" x-model="expiryOption" class="sr-only">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div x-show="expiryOption === 'custom'" x-cloak class="mt-3">
                    <input type="datetime-local" name="custom_expires_at" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
            </div>

            {{-- Protection --}}
            <div class="mt-4 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Extra protection <span class="font-normal text-novix-muted">(optional)</span></h3>

                <label class="mt-3 flex items-center gap-2.5 text-sm text-novix-ink dark:text-white">
                    <input type="checkbox" x-model="pinEnabled" class="h-4 w-4 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    Require a 4-digit PIN to view
                </label>
                <div x-show="pinEnabled" x-cloak class="mt-2">
                    <input type="text" name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" placeholder="0000"
                        class="w-32 rounded-lg border border-gray-200 px-3 py-2 text-center text-lg font-bold tracking-widest dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>

                <label class="mt-3 flex items-center gap-2.5 text-sm text-novix-ink dark:text-white">
                    <input type="checkbox" name="is_one_time" value="1" class="h-4 w-4 rounded border-gray-300 text-novix-green focus:ring-novix-green">
                    One-time view — link stops working right after it's first opened
                </label>
            </div>

            <button type="submit" class="mt-5 w-full rounded-xl bg-novix-green py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark">
                Create share link
            </button>
        </form>
    </div>
</x-app-layout>
