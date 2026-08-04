@php
    // Primary destinations for the installed/mobile experience. Four tabs plus a
    // raised Upload action in the middle — upload is the one thing people open
    // the app to do, so it gets the thumb-reachable centre slot rather than
    // being buried behind a menu.
    $tabs = [
        [
            'label' => 'Home',
            'url' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => '<path d="M3 10.5 12 3l9 7.5M5.5 9.5V20a1 1 0 0 0 1 1H10v-5.5h4V21h3.5a1 1 0 0 0 1-1V9.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
        [
            'label' => 'Reports',
            'url' => route('reports.index'),
            'active' => request()->routeIs('reports.*'),
            'icon' => '<path d="M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9.5 8h5M9.5 12h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
        ],
        [
            'label' => 'Meds',
            'url' => route('medications.index'),
            'active' => request()->routeIs('medications.*'),
            'icon' => '<path d="M10.5 20.5 3.5 13.5a4.95 4.95 0 1 1 7-7l1 1 1-1a4.95 4.95 0 0 1 7 7l-7 7-1.5-1.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
        [
            'label' => 'Assistant',
            'url' => route('assistant'),
            'active' => request()->routeIs('assistant*'),
            'icon' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>',
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
    class="fixed inset-x-0 bottom-0 z-40 border-t border-novix-green/10 bg-white/95 backdrop-blur-lg sm:hidden sm:standalone:block dark:border-white/10 dark:bg-novix-ink/95"
    style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="relative mx-auto grid max-w-md grid-cols-5 items-end px-2 pb-1 pt-1.5">
        @foreach($tabs as $i => $tab)
            {{-- The upload button occupies column 3, so the last two tabs shift right. --}}
            <a href="{{ $tab['url'] }}"
                @class([
                    'flex flex-col items-center gap-1 rounded-xl px-1 py-1.5 transition',
                    'col-start-4' => $i === 2,
                    'col-start-5' => $i === 3,
                    'text-novix-green dark:text-novix-mint' => $tab['active'],
                    'text-novix-muted' => ! $tab['active'],
                ])
                @if($tab['active']) aria-current="page" @endif>
                <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $tab['icon'] !!}</svg>
                <span class="text-[10px] font-semibold leading-none">{{ $tab['label'] }}</span>
                {{-- Gold dot echoes the "i" dot in the brand mark. --}}
                <span @class([
                    'h-1 w-1 rounded-full',
                    'bg-novix-gold' => $tab['active'],
                    'bg-transparent' => ! $tab['active'],
                ]) aria-hidden="true"></span>
            </a>
        @endforeach

        {{-- Absolutely positioned so it straddles the top edge of the bar; the
             grid just leaves column 3 empty for it. --}}
        <a href="{{ route('reports.upload') }}" aria-label="Upload a report"
            class="absolute left-1/2 top-0 flex h-14 w-14 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-2xl bg-gradient-to-br from-novix-green to-novix-green-dark text-white shadow-lg shadow-novix-green/40 transition active:scale-95">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v12m0-12 4 4m-4-4-4 4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</nav>
