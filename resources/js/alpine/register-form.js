export default function registerForm({ csrfToken, checkEmailUrl, registerUrl }) {
    return {
        loading: false,
        shake: false,
        serverErrors: {},
        emailStatus: null, // null | 'checking' | 'available' | 'taken'
        emailCheckTimer: null,

        checkEmail(email) {
            clearTimeout(this.emailCheckTimer);

            if (!email || !email.includes('@')) {
                this.emailStatus = null;
                return;
            }

            this.emailStatus = 'checking';
            this.emailCheckTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`${checkEmailUrl}?email=${encodeURIComponent(email)}`);
                    const json = await res.json();
                    this.emailStatus = json.available === null ? null : (json.available ? 'available' : 'taken');
                } catch (e) {
                    this.emailStatus = null;
                }
            }, 500);
        },

        async submit() {
            this.serverErrors = {};
            this.loading = true;

            try {
                const body = new FormData(this.$refs.registerForm);

                const res = await fetch(registerUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body,
                });
                const json = await res.json();

                if (!res.ok || json.success === false) {
                    this.serverErrors = json.errors || (json.message ? { _general: [json.message] } : {});
                    this.triggerShake();
                    return;
                }

                this.celebrateAndRedirect(json.redirect);
            } catch (e) {
                this.triggerShake();
            } finally {
                this.loading = false;
            }
        },

        triggerShake() {
            this.shake = true;
            setTimeout(() => (this.shake = false), 500);
        },

        celebrateAndRedirect(url) {
            this.loading = true;
            document.dispatchEvent(new CustomEvent('novix:confetti'));
            setTimeout(() => window.location.assign(url), 1100);
        },

        errorFor(field) {
            return this.serverErrors?.[field]?.[0] ?? null;
        },
    };
}
