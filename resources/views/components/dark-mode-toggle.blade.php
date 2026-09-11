@props(['persistUrl' => null])

<button
    type="button"
    x-data="darkMode({ persistUrl: @js($persistUrl), csrfToken: @js(csrf_token()), initial: @js(auth()->check() ? auth()->user()->theme_preference === 'dark' : null) })"
    @click="toggle()"
    class="flex h-10 w-10 items-center justify-center rounded-full border border-nivayalife-green/15 bg-white text-nivayalife-ink shadow-nivayalife-sm transition hover:bg-nivayalife-mint/40 dark:border-white/10 dark:bg-white/10 dark:text-white"
    aria-label="Toggle dark mode"
>
    <svg x-show="!isDark" class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.4 6.4-1.4-1.4M6.6 6.6 5.2 5.2m12.2 0-1.4 1.4M6.6 17.4l-1.4 1.4M17 12a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    <svg x-cloak x-show="isDark" class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
</button>
