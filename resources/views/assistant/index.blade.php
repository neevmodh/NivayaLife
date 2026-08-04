@php
    $starterPrompts = [
        'What did my last report show?',
        'What medications am I currently on?',
        'Am I due for any vaccinations?',
        'How do I share a report with someone?',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">AI Assistant</h2>
        <p class="mt-1 text-sm text-novix-muted">For {{ $active->full_name }}</p>
    </x-slot>

    <div class="mx-auto flex h-[calc(100vh-13rem)] max-w-3xl flex-col px-4 py-8 sm:px-6 lg:px-8"
        x-data="assistantChat({
            familyMemberId: {{ $active->id }},
            sendUrl: @js(route('assistant.send')),
            csrfToken: @js(csrf_token()),
            initialMessages: @js($messages->map(fn ($m) => ['role' => $m->role, 'content' => $m->content, 'created_at' => $m->created_at?->toIso8601String()])),
        })"
    >
        <div class="mb-3 rounded-novix bg-novix-mint/40 p-3 text-xs text-novix-ink dark:bg-novix-green/10 dark:text-white/80">
            This assistant answers using {{ $active->full_name }}'s records in Nivaya Life and can explain how to use the app &mdash; it isn't a substitute for professional medical advice.
        </div>

        <div x-ref="scrollArea" class="flex-1 space-y-4 overflow-y-auto rounded-novix bg-white p-4 shadow-novix-sm dark:bg-white/5">
            <template x-if="messages.length === 0">
                <div class="flex h-full flex-col items-center justify-center gap-4 py-10 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-novix-mint text-2xl dark:bg-novix-green/20" aria-hidden="true">&#129302;</span>
                    <p class="text-sm text-novix-muted">Ask me anything about {{ $active->full_name }}'s health records, or how to use Nivaya Life.</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach($starterPrompts as $prompt)
                            <button type="button" @click="ask(@js($prompt))" class="rounded-full border border-gray-200 px-3 py-1.5 text-xs font-medium text-novix-ink hover:border-novix-green/40 hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                                {{ $prompt }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </template>

            <template x-for="(message, index) in messages" :key="index">
                <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%] whitespace-pre-line rounded-2xl px-4 py-2.5 text-sm"
                        :class="message.role === 'user'
                            ? 'bg-novix-green text-white'
                            : 'bg-novix-cream text-novix-ink dark:bg-white/10 dark:text-white'"
                        x-text="message.content">
                    </div>
                </div>
            </template>

            <div x-show="sending" class="flex justify-start">
                <div class="flex items-center gap-1.5 rounded-2xl bg-novix-cream px-4 py-2.5 dark:bg-white/10">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-novix-muted [animation-delay:-0.3s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-novix-muted [animation-delay:-0.15s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-novix-muted"></span>
                </div>
            </div>
        </div>

        <form @submit.prevent="send()" class="mt-3 flex gap-2">
            <input type="text" x-model="input" placeholder="Ask a question&hellip;" :disabled="sending"
                class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <button type="submit" :disabled="sending || !input.trim()"
                class="rounded-xl bg-novix-green px-5 py-3 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark disabled:cursor-not-allowed disabled:opacity-40">
                Send
            </button>
        </form>
    </div>
</x-app-layout>
