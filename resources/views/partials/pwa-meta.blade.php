@if(config('services.vapid.public_key'))
    <meta name="vapid-public-key" content="{{ config('services.vapid.public_key') }}">
@endif
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#14503F">
{{-- ?v=2 busts the cached pre-rebrand icons browsers hold onto. --}}
<link rel="icon" href="/favicon.ico?v=2" sizes="48x48">
<link rel="icon" type="image/png" href="/icons/icon-192.png?v=2" sizes="192x192">
<link rel="apple-touch-icon" href="/icons/icon-192.png?v=2">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Nivaya Life">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {
                // Installability is a nice-to-have — a failed registration
                // (e.g. an unsupported browser) shouldn't block the app.
            });
        });
    }
</script>
