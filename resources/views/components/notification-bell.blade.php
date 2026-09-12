@props(['sidebar' => false])

@php
    use App\Support\NotificationFeed;

    $items = NotificationFeed::for(auth()->user());
    $count = $items->count();

    $toneDot = [
        'urgent' => 'bg-nivayalife-pink-dark',
        'warn' => 'bg-nivayalife-yellow',
        'info' => 'bg-nivayalife-blue',
    ];
@endphp

<div x-data="{ open: false }" class="relative {{ $sidebar ? 'min-w-0 flex-1' : '' }}">
    @if($sidebar)
        {{-- In the sidebar this is a nav-style row (icon + label), not a bare
             icon — a plain unlabeled bell tucked at the bottom of a narrow
             rail was easy to miss next to the dark-mode toggle. --}}
        <button type="button" @click="open = !open" @click.outside="open = false"
            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-nivayalife-muted transition hover:bg-nivayalife-cream hover:text-nivayalife-ink dark:hover:bg-white/5 dark:hover:text-white"
            :aria-expanded="open.toString()"
            aria-label="{{ $count > 0 ? "Notifications, {$count} needing attention" : 'Notifications' }}">
            <span class="relative flex-shrink-0">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M18 8.5a6 6 0 1 0-12 0c0 6-2.5 7.5-2.5 7.5h17S18 14.5 18 8.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <path d="M13.7 19.5a2 2 0 0 1-3.4 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
                @if($count > 0)
                    <span class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-nivayalife-pink-dark ring-2 ring-white dark:ring-nivayalife-night"></span>
                @endif
            </span>
            <span class="flex-1 text-left">Notifications</span>
            @if($count > 0)
                <span class="rounded-full bg-nivayalife-mint px-1.5 py-0.5 text-[10px] font-bold leading-none text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">{{ $count > 9 ? '9+' : $count }}</span>
            @endif
        </button>
    @else
        <button type="button" @click="open = !open" @click.outside="open = false"
            class="relative flex h-10 w-10 items-center justify-center rounded-full transition hover:bg-white/10 active:scale-95 sm:hover:bg-nivayalife-cream dark:sm:hover:bg-white/10"
            :aria-expanded="open.toString()"
            aria-label="{{ $count > 0 ? "Notifications, {$count} needing attention" : 'Notifications' }}">
            {{-- White on the green mobile header, ink on the light desktop bar. --}}
            <svg class="h-5 w-5 text-white sm:text-nivayalife-ink dark:sm:text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M18 8.5a6 6 0 1 0-12 0c0 6-2.5 7.5-2.5 7.5h17S18 14.5 18 8.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M13.7 19.5a2 2 0 0 1-3.4 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>

            @if($count > 0)
                <span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-nivayalife-pink-dark px-1 text-[10px] font-bold leading-none text-white ring-2 ring-nivayalife-green sm:ring-white dark:sm:ring-nivayalife-night">
                    {{ $count > 9 ? '9+' : $count }}
                </span>
            @endif
        </button>
    @endif

    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        @class([
            'absolute z-30 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white shadow-nivayalife dark:bg-nivayalife-night',
            'bottom-full left-0 mb-2' => $sidebar,
            'right-0 mt-2' => ! $sidebar,
        ])>

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-white/10">
            <p class="text-sm font-bold text-nivayalife-ink dark:text-white">Needs attention</p>
            @if($count > 0)
                <span class="rounded-full bg-nivayalife-mint px-2 py-0.5 text-[11px] font-bold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">{{ $count }}</span>
            @endif
        </div>

        @if($count === 0)
            <div class="flex flex-col items-center gap-2 px-6 py-10 text-center">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <p class="text-sm font-medium text-nivayalife-ink dark:text-white">You're all caught up</p>
                <p class="text-xs text-nivayalife-muted">Due doses, vaccinations and invitations show up here.</p>
            </div>
        @else
            <ul class="max-h-96 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10">
                @foreach($items as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="flex gap-3 px-4 py-3 transition hover:bg-nivayalife-cream/70 dark:hover:bg-white/5">
                            <span class="relative mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-nivayalife-cream text-nivayalife-green dark:bg-white/10 dark:text-nivayalife-mint" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="{{ $item['icon'] }}" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full {{ $toneDot[$item['tone']] ?? 'bg-nivayalife-muted' }} ring-2 ring-white dark:ring-nivayalife-night"></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold leading-snug text-nivayalife-ink dark:text-white">{{ $item['title'] }}</span>
                                <span class="mt-0.5 block truncate text-xs text-nivayalife-muted">{{ $item['meta'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
