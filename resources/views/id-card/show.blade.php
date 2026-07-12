<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Emergency ID Card</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

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
        <div class="mx-auto overflow-hidden rounded-novix bg-novix-green shadow-novix">
            <div class="flex items-center justify-between px-6 pt-5">
                <x-novix-logo size="sm" dark />
                <span class="text-xs font-semibold uppercase tracking-widest text-white/70">Emergency Card</span>
            </div>

            <div class="flex items-center gap-5 p-6">
                @if($active->photo_path)
                    <img src="{{ Storage::url($card->photo_path ?? $active->photo_path) }}" class="h-24 w-24 rounded-2xl border-2 border-white/30 object-cover" alt="{{ $active->full_name }}">
                @else
                    <span class="flex h-24 w-24 items-center justify-center rounded-2xl border-2 border-white/30 bg-white/10 text-3xl font-bold text-white">{{ strtoupper(substr($active->full_name, 0, 1)) }}</span>
                @endif

                <div class="flex-1 text-white">
                    <h1 class="text-xl font-bold">{{ $active->full_name }}</h1>
                    <p class="text-xs text-white/70">{{ $active->unique_health_id }}</p>
                    <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                        <span class="text-white/60">Blood group</span><span class="font-semibold">{{ $active->blood_group ?? '—' }}</span>
                        <span class="text-white/60">Date of birth</span><span class="font-semibold">{{ $active->date_of_birth?->format('M j, Y') ?? '—' }}</span>
                        <span class="text-white/60">Emergency contact</span><span class="font-semibold">{{ $active->emergency_contact_name ?? '—' }}</span>
                        <span class="text-white/60">Phone</span><span class="font-semibold">{{ $active->emergency_contact_phone ?? '—' }}</span>
                    </div>
                </div>

                @if($qrDataUri)
                    <img src="{{ $qrDataUri }}" class="h-24 w-24 flex-shrink-0 rounded-lg bg-white p-1.5" alt="Verification QR code">
                @endif
            </div>

            <div class="flex items-center justify-between border-t border-white/10 bg-black/10 px-6 py-3 text-[11px] text-white/60">
                <span>Card {{ $card->card_number }}</span>
                <span>Issued {{ $card->issued_at->format('M j, Y') }}</span>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-gray-100 bg-white p-4 text-sm text-novix-muted dark:border-white/10 dark:bg-white/5">
            Scanning the QR code opens a public verification page confirming this ID and its blood group / emergency
            contact — no other health data is exposed.
        </div>
    </div>
</x-app-layout>
