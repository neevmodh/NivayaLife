/**
 * The URL is read at call-time (an argument to copy()), not baked into the
 * factory at mount-time — needed anywhere the link becomes known only after
 * an async response, since Alpine initializes x-data before that value
 * exists and a captured closure value would never update.
 */
export default function copyLink({ url = null } = {}) {
    return {
        copied: false,

        async copy(explicitUrl = null) {
            const target = explicitUrl ?? url;
            if (!target) return;

            try {
                await navigator.clipboard.writeText(target);
            } catch (e) {
                const el = document.createElement('textarea');
                el.value = target;
                el.style.position = 'fixed';
                el.style.opacity = '0';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    };
}
