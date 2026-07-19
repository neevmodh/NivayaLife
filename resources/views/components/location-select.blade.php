@props([
    'country' => 'India',
    'state' => '',
    'city' => '',
    'dynamicErrors' => false,
    'elementId' => null,
    'required' => true,
])

<div
    @if($elementId) id="{{ $elementId }}" @endif
    x-data="locationSelect({ initialCountry: @js($country), initialState: @js($state), initialCity: @js($city) })"
    class="grid grid-cols-1 gap-4 sm:grid-cols-3"
>
    {{-- The selects drive cascading lookups via ISO codes; the actual form
         fields are the human-readable names the schema stores, kept in sync
         via hidden inputs so a plain FormData(form) capture is correct. --}}
    <input type="hidden" name="country" :value="countryName">
    <input type="hidden" name="state" :value="stateName">

    <template x-if="!loaded">
        <div class="col-span-full flex items-center gap-2 text-sm text-novix-muted">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" stroke-opacity="0.3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            Loading locations...
        </div>
    </template>

    <div x-show="loaded" x-cloak>
        <label class="mb-1.5 block text-xs font-semibold text-novix-muted">Country{{ $required ? ' *' : ' (optional)' }}</label>
        <select x-model="countryIso" @change="onCountryChange()" @if($required) required @endif
            @if($dynamicErrors) :class="errorFor('country') ? 'border-novix-pink-dark' : 'border-gray-200 focus:border-novix-green'" @endif
            class="w-full appearance-none rounded-xl border bg-novix-cream/40 px-4 py-3 text-sm text-novix-ink shadow-sm transition focus:outline-none focus:ring-2 focus:ring-novix-green/30 {{ $dynamicErrors ? '' : 'border-gray-200 focus:border-novix-green' }}">
            @unless($required)
                <option value="">{{ __('Select a country') }}</option>
            @endunless
            <template x-for="c in countries" :key="c.isoCode">
                <option :value="c.isoCode" x-text="c.name" :selected="c.isoCode === countryIso"></option>
            </template>
        </select>
    </div>

    <div x-show="loaded" x-cloak>
        <label class="mb-1.5 block text-xs font-semibold text-novix-muted">State{{ $required ? ' *' : ' (optional)' }}</label>
        <select x-model="stateIso" @change="onStateChange()" :disabled="states.length === 0" @if($required) required @endif
            :class="states.length === 0 ? 'cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-novix-cream/40 text-novix-ink'"
            class="w-full appearance-none rounded-xl border border-gray-200 px-4 py-3 text-sm shadow-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
            <option value="">{{ __('Select a state') }}</option>
            <template x-for="s in states" :key="s.isoCode">
                <option :value="s.isoCode" x-text="s.name" :selected="s.isoCode === stateIso"></option>
            </template>
        </select>
    </div>

    <div x-show="loaded" x-cloak>
        <label class="mb-1.5 block text-xs font-semibold text-novix-muted">City{{ $required ? ' *' : ' (optional)' }}</label>
        <select name="city" x-model="cityName" :disabled="cities.length === 0" @if($required) required @endif
            :class="cities.length === 0 ? 'cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-novix-cream/40 text-novix-ink'"
            class="w-full appearance-none rounded-xl border border-gray-200 px-4 py-3 text-sm shadow-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
            <option value="">{{ __('Select a city') }}</option>
            <template x-for="c in cities" :key="c.name">
                <option :value="c.name" x-text="c.name" :selected="c.name === cityName"></option>
            </template>
        </select>
    </div>
</div>
