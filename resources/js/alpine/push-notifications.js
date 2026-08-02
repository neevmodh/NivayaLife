/**
 * Medication/vaccination reminder push notifications. Lives on the profile
 * page as a single subscribe/unsubscribe toggle — the actual send happens
 * server-side (ProcessMedicationReminders / SendVaccinationReminders), this
 * only manages the browser's PushSubscription and tells the backend about it.
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

export default function pushNotifications({ csrfToken, subscribeUrl, unsubscribeUrl, testUrl, vapidPublicKey }) {
    return {
        supported: 'serviceWorker' in navigator && 'PushManager' in window,
        subscribed: false,
        permissionDenied: false,
        busy: false,
        testSent: false,

        async init() {
            if (!this.supported || !vapidPublicKey) return;

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            this.subscribed = !!subscription;
            this.permissionDenied = Notification.permission === 'denied';
        },

        async subscribe() {
            if (!this.supported || this.busy) return;
            this.busy = true;

            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    this.permissionDenied = permission === 'denied';
                    return;
                }

                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                });

                await fetch(subscribeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(subscription.toJSON()),
                });

                this.subscribed = true;
            } catch (e) {
                // Permission dismissed, or the browser blocked it — leave
                // subscribed false, nothing else to recover from here.
            } finally {
                this.busy = false;
            }
        },

        async unsubscribe() {
            if (!this.supported || this.busy) return;
            this.busy = true;

            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription();

                if (subscription) {
                    await fetch(unsubscribeUrl, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ endpoint: subscription.endpoint }),
                    });
                    await subscription.unsubscribe();
                }

                this.subscribed = false;
            } finally {
                this.busy = false;
            }
        },

        async sendTest() {
            if (this.busy) return;
            this.busy = true;

            try {
                const res = await fetch(testUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                });
                if (res.ok) {
                    this.testSent = true;
                    setTimeout(() => (this.testSent = false), 4000);
                }
            } finally {
                this.busy = false;
            }
        },
    };
}
