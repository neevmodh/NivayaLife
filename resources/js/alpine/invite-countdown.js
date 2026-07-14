/**
 * Drives both the "expires in..." countdown and the 2-minute resend cooldown
 * on a pending invitation card. Ticks client-side so the /family page never
 * needs to poll the server just to keep a countdown accurate.
 */
export default function inviteCountdown({ expiresAt, lastSentAt, cooldownSeconds = 120 }) {
    return {
        expiresAtMs: new Date(expiresAt).getTime(),
        lastSentAtMs: new Date(lastSentAt).getTime(),
        cooldownSeconds,
        now: Date.now(),
        timer: null,

        init() {
            this.timer = setInterval(() => (this.now = Date.now()), 1000);
        },

        destroy() {
            clearInterval(this.timer);
        },

        get isExpired() {
            return this.now >= this.expiresAtMs;
        },

        get expiresInLabel() {
            const diff = this.expiresAtMs - this.now;
            if (diff <= 0) return 'Expired';

            const days = Math.floor(diff / 86400000);
            const hours = Math.floor((diff % 86400000) / 3600000);

            if (days > 0) return `Expires in ${days}d ${hours}h`;

            const minutes = Math.floor((diff % 3600000) / 60000);
            if (hours > 0) return `Expires in ${hours}h ${minutes}m`;

            return `Expires in ${Math.max(minutes, 1)}m`;
        },

        get resendCooldownRemaining() {
            const elapsed = (this.now - this.lastSentAtMs) / 1000;
            return Math.max(0, Math.ceil(this.cooldownSeconds - elapsed));
        },

        get canResend() {
            return this.resendCooldownRemaining <= 0;
        },
    };
}
