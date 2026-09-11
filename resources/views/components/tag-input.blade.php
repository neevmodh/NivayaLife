@props([
    'name',
    'label',
    'placeholder' => 'Type and press Enter...',
    'initial' => [],
])

<div x-data="tagInput({ initial: @js($initial) })">
    <label class="mb-1.5 block text-xs font-semibold text-nivayalife-muted">{{ $label }}</label>

    <template x-for="(tag, index) in tags" :key="tag">
        <input type="hidden" :name="'{{ $name }}[]'" :value="tag">
    </template>

    <div class="flex min-h-[3rem] flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-nivayalife-cream/40 px-3 py-2 shadow-sm focus-within:border-nivayalife-green focus-within:ring-2 focus-within:ring-nivayalife-green/30">
        <template x-for="(tag, index) in tags" :key="tag">
            <span class="flex items-center gap-1.5 rounded-full bg-nivayalife-mint px-3 py-1 text-xs font-semibold text-nivayalife-green">
                <span x-text="tag"></span>
                <button type="button" @click="remove(index)" class="text-nivayalife-green/70 hover:text-nivayalife-pink-dark" aria-label="Remove">&times;</button>
            </span>
        </template>

        <input
            type="text"
            x-model="draft"
            @keydown.enter.prevent="addFromDraft()"
            @keydown.comma.prevent="addFromDraft()"
            @blur="addFromDraft()"
            placeholder="{{ $placeholder }}"
            class="min-w-[10rem] flex-1 border-0 bg-transparent p-1 text-sm text-nivayalife-ink placeholder:text-nivayalife-muted focus:outline-none focus:ring-0"
        >
    </div>
    <p class="mt-1 text-xs text-nivayalife-muted">Optional — press Enter after each one.</p>
</div>
