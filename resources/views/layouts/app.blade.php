<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->check() && auth()->user()->theme_preference === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Nivaya Life') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @include('partials.pwa-meta')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <x-pending-invitation-popup />
        <x-install-prompt />

        <div class="novix-surface min-h-screen bg-novix-cream dark:bg-novix-night">
            @include('layouts.navigation')

            {{-- The header used to sit in its own white band, which cut a hard
                 seam across the page where it met the cream body. It now shares
                 the page background and just leads the content. --}}
            @isset($header)
                <header class="mx-auto max-w-7xl px-4 pb-1 pt-6 sm:px-6 sm:pt-8 lg:px-8">
                    {{ $header }}
                </header>
            @endisset

            {{-- pb-24 clears the fixed mobile tab bar; sm:pb-0 drops it once the
                 bar is hidden and the top nav takes over — except in an
                 installed PWA, where the bar stays at every width. --}}
            <main class="pb-24 sm:pb-0 sm:standalone:pb-24">
                {{ $slot }}
            </main>

            <x-bottom-nav />
            <x-assistant-fab />
        </div>
    </body>
</html>
