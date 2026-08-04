@php
    $navFamilyMembers = \App\Models\FamilyMember::where('primary_account_id', auth()->id())
        ->orWhere('linked_user_id', auth()->id())
        ->orderByRaw("relation = 'self' desc")
        ->orderBy('full_name')
        ->get();
    $navActive = $navFamilyMembers->firstWhere('id', session('active_family_member_id')) ?? $navFamilyMembers->firstWhere('relation', 'self') ?? $navFamilyMembers->first();
@endphp

{{-- Mobile gets a solid brand-green header (matching the installed-app
     reference designs); sm: and up reverts to the light, blurred desktop
     bar — same nav, two skins, split entirely by breakpoint rather than a
     second template. --}}
<nav x-data="{ open: false, switcherOpen: false }"
    class="sticky top-0 z-30 border-b border-novix-green-dark/40 bg-novix-green sm:border-novix-green/10 sm:bg-white/85 sm:backdrop-blur-md dark:sm:border-white/10 dark:sm:bg-novix-ink/85">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}"><x-novix-logo size="sm" dark="responsive" /></a>

                <div class="hidden gap-6 text-sm font-medium sm:flex">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'text-novix-green' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">Dashboard</a>
                    <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'text-novix-green' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">Reports</a>

                    <div class="relative" x-data="{ healthOpen: false }">
                        <button @click="healthOpen = !healthOpen" @click.outside="healthOpen = false"
                            class="flex items-center gap-1 {{ request()->routeIs(['timeline*', 'medications.*', 'vaccinations.*']) ? 'text-novix-green' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">
                            Health
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                        <div x-show="healthOpen" x-cloak x-transition
                            class="absolute left-0 z-20 mt-2 w-48 rounded-xl border border-gray-100 bg-white p-1.5 shadow-novix dark:border-white/10 dark:bg-novix-ink">
                            <a href="{{ route('timeline') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('timeline*') ? 'bg-novix-mint/60 text-novix-green dark:bg-white/10 dark:text-novix-mint' : 'text-novix-ink hover:bg-novix-cream dark:text-white dark:hover:bg-white/10' }}">Timeline</a>
                            <a href="{{ route('medications.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('medications.*') ? 'bg-novix-mint/60 text-novix-green dark:bg-white/10 dark:text-novix-mint' : 'text-novix-ink hover:bg-novix-cream dark:text-white dark:hover:bg-white/10' }}">Medications</a>
                            <a href="{{ route('vaccinations.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('vaccinations.*') ? 'bg-novix-mint/60 text-novix-green dark:bg-white/10 dark:text-novix-mint' : 'text-novix-ink hover:bg-novix-cream dark:text-white dark:hover:bg-white/10' }}">Vaccinations</a>
                        </div>
                    </div>

                    <a href="{{ route('family.index') }}" class="{{ request()->routeIs('family.*') ? 'text-novix-green' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">Family</a>
                    <a href="{{ route('id-card.show') }}" class="{{ request()->routeIs('id-card.show') ? 'text-novix-green' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">Emergency Card</a>
                    <a href="{{ route('assistant') }}" class="{{ request()->routeIs('assistant*') ? 'text-novix-green' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">Assistant</a>
                    @if(auth()->user()?->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'text-novix-green' : 'text-novix-muted hover:text-novix-ink dark:hover:text-white' }}">Admin</a>
                    @endif
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                @if($navFamilyMembers->count() > 1 && $navActive)
                    <div class="relative">
                        <button @click="switcherOpen = !switcherOpen" @click.outside="switcherOpen = false"
                            class="flex items-center gap-2 rounded-full border border-gray-200 py-1 pl-1 pr-3 text-sm font-medium text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                            <x-avatar :photo-path="$navActive->photo_path" :full-name="$navActive->full_name" :gender="$navActive->gender" :age="$navActive->age()" size="h-7 w-7" />
                            {{ Str::of($navActive->full_name)->words(1, '') }}
                        </button>
                        <div x-show="switcherOpen" x-cloak x-transition
                            class="absolute right-0 z-20 mt-2 w-64 rounded-xl border border-gray-100 bg-white p-2 shadow-novix dark:border-white/10 dark:bg-novix-ink">
                            <p class="px-2 py-1 text-xs font-semibold text-novix-muted">Switch family member</p>
                            @foreach($navFamilyMembers as $member)
                                <form method="POST" action="{{ route('dashboard.switch', $member) }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left text-sm hover:bg-novix-cream dark:hover:bg-white/10 {{ $navActive->id === $member->id ? 'bg-novix-mint/60 dark:bg-white/10' : '' }}">
                                        <x-avatar :photo-path="$member->photo_path" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-8 w-8" />
                                        <span>
                                            <span class="block font-medium text-novix-ink dark:text-white">{{ $member->full_name }}</span>
                                            <span class="block text-xs capitalize text-novix-muted">{{ $member->relation }}</span>
                                        </span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif

                <x-dark-mode-toggle persist-url="{{ route('profile.theme') }}" />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-1 rounded-full border border-transparent px-2 py-1.5 text-sm font-medium text-novix-ink hover:bg-novix-cream dark:text-white dark:hover:bg-white/10">
                            {{ Auth::user()->name }}
                            <svg class="h-4 w-4 text-novix-muted" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center gap-1 sm:hidden">
                <x-dark-mode-toggle persist-url="{{ route('profile.theme') }}" />
                @if($navActive)
                    <a href="{{ route('family.index') }}" aria-label="Family members">
                        <x-avatar :photo-path="$navActive->photo_path" :full-name="$navActive->full_name" :gender="$navActive->gender" :age="$navActive->age()" size="h-8 w-8" class="ring-2 ring-white/40" />
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-novix-green/10 bg-white dark:border-white/10 dark:bg-novix-ink sm:hidden">
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
                <div class="text-base font-medium text-novix-ink dark:text-white">{{ Auth::user()->name }}</div>
                <div class="text-sm font-medium text-novix-muted">{{ Auth::user()->email }}</div>
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
