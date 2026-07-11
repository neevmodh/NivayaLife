# Novix

Novix is a secure, family-oriented health record manager. It lets a household keep every family member's medical reports, prescriptions, and medications in one place, with AI-assisted explanations and quick access in emergencies.

This repository covers the **Phase 1 free-tier MVP**: a Laravel/PHP app buildable entirely on free tools and services.

## Features (Phase 1 MVP)

- **Family Health Account** — add/edit family member profiles (parents, spouse, children, grandparents) and switch between them app-wide.
- **Secure Health Locker** — upload reports (blood tests, prescriptions, X-rays, insurance, bills, ECGs) with automatic categorization and image compression.
- **Personal Health Timeline** — chronological view of reports and medications per family member.
- **OCR extraction** — Tesseract OCR pulls text out of uploaded reports for review and correction.
- **AI report explanations** — Google Gemini turns OCR'd report text into a plain-language summary, always with a "not medical advice" disclaimer.
- **Secure report sharing** — signed, time-limited links with QR codes for sharing a single report without requiring login.
- **Emergency Medical Card** — a public, no-login page per family member with blood group, allergies, medicines, and emergency contact.
- **Medicine reminders** — scheduled email reminders for due medications.
- **Multilingual support** — Hindi and Gujarati translations of AI report summaries, cached to avoid re-translating.
- **Health Trends** — numeric lab values (BP, blood sugar, weight, HbA1c, cholesterol) extracted and charted over time.
- **Security & privacy** — per-user record ownership checks, validated uploads, non-browsable storage, audit logging, and full account deletion.

## Tech stack

- **Backend:** Laravel 11 (PHP), MySQL
- **Frontend:** Blade + Tailwind CSS
- **Auth:** Laravel Breeze (email/password + Google OAuth via Socialite)
- **OCR:** Tesseract (via Homebrew)
- **AI:** Google Gemini API (`gemini-1.5-flash`)
- **Local dev:** Laravel Herd, DBngin (MySQL 8)

## Local setup

1. Install [Laravel Herd](https://herd.laravel.com), [DBngin](https://dbngin.com), Xcode Command Line Tools, and [Homebrew](https://brew.sh) (`brew install tesseract`).
2. Clone this repo into `~/Sites` so Herd serves it automatically:
   ```bash
   git clone https://github.com/neevmodh/Novix.git ~/Sites/novix
   ```
3. Install dependencies and set up the environment:
   ```bash
   cd ~/Sites/novix
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   ```
4. Add your `GEMINI_API_KEY` and SMTP credentials to `.env`.
5. Visit `http://novix.test` in your browser.

## Status

Phase 1 MVP under active development, built prompt-by-prompt with an AI coding assistant. See project docs for the full build sequence.

## License

Apache License 2.0 — see [LICENSE](LICENSE).
