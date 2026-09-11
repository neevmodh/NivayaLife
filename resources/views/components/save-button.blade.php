@props(['label' => 'Save changes'])

<div class="flex items-center gap-3">
    <button type="submit" :disabled="saving"
        class="flex items-center gap-2 rounded-xl bg-nivayalife-green px-6 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark disabled:opacity-60">
        <svg x-show="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        {{ $label }}
    </button>

    <span x-show="saved" x-cloak x-transition class="flex items-center gap-1.5 text-sm font-semibold text-nivayalife-green">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Saved
    </span>

    <span x-show="errorFor('_general')" x-cloak x-text="errorFor('_general')" class="text-sm text-nivayalife-pink-dark"></span>
</div>
