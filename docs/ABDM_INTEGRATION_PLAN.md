# ABDM integration plan

Decision made 2026-09-12: pursue ABHA/ABDM integration. This tracks the
plan so it survives across sessions — check this file's status column
before assuming what's done.

## Why, briefly

Indian competitors (MyDigiRecords, eka.care, Health-e) all lean on ABDM
certification as a trust signal and to pull in records NivayaLife users
never uploaded themselves. See `docs/COMPETITIVE_ANALYSIS.md` for the full
comparison this decision came out of.

## What ABDM actually requires (per NHA's own developer docs)

ABDM (Ayushman Bharat Digital Mission) has three integrator/solution
types: **HIP** (Health Information Provider — you *push* records you
hold, e.g. a hospital), **HIU** (Health Information User — an entity like
an insurer that *pulls* records), and **PHR** (Personal Health Record app
— ABHA creation/linking plus letting an individual or family manage and
view their own records). NivayaLife is a **PHR** app: we're not a care
provider generating original records, and we're not a third party like an
insurer requesting someone else's data — we're the tool a family uses to
hold and view their own ABHA-linked records once they consent.

Realistic phases (a commonly-cited timeline is ~90 days end to end, most
of it process/compliance time, not raw coding):

1. **Sandbox registration** (one-time) — register on the ABDM sandbox
   portal, declare solution type **PHR**, get a sandbox
   `clientId`/`clientSecret`, register callback URLs, pass ABDM's
   connectivity ping.
2. **ABHA identity flows** — let a user link their existing ABHA (the
   14-digit national health ID) to their NivayaLife profile, or create a
   new one via Aadhaar/mobile OTP.
3. **Consent Manager (HIE-CM) integration** — request consent from a
   user to fetch their records from a specific provider; ABDM's Consent
   Manager (not us) handles the actual consent UI/approval.
4. **FHIR R4 data exchange** — once consent is granted, fetch records as
   FHIR R4 bundles (India-specific profiles) and map them into
   NivayaLife's own `reports`/`vitals` schema.
5. **Security uplift + VAPT** — a formal vulnerability assessment and
   penetration test is required before NHA grants production access.
   This is typically a paid third-party audit, not something either of
   us can self-certify.
6. **NHA walkthrough + production credentials** — after VAPT, NHA
   reviews the integration before issuing production `clientId`/secret.

## Division of labor

| Step | Who | Status |
|---|---|---|
| 1. Sandbox registration on sandbox.abdm.gov.in | **You** — this is organization/account registration (needs your business details, PAN, an authorized signatory's identity) and I don't create accounts on your behalf | **Not started — blocking everything else** |
| 2–4. ABHA linking, consent flow, FHIR data mapping | **Me**, once I have sandbox `clientId`/`clientSecret` and the callback-URL requirements from step 1 | Blocked on step 1 |
| 5. VAPT | **You** (hire an auditor) — a real compliance/legal step, not code | Not started |
| 6. NHA production walkthrough | **You**, with technical support from me if NHA asks integration questions | Blocked on 1–5 |

## What to do right now

Go to **sandbox.abdm.gov.in** and register NivayaLife with **PHR** as the
solution type. You'll likely need:
- Entity details — registering as an individual (no company/GSTIN) is
  fine for sandbox; production later may require a registered business
  entity, confirm on the portal as you go.
- A technical contact email (use whichever inbox you want ABDM
  correspondence to go to).
- Your app's planned callback URL base — for sandbox testing this can be
  `https://nivayalife.up.railway.app` prefixed paths; we'll pick exact
  paths together once I scaffold the routes (step 2+).

Once you have a sandbox `clientId` and `clientSecret`, share them with me
the same way you shared the Gemini/Resend/Google keys earlier (paste
them, I'll set them as Railway environment variables — never commit them
to the repo) and I'll start on step 2.

## What I'll build once unblocked (step 2–4, not started yet)

- `abha_number` / `abha_address` nullable columns on `family_members` (a
  member's own national health ID, separate from NivayaLife's existing
  internal `unique_health_id`).
- An `AbdmClient` service class wrapping the sandbox base URL + OAuth2
  session-token flow (matches the pattern already used for the optional
  XrayVision/ClinicalNlp microservice clients — off unless configured).
- A "Link your ABHA ID" action in Profile → Health, and a consent-request
  flow surfaced wherever a user wants to pull in outside records.
- Config entries in `config/services.php` under a new `abdm` key, reading
  `ABDM_CLIENT_ID` / `ABDM_CLIENT_SECRET` / `ABDM_BASE_URL` env vars —
  nullable/optional, so the rest of the app is completely unaffected
  until sandbox credentials exist.

## Registration decision log

- 2026-09-12: Registering as an **individual** (no registered company/
  GSTIN), entity name = the account holder's own legal name, product
  name = NivayaLife, solution type = **PHR**.

## Sources

- [ABDM Sandbox Integration and Exit process](https://docs.coronasafe.network/abdm-documentation/implementers-guide/abdm-sandbox-integration-and-exit-process)
- [ABDM HIU Integration from Scratch: M3 Certification and Health Records Pull Guide](https://nirmitee.io/blog/building-abdm-hiu-from-scratch-m3-flow-reference-architecture/)
- [Working with ABDM APIs — Sandbox Documentation](https://kiranma72.github.io/abdm-docs/1-basics/working_with_abdm_apis/)
- [ABDM Compliance Guide 2026: HIP/HIU, FHIR, Checklist](https://ringsafe.in/abdm-health-data-guide/)
