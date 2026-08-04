/**
 * Drives the whole /family/add page: the step-1 connection-type chooser, and
 * both paths' single-page forms. Neither path is a multi-step wizard, so
 * captured photos are bundled directly into one combined submission rather
 * than pre-uploaded — see invite-signup.js for the same pattern.
 */
export default function familyAddPage({
    inviteUrl,
    dependentUrl,
    csrfToken,
    primaryCountry,
    primaryState,
    primaryCity,
    primaryAddressLine1,
    primaryAddressLine2,
    primaryPincode,
    primaryPhone,
}) {
    return {
        mode: null, // null | 'linked' | 'dependent'
        loading: false,
        shake: false,
        errors: {},
        inviteSuccess: null, // { name, inviteUrl } once an invite is sent

        // Fields the "same as my address" / "use my phone" toggles need to
        // reach directly (kept on this shared scope rather than inside the
        // reusable floating-input component's own isolated local state).
        form_address_line1: '',
        form_address_line2: '',
        form_pincode: '',
        form_emergency_phone: '',

        chooseMode(mode) {
            this.mode = mode;
            this.errors = {};
        },

        async submitInvite(event) {
            this.loading = true;
            this.errors = {};

            try {
                const body = new FormData(event.target);
                const res = await fetch(inviteUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body,
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    this.errors = json.errors || {};
                    this.triggerShake();
                    return;
                }

                this.inviteSuccess = { name: json.name, inviteUrl: json.invite_url, qrDataUri: json.qr_data_uri };
            } catch (e) {
                this.triggerShake();
            } finally {
                this.loading = false;
            }
        },

        async submitDependent(event) {
            this.loading = true;
            this.errors = {};

            try {
                const cameraEl = document.getElementById('novix-dependent-camera');
                const camera = window.Alpine.$data(cameraEl);

                const body = new FormData(event.target);
                if (camera.capturedDataUrl) {
                    body.append('photo', camera.dataUrlToBlob(camera.capturedDataUrl), 'photo.jpg');
                }

                const res = await fetch(dependentUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body,
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    this.errors = json.errors || {};
                    this.triggerShake();
                    return;
                }

                window.dispatchEvent(new CustomEvent('novix:confetti'));
                setTimeout(() => window.location.assign(json.redirect), 1100);
            } catch (e) {
                this.triggerShake();
            } finally {
                this.loading = false;
            }
        },

        applySameAddress(checked) {
            if (!checked) return;

            const locEl = document.getElementById('novix-dependent-location');
            window.Alpine.$data(locEl).applyPreset(primaryCountry, primaryState, primaryCity);

            this.form_address_line1 = primaryAddressLine1 || '';
            this.form_address_line2 = primaryAddressLine2 || '';
            this.form_pincode = primaryPincode || '';
        },

        applyMyPhone(checked) {
            this.form_emergency_phone = checked ? (primaryPhone || '') : '';
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
