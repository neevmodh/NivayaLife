<div x-data="pushNotifications({
        csrfToken: document.querySelector('meta[name=csrf-token]').content,
        subscribeUrl: @js(route('push-subscriptions.store')),
        unsubscribeUrl: @js(route('push-subscriptions.destroy')),
        testUrl: @js(route('push-subscriptions.test')),
        vapidPublicKey: document.querySelector('meta[name=vapid-public-key]')?.content,
    })" x-init="init()">
    <h3 class="text-lg font-bold text-nivayalife-ink dark:text-white">Push notifications</h3>
    <p class="mt-1 text-sm text-nivayalife-muted">
        Get a notification on this device when a medication dose is due, or a vaccination is coming up — on top of the email reminders you already get.
    </p>

    <template x-if="!supported">
        <p class="mt-4 rounded-xl bg-gray-50 px-4 py-3 text-sm text-nivayalife-muted dark:bg-white/5">
            Your browser doesn't support push notifications. Try Chrome, Edge, or the installed app on Android.
        </p>
    </template>

    <template x-if="supported && !@js((bool) config('services.vapid.public_key'))">
        <p class="mt-4 rounded-xl bg-gray-50 px-4 py-3 text-sm text-nivayalife-muted dark:bg-white/5">
            Push notifications aren't set up on this server yet.
        </p>
    </template>

    <template x-if="supported && permissionDenied">
        <p class="mt-4 rounded-xl bg-nivayalife-pink/10 px-4 py-3 text-sm text-nivayalife-pink-dark">
            Notifications are blocked for this site in your browser settings — enable them there, then reload this page.
        </p>
    </template>

    <template x-if="supported">
        <div class="mt-4 flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 dark:border-white/10">
            <div>
                <p class="text-sm font-medium text-nivayalife-ink dark:text-white">Reminders on this device</p>
                <p class="text-xs text-nivayalife-muted" x-text="subscribed ? 'Enabled' : 'Off'"></p>
            </div>
            <button type="button" :disabled="busy"
                @click="subscribed ? unsubscribe() : subscribe()"
                :class="subscribed ? 'border border-nivayalife-pink-dark text-nivayalife-pink-dark hover:bg-nivayalife-pink-dark hover:text-white' : 'bg-nivayalife-green text-white hover:bg-nivayalife-green-dark'"
                class="rounded-xl px-5 py-2 text-sm font-semibold shadow-nivayalife-sm disabled:cursor-not-allowed disabled:opacity-50">
                <span x-show="!subscribed">Enable</span>
                <span x-show="subscribed">Disable</span>
            </button>
        </div>
    </template>

    <template x-if="supported && subscribed">
        <div class="mt-3 flex items-center gap-3">
            <button type="button" :disabled="busy" @click="sendTest()"
                class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-nivayalife-ink hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Send test notification
            </button>
            <span x-show="testSent" class="flex items-center gap-1.5 text-xs font-semibold text-nivayalife-green">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Sent — check this device
            </span>
        </div>
    </template>

    <p class="mt-4 text-xs text-nivayalife-muted">
        Medication reminders arrive as an alarm-style notification with "Mark as taken" and "Snooze 10 min" buttons, and a follow-up nudge if a dose is still pending after 30 minutes.
    </p>
</div>
