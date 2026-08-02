/**
 * Custom "Install Novix" popup — Chrome suppresses its own automatic
 * install banner once beforeinstallprompt is captured, so this owns showing
 * the prompt entirely. Only fires on browsers that support the event
 * (Chrome/Edge on Android and desktop); Safari/Firefox never dispatch it,
 * so the popup simply never appears there — no broken button, no error.
 */
const DISMISS_KEY = 'novix_install_dismissed_at';
const DISMISS_DAYS = 7;

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function recentlyDismissed() {
    const at = Number(localStorage.getItem(DISMISS_KEY) || 0);
    return at && Date.now() - at < DISMISS_DAYS * 24 * 60 * 60 * 1000;
}

export default function installPrompt() {
    return {
        show: false,
        deferredEvent: null,

        init() {
            if (isStandalone() || recentlyDismissed()) return;

            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                this.deferredEvent = event;
                this.show = true;
            });

            window.addEventListener('appinstalled', () => {
                this.show = false;
                this.deferredEvent = null;
            });
        },

        async install() {
            if (!this.deferredEvent) return;

            this.deferredEvent.prompt();
            await this.deferredEvent.userChoice;

            // Whatever the user chose, the browser won't let this exact
            // event fire prompt() a second time — clear it either way.
            this.deferredEvent = null;
            this.show = false;
        },

        dismiss() {
            localStorage.setItem(DISMISS_KEY, String(Date.now()));
            this.show = false;
        },
    };
}
