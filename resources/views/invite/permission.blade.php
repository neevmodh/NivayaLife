<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose what to share — Nivaya Life</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-novix-cream font-sans antialiased">
<div class="mx-auto max-w-xl px-4 py-10 sm:py-14">
    <a href="{{ url('/') }}" class="mb-6 flex justify-center"><x-novix-logo /></a>

    <div class="rounded-novix bg-white p-6 shadow-novix sm:p-8" x-data="{ scope: '' }">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-novix-mint px-3 py-1 text-xs font-semibold text-novix-green">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M6 10V8a6 6 0 1 1 12 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
            Your privacy, your choice
        </span>
        <h1 class="mt-4 text-2xl font-bold text-novix-ink">You're in! Now, how much can {{ $invitation->primaryAccount->name }} see?</h1>
        <p class="mt-1 text-sm text-novix-muted">
            This is your health record — you decide what {{ $invitation->primaryAccount->name }} can view. You can change
            this any time from your own account.
        </p>

        <form method="POST" action="{{ route('invite.permission', $invitation->token) }}" class="mt-6 space-y-3">
            @csrf

            <label class="block cursor-pointer rounded-xl border-2 p-4 transition"
                :class="scope === 'full' ? 'border-novix-green bg-novix-mint/40' : 'border-gray-200 hover:border-novix-green/40'">
                <input type="radio" name="scope" value="full" x-model="scope" class="sr-only">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border-2" :class="scope === 'full' ? 'border-novix-green bg-novix-green' : 'border-gray-300'">
                        <svg x-show="scope === 'full'" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-novix-ink">Full access</p>
                        <p class="mt-0.5 text-xs text-novix-muted">They can see and edit your complete profile — reports, medications, vitals, everything.</p>
                    </div>
                </div>
            </label>

            <label class="block cursor-pointer rounded-xl border-2 p-4 transition"
                :class="scope === 'reports_only' ? 'border-novix-green bg-novix-mint/40' : 'border-gray-200 hover:border-novix-green/40'">
                <input type="radio" name="scope" value="reports_only" x-model="scope" class="sr-only">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border-2" :class="scope === 'reports_only' ? 'border-novix-green bg-novix-green' : 'border-gray-300'">
                        <svg x-show="scope === 'reports_only'" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-novix-ink">Reports only</p>
                        <p class="mt-0.5 text-xs text-novix-muted">They can see uploaded reports only — not day-to-day vitals or medications.</p>
                    </div>
                </div>
            </label>

            <label class="block cursor-pointer rounded-xl border-2 p-4 transition"
                :class="scope === 'summary_only' ? 'border-novix-green bg-novix-mint/40' : 'border-gray-200 hover:border-novix-green/40'">
                <input type="radio" name="scope" value="summary_only" x-model="scope" class="sr-only">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full border-2" :class="scope === 'summary_only' ? 'border-novix-green bg-novix-green' : 'border-gray-300'">
                        <svg x-show="scope === 'summary_only'" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-novix-ink">Summary only</p>
                        <p class="mt-0.5 text-xs text-novix-muted">They can see AI-generated plain-language summaries only — no raw reports or detailed data.</p>
                    </div>
                </div>
            </label>

            <button type="submit" :disabled="!scope"
                class="w-full rounded-xl bg-novix-green py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark disabled:cursor-not-allowed disabled:opacity-40">
                Confirm &amp; continue to my dashboard
            </button>
        </form>
    </div>
</div>
</body>
</html>
