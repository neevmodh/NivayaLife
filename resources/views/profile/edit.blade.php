@php
    $tabs = [
        'basic' => 'Basic Info',
        'photo' => 'Photo',
        'address' => 'Address',
        'health' => 'Health',
        'emergency' => 'Emergency Contact',
        'security' => 'Account Security',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Profile</h2>
    </x-slot>

    @php
        $initialTab = ($errors->hasBag('updatePassword') || $errors->hasBag('userDeletion') || session('status') === 'password-updated')
            ? 'security'
            : 'basic';
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ tab: @js($initialTab) }">

        <div class="flex gap-1 overflow-x-auto rounded-full bg-novix-mint/50 p-1 text-sm font-medium dark:bg-white/10">
            @foreach($tabs as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-novix-green text-white shadow-novix-sm' : 'text-novix-green/70 hover:text-novix-green dark:text-white/60'"
                    class="whitespace-nowrap rounded-full px-4 py-2 transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mt-6 rounded-novix bg-white p-6 shadow-novix-sm sm:p-8 dark:bg-white/5">
            <div x-show="tab === 'basic'">@include('profile.tabs.basic-info')</div>
            <div x-show="tab === 'photo'" x-cloak>@include('profile.tabs.photo')</div>
            <div x-show="tab === 'address'" x-cloak>@include('profile.tabs.address')</div>
            <div x-show="tab === 'health'" x-cloak>@include('profile.tabs.health')</div>
            <div x-show="tab === 'emergency'" x-cloak>@include('profile.tabs.emergency-contact')</div>
            <div x-show="tab === 'security'" x-cloak>@include('profile.tabs.security')</div>
        </div>
    </div>
</x-app-layout>
