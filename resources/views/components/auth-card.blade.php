@props(['active' => 'login'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Novix') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased dark:bg-novix-ink">
        <div class="min-h-screen bg-novix-cream dark:bg-novix-ink flex items-center justify-center p-4 sm:p-6">
            <div class="w-full max-w-4xl bg-white dark:bg-white/5 rounded-novix shadow-novix overflow-hidden grid md:grid-cols-2 relative">
                <div class="absolute right-4 top-4 z-10 md:right-6 md:top-6">
                    <x-dark-mode-toggle />
                </div>

                <div class="relative hidden md:flex flex-col justify-between bg-novix-green p-10 text-white overflow-hidden">
                    <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-white/5"></div>
                    <div class="absolute -left-16 bottom-0 h-64 w-64 rounded-full bg-white/5"></div>

                    <div class="relative">
                        <a href="{{ url('/') }}"><x-novix-logo dark /></a>
                    </div>

                    <div class="relative">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 5v6c0 5 3.4 9 8 11 4.6-2 8-6 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            No more paper
                        </span>
                        <h2 class="mt-4 text-3xl font-bold leading-tight">Welcome to Novix</h2>
                        <p class="mt-3 text-white/80 text-sm leading-relaxed">
                            One secure place for your family's health records — organized online and explained
                            in plain language.
                        </p>

                        <ul class="mt-8 space-y-3 text-sm text-white/90">
                            <li class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                </span>
                                Nothing lost, nothing damaged
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                Built for the whole family
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                Ready when it matters most
                            </li>
                        </ul>
                    </div>

                    <p class="relative text-xs text-white/50">&copy; {{ date('Y') }} Novix</p>
                </div>

                <div class="p-8 sm:p-10 flex flex-col justify-center">
                    <div class="md:hidden mb-6 flex justify-center">
                        <x-novix-logo />
                    </div>

                    <div class="mx-auto w-full max-w-sm">
                        <div class="flex rounded-full bg-novix-mint/60 p-1 text-sm font-medium">
                            <a href="{{ route('login') }}"
                                class="flex-1 text-center rounded-full py-2 transition {{ $active === 'login' ? 'bg-novix-green text-white shadow-novix-sm' : 'text-novix-green/70 hover:text-novix-green' }}">
                                Login
                            </a>
                            <a href="{{ route('register') }}"
                                class="flex-1 text-center rounded-full py-2 transition {{ $active === 'register' ? 'bg-novix-green text-white shadow-novix-sm' : 'text-novix-green/70 hover:text-novix-green' }}">
                                Create Account
                            </a>
                        </div>

                        <div class="mt-8">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
