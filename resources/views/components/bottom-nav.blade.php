@php
    use App\Support\NotificationFeed;

    // Primary destinations for the installed/mobile experience, mirroring the
    // reference app designs: Home, Records, a raised Upload action in the
    // thumb-reachable centre, Family, Profile. Medications and the assistant
    // stay one tap away from Home rather than costing a permanent tab slot.
    $tabs = [
        [
            'label' => 'Home',
            'url' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => '<path d="M4 11.5 12 4l8 7.5M6.2 10.2V19a1 1 0 0 0 1 1h3.3v-4.6h3V20h3.3a1 1 0 0 0 1-1v-8.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
        [
            'label' => 'Records',
            'url' => route('reports.index'),
            'active' => request()->routeIs('reports.*') || request()->routeIs('timeline*'),
            'icon' => '<path d="M8 3h6.6L19 7.4V19a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14.5 3v4.5H19M9.5 12.5h6M9.5 16h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
            'badge' => NotificationFeed::hasPendingReports(auth()->user()),
        ],
        [
            'label' => 'Family',
            'url' => route('family.index'),
            'active' => request()->routeIs('family.*'),
            'icon' => '<circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 20v-.8a5.5 5.5 0 0 1 11 0v.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="17" cy="9.2" r="2.4" stroke="currentColor" stroke-width="1.7"/><path d="M15.4 14.6a4.4 4.4 0 0 1 5.6 4.2v1.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        ],
        [
            'label' => 'Profile',
            'url' => route('profile.edit'),
            'active' => request()->routeIs('profile.*'),
            'icon' => '<circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M5.2 20v-.4a6.8 6.8 0 0 1 13.6 0v.4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        ],
    ];
@endphp

{{-- sm:standalone:block keeps the tab bar at every width when running as an
     installed app — an installed tablet/desktop PWA should still feel like
     an app, while plain browser visits at those widths use the top nav.
     The sm: prefix is load-bearing even though block is already the mobile
     default: stacking it onto standalone: is what sorts this rule AFTER
     sm:hidden in the compiled CSS, so it wins the cascade. A bare
     standalone:block emits earlier and loses (verified against the build). --}}
<nav aria-label="Primary"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-nivayalife-green/10 bg-white/95 backdrop-blur-lg sm:hidden sm:standalone:block dark:border-white/10 dark:bg-nivayalife-night/95"
    style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="relative mx-auto grid max-w-md grid-cols-5 items-end px-2 pb-1 pt-1.5">
        @foreach($tabs as $i => $tab)
            {{-- The upload button occupies column 3, so the last two tabs shift right. --}}
            <a href="{{ $tab['url'] }}"
                @class([
                    'flex flex-col items-center gap-1 rounded-xl px-1 py-1.5 transition',
                    'col-start-4' => $i === 2,
                    'col-start-5' => $i === 3,
                    'text-nivayalife-green dark:text-nivayalife-mint' => $tab['active'],
                    'text-nivayalife-muted' => ! $tab['active'],
                ])
                @if($tab['active']) aria-current="page" @endif>
                <span class="relative">
                    <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $tab['icon'] !!}</svg>
                    @if($tab['badge'] ?? false)
                        <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-nivayalife-blue ring-2 ring-white dark:ring-nivayalife-night" aria-hidden="true"></span>
                    @endif
                </span>
                <span class="text-[10px] font-semibold leading-none">{{ $tab['label'] }}</span>
                {{-- Gold dot echoes the "i" dot in the brand mark. --}}
                <span @class([
                    'h-1 w-1 rounded-full',
                    'bg-nivayalife-gold' => $tab['active'],
                    'bg-transparent' => ! $tab['active'],
                ]) aria-hidden="true"></span>
            </a>
        @endforeach

        {{-- Absolutely positioned so it straddles the top edge of the bar; the
             grid just leaves column 3 empty for it. --}}
        <a href="{{ route('reports.upload') }}" aria-label="Upload a report"
            class="absolute left-1/2 top-0 flex h-14 w-14 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-2xl bg-gradient-to-br from-nivayalife-green to-nivayalife-green-dark text-white shadow-lg shadow-nivayalife-green/40 ring-2 ring-nivayalife-gold/60 transition active:scale-95">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v12m0-12 4 4m-4-4-4 4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</nav>
