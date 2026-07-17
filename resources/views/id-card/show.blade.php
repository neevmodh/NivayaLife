<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Emergency ID Card</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

        @if(session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                class="mb-6 rounded-xl bg-novix-mint px-4 py-3 text-sm font-semibold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">
                @switch(session('status'))
                    @case('card-reissued') A new card has been issued. The previous card and QR code no longer work. @break
                    @case('card-deactivated') Card deactivated. The public page now shows as inactive. @break
                    @case('card-activated') Card reactivated. @break
                    @default {{ session('status') }}
                @endswitch
            </div>
        @endif

        @if($familyMembers->count() > 1)
            <div class="mb-6 flex flex-wrap gap-3">
                @foreach($familyMembers as $member)
                    <a href="{{ route('id-card.show') }}?member={{ $member->id }}"
                        class="rounded-full border px-4 py-1.5 text-sm font-medium {{ $member->id === $active->id ? 'border-novix-green bg-novix-green text-white' : 'border-gray-200 text-novix-ink hover:bg-novix-cream' }}">
                        {{ Str::of($member->full_name)->words(1, '') }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Card --}}
        <div class="mx-auto overflow-hidden rounded-novix shadow-novix {{ $card->is_active ? 'bg-novix-green' : 'bg-gray-400' }}">
            <div class="flex items-center justify-between px-6 pt-5">
                <x-novix-logo size="sm" dark />
                <span class="text-xs font-semibold uppercase tracking-widest text-white/70">
                    Emergency Card {{ $card->is_active ? '' : '· Inactive' }}
                </span>
            </div>

            <div class="flex flex-col items-center gap-4 p-6 text-center sm:flex-row sm:items-center sm:gap-5 sm:text-left">
                @if($active->photo_path)
                    <img src="{{ Storage::url($card->photo_path ?? $active->photo_path) }}" class="h-20 w-20 flex-shrink-0 rounded-2xl border-2 border-white/30 object-cover sm:h-24 sm:w-24" alt="{{ $active->full_name }}">
                @else
                    <span class="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-2xl border-2 border-white/30 bg-white/10 text-3xl font-bold text-white sm:h-24 sm:w-24">{{ strtoupper(substr($active->full_name, 0, 1)) }}</span>
                @endif

                <div class="min-w-0 flex-1 text-white">
                    <h1 class="truncate text-xl font-bold">{{ $active->full_name }}</h1>
                    <p class="text-xs text-white/70">{{ $active->unique_health_id }}</p>
                    <div class="mx-auto mt-3 grid max-w-xs grid-cols-2 gap-x-4 gap-y-1.5 text-xs sm:mx-0 sm:max-w-none">
                        <span class="text-white/60">Blood group</span><span class="font-semibold">{{ $active->blood_group ?? '—' }}</span>
                        <span class="text-white/60">Age</span><span class="font-semibold">{{ $active->age() ?? '—' }}</span>
                        <span class="text-white/60">Emergency contact</span><span class="truncate font-semibold">{{ $active->emergency_contact_name ?? '—' }}</span>
                        <span class="text-white/60">Phone</span><span class="font-semibold">{{ $active->emergency_contact_phone ?? '—' }}</span>
                    </div>
                </div>

                @if($qrDataUri)
                    <img src="{{ $qrDataUri }}" class="h-24 w-24 flex-shrink-0 rounded-lg bg-white p-1.5" alt="Emergency card QR code">
                @endif
            </div>

            <div class="flex items-center justify-between border-t border-white/10 bg-black/10 px-6 py-3 text-[11px] text-white/60">
                <span>Card {{ $card->card_number }}</span>
                <span>Issued {{ $card->issued_at->format('M j, Y') }}</span>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-gray-100 bg-white p-4 text-sm text-novix-muted dark:border-white/10 dark:bg-white/5">
            Scanning the QR code opens a public emergency page — blood group, allergies, chronic conditions,
            medications, and emergency contact — that anyone can view without logging in, so a first responder can
            read it instantly. No address or account information is ever shown there.
        </div>

        {{-- Actions --}}
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('emergency.show', $card->card_number) }}" target="_blank"
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                View public page
            </a>
            <a href="{{ route('emergency.pdf.full', $card->card_number) }}"
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Download full PDF
            </a>
            <a href="{{ route('emergency.pdf.wallet', $card->card_number) }}"
                class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Download wallet card
            </a>

            <div class="flex-1"></div>

            <form method="POST" action="{{ route('id-card.toggle-active', $card) }}" onsubmit="return confirm('{{ $card->is_active ? 'Deactivate this card? The public page will immediately stop showing any data.' : 'Reactivate this card?' }}');">
                @csrf
                <button type="submit" class="rounded-xl border px-4 py-2.5 text-sm font-semibold {{ $card->is_active ? 'border-novix-pink-dark/30 text-novix-pink-dark hover:bg-novix-pink/10' : 'border-novix-green/30 text-novix-green hover:bg-novix-mint/40' }}">
                    {{ $card->is_active ? 'Deactivate card' : 'Reactivate card' }}
                </button>
            </form>

            <form method="POST" action="{{ route('id-card.reissue') }}" onsubmit="return confirm('Issue a brand new card? The current card number and QR code will stop working immediately.');">
                @csrf
                <button type="submit" class="rounded-xl border border-novix-yellow/50 px-4 py-2.5 text-sm font-semibold text-novix-ink hover:bg-novix-yellow/10 dark:text-white">
                    Reissue card
                </button>
            </form>
        </div>

        @if($active->idCardHistory->count() > 1)
            <div class="mt-8" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="text-xs font-semibold text-novix-muted hover:text-novix-ink">
                    <span x-text="open ? 'Hide' : 'Show'"></span> issuance history ({{ $active->idCardHistory->count() }})
                </button>
                <div x-show="open" x-cloak class="mt-3 space-y-2">
                    @foreach($active->idCardHistory as $historyCard)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 text-xs dark:border-white/10">
                            <span class="font-mono text-novix-ink dark:text-white">{{ $historyCard->card_number }}</span>
                            <span class="text-novix-muted">Issued {{ $historyCard->issued_at->format('M j, Y') }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $historyCard->is_active ? 'bg-novix-mint text-novix-green' : 'bg-gray-100 text-gray-500' }}">
                                {{ $historyCard->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
