@php
    $starterPrompts = [
        ['label' => 'What did my last report show?', 'icon' => 'M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z'],
        ['label' => 'What am I currently taking?', 'icon' => 'M10.5 20.5 3.5 13.5a4.95 4.95 0 1 1 7-7l1 1 1-1a4.95 4.95 0 0 1 7 7l-7 7-1.5-1.5'],
        ['label' => 'Am I due for any vaccinations?', 'icon' => 'M19 8 8 19l-5-5M14 3l7 7-3 3-7-7 3-3Z'],
        ['label' => 'How do I share a report?', 'icon' => 'M18 8a3 3 0 1 0-2.83-4H15a3 3 0 0 0 .09 4.26L8.9 11.7a3 3 0 1 0 0 4.6l6.19 3.44A3 3 0 1 0 15 17.7l-6.19-3.44a3 3 0 0 0 0-.52L15 10.3c.52.44 1.19.7 1.91.7A3 3 0 0 0 18 8Z'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-novix-green shadow-novix-sm" aria-hidden="true">
                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                    <path d="M12 3v2.2M8.5 6.2h7a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-5a3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <circle cx="10" cy="11.5" r="1.1" fill="currentColor"/><circle cx="14" cy="11.5" r="1.1" fill="currentColor"/>
                    <path d="M3 11.5v2M21 11.5v2M9.5 20.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </span>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">AI Assistant</h2>
                <p class="text-sm text-novix-muted">Reading {{ $active->full_name }}'s records</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto flex h-[calc(100dvh-13rem)] max-w-3xl flex-col px-4 py-6 sm:px-6 lg:px-8"
        x-data="assistantChat({
            familyMemberId: {{ $active->id }},
            sendUrl: @js(route('assistant.send')),
            csrfToken: @js(csrf_token()),
            initialMessages: @js($messages->map(fn ($m) => ['role' => $m->role, 'content' => $m->content, 'created_at' => $m->created_at?->toIso8601String()])),
        })"
    >
        <div class="mb-3 flex items-start gap-2 rounded-novix border-l-2 border-novix-gold bg-novix-mint/40 p-3 text-xs text-novix-ink dark:bg-novix-green/10 dark:text-white/80">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-novix-green dark:text-novix-mint" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Explains and organizes {{ $active->full_name }}'s records — it never diagnoses, and never changes a medication. Always confirm with a doctor.</span>
        </div>

        <div x-ref="scrollArea" class="flex-1 space-y-4 overflow-y-auto rounded-novix bg-white p-4 shadow-novix-sm dark:bg-white/5">
            <template x-if="messages.length === 0">
                <div class="flex h-full flex-col items-center justify-center gap-5 py-10 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-novix-green shadow-novix-sm" aria-hidden="true">
                        <svg class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3v2.2M8.5 6.2h7a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-5a3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <circle cx="10" cy="11.5" r="1.1" fill="currentColor"/><circle cx="14" cy="11.5" r="1.1" fill="currentColor"/>
                            <path d="M3 11.5v2M21 11.5v2M9.5 20.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <p class="max-w-xs text-sm text-novix-muted">Ask about {{ $active->full_name }}'s reports, medications or vaccinations — or how to use Nivaya Life.</p>
                    <div class="flex w-full max-w-md flex-col gap-2">
                        @foreach($starterPrompts as $prompt)
                            <button type="button" @click="ask(@js($prompt['label']))"
                                class="group flex items-center gap-2.5 rounded-xl border border-gray-200 px-3.5 py-2.5 text-left text-xs font-medium text-novix-ink transition hover:-translate-y-0.5 hover:border-novix-green/40 hover:bg-novix-cream active:translate-y-0 dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-novix-mint text-novix-green transition group-hover:scale-110 dark:bg-novix-green/20 dark:text-novix-mint" aria-hidden="true">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="{{ $prompt['icon'] }}" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span class="flex-1">{{ $prompt['label'] }}</span>
                                <svg class="h-3.5 w-3.5 flex-shrink-0 text-novix-muted transition group-hover:translate-x-0.5 group-hover:text-novix-green" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </template>

            <template x-for="(message, index) in messages" :key="index">
                <div class="group/msg flex flex-col" :class="message.role === 'user' ? 'items-end' : 'items-start'">
                    <div class="max-w-[85%] whitespace-pre-line rounded-2xl px-4 py-2.5 text-sm leading-relaxed shadow-sm"
                        :class="message.role === 'user'
                            ? 'bg-novix-green text-white'
                            : message.urgent
                                ? 'border-l-2 border-novix-pink-dark bg-novix-pink/15 text-novix-ink dark:text-white'
                                : 'bg-novix-cream text-novix-ink dark:bg-white/10 dark:text-white'"
                        x-text="message.content">
                    </div>
                    <div class="mt-1 flex items-center gap-2 px-1 opacity-0 transition group-hover/msg:opacity-100">
                        <span class="text-[10px] tabular-nums text-novix-muted" x-text="timeFor(message)"></span>
                        <button type="button" @click="copy(index)" class="text-[10px] font-semibold text-novix-muted transition hover:text-novix-green"
                            :aria-label="copiedIndex === index ? 'Copied' : 'Copy message'">
                            <span x-text="copiedIndex === index ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                </div>
            </template>

            <div x-show="sending" class="flex justify-start">
                <div class="flex items-center gap-1.5 rounded-2xl bg-novix-cream px-4 py-3 dark:bg-white/10">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-novix-green [animation-delay:-0.3s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-novix-green [animation-delay:-0.15s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-novix-green"></span>
                </div>
            </div>
        </div>

        {{-- Dictation language — only shown where the browser can actually listen. --}}
        <div x-cloak x-show="speechSupported" class="mt-3 flex items-center gap-2 overflow-x-auto pb-1">
            <span class="flex-shrink-0 text-[11px] font-semibold uppercase tracking-wide text-novix-muted">Speak in</span>
            <template x-for="lang in languages" :key="lang.code">
                <button type="button" @click="setLanguage(lang.code)"
                    class="flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold transition"
                    :class="dictationLang === lang.code
                        ? 'bg-novix-green text-white shadow-novix-sm'
                        : 'bg-white text-novix-muted hover:text-novix-green dark:bg-white/5'"
                    :aria-pressed="(dictationLang === lang.code).toString()"
                    x-text="lang.label"></button>
            </template>
        </div>

        <p x-cloak x-show="speechError" x-text="speechError" class="mt-2 text-xs text-novix-pink-dark"></p>

        <form @submit.prevent="send()" class="mt-3 flex gap-2">
            <div class="relative flex-1">
                <input type="text" x-model="input" :disabled="sending"
                    :placeholder="listening ? 'Listening…' : 'Ask a question…'"
                    class="w-full rounded-xl border border-gray-200 py-3 pl-4 pr-12 text-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white">

                <button type="button" x-cloak x-show="speechSupported" @click="toggleDictation()"
                    class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg transition active:scale-90"
                    :class="listening ? 'bg-novix-pink-dark text-white' : 'text-novix-muted hover:bg-novix-cream hover:text-novix-green dark:hover:bg-white/10'"
                    :aria-label="listening ? 'Stop dictation' : 'Dictate your question'"
                    :aria-pressed="listening.toString()">
                    <span x-show="listening" class="absolute inline-flex h-full w-full animate-ping rounded-lg bg-novix-pink-dark/40" aria-hidden="true"></span>
                    <svg class="relative h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V6a3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M5.5 11.5a6.5 6.5 0 0 0 13 0M12 18v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <button type="submit" :disabled="sending || !input.trim()"
                class="flex items-center gap-2 rounded-xl bg-novix-green px-5 py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark active:scale-95 disabled:cursor-not-allowed disabled:opacity-40">
                Send
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12h15m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </form>
    </div>
</x-app-layout>
