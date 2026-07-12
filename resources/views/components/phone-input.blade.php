@props(['name', 'label', 'value' => '', 'dynamicErrors' => false])

<div x-data="{ val: @js((string) $value), touched: false }" class="relative">
    <input
        type="tel"
        inputmode="numeric"
        name="{{ $name }}"
        id="{{ $name }}"
        x-model="val"
        @input="val = val.replace(/\D/g, '').slice(0, 10)"
        @blur="touched = true"
        placeholder=" "
        required
        :class="(touched && val.length !== 10) @if($dynamicErrors) || errorFor('{{ $name }}') @endif ? 'border-novix-pink-dark ring-2 ring-novix-pink-dark/20' : 'border-gray-200 focus:border-novix-green'"
        class="peer w-full rounded-xl border bg-novix-cream/40 px-4 pt-5 pb-2 pr-10 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30"
    >
    <label for="{{ $name }}" class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">
        {{ $label }} *
    </label>

    <svg x-cloak x-show="val.length === 10" class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-novix-green" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>

    <p x-cloak x-show="touched && val.length > 0 && val.length !== 10" class="mt-1 text-xs text-novix-pink-dark">Enter exactly 10 digits.</p>
    @if($dynamicErrors)
        <p x-cloak x-show="!(touched && val.length !== 10) && errorFor('{{ $name }}')" x-text="errorFor('{{ $name }}')" class="mt-1 text-xs text-novix-pink-dark"></p>
    @endif
</div>
