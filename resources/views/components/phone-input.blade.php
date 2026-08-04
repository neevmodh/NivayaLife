@props(['name', 'label', 'value' => '', 'dynamicErrors' => false, 'withCountryCode' => false, 'countryCodeValue' => '+91'])

<div
    x-data="{
        val: @js((string) $value),
        touched: false,
        @if($withCountryCode)
        countryCode: @js($countryCodeValue),
        countries: [],
        async init() {
            const { Country } = await import('country-state-city');
            this.countries = Country.getAllCountries()
                .filter((c) => c.phonecode)
                .map((c) => ({ name: c.name, dial: '+' + c.phonecode.replace('+', ''), flag: c.flag }))
                .sort((a, b) => a.name.localeCompare(b.name));
        },
        @endif
    }"
>
    <div class="flex gap-2">
        @if($withCountryCode)
            <select x-model="countryCode" name="country_code" required
                class="w-[6.5rem] shrink-0 appearance-none rounded-xl border border-gray-200 bg-novix-cream/40 px-2 text-sm text-novix-ink shadow-sm transition focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                <option value="+91">🇮🇳 +91</option>
                <template x-for="c in countries" :key="c.dial + c.name">
                    <option :value="c.dial" x-text="c.flag + ' ' + c.dial" :selected="c.dial === countryCode"></option>
                </template>
            </select>
        @endif

        <div class="relative flex-1">
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
        </div>
    </div>

    <p x-cloak x-show="touched && val.length > 0 && val.length !== 10" class="mt-1 text-xs text-novix-pink-dark">Enter exactly 10 digits.</p>
    @if($dynamicErrors)
        <p x-cloak x-show="!(touched && val.length !== 10) && errorFor('{{ $name }}')" x-text="errorFor('{{ $name }}')" class="mt-1 text-xs text-novix-pink-dark"></p>
    @endif
</div>
