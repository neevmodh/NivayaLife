/**
 * Generic AJAX form submit for the profile tabs — each tab saves
 * independently via fetch (real PATCH, no method-spoofing needed) and shows
 * a brief inline checkmark on success instead of a full page reload, so the
 * active tab and scroll position never get lost.
 */
export default function ajaxForm({ url, method = 'POST', csrfToken }) {
    return {
        saving: false,
        saved: false,
        errors: {},

        async submit(event) {
            this.saving = true;
            this.saved = false;
            this.errors = {};

            try {
                const body = new FormData(event.target);
                const res = await fetch(url, {
                    method,
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body,
                });
                const json = await res.json();

                if (!res.ok || json.success === false) {
                    this.errors = json.errors || {};
                    return;
                }

                this.saved = true;
                setTimeout(() => (this.saved = false), 2500);
            } catch (e) {
                this.errors = { _general: ['Something went wrong — please try again.'] };
            } finally {
                this.saving = false;
            }
        },

        errorFor(field) {
            return this.errors?.[field]?.[0] ?? null;
        },
    };
}
