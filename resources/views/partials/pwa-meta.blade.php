@if(config('services.vapid.public_key'))
    <meta name="vapid-public-key" content="{{ config('services.vapid.public_key') }}">
@endif
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1E5A45">
<link rel="icon" href="/icons/icon-192.png">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Novix">

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
