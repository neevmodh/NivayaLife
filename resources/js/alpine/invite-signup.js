/**
 * A condensed one-page signup for someone accepting a family invite — reuses
 * the same camera-capture component as full registration, but since this is
 * a single form (not a multi-step wizard) the captured photo is bundled
 * directly into the one submission rather than pre-uploaded to a tmp path.
 */
export default function inviteSignupForm({ registerUrl, csrfToken }) {
    return {
        loading: false,
        shake: false,
        errors: {},

        async submit(event) {
            this.loading = true;
            this.errors = {};

            try {
                const cameraEl = document.getElementById('nivayalife-invite-camera');
                const camera = window.Alpine.$data(cameraEl);

                if (!camera.capturedDataUrl) {
                    this.errors = { photo: ['Please capture or upload a photo before continuing.'] };
                    this.triggerShake();
                    return;
                }

                const body = new FormData(event.target);
                body.append('photo', camera.dataUrlToBlob(camera.capturedDataUrl), 'photo.jpg');

                const res = await fetch(registerUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body,
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    this.errors = json.errors || { _general: [json.message || 'Something went wrong.'] };
                    this.triggerShake();
                    return;
                }

                window.location.assign(json.redirect);
            } catch (e) {
                this.errors = { _general: ['Something went wrong — please try again.'] };
                this.triggerShake();
            } finally {
                this.loading = false;
            }
        },

        triggerShake() {
            this.shake = true;
            setTimeout(() => (this.shake = false), 500);
        },

        errorFor(field) {
            return this.errors?.[field]?.[0] ?? null;
        },
    };
}
