const ZONES = {
    underweight: '#8FB8E0',
    normal: '#1E5A45',
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

/**
 * Owns the height/weight inputs AND the gauge visualization in one scope so
 * typing in either input re-renders the needle immediately — no cross-
 * component wiring needed. Pass editable:false for a frozen display-only
 * gauge (dashboard/profile summary).
 */
export default function bmiGauge({ heightCm = null, weightKg = null, editable = true } = {}) {
    return {
        heightCm,
        weightKg,
        editable,
        bmi: 0,
        category: '',
        angle: -90,

        init() {
            this.$watch('heightCm', () => this.recalculate());
            this.$watch('weightKg', () => this.recalculate());
            this.recalculate();
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
