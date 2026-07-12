@props([
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'required' => false,
    'autocomplete' => null,
    'error' => null,
    'dynamicErrors' => false,
    'inputmode' => null,
    'maxlength' => null,
])

<div x-data="{ val: @js((string) $value) }" class="relative">
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $value }}"
        @if($required) required @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($inputmode) inputmode="{{ $inputmode }}" @endif
        @if($maxlength) maxlength="{{ $maxlength }}" @endif
        x-model="val"
        placeholder=" "
        @if($dynamicErrors)
        :class="errorFor('{{ $name }}') ? 'border-novix-pink-dark ring-2 ring-novix-pink-dark/20' : 'border-gray-200 focus:border-novix-green'"
        @endif
        {{ $attributes->merge(['class' => 'peer w-full rounded-xl border bg-novix-cream/40 px-4 pt-5 pb-2 pr-10 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30' . ($dynamicErrors ? '' : ' border-gray-200 focus:border-novix-green')]) }}
    >
    <label for="{{ $name }}"
        class="pointer-events-none absolute left-4 top-3.5 text-sm text-novix-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-novix-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">
        {{ $label }}{{ $required ? ' *' : '' }}
    </label>

    @if($dynamicErrors)
        <svg x-cloak x-show="val && val.length > 0 && !errorFor('{{ $name }}')" class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-novix-green" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <svg x-cloak x-show="errorFor('{{ $name }}')" class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-novix-pink-dark" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        <p x-cloak x-show="errorFor('{{ $name }}')" x-text="errorFor('{{ $name }}')" class="mt-1 text-xs text-novix-pink-dark"></p>
    @elseif($error)
        <p class="mt-1 text-xs text-novix-pink-dark">{{ $error }}</p>
    @endif
</div>
