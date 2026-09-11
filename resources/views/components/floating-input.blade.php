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
    'id' => null, // override when the same field `name` appears more than once on a page (e.g. two alternate forms)
])

@php($fieldId = $id ?? $name)

<div x-data="{ val: @js((string) $value) }" class="relative">
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        value="{{ $value }}"
        @if($required) required @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($inputmode) inputmode="{{ $inputmode }}" @endif
        @if($maxlength) maxlength="{{ $maxlength }}" @endif
        x-model="val"
        placeholder=" "
        @if($dynamicErrors)
        :class="errorFor('{{ $name }}') ? 'border-nivayalife-pink-dark ring-2 ring-nivayalife-pink-dark/20' : 'border-gray-200 focus:border-nivayalife-green'"
        @endif
        {{ $attributes->merge(['class' => 'peer w-full rounded-xl border bg-nivayalife-cream/40 px-4 pt-5 pb-2 pr-10 text-sm text-nivayalife-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30' . ($dynamicErrors ? '' : ' border-gray-200 focus:border-nivayalife-green')]) }}
    >
    <label for="{{ $fieldId }}"
        class="pointer-events-none absolute left-4 top-3.5 text-sm text-nivayalife-muted transition-all duration-150 peer-focus:top-1.5 peer-focus:text-[11px] peer-focus:text-nivayalife-green peer-[&:not(:placeholder-shown)]:top-1.5 peer-[&:not(:placeholder-shown)]:text-[11px]">
        {{ $label }}{{ $required ? ' *' : '' }}
    </label>

    @if($dynamicErrors)
        <svg x-cloak x-show="val && val.length > 0 && !errorFor('{{ $name }}')" class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-nivayalife-green" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <svg x-cloak x-show="errorFor('{{ $name }}')" class="pointer-events-none absolute right-3 top-3.5 h-5 w-5 text-nivayalife-pink-dark" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        <p x-cloak x-show="errorFor('{{ $name }}')" x-text="errorFor('{{ $name }}')" class="mt-1 text-xs text-nivayalife-pink-dark"></p>
    @elseif($error)
        <p class="mt-1 text-xs text-nivayalife-pink-dark">{{ $error }}</p>
    @endif
</div>
