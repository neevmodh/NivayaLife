<div align="center">

# 🩺 Novix

### Your family's health records — organized, explained, and reachable in an emergency.

[![Laravel](https://img.shields.io/badge/Laravel-11.54-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8%2F9-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![Vite](https://img.shields.io/badge/Vite-6-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg?style=for-the-badge)](LICENSE)

[![Gemini](https://img.shields.io/badge/Gemini_%2B_Groq-AI_fallback_chain-8E75B2?style=flat-square&logo=googlegemini&logoColor=white)](#ai-provider-fallback-chain)
[![Tesseract](https://img.shields.io/badge/Tesseract_OCR-parallel_pages-43853D?style=flat-square)](#reports-ocr--ai-pipeline)
[![Resend](https://img.shields.io/badge/Resend-real_email-000000?style=flat-square)](#-getting-started)
[![dompdf](https://img.shields.io/badge/dompdf-real_PDFs-D32F2F?style=flat-square)](#emergency-card--report-sharing-pipeline)
[![Tables](https://img.shields.io/badge/database%20tables-29-1E5A45?style=flat-square)](#-database-schema)
[![Routes](https://img.shields.io/badge/routes-100%2B-2E7A5D?style=flat-square)](#-application-routes)
[![Status](https://img.shields.io/badge/status-active%20development-yellow?style=flat-square)](#-roadmap)

</div>

---

## 📑 Table of Contents

- [About](#-about)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture & Pipelines](#-architecture--pipelines)
- [Database Schema](#-database-schema)
- [Project Structure](#-project-structure)
- [Application Routes](#-application-routes)
- [Getting Started](#-getting-started)
- [Security & Privacy](#-security--privacy)
- [Roadmap](#-roadmap)
- [Contributing](#-contributing)
- [License](#-license)

---

## 📖 About

**Novix** is a secure, family-oriented health record manager. It gives a household a single place to store, understand, and act on every family member's medical history — lab reports, prescriptions, medications, vitals, allergies, and emergency information — instead of scattered paper files and phone photos.

Every family member is represented as their own record with two possible modes: a **linked** member who has their own login (the account owner, or anyone they've invited who accepted), or a **dependent** member with no login of their own (a child, an elderly parent) whose records the primary account manages directly. Two independent adult accounts can also link to each other and share access on their own terms — never a one-way street.

> ⚠️ **Not a medical device.** Novix organizes, extracts, and explains records for convenience. Every AI-generated summary carries a disclaimer and is never presented as a diagnosis. It does not replace a qualified doctor.

---

## ✨ Features

### Onboarding & Identity

| | Feature | Description |
|---|---|---|
| 🧙 | **5-Step Animated Registration Wizard** | Session-backed multi-step signup (survives a page refresh) — account basics with live email availability + password strength, live selfie capture via the browser camera, cascading country/state/city address, an animated semi-circular BMI gauge with allergy/medicine tags, and a final review-and-confirm step with required consent capture. Nothing touches the database until the final step. |
| 🔐 | **Google OAuth + Password Auth** | Sign in with Google or email/password. A brand-new Google identity is never turned into a database row until the wizard's final step completes — an abandoned signup leaves no account behind either way. |
| 📸 | **Live Camera Capture** | `getUserMedia`-based capture with a face-guide overlay, square crop, client-side JPEG compression, and a file-upload fallback for devices without a camera. |
| 🌍 | **Cascading Location Picker** | Real country → state → city data (India defaulted, full global dataset), lazy-loaded on demand so it never bloats other pages. |

### Family & Dashboard

| | Feature | Description |
|---|---|---|
| 🏠 | **Family Dashboard** | Hero card with photo/blood group/health ID, live BMI trend, recent reports with AI summaries, today's medications, quick-action tiles, and a family-member switcher. |
| 👨‍👩‍👧 | **Family Management** | Invite an independent adult by email (they keep their own login and choose exactly what to share back — full, reports-only, or summary-only) or add a dependent with no login of their own. Archive/restore respects every `RESTRICT`-guarded medical table. An in-app popup surfaces pending invitations the moment you log in. |
| 🔁 | **Reciprocal Sharing** | If someone you invite already has their own Novix account, inviting them creates a two-way grant instead of colliding with their existing profile — both sides see the connection and can revoke it independently from their own "Shared With" list. |
| 👤 | **Tabbed Profile Editor** | Independently-saving tabs — Basic Info, Photo, Address, Health, Emergency Contact, Account Security — each with inline AJAX feedback, no full-page reloads. Reused for editing dependents and for full-access linked members. |
| 📈 | **BMI History, Not Overwrites** | Editing height/weight always inserts a new `bmi_logs` row, preserving trend history instead of destroying it. |
| 🌗 | **Dark Mode** | Toggle persisted server-side per user, not just in browser storage — follows you across devices. |
| ✅ | **First-Run Checklist** | A dismissible dashboard card guiding a brand-new account through its first three real actions — add a family member, upload a report, try the assistant — computed from actual data, not a hardcoded flag. |

### Medications & Vaccinations

| | Feature | Description |
|---|---|---|
| 💊 | **Medication Management** | Full CRUD for dosage, frequency, multiple reminder times per day, a start/end date range, and a per-medication reminder toggle. |
| ✔️ | **Tap-to-Mark Dose Tracking** | Each scheduled dose shows as a pill on the dashboard — pending, taken, or missed — that toggles with a single tap, no page reload. A daily job generates the day's pending dose slots; a 15-minute sweep ages any slot nobody acted on into "missed" after a grace period. |
| 📧 | **Real Email Reminders** | An actual email goes out around each scheduled dose time (if reminders are on for that medication), and once a day for any vaccination due within a week or already overdue — not just a UI badge nobody sees. |
| 💉 | **Vaccination Tracking** | Dose number, date administered, next-due date, and location, with overdue/due-soon badges surfaced on both the dedicated page and the dashboard. |

### Reports, OCR & AI

| | Feature | Description |
|---|---|---|
| 📤 | **Batch Report Upload** | Drag-and-drop, camera capture, or file picker for PDF/JPG/PNG — multiple files at once, each with its own thumbnail, upload-progress bar, and metadata form. |
| 🪄 | **Auto-Detect on Upload** | The instant a file is selected, it's OCR'd in the background and the report type, date, hospital, and doctor fields are pre-filled from local heuristics (keyword-scored type classification, date-format parsing, matching against the family's own upload history) — no AI cost. Manually-edited fields are never overwritten. The extracted text is cached by file hash so the real upload moments later reuses it instead of running OCR twice. |
| 🔁 | **Duplicate Detection** | Re-uploading the exact same file (matched by content hash, not filename) warns you with a link to the existing report and an explicit "upload anyway" override, instead of silently creating a copy. |
| 🔍 | **Fast, Parallel OCR Pipeline** | A queued job runs every upload through **Tesseract** — pages of a multi-page PDF are OCR'd concurrently (capped batches) instead of one at a time, cutting multi-page processing time by roughly half to two-thirds. PDFs are rasterized via Imagick/Ghostscript; photos get grayscale/contrast/deskew/despeckle preprocessing, since a real phone photo of a prescription is rarely flat and well-lit. A page that reads as garbled automatically retries at 90°/180°/270° rotation — a common fix for sideways phone photos — before giving up. |
| ✍️ | **Editable Extracted Text** | The OCR result is shown in a collapsible panel the user can correct by hand — a human correction always overwrites the machine's guess. |
| 🔎 | **Search & Filter Reports** | Full-text search across every report's OCR text, AI summary, filename, hospital, and doctor, plus type and date-range filters on the Reports list. |
| 🤖 | **Automatic Short AI Summary** | The moment OCR succeeds, a 2–3 sentence AI summary is generated automatically — deliberately brief and always disclaimer-stamped. |
| 📚 | **On-Demand Detailed Explanation** | A "Get detailed explanation" button calls the AI again for a fuller, plain-language breakdown with doctor-discussion questions — never auto-run, so it never spends AI usage without the user asking. Every explanation is kept in a permanent history, not just the latest. A "Try summary again" retry path exists for the rare automatic summary that fails outright. |
| 🌐 | **Cached Translation** | Switch the summary between English/Hindi/Gujarati — each language is translated once and cached in `ai_responses`; flipping back and forth never re-calls the API. |
| 📐 | **Pattern-Matched Vitals** | Blood pressure, blood sugar, HbA1c, cholesterol, and hemoglobin are parsed straight out of the OCR text into `health_metrics` — no AI call needed, feeds the timeline's trend view for free. |
| 🧠 | **AI Assistant Chat** | A per-family-member chat that answers questions using that person's actual records (reports, medications, vaccinations, vitals) or explains how to use Novix itself — whichever the question calls for — via a single context-aware prompt. Conversation history persists and folds into later turns. |
| 🔀 | **Multi-Provider AI Fallback** | Every AI feature routes through up to three Gemini API keys and then Groq as a last resort, tried in order — a key hitting its free-tier limit, or a transient provider outage, no longer takes AI features down. A credential that just failed is skipped for a cooldown window rather than retried on every call. |

### Emergency Access & Sharing

| | Feature | Description |
|---|---|---|
| 🪪 | **Public Emergency Card** | A no-login page any first responder can open by scanning a real QR code — photo, age (computed from DOB), blood group front-and-center, allergies flagged red-first by severity, active conditions/medications, tap-to-call emergency contact, family doctor. Deactivating it hides everything within seconds. |
| 🖨️ | **Print-Ready Card PDFs** | A wallet/ID-card-sized PDF and a full-page PDF, both with the same real, scannable QR code embedded — genuinely meant to be printed and carried, not a mockup. |
| 🌐 | **Multi-Language Card** | English/Hindi/Gujarati toggle on the public card — reads from a static label dictionary, never calls the AI API live. |
| ♻️ | **Reissue & Instant Deactivate** | Lost the card? Reissue generates a fresh card number and QR while keeping the old one on record as issuance history; deactivate flips it off instantly without needing a reissue. |
| 🔗 | **Time-Boxed Report Sharing** | Share one report or a family member's full history via a link that **always** expires (24h/48h/7 days/custom — never "forever"), optional 4-digit PIN (hash stored, never the plain code), and optional one-time-view that auto-revokes itself right after the first open. |
| 📱 | **Real Share QR + Native Share** | A scannable QR for the share link, one-tap copy, and the Web Share API for WhatsApp/Email where the browser supports it. |
| 📄 | **Doctor-Ready Share PDF** | The public share page's PDF export includes only minimal identity (name/age/blood group — never address or emergency contact), the original report image, OCR text, and the AI summary/explanation — laid out to actually hand to a doctor. |
| 📊 | **Share History, Never a Black Box** | Every link ever created for a family member, with live status (active/expired/revoked/viewed), view count, last-viewed time, and one-click revoke. |

### Timeline & Compliance

| | Feature | Description |
|---|---|---|
| 🕰️ | **Health Timeline** | Reports, medications, vaccinations, and vitals merged into one chronological feed, grouped by year → month with older years collapsed by default. Filter by category or date range, full-text search across every report's OCR text and AI summary, and inline accordion previews. |
| 📊 | **Compare Over Time** | Select two or more vitals of the same type and see them compared with the percentage change spelled out in plain language ("down 6.7% since March 1"). |
| 📑 | **Doctor-Ready Timeline Export** | Pick a date range and export a clean PDF summary of every report and metric in that window — meant to be handed to a new doctor at a first consultation. |
| 🛡️ | **Audit Logging** | Sensitive actions — account creation, invitations, sharing grants/revokes, card reissues, share views — are written to an immutable, insert-only `audit_log` table with IP/user-agent capture. |
| 🗑️ | **Full Account Deletion** | Typed "DELETE" confirmation, then a properly ordered cascade through every dependent table (respecting the schema's deliberate `RESTRICT` constraints) — a genuine full erasure, not a soft gesture. |

---

## 🧱 Tech Stack

<div align="center">

| Layer | Technology | Version |
|---|---|---|
| **Language** | PHP | 8.2+ (running 8.4.23) |
| **Backend Framework** | Laravel | 11.54 |
| **Database** | MySQL | 8 / 9 |
| **Queue** | Laravel Queues, `database` driver | — |
| **Auth Scaffolding** | Laravel Breeze (Blade stack) | 2.4 |
| **OAuth** | Laravel Socialite (Google) | 5.28 |
| **OCR Engine** | Tesseract, run as concurrent OS processes per page (via `Illuminate\Support\Facades\Process`) | 5.5 |
| **PDF Rasterization** | Imagick (PHP ext) + Ghostscript | 10.07 |
| **AI Providers** | Google Gemini REST API (`gemini-flash-latest`, up to 3 keys) + Groq (OpenAI-compatible chat completions, `llama-3.3-70b-versatile`) as a fallback — thin custom clients, no heavyweight SDKs | — |
| **Outbound Email** | Resend (native Laravel mail transport) | — |
| **PDF Generation** | barryvdh/laravel-dompdf | 3.1 |
| **QR Codes** | simplesoftwareio/simple-qrcode (SVG on-screen, PNG embedded in PDFs) | 4.2 |
| **Templating** | Blade components | — |
| **Interactivity** | Alpine.js | 3.15 |
| **CSS Framework** | Tailwind CSS + `@tailwindcss/forms` | 3.x |
| **Build Tool** | Vite + laravel-vite-plugin | 6.x |
| **Location Data** | `country-state-city` (lazy-loaded chunk) | 3.2 |
| **Fonts** | Figtree (via Bunny Fonts, GDPR-friendly) | — |
| **Local Dev** | Laravel Herd / `php artisan serve` + DBngin | — |

</div>

**Why this stack:** Laravel's model-level validation (a custom `HasValidation` trait run on every `saving` event) means invalid data can never reach the database even if a caller bypasses form-request validation. Every PDF and QR code in the app is produced by two shared, reusable services — `PdfExportService` and `QrCodeService` — rather than each feature rolling its own, so a wallet card, a full emergency PDF, and a shared-report PDF all go through the exact same, verified rendering path. AI calls are cost-gated by design: only a short, automatic summary is ever generated without the user asking, everything else (detailed explanations, translations, the assistant) is on-demand and cached where it makes sense so re-viewing something already generated never spends usage twice. A single `AiClient` service fronts every AI call and transparently retries across every configured credential — Gemini keys first, Groq last — so no individual feature has to know or care which provider actually served a given request.

---

## 🏗️ Architecture & Pipelines

### Registration wizard pipeline

The wizard is **session-backed, not client-state-only**: each step's data is validated and persisted to the server session the moment "Next" is clicked (via `fetch`, no page reload), so refreshing mid-signup resumes exactly where you left off. **Nothing touches the database until step 5 commits** — an abandoned signup never leaves a half-created account.

```mermaid
flowchart TD
    A["Step 1 — Account Basics\nname, email, password, phone, DOB, blood group"] -->|POST /register/step-1| SA["Session: wizard.step1"]
    SA --> B["Step 2 — Photo\nlive camera capture or upload"]
    B -->|POST /register/step-2| SB["Session: wizard.step2\n(tmp file on local disk)"]
    SB --> C["Step 3 — Address\ncascading country/state/city"]
    C -->|POST /register/step-3| SC["Session: wizard.step3"]
    SC --> D["Step 4 — Health\nheight, weight, allergies, medicines"]
    D -->|POST /register/step-4| SD["Session: wizard.step4"]
    SD --> E["Step 5 — Review + Consent\nlive recap via GET /register/summary"]
    E -->|POST /register — final submit| TX{{"DB Transaction"}}

    TX --> U["users"]
    TX --> FM["family_members\n(relation=self, access_type=linked)"]
    TX --> BMI["bmi_logs"]
    TX --> ALG["allergies / medications"]
    TX --> CON["consents ×3"]
    TX --> IDC["id_cards\n(QR generated)"]
    TX --> AUD["audit_log"]
    TX --> LOGIN["Auth::login + redirect"]

    style TX fill:#1E5A45,color:#fff
    style LOGIN fill:#2E7A5D,color:#fff
```

### Family invitation & reciprocal sharing pipeline

Inviting someone who **already has their own account** can't simply link them to a second `family_members` row (`linked_user_id` is unique) — so acceptance detects that case and grants a `sharing_permissions` row against their *real* profile instead, leaving both people's own records untouched.

```mermaid
flowchart TD
    Inv["Owner sends invite\n(email + relation)"] --> Shell["family_members shell row\nstatus=invited"]
    Shell --> Mail["Branded email with token link"]
    Mail --> Click["Invitee opens /invite/{token}"]
    Click --> HasAcct{"Already has\na Novix account?"}
    HasAcct -->|No| Signup["Signup form\n(name/photo/password)"]
    Signup --> LinkShell["Link straight onto\nthe shell row"]
    HasAcct -->|Yes, reciprocal| Login["Log in"]
    Login --> Perm["Choose sharing scope\n(full / reports_only / summary_only)"]
    Perm --> Grant["sharing_permissions row\nagainst invitee's OWN record"]
    Grant --> Cleanup["Redundant shell row\nforceDelete()"]
    LinkShell --> Dashboard["/dashboard"]
    Cleanup --> BothSee["Both sides see the connection\n— owner's Family list AND\ninvitee's own 'Shared With'"]

    style Grant fill:#1E5A45,color:#fff
    style BothSee fill:#2E7A5D,color:#fff
```

### Reports, OCR & AI pipeline

Upload responds **immediately** — OCR and every AI call happen in queued background jobs, never blocking the request. Only the short summary runs automatically; the detailed explanation, translations, and assistant chat are strictly user-initiated. Detection (the moment a file is picked) and the real upload (moments later) share one cached OCR result instead of paying for Tesseract twice.

```mermaid
flowchart TD
    Select["File selected"] -.->|"detect — before the form is even filled in"| Detect["OCR runs immediately;\npre-fills type/date/hospital/doctor;\ncaches OCR text by file hash"]

    Select --> Submit["Upload submitted"]
    Submit --> Dup{"Same file hash\nalready uploaded?"}
    Dup -->|Yes| Warn["Warn + link to existing report\n('upload anyway' override)"]
    Dup -->|No| R["reports row created\nocr_status=pending"]
    R -->|dispatch| OCR["ProcessReportOcrJob"]
    OCR --> CacheHit{"Cached OCR text\nfrom detection?"}
    CacheHit -->|Yes| Done
    CacheHit -->|No| Type{"PDF or image?"}
    Type -->|PDF| Raster["Imagick + Ghostscript\nrasterize each page"]
    Type -->|Image| Prep["Grayscale, contrast,\ndeskew, despeckle"]
    Raster --> Tess["Tesseract — pages OCR'd\nconcurrently, capped batches"]
    Prep --> Tess
    Tess --> Usable{"Usable text\nextracted?"}
    Usable -->|No| Retry["Retry at 90°/180°/270°\nrotation"]
    Retry --> Usable2{"Usable now?"}
    Usable2 -->|No| Failed["ocr_status=failed"]
    Usable2 -->|Yes| Done
    Usable -->|Yes| Done["ocr_status=completed\nocr_text saved"]
    Done --> Metrics["ExtractHealthMetricsJob\n(regex, no AI cost)"]
    Done --> Summary["GenerateShortSummaryJob\n2-3 sentences + disclaimer"]
    Summary -.->|via AiClient fallback chain| Cache["reports.ai_summary\n+ ai_responses row"]

    Cache -.->|user clicks| Detail["On-demand detailed explanation"]
    Cache -.->|user picks language| Translate["On-demand translation (cached)"]
    Cache -.->|user asks| Chat["Assistant chat\n(this report is part of its context)"]

    style Summary fill:#1E5A45,color:#fff
    style Failed fill:#B23B32,color:#fff
    style Warn fill:#F5C879,color:#1F2A24
```

### AI provider fallback chain

Every AI feature — report summaries, detailed explanations, translations, the assistant — calls through one `AiClient` service rather than a specific provider directly, so no feature needs to know which credential actually served it.

```mermaid
flowchart LR
    Call["Any AI feature calls\nAiClient::generate()"] --> K1{"Gemini key 1"}
    K1 -->|success| Done(["Response returned"])
    K1 -->|fails| K2{"Gemini key 2"}
    K2 -->|success| Done
    K2 -->|fails| K3{"Gemini key 3"}
    K3 -->|success| Done
    K3 -->|fails| G{"Groq\n(last resort)"}
    G -->|success| Done
    G -->|fails, all in cooldown| Retry["One more pass,\nignoring cooldowns"]
    Retry --> Done
    Retry -.->|still nothing| Err["Exception —\ncaller shows a friendly failure"]

    K1 -.->|on failure| CD["15-minute cooldown\nfor that credential"]
    K2 -.->|on failure| CD
    K3 -.->|on failure| CD
    G -.->|on failure| CD

    style Done fill:#1E5A45,color:#fff
    style Err fill:#B23B32,color:#fff
```

### Medication & vaccination reminder pipeline

Two scheduled commands do the actual reminding; a third keeps the dashboard's dose pills backed by real data instead of nothing.

```mermaid
flowchart TD
    Daily["Daily, 00:05 —\napp:generate-medication-logs"] --> Logs["Creates today's pending\nmedication_logs rows\nfor every active, scheduled medication"]

    Every15["Every 15 minutes —\napp:process-medication-reminders"] --> Due{"Pending dose\ndue in the last 20 min,\nreminders on?"}
    Due -->|Yes| Send1["Email MedicationDoseReminderMail\nto the account + linked user"]
    Send1 --> Mark1["reminded_at set\n(no repeat email)"]
    Every15 --> Stale{"Pending dose\n>2 hours overdue?"}
    Stale -->|Yes| Missed["status = missed"]

    DailyVax["Daily, 08:00 —\napp:send-vaccination-reminders"] --> DueVax{"Due within 7 days,\nor overdue,\nnot reminded today?"}
    DueVax -->|Yes| Send2["Email VaccinationDueReminderMail"]
    Send2 --> Mark2["last_reminded_at set"]

    Logs -.-> Dashboard["Dashboard dose pills\n(tap to mark taken)"]
    Mark1 -.-> Dashboard
    Missed -.-> Dashboard

    style Send1 fill:#1E5A45,color:#fff
    style Send2 fill:#1E5A45,color:#fff
    style Missed fill:#B23B32,color:#fff
```

### Emergency Card & Report Sharing pipeline

Both features are built on the same two shared services — `QrCodeService` and `PdfExportService` — so a QR code and a PDF are never re-implemented per feature.

```mermaid
sequenceDiagram
    participant Owner
    participant App as Novix
    participant QR as QrCodeService
    participant PDF as PdfExportService
    participant Responder as Anyone with the link/QR

    Owner->>App: Generate card / Create share
    App->>QR: encode public URL (plain text, not JSON)
    QR-->>App: SVG (screen) + PNG (for PDF)
    App->>PDF: render wallet/full/share PDF with embedded QR
    PDF-->>Owner: real, scannable, printable PDF

    Responder->>App: Scan QR / open link
    App->>App: check is_active / expires_at / revoked_at
    alt PIN required
        App->>Responder: PIN gate
        Responder->>App: submit PIN
        App->>App: Hash::check()
    end
    App->>App: record view (view_count++, audit_log)
    App-->>Responder: read-only page (no login)
    Note over App: one-time-view shares auto-revoke<br/>right after this first successful view
```

---

## 🗄️ Database Schema

**29 tables total** — 21 domain tables + 8 Laravel framework tables (`users` base columns, `cache`, `jobs`, `sessions`, `password_reset_tokens`, `failed_jobs`, `job_batches`, `migrations`). All domain tables use `utf8mb4_unicode_ci` + InnoDB, with foreign keys carrying deliberate, medically-safe `CASCADE` / `RESTRICT` / `SET NULL` rules — for example, `bmi_logs`, `allergies`, and `medications` all `RESTRICT` deletion of their `family_members` row, so routine record edits can never silently destroy medical history (a full account deletion explicitly cascades through these in the correct order instead).

<details>
<summary><b>📊 Entity-Relationship Diagram (click to expand)</b></summary>

```mermaid
erDiagram
    USERS ||--o{ FAMILY_MEMBERS : owns
    USERS ||--o| FAMILY_MEMBERS : "linked as"
    USERS ||--o{ FAMILY_INVITATIONS : sends
    USERS ||--o{ REPORTS : uploads
    USERS ||--o{ CONSENTS : grants
    USERS ||--o{ AUDIT_LOG : "acts as"
    USERS ||--o{ SHARING_PERMISSIONS : receives

    FAMILY_MEMBERS ||--o{ REPORTS : has
    FAMILY_MEMBERS ||--o{ ALLERGIES : has
    FAMILY_MEMBERS ||--o{ CHRONIC_CONDITIONS : has
    FAMILY_MEMBERS ||--o{ VACCINATIONS : has
    FAMILY_MEMBERS ||--o{ DOCTORS : has
    FAMILY_MEMBERS ||--o{ BMI_LOGS : has
    FAMILY_MEMBERS ||--o{ HEALTH_METRICS : has
    FAMILY_MEMBERS ||--o{ MEDICATIONS : has
    FAMILY_MEMBERS ||--o{ SHARES : "shared via"
    FAMILY_MEMBERS ||--o{ ID_CARDS : "issuance history"
    FAMILY_MEMBERS ||--o{ SHARING_PERMISSIONS : grants
    FAMILY_MEMBERS ||--o{ INSURANCE_POLICIES : has
    FAMILY_MEMBERS ||--o{ CHAT_MESSAGES : "asked about"

    REPORTS ||--o{ HEALTH_METRICS : "extracted into"
    REPORTS ||--o{ AI_JOBS : "processed by"
    REPORTS ||--o{ AI_RESPONSES : "AI answers for"
    REPORTS ||--o{ SHARES : "shared via"
    REPORTS ||--o{ MEDICATIONS : "source of"

    AI_JOBS ||--o{ AI_RESPONSES : produces
    MEDICATIONS ||--o{ MEDICATION_LOGS : adherence
```

</details>

### 1️⃣ Identity & Family Structure

The account graph — a Google/password user, the family members they manage, and the email-invitation flow that upgrades a dependent into an independently-logged-in linked member (or reciprocally links two existing accounts).

| Table | Purpose | Key columns |
|---|---|---|
| 🟢 `users` | Login accounts | `email` (unique), `google_id` (unique), `password`, `phone`, `avatar_path`, `theme_preference`, two-factor columns |
| 🟢 `family_members` | Every person tracked — self, spouse, kids, parents | `primary_account_id` → users, `linked_user_id` → users (nullable, unique), `unique_health_id` (unique, `NVX-XXXXXXXX`), `relation`, `access_type` (`linked`/`dependent`), `status`, DOB, blood group, height/weight, address, emergency contact — soft-deletes |
| 🟢 `family_invitations` | Email-based invite flow — upgrades a dependent to linked, or reciprocally connects two existing accounts | `token` (unique), `status` (`pending`/`accepted`/`expired`), `expires_at`, `invited_by` |

### 2️⃣ Health Profile

Longitudinal clinical facts not tied to a single report.

| Table | Purpose | Key columns |
|---|---|---|
| 🟡 `allergies` | Known allergies | `allergen_name`, `severity` (`mild`/`moderate`/`severe`), `reaction_description` |
| 🟡 `chronic_conditions` | Ongoing conditions | `condition_name`, `status` (`active`/`managed`/`resolved`) |
| 🟡 `vaccinations` | Immunization history | `vaccine_name`, `dose_number`, `date_administered`, `next_due_date`, `last_reminded_at` (dedupes the daily reminder email) |
| 🟡 `doctors` | Known doctors | `name`, `specialization`, `hospital_or_clinic_name` |

### 3️⃣ Reports, Vitals & AI Processing

Uploaded documents (full-text searchable), structured vitals, and the async AI summarization pipeline with a full answer-history table — never just the latest response.

| Table | Purpose | Key columns |
|---|---|---|
| 🔵 `reports` | Uploaded documents | `type` (blood_test/prescription/xray/mri_ct/insurance/bill/ecg/other), `ocr_text` (FULLTEXT indexed with `ai_summary`), `ocr_status` (pending/processing/completed/failed), `ai_summary` (cache of the latest short summary), `is_archived` — soft-deletes |
| 🔵 `bmi_logs` | BMI history over time | `height_cm`, `weight_kg`, `bmi_value` (auto-computed), `bmi_category` (auto-computed), `recorded_date`, `source` (`manual`/`report_extracted`) |
| 🔵 `health_metrics` | Extracted vitals (BP, sugar, cholesterol, HbA1c, hemoglobin...) | `metric_type`, `value`, `unit`, `recorded_date`, `source` (`manual`/`ocr_extracted`/`ai_extracted`) |
| 🔵 `ai_jobs` | Async AI job history | `job_type` (ocr/summary/translation/entity_extraction), `status`, `provider` (`gemini`/`groq` — whichever actually served the call), `input_tokens`, `output_tokens` |
| 🔵 `ai_responses` | **Permanent history** of every AI answer — every summary, every detailed explanation, every translation | `response_type`, `content`, `language`, `provider`, `generated_at` |
| 🔵 `chat_messages` | Assistant chat log, per family member | `role` (`user`/`assistant`), `content`, `asked_by_user_id`, `input_tokens`/`output_tokens` (null for non-AI system replies, so a rate-limit notice never counts against usage) |

### 4️⃣ Medications

| Table | Purpose | Key columns |
|---|---|---|
| 🟣 `medications` | Active/past prescriptions | `medicine_name`, `dosage`, `frequency`, `schedule_times` (JSON array of `HH:MM`), `active`, `reminder_enabled` |
| 🟣 `medication_logs` | Per-dose adherence log | `scheduled_at`, `taken_at`, `reminded_at` (dedupes the reminder email), `status` (`pending`/`taken`/`missed`/`skipped`) |

### 5️⃣ Sharing, Identity Cards & Compliance

| Table | Purpose | Key columns |
|---|---|---|
| 🔴 `shares` | Time-boxed external share links — **never issued without an expiry** | `token` (unique), `access_type` (single_report/full_summary/emergency_card), `shared_with_label`, `pin_hash` (nullable, never the plain PIN), `is_one_time`, `expires_at`, `revoked_at`, `first_viewed_at`, `view_count` |
| 🔴 `id_cards` | Emergency ID cards — **full issuance history preserved**, never overwritten | `card_number` (unique), `photo_path` (snapshot at issue time), `qr_code_path`, `is_active` |
| 🔴 `consents` | Legal/compliance consent capture | `consent_type` (upload/ai_processing/sharing/account_creation), `ip_address`, `user_agent` |
| 🔴 `audit_log` | **Immutable**, insert-only action trail | `action`, `target_type`, `target_id`, `ip_address` — no `updated_at` by design |
| 🔴 `sharing_permissions` | A linked member's explicit, revocable grant of view access | Composite unique (`family_member_id`, `granted_to_user_id`), `scope`, `revoked_at` |
| 🔴 `insurance_policies` | Insurance policy tracking | `provider_name`, `policy_number`, `coverage_amount`, `expiry_date` |

---

## 📁 Project Structure

```
novix/
├── app/
│   ├── Console/Commands/
│   │   ├── GenerateMedicationLogs.php     # Daily — today's pending dose rows
│   │   ├── ProcessMedicationReminders.php # Every 15 min — reminder emails + missed sweep
│   │   ├── SendVaccinationReminders.php   # Daily — due-soon/overdue reminder emails
│   │   └── ExpireFamilyInvitations.php
│   ├── Http/Controllers/
│   │   ├── Auth/                    # Login, Google OAuth, registration wizard
│   │   ├── Concerns/                #   ResolvesActiveFamilyMember (shared trait)
│   │   ├── DashboardController.php
│   │   ├── EmergencyCardController.php   # Public emergency card + PDFs
│   │   ├── FamilyController.php          # Family list, show, archive/restore
│   │   ├── FamilyAddController.php       # /family/add form
│   │   ├── FamilyInviteController.php    # Invite by email
│   │   ├── FamilyDependentController.php # Add a dependent
│   │   ├── InvitationController.php      # Accept/decline flow (public + reciprocal)
│   │   ├── IdCardController.php          # Owner-side card management
│   │   ├── ReportUploadController.php    # Batch upload + upload-time auto-detect
│   │   ├── ReportController.php          # Report detail, search/filter, OCR text edit
│   │   ├── ReportAiController.php        # Detailed explanation, translation, retry
│   │   ├── MedicationController.php      # CRUD + dashboard dose-toggle
│   │   ├── VaccinationController.php     # CRUD
│   │   ├── AiChatController.php          # Assistant chat
│   │   ├── ShareController.php           # Owner-side share creation + history
│   │   ├── PublicShareController.php     # Public share view, PIN gate, PDF
│   │   ├── TimelineController.php        # Merged timeline + PDF export
│   │   └── ProfileController.php
│   ├── Http/Requests/Registration/  # Per-step wizard validation
│   ├── Jobs/                        # ProcessReportOcrJob, ExtractHealthMetricsJob,
│   │                                 #   GenerateShortSummaryJob
│   ├── Mail/                        # FamilyInvitationMail, MedicationDoseReminderMail,
│   │                                 #   VaccinationDueReminderMail
│   ├── Rules/                       # NoHeaderInjection (CRLF-injection hardening)
│   ├── Services/
│   │   ├── Ai/                      # AiClient — multi-credential fallback router
│   │   ├── Assistant/               # AssistantContextBuilder (prompt from real records)
│   │   ├── Gemini/                  # GeminiClient
│   │   ├── Groq/                    # GroqClient
│   │   ├── Ocr/                     # OcrExtractor (Tesseract + Imagick, parallel pages)
│   │   ├── Pdf/                     # PdfExportService (shared by every PDF)
│   │   ├── Qr/                      # QrCodeService (shared by every QR code)
│   │   └── Reports/                 # HealthMetricExtractor, ReportFieldDetector
│   ├── Models/
│   │   ├── Concerns/HasValidation.php   # Model-level validation trait
│   │   └── *.php                        # 21 domain models
│   └── Policies/
├── config/emergency_card.php        # Static EN/HI/GU label dictionary
├── database/migrations/             # 34 sequential migrations
├── resources/
│   ├── js/
│   │   ├── alpine/                  # registration-wizard, camera-capture, bmi-gauge,
│   │   │                            #   location-select, tag-input, report-upload,
│   │   │                            #   report-processing, metric-compare, share-actions,
│   │   │                            #   assistant-chat, dose-tracker...
│   │   └── app.js                   # Registers all Alpine.data components
│   └── views/
│       ├── auth/wizard/             # 5 step partials
│       ├── components/              # Reusable x-* Blade components
│       ├── emergency/               # Public card + PDF templates
│       ├── emails/                  # Branded HTML email templates
│       ├── family/                  # Family list/show/add
│       ├── invite/                  # Public invitation accept flow
│       ├── medications/             # List, create/edit form
│       ├── vaccinations/            # List, create/edit form
│       ├── assistant/               # Chat UI
│       ├── profile/tabs/            # Profile tabs (reused for dependents & full-access)
│       ├── reports/                 # Upload, index, detail
│       ├── shares/                  # Owner create/history + public views
│       ├── timeline/                # Merged timeline + PDF export
│       └── dashboard.blade.php
└── routes/
    ├── web.php
    ├── console.php                  # Scheduled command registration
    └── auth.php
```

---

## 🌐 Application Routes

<details>
<summary><b>100+ routes — click to expand full list, grouped by feature</b></summary>

**Auth & Registration**

| Method | URI | Name |
|---|---|---|
| GET/POST | `/register`, `/register/step-{1..4}` | `register.*` |
| GET | `/register/check-email`, `/register/photo-preview`, `/register/summary` | `register.*` |
| GET/POST | `/login`, `/logout` | `login`, `logout` |
| GET/POST | `/auth/google/redirect`, `/auth/google/callback` | `auth.google.*` |
| GET/POST | `/forgot-password`, `/reset-password` | `password.*` |

**Dashboard & Profile**

| Method | URI | Name |
|---|---|---|
| GET | `/dashboard` | `dashboard` |
| POST | `/dashboard/switch/{familyMember}`, `/dashboard/dismiss-onboarding` | `dashboard.*` |
| GET | `/profile` | `profile.edit` |
| POST | `/profile/{familyMember?}/{basic-info,photo,address,health,emergency-contact}` | `profile.*` |
| PATCH | `/profile/theme` | `profile.theme` |
| DELETE | `/profile` | `profile.destroy` |

**Family Management**

| Method | URI | Name |
|---|---|---|
| GET | `/family`, `/family/add`, `/family/{familyMember}`, `/family/{familyMember}/edit` | `family.*` |
| POST | `/family/invite`, `/family/dependent` | `family.invite.store`, `family.dependent.store` |
| POST/DELETE | `/family/invite/{invitation}/{resend,cancel}` | `family.invite.*` |
| POST | `/family/{familyMember}/{archive,restore}` | `family.*` |
| POST | `/family/sharing/{sharingPermission}/revoke` | `family.sharing.revoke` |
| GET/POST | `/invite/{token}`, `/invite/{token}/{register,permission,dismiss}` | `invite.*` |

**Reports, OCR & AI**

| Method | URI | Name |
|---|---|---|
| POST | `/reports/detect` | `reports.detect` (upload-time auto-detect) |
| GET/POST | `/reports/upload`, `/reports` | `reports.upload`, `reports.store`, `reports.index` (search + type/date filters) |
| GET | `/reports/{report}`, `/reports/{report}/{status,file}` | `reports.show`, `reports.status`, `reports.file` |
| PATCH | `/reports/{report}/ocr-text` | `reports.ocr-text` |
| POST | `/reports/{report}/{detailed-explanation,translate,retry-summary}` | `reports.*` |

**Medications & Vaccinations**

| Method | URI | Name |
|---|---|---|
| GET | `/medications`, `/medications/create`, `/medications/{medication}/edit` | `medications.*` |
| POST/PATCH/DELETE | `/medications`, `/medications/{medication}` | `medications.*` |
| POST | `/medications/{medication}/toggle-dose` | `medications.toggle-dose` |
| GET | `/vaccinations`, `/vaccinations/create`, `/vaccinations/{vaccination}/edit` | `vaccinations.*` |
| POST/PATCH/DELETE | `/vaccinations`, `/vaccinations/{vaccination}` | `vaccinations.*` |

**AI Assistant**

| Method | URI | Name |
|---|---|---|
| GET | `/assistant` | `assistant` |
| POST | `/assistant/send` | `assistant.send` |

**Emergency Card**

| Method | URI | Name |
|---|---|---|
| GET | `/emergency/{cardNumber}`, `/emergency/{cardNumber}/pdf`, `/emergency/{cardNumber}/pdf/wallet` | `emergency.*` (public, no auth) |
| GET | `/id-card` | `id-card.show` |
| POST | `/id-card/reissue`, `/id-card/{idCard}/toggle-active` | `id-card.*` |

**Report Sharing**

| Method | URI | Name |
|---|---|---|
| GET/POST | `/shares/create`, `/shares`, `/shares/history` | `shares.*` |
| POST | `/shares/{share}/revoke` | `shares.revoke` |
| GET/POST | `/s/{token}`, `/s/{token}/pin`, `/s/{token}/pdf`, `/s/{token}/file/{report}` | `share.public.*` (public, no auth) |

**Health Timeline**

| Method | URI | Name |
|---|---|---|
| GET | `/timeline`, `/timeline/export` | `timeline`, `timeline.export` |

</details>

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+ with Composer
- MySQL 8/9 (via [DBngin](https://dbngin.com), Laravel Herd, or any local install)
- Node.js + npm
- **Tesseract OCR** and **Ghostscript** (`brew install tesseract ghostscript` on macOS) — required for the report OCR pipeline
- The PHP **Imagick** extension — required for PDF rasterization and PNG QR generation
- A [Google Gemini API key](https://ai.google.dev) — required for AI summaries/explanations/translations/assistant chat. Up to 2 backup Gemini keys and a [Groq API key](https://console.groq.com) are optional — if set, AI features automatically fall back through them in order when one hits its free-tier limit or fails (see `App\Services\Ai\AiClient`)
- A [Google OAuth client ID/secret](https://console.cloud.google.com) (optional, for Google sign-in)

### Installation

```bash
git clone https://github.com/neevmodh/Novix.git
cd Novix

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Point .env at your database, then:
php artisan migrate
php artisan storage:link

# Build front-end assets
npm run build
```

Add your credentials to `.env`:

```dotenv
GOOGLE_CLIENT_ID=your-google-oauth-client-id
GOOGLE_CLIENT_SECRET=your-google-oauth-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

GEMINI_API_KEY=your-gemini-api-key
GEMINI_API_KEY_2=optional-backup-gemini-key
GEMINI_API_KEY_3=optional-backup-gemini-key
GEMINI_MODEL=gemini-flash-latest
GROQ_API_KEY=optional-groq-key-as-final-fallback
GROQ_MODEL=llama-3.3-70b-versatile

# Family invitations and medication/vaccination reminders need a real
# mailer — defaults to MAIL_MAILER=log (writes to the log file, sends
# nothing) until you set this up. Uses Resend (resend.com, free tier)
# by default; swap MAIL_MAILER and its config in config/mail.php for
# SMTP/Postmark/SES if you'd rather use something else.
MAIL_MAILER=resend
MAIL_FROM_ADDRESS="onboarding@resend.dev"
RESEND_KEY=your-resend-api-key

QUEUE_CONNECTION=database
```

> **Note:** Resend's `onboarding@resend.dev` sender only delivers to the
> email address your Resend account was created with, until you verify
> a custom domain (resend.com/domains) — needed before invitations can
> reach anyone besides yourself.

**Important:** `php artisan serve` and `queue:work` only read `.env` once, at
startup. If you change any credentials while they're already running, restart
both — otherwise they'll keep using the old values silently.

Run the app **and** a queue worker — OCR and AI calls run as background jobs and never block the upload response:

```bash
php artisan serve
php artisan queue:work
```

Then visit **http://localhost:8000** 🎉

---

## 🔒 Security & Privacy

- Model-level validation (`HasValidation` trait) runs on every `saving` event — invalid data can never reach the database even if a caller bypasses form-request validation.
- Every route touching family data verifies record ownership or an active `sharing_permissions` grant against the logged-in user; edit-level actions require full-scope access, never just a viewing grant.
- Foreign keys use deliberate, medically-safe cascade rules — `RESTRICT` on tables that hold real medical history (`bmi_logs`, `allergies`, `medications`, `reports`...), so routine edits can never silently wipe it.
- A brand-new Google identity is never written to `users` until registration fully completes — no half-created accounts from an abandoned signup, via either auth path.
- **Share links always expire** — there is no "never expires" option, by design. A 4-digit PIN is stored as a hash, never in plain text. A one-time-view link auto-revokes itself immediately after its first successful view; an owner's manual revoke is always an absolute, immediate stop for everyone, including whoever was mid-view.
- **Public pages leak nothing extra.** The emergency card shows only what a responder needs (never the home address); a shared report's PDF shows only name/age/blood group (never address or emergency contact) alongside the actual report content.
- Report files and QR codes are stored on a private disk and served through authorization-checked routes — never a public, guessable URL.
- Sensitive actions — account creation, invitations, sharing grants/revokes, card reissues/deactivation, and every share view — are written to an append-only `audit_log` with IP/user-agent capture.
- AI cost is bounded by design: only the automatic short summary ever runs without a user click, and every other AI feature is on-demand and cached where re-viewing the same thing shouldn't spend usage twice.
- A custom `NoHeaderInjection` validation rule rejects embedded CR/LF in every user-supplied email field that reaches outbound mail (family invitations, password reset, registration) — closes a real CRLF-injection gap in Laravel's own default `email` validation rule (CVE-2026-48019) that's unpatched on this app's framework line.
- Full account deletion requires a typed "DELETE" confirmation plus current-password re-entry, and explicitly cascades through every dependent table in the correct order.
- 2FA columns (`two_factor_secret`, `two_factor_recovery_codes`) are stored `encrypted`/`encrypted:array` at the Eloquent cast level.

Found a vulnerability? Please open a private security advisory rather than a public issue.

---

## 🗺️ Roadmap

- [x] Full database schema (29 tables, normalized, FK-audited)
- [x] Session-backed multi-step registration wizard
- [x] Google OAuth + email/password auth
- [x] Family dashboard with live BMI gauge and a first-run checklist
- [x] Tabbed profile editor
- [x] Family invitations — dependents and reciprocal linked-account sharing
- [x] Emergency ID card with real QR generation, reissue, and instant deactivate
- [x] Dark mode (server-persisted)
- [x] Report upload with upload-time auto-detect and duplicate detection
- [x] Fast, parallel OCR pipeline (Tesseract + Imagick/Ghostscript) with rotation retry
- [x] AI report summarization, on-demand detailed explanations, cached translation, and an assistant chat
- [x] Multi-credential AI fallback chain (3 Gemini keys + Groq) — no single key outage takes AI features down
- [x] Medication management with reminder scheduling and real email delivery (Resend)
- [x] Vaccination tracking with due/overdue reminder emails
- [x] Health timeline with filters, full-text search, and compare-over-time
- [x] Secure, time-boxed, PIN-protectable report sharing links with real QR + PDF export
- [ ] Insurance policy management UI (`insurance_policies` table exists — no controller/views yet)
- [ ] Domain verification for outbound email (currently limited to the Resend account's own address until a custom domain is verified)
- [ ] Public deployment
- [ ] Native mobile app / PWA packaging

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

1. Fork the repo
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

Distributed under the **Apache License 2.0**. See [`LICENSE`](LICENSE) for details.

---

<div align="center">

Made with ❤️ for families everywhere.

</div>
