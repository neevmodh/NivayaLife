/**
 * Dashboard "today's medications" pills. Status is tracked client-side
 * (keyed by "medicationId:time") and updated optimistically on click so the
 * color flips instantly, then reconciled with whatever the server actually
 * persisted — reverted on failure rather than left in a state that doesn't
 * match the database.
 */
export default function doseTracker({ csrfToken, initialStatuses }) {
    return {
        statuses: { ...initialStatuses },
        pending: {},

        statusFor(medicationId, time) {
            return this.statuses[`${medicationId}:${time}`] ?? 'pending';
        },

        async toggle(medicationId, time) {
            const key = `${medicationId}:${time}`;
            if (this.pending[key]) return;

            this.pending[key] = true;
            const previous = this.statuses[key] ?? 'pending';
            this.statuses[key] = previous === 'taken' ? 'pending' : 'taken';

            try {
                const response = await fetch(`/medications/${medicationId}/toggle-dose`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ time }),
                });
                const json = await response.json();
                this.statuses[key] = json.success ? json.status : previous;
            } catch (e) {
                this.statuses[key] = previous;
            } finally {
                this.pending[key] = false;
            }
        },
    };
}
