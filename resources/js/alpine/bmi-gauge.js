const ZONES = {
    underweight: '#8FB8E0',
    normal: '#14503F',
    overweight: '#F5C879',
    obese: '#E8615A',
};

export function categoryFor(bmi) {
    if (bmi < 18.5) return 'underweight';
    if (bmi < 25) return 'normal';
    if (bmi < 30) return 'overweight';
    return 'obese';
}

function angleFor(bmi) {
    const min = 10;
    const max = 45;
    const clamped = Math.min(Math.max(bmi, min), max);
    return -90 + ((clamped - min) / (max - min)) * 180;
}

function round(value, decimals) {
    const factor = 10 ** decimals;
    return Math.round(value * factor) / factor;
}

/**
 * Owns the height/weight inputs AND the gauge visualization in one scope so
 * typing in either input re-renders the needle immediately — no cross-
 * component wiring needed. Pass editable:false for a frozen display-only
 * gauge (dashboard/profile summary).
 *
 * heightCm is always the value actually submitted with the form (via a
 * hidden input) and the only one BMI is computed from — heightUnit and the
 * per-unit display fields (heightInput for cm/m/in, feet+inches for ft) are
 * purely a UI convenience layered on top, converted back to cm on every
 * keystroke.
 */
export default function bmiGauge({ heightCm = null, weightKg = null, editable = true } = {}) {
    return {
        heightCm,
        weightKg,
        editable,
        heightUnit: 'cm', // 'cm' | 'm' | 'in' | 'ft'
        heightInput: heightCm,
        feet: null,
        inches: null,
        bmi: 0,
        category: '',
        angle: -90,

        init() {
            this.syncDisplayFromCm();
            this.$watch('heightCm', () => this.recalculate());
            this.$watch('weightKg', () => this.recalculate());
            this.recalculate();
        },

        syncDisplayFromCm() {
            const cm = parseFloat(this.heightCm) || 0;

            if (!cm) {
                this.heightInput = null;
                this.feet = null;
                this.inches = null;
                return;
            }

            if (this.heightUnit === 'cm') this.heightInput = round(cm, 1);
            else if (this.heightUnit === 'm') this.heightInput = round(cm / 100, 2);
            else if (this.heightUnit === 'in') this.heightInput = round(cm / 2.54, 1);
            else if (this.heightUnit === 'ft') {
                const totalInches = cm / 2.54;
                this.feet = Math.floor(totalInches / 12);
                this.inches = round(totalInches % 12, 1);
            }
        },

        onUnitChange() {
            this.syncDisplayFromCm();
        },

        updateHeightFromInput() {
            const v = parseFloat(this.heightInput);
            if (!v || v <= 0) {
                this.heightCm = null;
                return;
            }

            if (this.heightUnit === 'cm') this.heightCm = round(v, 1);
            else if (this.heightUnit === 'm') this.heightCm = round(v * 100, 1);
            else if (this.heightUnit === 'in') this.heightCm = round(v * 2.54, 1);
        },

        updateHeightFromFeetInches() {
            const ft = parseFloat(this.feet) || 0;
            const inch = parseFloat(this.inches) || 0;

            if (!ft && !inch) {
                this.heightCm = null;
                return;
            }

            this.heightCm = round((ft * 12 + inch) * 2.54, 1);
        },

        recalculate() {
            const h = parseFloat(this.heightCm);
            const w = parseFloat(this.weightKg);

            if (!h || !w || h <= 0) {
                this.bmi = 0;
                this.category = '';
                this.angle = -90;
                return;
            }

            const hm = h / 100;
            const bmi = w / (hm * hm);
            this.bmi = Math.round(bmi * 10) / 10;
            this.category = categoryFor(bmi);
            this.angle = angleFor(bmi);
        },

        get categoryColor() {
            return ZONES[this.category] || '#9AA79F';
        },

        get categoryLabel() {
            return {
                underweight: 'Underweight',
                normal: 'Normal',
                overweight: 'Overweight',
                obese: 'Obese',
            }[this.category] || '—';
        },
    };
}
