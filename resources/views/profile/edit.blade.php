@php
    $tabs = [
        'basic' => 'Basic Info',
        'photo' => 'Photo',
        'address' => 'Address',
        'health' => 'Health',
        'emergency' => 'Emergency Contact',
    ];
    if (! $isDependentEdit) {
        $tabs['notifications'] = 'Notifications';
        $tabs['security'] = 'Account Security';
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">
            {{ $isDependentEdit ? "Edit {$member->full_name}'s profile" : 'Profile' }}
        </h2>
    </x-slot>

    @php
        $requestedTab = request()->query('tab');
        $initialTab = match (true) {
            $requestedTab && array_key_exists($requestedTab, $tabs) => $requestedTab,
            !$isDependentEdit && ($errors->hasBag('updatePassword') || $errors->hasBag('userDeletion') || session('status') === 'password-updated') => 'security',
            default => 'basic',
        };
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ tab: @js($initialTab) }">

        @if($isDependentEdit)
            <a href="{{ route('family.index') }}" class="mb-4 flex items-center gap-1 text-sm font-semibold text-novix-green hover:underline">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Back to family
            </a>
        @endif

        <div class="relative" x-data="{ tabsOverflow: false }" x-init="$nextTick(() => tabsOverflow = $refs.tabScroll.scrollWidth > $refs.tabScroll.clientWidth + 4)">
            <div x-ref="tabScroll" @scroll="tabsOverflow = ($el.scrollWidth - $el.scrollLeft - $el.clientWidth) > 4"
                class="flex gap-1 overflow-x-auto rounded-full bg-novix-mint/50 p-1 text-sm font-medium dark:bg-white/10">
                @foreach($tabs as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-novix-green text-white shadow-novix-sm' : 'text-novix-green/70 hover:text-novix-green dark:text-white/60'"
                        class="whitespace-nowrap rounded-full px-4 py-2 transition">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            {{-- Fades in only while there are more tabs off-screen to the right, and disappears once scrolled to the end — hints the tab bar scrolls without relying on a scrollbar alone. --}}
            <div x-show="tabsOverflow" x-cloak class="pointer-events-none absolute inset-y-0 right-0 w-10 rounded-r-full bg-gradient-to-l from-novix-mint to-transparent dark:from-white/10"></div>
        </div>

        <div class="mt-6 rounded-novix bg-white p-6 shadow-novix-sm sm:p-8 dark:bg-white/5">
            <div x-show="tab === 'basic'">@include('profile.tabs.basic-info')</div>
            <div x-show="tab === 'photo'" x-cloak>@include('profile.tabs.photo')</div>
            <div x-show="tab === 'address'" x-cloak>@include('profile.tabs.address')</div>
            <div x-show="tab === 'health'" x-cloak>@include('profile.tabs.health')</div>
            <div x-show="tab === 'emergency'" x-cloak>@include('profile.tabs.emergency-contact')</div>
            @if(! $isDependentEdit)
                <div x-show="tab === 'notifications'" x-cloak>@include('profile.tabs.notifications')</div>
                <div x-show="tab === 'security'" x-cloak>@include('profile.tabs.security')</div>
            @endif
        </div>
    </div>
</x-app-layout>
