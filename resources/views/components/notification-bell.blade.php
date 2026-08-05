@php
    use App\Support\NotificationFeed;

    $items = NotificationFeed::for(auth()->user());
    $count = $items->count();

    $toneDot = [
        'urgent' => 'bg-novix-pink-dark',
        'warn' => 'bg-novix-yellow',
        'info' => 'bg-novix-blue',
    ];
@endphp

<div x-data="{ open: false }" class="relative">
    <button type="button" @click="open = !open" @click.outside="open = false"
        class="relative flex h-10 w-10 items-center justify-center rounded-full transition hover:bg-white/10 active:scale-95 sm:hover:bg-novix-cream dark:sm:hover:bg-white/10"
        :aria-expanded="open.toString()"
        aria-label="{{ $count > 0 ? "Notifications, {$count} needing attention" : 'Notifications' }}">
        {{-- White on the green mobile header, ink on the light desktop bar. --}}
        <svg class="h-5 w-5 text-white sm:text-novix-ink dark:sm:text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M18 8.5a6 6 0 1 0-12 0c0 6-2.5 7.5-2.5 7.5h17S18 14.5 18 8.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            <path d="M13.7 19.5a2 2 0 0 1-3.4 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        </svg>

        @if($count > 0)
            <span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-novix-pink-dark px-1 text-[10px] font-bold leading-none text-white ring-2 ring-novix-green sm:ring-white dark:sm:ring-novix-night">
                {{ $count > 9 ? '9+' : $count }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="absolute right-0 z-30 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-novix border-t-2 border-novix-gold/50 bg-white shadow-novix dark:bg-novix-night">

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-white/10">
            <p class="text-sm font-bold text-novix-ink dark:text-white">Needs attention</p>
            @if($count > 0)
                <span class="rounded-full bg-novix-mint px-2 py-0.5 text-[11px] font-bold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">{{ $count }}</span>
            @endif
        </div>

        @if($count === 0)
            <div class="flex flex-col items-center gap-2 px-6 py-10 text-center">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <p class="text-sm font-medium text-novix-ink dark:text-white">You're all caught up</p>
                <p class="text-xs text-novix-muted">Due doses, vaccinations and invitations show up here.</p>
            </div>
        @else
            <ul class="max-h-96 divide-y divide-gray-100 overflow-y-auto dark:divide-white/10">
                @foreach($items as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="flex gap-3 px-4 py-3 transition hover:bg-novix-cream/70 dark:hover:bg-white/5">
                            <span class="relative mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-novix-cream text-novix-green dark:bg-white/10 dark:text-novix-mint" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="{{ $item['icon'] }}" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full {{ $toneDot[$item['tone']] ?? 'bg-novix-muted' }} ring-2 ring-white dark:ring-novix-night"></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold leading-snug text-novix-ink dark:text-white">{{ $item['title'] }}</span>
                                <span class="mt-0.5 block truncate text-xs text-novix-muted">{{ $item['meta'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
