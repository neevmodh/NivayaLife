{{-- iconBg/iconColor must be full, literal Tailwind class names (e.g.
     "bg-novix-mint"), not bare color fragments — Tailwind's build-time
     scanner only picks up complete class strings it can find as text. --}}
@props(['iconColor' => 'text-novix-green', 'iconBg' => 'bg-novix-mint'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Novix</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-novix-cream p-4 font-sans antialiased">
    <div class="w-full max-w-md rounded-novix bg-white p-8 text-center shadow-novix">
        <a href="{{ url('/') }}" class="mb-6 inline-block"><x-novix-logo /></a>

        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $iconBg }} {{ $iconColor }}">
            {{ $icon ?? '' }}
        </span>

        {{ $slot }}
    </div>
</body>
</html>
