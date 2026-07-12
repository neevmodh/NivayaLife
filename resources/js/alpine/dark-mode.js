/**
 * Dark mode is applied instantly from localStorage (no flash) and mirrored
 * server-side once a user is authenticated, via users.theme_preference, so
 * the preference follows them across devices rather than living only in one
 * browser's storage.
 */
export default function darkMode({ persistUrl = null, csrfToken = null, initial = null } = {}) {
    return {
        isDark: initial ?? localStorage.getItem('novix-theme') === 'dark',

        init() {
            this.apply();
        },

        toggle() {
            this.isDark = !this.isDark;
            this.apply();
            localStorage.setItem('novix-theme', this.isDark ? 'dark' : 'light');

            if (persistUrl) {
                fetch(persistUrl, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ theme_preference: this.isDark ? 'dark' : 'light' }),
                }).catch(() => {});
            }
        },

        apply() {
            document.documentElement.classList.toggle('dark', this.isDark);
        },
    };
}
