import ApexCharts from 'apexcharts';

/**
 * Thin Alpine wrapper around ApexCharts — one component instance per chart
 * element. Options/series are passed in as plain data (already computed
 * server-side), so this file only owns the render lifecycle and dark-mode
 * re-theming, not any data shaping.
 */
export default function adminChart({ type, series, options = {} }) {
    return {
        chart: null,

        init() {
            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new ApexCharts(this.$el, {
                chart: {
                    type,
                    height: options.height || 260,
                    toolbar: { show: false },
                    fontFamily: 'Figtree, sans-serif',
                    foreColor: isDark ? '#FBF6EA' : '#1F2A24',
                },
                theme: { mode: isDark ? 'dark' : 'light' },
                series,
                ...options,
            });

            this.chart.render();

            // The rest of the app already toggles a `dark` class on <html>
            // for its own dark-mode support — mirror that here since
            // ApexCharts needs an explicit re-render to re-theme, it doesn't
            // follow CSS custom properties like the rest of the UI does.
            this._observer = new MutationObserver(() => {
                const nowDark = document.documentElement.classList.contains('dark');
                this.chart.updateOptions({
                    theme: { mode: nowDark ? 'dark' : 'light' },
                    chart: { foreColor: nowDark ? '#FBF6EA' : '#1F2A24' },
                });
            });
            this._observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        destroy() {
            this._observer?.disconnect();
            this.chart?.destroy();
        },
    };
}
