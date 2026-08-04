<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — Nivaya Life</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-novix-cream font-sans text-novix-ink antialiased dark:bg-novix-night dark:text-novix-cream">

    <header class="border-b border-novix-green/10 bg-novix-cream/90 backdrop-blur dark:bg-novix-night/90">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
            <a href="/" class="flex items-center gap-2" aria-label="Nivaya Life home">
                <x-novix-logo size="sm" />
            </a>
            <a href="{{ url('/terms') }}" class="text-sm font-medium text-novix-green hover:underline">Terms of Service</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-12">
        <h1 class="text-3xl font-extrabold text-novix-ink dark:text-white">Privacy Policy</h1>
        <p class="mt-2 text-sm text-novix-muted">Last updated {{ now()->format('F j, Y') }}</p>

        <div class="mt-10 space-y-10 text-sm leading-relaxed text-novix-ink/90 dark:text-white/80">

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">1. What this covers</h2>
                <p class="mt-2">This policy explains what information Nivaya Life collects, how it's used, who it's shared with, and the choices you have — including around your health data, which we treat with particular care.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">2. Information we collect</h2>
                <ul class="mt-2 list-inside list-disc space-y-1.5">
                    <li><strong>Account details</strong> — name, email address, phone number, gender, and password (or Google account identifier, if you sign in with Google).</li>
                    <li><strong>Health information</strong> — height, weight, blood group, date of birth, allergies, chronic conditions, and any medical documents you upload (prescriptions, lab reports, imaging, discharge summaries, and similar), along with text and data extracted from them.</li>
                    <li><strong>Family member information</strong> — details for any dependents you add to your account.</li>
                    <li><strong>Usage information</strong> — sign-in history, and basic analytics such as page views.</li>
                    <li><strong>Device information</strong> — if you enable push notifications, a browser-issued subscription identifier used to deliver them.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">3. How we use it</h2>
                <p class="mt-2">We use your information to: provide the core service (storing and organizing your records); extract and summarize document content via OCR and AI; send medication and vaccination reminders you've enabled; let you share records with people you choose; secure your account and investigate abuse; and understand overall usage of the app so we can improve it.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">4. AI and third-party processing of your documents</h2>
                <p class="mt-2">To generate summaries, translations, and extracted lab values, the text or images of documents you upload are sent to the following third-party services as needed:</p>
                <ul class="mt-2 list-inside list-disc space-y-1.5">
                    <li><strong>Google Gemini</strong> and <strong>Groq</strong> — AI models used to generate plain-language summaries, explanations, and translations.</li>
                    <li><strong>Google OAuth</strong> — if you choose to sign in with Google, used only to verify your identity.</li>
                </ul>
                <p class="mt-2">OCR and biomedical entity extraction otherwise run on infrastructure we operate directly. These providers process data on our behalf to deliver the feature you've requested — they do not have independent rights to use your data for their own purposes beyond providing that processing.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">5. Other service providers</h2>
                <p class="mt-2">We use <strong>Resend</strong> to deliver transactional emails (reminders, notifications) and <strong>Railway</strong> to host the application and database. These providers can access data only as needed to provide their infrastructure service to us.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">6. What we don't do</h2>
                <p class="mt-2">We do not sell your personal or health information. We do not use your health data for advertising or share it with advertisers.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">7. Security</h2>
                <p class="mt-2">Data is transmitted over encrypted connections (HTTPS). Access to your account requires your password (or Google sign-in) and, if you choose to enable it, two-factor authentication. Communication between our internal services is authenticated and restricted to our private network.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">8. Data retention and account deletion</h2>
                <p class="mt-2">While your account is active, your data is retained for as long as you keep your account. If you delete your account:</p>
                <ul class="mt-2 list-inside list-disc space-y-1.5">
                    <li>Your profile, records, and files are immediately removed from active use, and you are signed out everywhere.</li>
                    <li>A full internal record of the deleted data is retained for account recovery and audit purposes rather than being immediately erased.</li>
                    <li>If you register a new account later using the same email address, it is a completely new, independent account — none of the previously deleted data is automatically restored to it.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">9. Your choices and rights</h2>
                <p class="mt-2">You can view, edit, or delete most of your information directly from your profile at any time. You can revoke a record share, cancel a pending invitation, disable push notifications, or delete your account entirely whenever you choose. To request anything not available directly in the app (such as a copy of your data), contact us using the details below.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">10. Cookies and sessions</h2>
                <p class="mt-2">We use a session cookie to keep you signed in and a CSRF token to protect form submissions. We do not use third-party advertising or tracking cookies.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">11. Children's information</h2>
                <p class="mt-2">Nivaya Life accounts are intended for adults. A parent or guardian may add a child as a family member/dependent under their own account; children do not register their own accounts.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">12. Changes to this policy</h2>
                <p class="mt-2">We may update this policy from time to time. Material changes will be reflected by updating the date at the top of this page.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-novix-ink dark:text-white">13. Contact</h2>
                <p class="mt-2">Questions about this policy, or requests regarding your data, can be sent to <a href="mailto:support@novix.app" class="text-novix-green underline">support@novix.app</a>.</p>
            </section>

        </div>

        <a href="/" class="mt-12 inline-block text-sm font-semibold text-novix-green hover:underline">&larr; Back to Nivaya Life</a>
    </main>

</body>
</html>
