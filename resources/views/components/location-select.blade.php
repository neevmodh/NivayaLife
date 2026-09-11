@props([
    'country' => '',
    'state' => '',
    'city' => '',
    'dynamicErrors' => false,
    'elementId' => null,
    'required' => true,
])

@php($fieldId = $elementId ?? 'location-'.\Illuminate\Support\Str::random(6))

<div
    @if($elementId) id="{{ $elementId }}" @endif
    x-data="locationSelect({ required: @js($required), initialCountry: @js($country), initialState: @js($state), initialCity: @js($city) })"
    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
>
    {{-- The selects drive cascading lookups via ISO codes; the actual form
         fields are the human-readable names the schema stores, kept in sync
         via hidden inputs so a plain FormData(form) capture is correct. --}}
    <input type="hidden" name="country" :value="countryName">
    <input type="hidden" name="state" :value="stateName">

    <template x-if="!loaded && !loadError">
        <div class="col-span-full flex items-center gap-2 text-sm text-nivayalife-muted">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            Loading locations...
        </div>
    </template>

    <template x-if="loadError">
        <div class="col-span-full flex items-center gap-2 text-sm text-nivayalife-pink-dark">
            <span>Couldn't load the location list.</span>
            <button type="button" @click="loadError = false; init()" class="font-semibold underline underline-offset-2">Retry</button>
        </div>
    </template>

    <div x-show="loaded" x-cloak>
        <label for="{{ $fieldId }}-country" class="mb-1.5 block text-xs font-semibold text-nivayalife-muted">Country{{ $required ? ' *' : ' (optional)' }}</label>
        <select id="{{ $fieldId }}-country" x-model="countryIso" @change="onCountryChange()" @if($required) required @endif
            @if($dynamicErrors) :class="errorFor('country') ? 'border-nivayalife-pink-dark' : 'border-gray-200 focus:border-nivayalife-green'" @endif
            class="w-full appearance-none rounded-xl border bg-nivayalife-cream/40 px-4 py-3 text-sm text-nivayalife-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30 {{ $dynamicErrors ? '' : 'border-gray-200 focus:border-nivayalife-green' }}">
            @unless($required)
                <option value="">{{ __('Select a country') }}</option>
            @endunless
            <template x-for="c in countries" :key="c.isoCode">
                <option :value="c.isoCode" x-text="c.name" :selected="c.isoCode === countryIso"></option>
            </template>
        </select>
    </div>

    <div x-show="loaded" x-cloak>
        <label for="{{ $fieldId }}-state" class="mb-1.5 block text-xs font-semibold text-nivayalife-muted">State{{ $required ? ' *' : ' (optional)' }}</label>
        <select id="{{ $fieldId }}-state" x-model="stateIso" @change="onStateChange()" :disabled="states.length === 0" @if($required) required @endif
            :class="states.length === 0 ? 'cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-nivayalife-cream/40 text-nivayalife-ink'"
            class="w-full appearance-none rounded-xl border border-gray-200 px-4 py-3 text-sm shadow-sm transition focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30">
            <option value="">{{ __('Select a state') }}</option>
            <template x-for="s in states" :key="s.isoCode">
                <option :value="s.isoCode" x-text="s.name" :selected="s.isoCode === stateIso"></option>
            </template>
        </select>
    </div>

    <div x-show="loaded" x-cloak>
        <label for="{{ $fieldId }}-city" class="mb-1.5 block text-xs font-semibold text-nivayalife-muted">City{{ $required ? ' *' : ' (optional)' }}</label>
        <input type="text" id="{{ $fieldId }}-city" name="city" x-model="cityName" list="{{ $fieldId }}-city-options"
            :disabled="cities.length === 0" @if($required) required @endif autocomplete="off"
            :class="cities.length === 0 ? 'cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-nivayalife-cream/40 text-nivayalife-ink'"
            class="w-full appearance-none rounded-xl border border-gray-200 px-4 py-3 text-sm shadow-sm transition focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30">
        <datalist id="{{ $fieldId }}-city-options">
            <template x-for="c in cities" :key="c.name">
                <option :value="c.name"></option>
            </template>
        </datalist>
    </div>
</div>
