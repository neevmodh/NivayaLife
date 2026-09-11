/**
 * Self-contained cascading country/state/city selector. Renders real
 * <select> elements with name attributes so a plain FormData(form) capture
 * picks up the chosen values with no cross-component wiring required.
 *
 * country-state-city bundles a full global city dataset (multiple MB) — it's
 * dynamically imported on init() so it lands in its own chunk and only loads
 * on pages that actually render this component, not on every page load.
 */
export default function locationSelect({ required = true, initialCountry = '', initialState = '', initialCity = '' } = {}) {
    return {
        loaded: false,
        loadError: false,
        countries: [],
        states: [],
        cities: [],
        countryIso: '',
        stateIso: '',
        // Optional + untouched starts blank rather than forcing a country,
        // so a server-side "was an address actually given?" check can just
        // look at whether country is filled instead of inferring it from
        // some other field. Required usage always gets an explicit prop.
        countryName: initialCountry || (required ? 'India' : ''),
        stateName: initialState,
        cityName: initialCity,
        _csc: null,

        async init() {
            try {
                const { Country, State, City } = await import('country-state-city');
                this._csc = { Country, State, City };
                this.countries = Country.getAllCountries();
            } catch (e) {
                this.loadError = true;
                return;
            }

            if (this.countryName) {
                this.applyPreset(this.countryName, this.stateName, this.cityName);
            }

            this.loaded = true;
        },

        /** Re-populate the whole selector from a (country name, state name, city name)
         * triple — used both at init and by "same as my address"-style toggles that
         * need to reset the selector after the component has already mounted. */
        applyPreset(countryName, stateName, cityName) {
            if (!this._csc) return;
            const { State, City } = this._csc;

            const country = this.countries.find((c) => c.name === countryName);

            if (!country) return;

            this.countryIso = country.isoCode;
            this.countryName = country.name;
            this.states = State.getStatesOfCountry(country.isoCode);

            const state = this.states.find((s) => s.name === stateName);
            if (state) {
                this.stateIso = state.isoCode;
                this.stateName = state.name;
                this.cities = City.getCitiesOfState(country.isoCode, state.isoCode);
                this.cityName = this.cities.find((c) => c.name === cityName)?.name ?? '';
            } else {
                this.stateIso = '';
                this.stateName = '';
                this.cities = [];
                this.cityName = '';
            }
        },

        onCountryChange() {
            const { Country, State } = this._csc;
            const country = Country.getAllCountries().find((c) => c.isoCode === this.countryIso);
            this.countryName = country ? country.name : '';
            this.states = this.countryIso ? State.getStatesOfCountry(this.countryIso) : [];
            this.stateIso = '';
            this.stateName = '';
            this.cities = [];
            this.cityName = '';
        },

        onStateChange() {
            const { City } = this._csc;
            const state = this.states.find((s) => s.isoCode === this.stateIso);
            this.stateName = state ? state.name : '';
            this.cities = (this.countryIso && this.stateIso) ? City.getCitiesOfState(this.countryIso, this.stateIso) : [];
            this.cityName = '';
        },
    };
}
