# Klinik FM Report — Functional Medicine Interpretation (Laravel)

Turns an uploaded **Gnosis Laboratories** PDF into a scored functional-medicine
interpretation and generates **Patient** + **Practitioner** PDF editions —
built PDPA-compliant and hardened by design.

> **Clinical decision-support, NOT a diagnosis.** Every output must be reviewed
> by a qualified clinician. Functional ranges are narrower than lab reference
> ranges and are not all universally validated.

## What's included (drop into a fresh Laravel app)

```
app/Support/FunctionalRanges.php        # marker catalogue + functional bands (the engine data)
app/Services/FunctionalMedicineInterpreter.php  # scoring + ratios (FT3:FT4, TC:HDL, TG:HDL, HOMA-IR)
app/Services/GnosisPdfParser.php        # extract values from Gnosis PDF text
app/Services/ReportPipeline.php         # parse -> patient/report -> interpret -> persist (txn)
app/Services/DualReportRenderer.php     # Blade -> A4 PDF (dompdf), stored PRIVATE
app/Models/*                            # Patient, LabReport, LabResult, Interpretation, Consent, AccessLog
app/Livewire/UploadLabReport.php        # hardened upload + explicit consent
app/Http/Controllers/LabReportController.php
app/Policies/LabReportPolicy.php        # IDOR / access control
app/Console/Commands/PurgeExpiredData.php  # PDPA s.10 retention purge
config/pdpa.php  config/fm.php
database/migrations/*                   # 8 tables incl. consents, access_logs, dsr, data_breaches
resources/views/reports/*               # patient + practitioner Blade templates
resources/views/privacy.blade.php       # bilingual PDPA notice (BM + EN)
routes/web.php  routes/console.php
```

## Deploy (push & migrate)

See **DEPLOY.md** for the exact `git push` and `php artisan migrate` steps
(run in your own environment — they can't be run from the assistant's sandbox).

## Setup

```bash
laravel new klinik-fm && cd klinik-fm         # or use an existing app
# copy the app/, config/, database/, resources/, routes/ files from this bundle in

composer require livewire/livewire smalot/pdfparser barryvdh/laravel-dompdf

php artisan migrate
# add PDPA_DPO_NAME / PDPA_DPO_EMAIL / PDPA_DPO_PHONE to .env
php artisan serve
```

Visit `/lab-reports/upload`, upload a Gnosis PDF, tick the consent box, Generate.
You are taken to the **review screen** (`/lab-reports/{id}/review`): every parsed
value is editable — verify it against the source PDF, tick the attestation, then
**Generate report**. Only then is the report scored and issued. Open
`/lab-reports/{id}` and download `/pdf/patient` or `/pdf/practitioner`.

## How the engine works

1. `GnosisPdfParser` reads the PDF text and maps each Gnosis line to an internal
   marker key + numeric value, plus meta (name, lab no, sex, age).
2. `ingestDraft()` stages a **draft** — nothing is scored yet.
3. **Clinician review gate** (`ReviewLabReport`): the parsed values are shown
   editable; the clinician verifies against the source PDF and attests before
   `finalize()` runs.
4. `FunctionalMedicineInterpreter` scores each marker OPTIMAL / SUBOPTIMAL /
   CRITICAL against `FunctionalRanges`, computes ratios, ranks the critical findings.
5. `DualReportRenderer` renders the two Blade editions to A4 PDF.

**Tune the medicine in one place:** `app/Support/FunctionalRanges.php`.

## PDPA (Act 709 + 2024) — what's wired in

- **s.6 consent** — explicit, unticked consent captured at upload + immutable
  `consents` record.
- **s.7 notice** — bilingual `/privacy` (BM + EN), versioned.
- **s.9 security** — encrypted casts on `name`, `ic_number`, `raw_text`; access
  control; `access_logs` (who accessed what, never the payload).
- **s.10 retention** — soft deletes + `pdpa:purge` daily command.
- **s.30–43 rights** — self-serve endpoints: export/portability (JSON), withdraw
  consent (stops processing), erasure (soft-delete + logged); `data_subject_requests`
  with 21-day SLA field. See `DataSubjectController`.
- **s.40 sensitive** — health + IC encrypted; explicit consent gate in the pipeline.
- **s.129 cross-border** — processor inventory in `config/pdpa.php`; keep DB +
  storage in Malaysia.
- **2024 breach** — `data_breaches` register (Commissioner ≤72h, individuals ≤7d);
  **appoint a DPO** (config).

## Security (webapps-security) — what's wired in

- Upload: real MIME (`mimetypes:application/pdf`), 10 MB cap, UUID filename,
  stored on the private `local` disk (never public/webroot).
- IDOR: `LabReportPolicy` — clinic-scoped ownership check on view/delete/pdf.
- Mass assignment: explicit `$fillable`; no `unguard()`.
- CSRF + `auth`/`verified` middleware on all private routes.
- Blade auto-escaping (`{{ }}`); no `{!! !!}` on user data.
- Secrets in `.env`; `APP_DEBUG=false` in prod.

## Pre-ship checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, HTTPS forced, HSTS/CSP headers set
- [ ] DB + object storage in a **Malaysian** region; backups too (s.129)
- [ ] `PDPA_DPO_*` set; DPO appointed; privacy notice reviewed by counsel
- [ ] Retention days in `config/pdpa.php` confirmed against clinic/MOH policy
- [ ] `pdpa:purge` scheduled; `php artisan schedule:work` / cron running
- [ ] Roles/permissions on who may upload/view; `access_logs` monitored
- [ ] `composer audit` clean; Laravel + packages current
- [ ] Parser output spot-checked against source PDFs before clinical use
- [ ] Confirm current PDPA thresholds/guidelines at pdp.gov.my (not legal advice)

## Limitations / next steps

- **Narrative sections** (dysregulation nodes, order of repair, protocol pointers)
  are auto-derived by `NarrativeBuilder` from the scored output as an editable
  starting point for the clinician — not autonomous diagnosis/prescription.
- The PDF parser targets the Gnosis layout. A **clinician review-and-confirm
  gate is now built in** — drafts cannot be viewed or exported as PDF until a
  clinician verifies the extracted values and attests. Keep validating extraction
  quality per report.
- The narrative sections (root-cause tree, order of repair, protocol) from the
  full house format can be layered on as additional Blade partials driven by the
  same engine output.
