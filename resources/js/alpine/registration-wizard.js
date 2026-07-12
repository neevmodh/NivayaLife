export default function registrationWizard({ resumeStep = 1, csrfToken, checkEmailUrl, summaryUrl, stepUrls, totalSteps = 5 }) {
    return {
        currentStep: resumeStep,
        furthestStep: resumeStep,
        totalSteps,
        loading: false,
        shake: false,
        serverErrors: {},
        emailStatus: null, // null | 'checking' | 'available' | 'taken'
        emailCheckTimer: null,
        summary: { step1: null, step3: null, step4: null },

        init() {
            this.focusFirstField();
            this.$watch('currentStep', (val) => {
                this.$nextTick(() => this.focusFirstField());
                if (val === 5) this.loadSummary();
            });
            if (this.currentStep === 5) this.loadSummary();
        },

        /**
         * The wizard never reloads the page between steps, so step 5's
         * review cards can't rely on the PHP variables rendered at initial
         * page load (stale — reflects the session before anything was
         * typed). Fetch the session's current values live instead.
         */
        async loadSummary() {
            try {
                const res = await fetch(summaryUrl, { headers: { Accept: 'application/json' } });
                this.summary = await res.json();
            } catch (e) {
                // leave previous summary state in place
            }
        },

        focusFirstField() {
            const stepEl = this.$refs['step' + this.currentStep];
            const field = stepEl?.querySelector('input:not([type=hidden]):not([type=file]):not([disabled]), select:not([disabled]), textarea');
            field?.focus();
        },

        goToStep(n) {
            if (n <= this.furthestStep) {
                this.serverErrors = {};
                this.currentStep = n;
            }
        },

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

        async submitStep(n) {
            this.serverErrors = {};
            this.loading = true;

            try {
                let ok, nextStep, redirect;

                if (n === 2) {
                    const cameraEl = document.getElementById('novix-camera-capture');
                    const camera = window.Alpine.$data(cameraEl);
                    ok = await camera.novixUpload();
                    nextStep = 3;
                    if (!ok && !camera.uploadError && !camera.hasCaptured) {
                        this.serverErrors = { photo: ['Please capture or upload a photo before continuing.'] };
                    }
                } else {
                    const formEl = this.$refs['step' + n + 'Form'];
                    const body = new FormData(formEl);

                    const res = await fetch(stepUrls[n], {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        body,
                    });
                    const json = await res.json();

                    ok = res.ok && json.success !== false;
                    if (!ok) {
                        this.serverErrors = json.errors || (json.message ? { _general: [json.message] } : {});
                    } else {
                        nextStep = json.next_step;
                        redirect = json.redirect;
                    }
                }

                if (!ok) {
                    this.triggerShake();
                    return;
                }

                if (redirect) {
                    this.celebrateAndRedirect(redirect);
                    return;
                }

                this.furthestStep = Math.max(this.furthestStep, nextStep);
                this.currentStep = nextStep;
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

        get progressPercent() {
            return Math.round((this.currentStep / this.totalSteps) * 100);
        },

        errorFor(field) {
            return this.serverErrors?.[field]?.[0] ?? null;
        },
    };
}
