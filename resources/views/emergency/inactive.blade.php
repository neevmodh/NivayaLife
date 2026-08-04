<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Card Inactive — Nivaya Life</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-novix-cream font-sans antialiased">
<div class="mx-auto max-w-md px-4 py-16 text-center">
    <a href="{{ url('/') }}" class="mb-8 flex justify-center"><x-novix-logo /></a>

    <div class="rounded-novix bg-white p-8 shadow-novix">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-novix-pink/25 text-novix-pink-dark" aria-hidden="true">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M6 10V8a6 6 0 1 1 12 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        </span>
        <h1 class="mt-4 text-lg font-bold text-novix-ink">{{ $labels['en']['inactive_title'] }}</h1>
        <p class="mt-2 text-sm text-novix-muted">{{ $labels['en']['inactive_body'] }}</p>
    </div>
</div>
</body>
</html>
