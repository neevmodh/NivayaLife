/**
 * Self-contained cascading country/state/city selector. Renders real
 * <select> elements with name attributes so a plain FormData(form) capture
 * picks up the chosen values with no cross-component wiring required.
 *
 * country-state-city bundles a full global city dataset (multiple MB) — it's
 * dynamically imported on init() so it lands in its own chunk and only loads
 * on pages that actually render this component, not on every page load.
 */
export default function locationSelect({ initialCountry = 'India', initialState = '', initialCity = '' } = {}) {
    return {
        loaded: false,
        countries: [],
        states: [],
        cities: [],
        countryIso: '',
        stateIso: '',
        countryName: initialCountry,
        stateName: initialState,
        cityName: initialCity,
        _csc: null,

        async init() {
            const { Country, State, City } = await import('country-state-city');
            this._csc = { Country, State, City };
            this.countries = Country.getAllCountries();

            const country = this.countries.find((c) => c.name === this.countryName)
                ?? this.countries.find((c) => c.isoCode === 'IN');

            if (country) {
                this.countryIso = country.isoCode;
                this.countryName = country.name;
                this.states = State.getStatesOfCountry(country.isoCode);

                const state = this.states.find((s) => s.name === this.stateName);
                if (state) {
                    this.stateIso = state.isoCode;
                    this.cities = City.getCitiesOfState(country.isoCode, state.isoCode);
                }
            }

            this.loaded = true;
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
