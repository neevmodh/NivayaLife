@php
    $navFamilyMembers = \App\Models\FamilyMember::where('primary_account_id', auth()->id())
        ->orWhere('linked_user_id', auth()->id())
        ->orderByRaw("relation = 'self' desc")
        ->orderBy('full_name')
        ->get();
    $navActive = $navFamilyMembers->firstWhere('id', session('active_family_member_id')) ?? $navFamilyMembers->firstWhere('relation', 'self') ?? $navFamilyMembers->first();

    $navGroups = [
        'Overview' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'icon' => 'M4 11.5 12 4l8 7.5M6.2 10.2V19a1 1 0 0 0 1 1h3.3v-4.6h3V20h3.3a1 1 0 0 0 1-1v-8.8'],
            ['label' => 'Reports', 'route' => 'reports.index', 'active' => request()->routeIs('reports.*'), 'icon' => 'M9 12h6m-6 4h6m1 5H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.6a1 1 0 0 1 .7.3l4.4 4.4a1 1 0 0 1 .3.7V19a2 2 0 0 1-2 2Z'],
            ['label' => 'Timeline', 'route' => 'timeline', 'active' => request()->routeIs('timeline*'), 'icon' => 'M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ],
        'Health' => [
            ['label' => 'Medications', 'route' => 'medications.index', 'active' => request()->routeIs('medications.*'), 'icon' => 'M10.5 20.5 3.5 13.5a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7ZM7 10l7 7'],
            ['label' => 'Vaccinations', 'route' => 'vaccinations.index', 'active' => request()->routeIs('vaccinations.*'), 'icon' => 'M19 8 8 19l-5-5M14 3l7 7-3 3-7-7 3-3Z'],
        ],
        'People' => [
            ['label' => 'Family', 'route' => 'family.index', 'active' => request()->routeIs('family.*'), 'icon' => 'M17 20h4v-2a4 4 0 0 0-3-3.9M13 3.1a4 4 0 0 1 0 7.8M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
            ['label' => 'Shared links', 'route' => 'shares.history', 'active' => request()->routeIs('shares.*'), 'icon' => 'M8.7 10.7 15.3 7.3M8.7 13.3l6.6 3.4M18 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm0 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z'],
            ['label' => 'Emergency card', 'route' => 'id-card.show', 'active' => request()->routeIs('id-card.show'), 'icon' => 'M12 21s-7-4.35-9.5-8.5C.83 9.1 2.3 5.5 6 5c2-.27 3.5 1 4 2 .5-1 2-2.27 4-2 3.7.5 5.17 4.1 3.5 7.5C19 16.65 12 21 12 21Z'],
        ],
        'Tools' => array_filter([
            ['label' => 'Assistant', 'route' => 'assistant', 'active' => request()->routeIs('assistant*'), 'icon' => 'M12 3v2m6.4 1.6-.7.7M21 12h-2M4 12H3m3.3-5.7-.7-.7M9.7 17h4.6m-5.8-2.5a4 4 0 1 1 5.7 0A4 4 0 0 1 12 18a4 4 0 0 1-2.8-1.5Z'],
            auth()->user()?->is_admin ? ['label' => 'Admin', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.*'), 'icon' => 'M4 20V10m5 10V4m5 16v-7m5 7V8'] : null,
        ]),
    ];
@endphp

{{-- Mobile: unchanged solid brand-green header + bottom tab bar. Desktop
     (sm: and up) gets a persistent left sidebar instead of a top bar — see
     the <aside> below. Two skins, split entirely by breakpoint. --}}
<nav x-data="{ open: false }"
    class="sticky top-0 z-30 border-b border-nivayalife-green-dark/40 bg-nivayalife-green sm:hidden">
    <div class="mx-auto max-w-7xl px-4">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}"><x-nivayalife-logo size="sm" dark="responsive" /></a>
            </div>

            <div class="-me-2 flex items-center gap-1">
                <x-notification-bell />
                <x-dark-mode-toggle persist-url="{{ route('profile.theme') }}" />
                @if($navActive)
                    <a href="{{ route('family.index') }}" aria-label="Family members">
                        <x-avatar :photo-path="$navActive->photo_path" :preset="$navActive->avatar_preset ?? null" :full-name="$navActive->full_name" :gender="$navActive->gender" :age="$navActive->age()" size="h-8 w-8" class="ring-2 ring-white/40" />
                    </a>
                @endif
                <button @click="open = ! open" :aria-expanded="open" aria-label="Toggle navigation menu" class="inline-flex items-center justify-center rounded-md p-2 text-white/90 hover:bg-white/10 hover:text-white">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Dashboard, Reports, Health and Family live in the bottom tab bar on
         mobile, so this menu only carries what the bar can't fit. --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-nivayalife-green/10 bg-white dark:border-white/10 dark:bg-nivayalife-night">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('timeline')" :active="request()->routeIs('timeline*')">Timeline</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('vaccinations.index')" :active="request()->routeIs('vaccinations.*')">Vaccinations</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('id-card.show')" :active="request()->routeIs('id-card.show')">Emergency Card</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('shares.history')" :active="request()->routeIs('shares.*')">Shared reports</x-responsive-nav-link>
            @if(auth()->user()?->is_admin)
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">Admin</x-responsive-nav-link>
            @endif
        </div>
        <div class="border-t border-gray-200 pb-1 pt-4 dark:border-white/10">
            <div class="px-4">
                <div class="text-base font-medium text-nivayalife-ink dark:text-white">{{ Auth::user()->name }}</div>
                <div class="text-sm font-medium text-nivayalife-muted">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profile</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

{{-- Desktop sidebar --}}
<aside class="sticky top-0 hidden h-screen w-60 flex-shrink-0 flex-col gap-6 overflow-y-auto border-r border-nivayalife-green/10 bg-white px-3.5 py-5 sm:flex dark:border-white/10 dark:bg-nivayalife-night">
    <a href="{{ route('dashboard') }}" class="px-2"><x-nivayalife-logo size="sm" /></a>

    <div class="flex flex-1 flex-col gap-5">
        @foreach($navGroups as $group => $items)
            @if(count($items))
                <div class="flex flex-col gap-0.5">
                    <p class="px-3 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-nivayalife-muted/80">{{ $group }}</p>
                    @foreach($items as $item)
                        <a href="{{ route($item['route']) }}"
                            @class([
                                'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-nivayalife-mint text-nivayalife-green dark:bg-white/10 dark:text-nivayalife-mint' => $item['active'],
                                'text-nivayalife-muted hover:bg-nivayalife-cream hover:text-nivayalife-ink dark:hover:bg-white/5 dark:hover:text-white' => ! $item['active'],
                            ])>
                            <svg class="h-[18px] w-[18px] flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>

    <div class="flex flex-col gap-2 border-t border-nivayalife-green/10 pt-3.5 dark:border-white/10">
        <div class="flex items-center gap-1 px-1">
            <x-notification-bell />
            <x-dark-mode-toggle persist-url="{{ route('profile.theme') }}" />
        </div>

        @if($navFamilyMembers->count() > 1 && $navActive)
            <div class="relative" x-data="{ switcherOpen: false }">
                <button @click="switcherOpen = !switcherOpen" @click.outside="switcherOpen = false"
                    class="flex w-full items-center gap-2 rounded-lg border border-gray-200 py-1.5 pl-1.5 pr-2.5 text-left text-sm font-medium text-nivayalife-ink hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    <x-avatar :photo-path="$navActive->photo_path" :preset="$navActive->avatar_preset ?? null" :full-name="$navActive->full_name" :gender="$navActive->gender" :age="$navActive->age()" size="h-7 w-7" />
                    <span class="min-w-0 flex-1 truncate">{{ Str::of($navActive->full_name)->words(1, '') }}</span>
                    <svg class="h-3.5 w-3.5 flex-shrink-0 text-nivayalife-muted" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
                <div x-show="switcherOpen" x-cloak x-transition
                    class="absolute bottom-full left-0 z-20 mb-2 w-64 rounded-xl border border-gray-100 bg-white p-2 shadow-nivayalife dark:border-white/10 dark:bg-nivayalife-night">
                    <p class="px-2 py-1 text-xs font-semibold text-nivayalife-muted">Switch family member</p>
                    @foreach($navFamilyMembers as $member)
                        <form method="POST" action="{{ route('dashboard.switch', $member) }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left text-sm hover:bg-nivayalife-cream dark:hover:bg-white/10 {{ $navActive->id === $member->id ? 'bg-nivayalife-mint/60 dark:bg-white/10' : '' }}">
                                <x-avatar :photo-path="$member->photo_path" :preset="$member->avatar_preset ?? null" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-8 w-8" />
                                <span>
                                    <span class="block font-medium text-nivayalife-ink dark:text-white">{{ $member->full_name }}</span>
                                    <span class="block text-xs capitalize text-nivayalife-muted">{{ $member->relation }}</span>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="relative" x-data="{ acctOpen: false }">
            <button @click="acctOpen = !acctOpen" @click.outside="acctOpen = false"
                class="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left text-sm font-medium text-nivayalife-ink hover:bg-nivayalife-cream dark:text-white dark:hover:bg-white/10">
                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-nivayalife-green text-xs font-bold text-white">{{ Str::of(Auth::user()->name)->substr(0, 1) }}</span>
                <span class="min-w-0 flex-1 truncate">
                    <span class="block truncate">{{ Auth::user()->name }}</span>
                    <span class="block truncate text-xs font-normal text-nivayalife-muted">{{ Auth::user()->email }}</span>
                </span>
            </button>
            <div x-show="acctOpen" x-cloak x-transition
                class="absolute bottom-full left-0 z-20 mb-2 w-56 rounded-xl border border-gray-100 bg-white p-1.5 shadow-nivayalife dark:border-white/10 dark:bg-nivayalife-night">
                <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm text-nivayalife-ink hover:bg-nivayalife-cream dark:text-white dark:hover:bg-white/10">Profile &amp; settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-nivayalife-ink hover:bg-nivayalife-cream dark:text-white dark:hover:bg-white/10">Log out</button>
                </form>
            </div>
        </div>
    </div>
</aside>
