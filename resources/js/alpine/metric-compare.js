/**
 * "Compare over time" for the health timeline — entirely client-side since
 * the values are already rendered on the page; a server round-trip would
 * just re-fetch the same numbers. Only entries of the same metric_type can
 * be compared against each other.
 */
export default function metricCompare() {
    return {
        selected: [],

        toggle(entry) {
            const idx = this.selected.findIndex((s) => s.id === entry.id);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            } else {
                this.selected.push(entry);
            }
        },

        isSelected(id) {
            return this.selected.some((s) => s.id === id);
        },

        clear() {
            this.selected = [];
        },

        get comparableGroups() {
            const groups = {};
            for (const entry of this.selected) {
                (groups[entry.metricType] ??= []).push(entry);
            }

            return Object.entries(groups)
                .filter(([, items]) => items.length >= 2)
                .map(([metricType, items]) => {
                    const sorted = [...items].sort((a, b) => new Date(a.date) - new Date(b.date));
                    const oldest = sorted[0];
                    const newest = sorted[sorted.length - 1];
                    const pct = oldest.value !== 0 ? ((newest.value - oldest.value) / Math.abs(oldest.value)) * 100 : 0;

                    return {
                        metricType,
                        label: metricType.replace(/_/g, ' '),
                        unit: newest.unit,
                        items: sorted,
                        oldest,
                        newest,
                        pctChange: Math.round(pct * 10) / 10,
                        direction: pct > 0.05 ? 'up' : pct < -0.05 ? 'down' : 'unchanged',
                    };
                });
        },
    };
}
