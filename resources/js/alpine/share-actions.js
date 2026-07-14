/**
 * Wraps the Web Share API for the share-success page. On phones this opens
 * the native share sheet (WhatsApp, Email, Messages, etc. — whatever's
 * installed); on browsers without support the button just doesn't render
 * (checked via supportsNativeShare), leaving "Copy link" as the fallback.
 */
export default function shareActions({ url, title = 'Health report shared via Novix' } = {}) {
    return {
        supportsNativeShare: typeof navigator !== 'undefined' && !!navigator.share,

        async nativeShare() {
            if (!navigator.share) return;
            try {
                await navigator.share({ title, url });
            } catch (e) {
                // User cancelled the share sheet — nothing to do.
            }
        },
    };
}
