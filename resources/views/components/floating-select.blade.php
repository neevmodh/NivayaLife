@props([
    'name',
    'label',
    'options' => [], // ['value' => 'Label']
    'value' => '',
    'required' => false,
    'error' => null,
    'dynamicErrors' => false,
    'placeholder' => 'Select...',
    'id' => null, // override when the same field `name` appears more than once on a page (e.g. two alternate forms)
])

@php($fieldId = $id ?? $name)

<div class="relative">
    <label for="{{ $fieldId }}" class="mb-1.5 block text-xs font-semibold text-novix-muted">
        {{ $label }}{{ $required ? ' *' : '' }}
    </label>
    <select
        name="{{ $name }}"
        id="{{ $fieldId }}"
        @if($required) required @endif
        @if($dynamicErrors)
        :class="errorFor('{{ $name }}') ? 'border-novix-pink-dark ring-2 ring-novix-pink-dark/20' : 'border-gray-200 focus:border-novix-green'"
        @endif
        {{ $attributes->merge(['class' => 'w-full appearance-none rounded-xl border bg-novix-cream/40 px-4 py-3 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30' . ($dynamicErrors ? '' : ' border-gray-200 focus:border-novix-green')]) }}
    >
        <option value="" @selected($value === '')>{{ $placeholder }}</option>
        @foreach($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>

    @if($dynamicErrors)
        <p x-cloak x-show="errorFor('{{ $name }}')" x-text="errorFor('{{ $name }}')" class="mt-1 text-xs text-novix-pink-dark"></p>
    @elseif($error)
        <p class="mt-1 text-xs text-novix-pink-dark">{{ $error }}</p>
    @endif
</div>
