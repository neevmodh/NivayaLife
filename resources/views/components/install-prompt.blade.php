<div x-data="installPrompt()" x-show="show" x-cloak class="fixed inset-x-0 bottom-0 z-[90] flex justify-center p-4 sm:bottom-6">
    <div x-show="show" x-transition class="flex w-full max-w-md items-center gap-4 rounded-nivayalife bg-white p-4 shadow-nivayalife dark:bg-nivayalife-night dark:ring-1 dark:ring-white/10">
        <img src="/icons/icon-192.png" alt="" class="h-12 w-12 flex-shrink-0 rounded-xl">

        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-nivayalife-ink dark:text-white">Install Nivaya Life</p>
            <p class="text-xs text-nivayalife-muted">Add it to your home screen for quick, full-screen access.</p>
        </div>

        <div class="flex flex-shrink-0 items-center gap-2">
            <button type="button" @click="dismiss()" class="rounded-lg px-3 py-2 text-xs font-semibold text-nivayalife-muted hover:text-nivayalife-ink dark:hover:text-white">
                Not now
            </button>
            <button type="button" @click="install()" class="rounded-lg bg-nivayalife-green px-4 py-2 text-xs font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark">
                Install
            </button>
        </div>
    </div>
</div>
