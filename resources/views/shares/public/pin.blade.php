<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter PIN — Nivaya Life</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-nivayalife-cream font-sans antialiased">
<div class="mx-auto max-w-sm px-4 py-16 text-center">
    <a href="{{ url('/') }}" class="mb-8 flex justify-center"><x-nivayalife-logo /></a>

    <div class="rounded-nivayalife bg-white p-6 shadow-nivayalife">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-nivayalife-mint text-nivayalife-green" aria-hidden="true">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2ZM8 11V7a4 4 0 1 1 8 0v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <h1 class="mt-3 text-lg font-bold text-nivayalife-ink">This link is protected</h1>
        <p class="mt-1 text-sm text-nivayalife-muted">Enter the 4-digit PIN you were given to view it.</p>

        @if($error)
            <p class="mt-3 rounded-lg bg-nivayalife-pink/20 px-3 py-2 text-sm font-semibold text-nivayalife-pink-dark">{{ $error }}</p>
        @endif

        <form method="POST" action="{{ route('share.public.pin', $share->token) }}" class="mt-4">
            @csrf
            <input type="text" name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autofocus required
                class="w-full rounded-xl border border-gray-200 px-4 py-3 text-center text-2xl font-bold tracking-[0.5em] focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30">
            <button type="submit" class="mt-4 w-full rounded-xl bg-nivayalife-green py-3 text-sm font-semibold text-white hover:bg-nivayalife-green-dark">
                Unlock
            </button>
        </form>
    </div>
</div>
</body>
</html>
