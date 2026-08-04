@php
    // Hidden on the assistant itself (a button to where you already are is
    // just clutter) and on the upload flow, where the raised upload action
    // already owns that corner.
    $hidden = request()->routeIs('assistant*') || request()->routeIs('reports.upload');
@endphp

@unless($hidden)
    {{-- Floating assistant. Sits above the mobile tab bar, and drops to the
         normal corner once that bar is gone. --}}
    <div x-data="{ open: false }" class="fixed bottom-24 right-4 z-40 flex flex-col items-end gap-2 sm:bottom-6 sm:right-6 sm:standalone:bottom-24"
        style="margin-bottom: env(safe-area-inset-bottom);">

        <div x-cloak x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-56 origin-bottom-right rounded-novix border-t-2 border-novix-gold/50 bg-white p-3 shadow-novix dark:bg-novix-night">
            <p class="text-xs font-bold text-novix-ink dark:text-white">Ask the assistant</p>
            <p class="mt-0.5 text-[11px] leading-relaxed text-novix-muted">
                Questions about reports, medicines or how to use the app — by typing or voice.
            </p>
            <a href="{{ route('assistant') }}"
                class="mt-2.5 flex items-center justify-center gap-1.5 rounded-xl bg-novix-green py-2 text-xs font-bold text-white transition hover:bg-novix-green-dark active:scale-95">
                Open assistant
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>

        <button type="button" @click="open = !open" @click.outside="open = false"
            class="group relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-novix-green to-novix-green-dark text-white shadow-novix ring-2 ring-novix-gold/50 transition hover:-translate-y-0.5 active:scale-95"
            :aria-expanded="open.toString()" aria-label="Ask the AI assistant">
            {{-- A single slow pulse marking it as the live, intelligent bit —
                 stilled for anyone who asked their OS for less motion. --}}
            <span class="absolute inline-flex h-full w-full animate-ping rounded-2xl bg-novix-green/25 motion-reduce:hidden" aria-hidden="true" style="animation-duration:3s"></span>
            <svg class="relative h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 3v2.2M8.5 6.2h7a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-5a3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <circle cx="10" cy="11.5" r="1.1" fill="currentColor"/><circle cx="14" cy="11.5" r="1.1" fill="currentColor"/>
                <path d="M3 11.5v2M21 11.5v2M9.5 20.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
@endunless
