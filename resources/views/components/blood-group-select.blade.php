@props([
    'name' => 'blood_group',
    'value' => '',
    'label' => 'Blood group',
    'dynamicErrors' => false,
])

<div x-data="bloodGroupSelect({ initial: @js($value) })" class="relative">
    <label class="mb-1.5 block text-xs font-semibold text-novix-muted">{{ $label }} *</label>

    <input type="hidden" name="{{ $name }}" :value="selected">

    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
        <template x-for="option in options" :key="option">
            <button
                type="button"
                @click="select(option)"
                :class="selected === option
                    ? 'border-novix-pink-dark bg-novix-pink-dark text-white shadow-novix-sm'
                    : 'border-gray-200 bg-white text-novix-ink hover:border-novix-pink-dark/50'"
                class="rounded-xl border-2 px-3 py-2.5 text-sm font-bold transition"
            >
                <span x-text="option"></span>
            </button>
        </template>
    </div>

    @if($dynamicErrors)
        <p x-cloak x-show="errorFor('{{ $name }}')" x-text="errorFor('{{ $name }}')" class="mt-1.5 text-xs text-novix-pink-dark"></p>
    @endif
</div>
