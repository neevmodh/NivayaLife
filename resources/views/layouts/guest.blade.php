<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Nivaya Life</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @include('partials.pwa-meta')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-nivayalife-ink antialiased">
        <div class="nivayalife-surface flex min-h-screen flex-col items-center bg-nivayalife-cream px-4 pt-10 sm:justify-center sm:pt-0">
            <a href="/" aria-label="Nivaya Life home">
                <x-nivayalife-logo />
            </a>

            <div class="mt-6 w-full overflow-hidden rounded-nivayalife bg-white p-6 shadow-nivayalife sm:max-w-md sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-6 pb-8 text-xs text-nivayalife-ink/50">&copy; {{ date('Y') }} Nivaya Life</p>
        </div>
    </body>
</html>
