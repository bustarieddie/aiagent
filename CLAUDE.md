# CLAUDE.md — Klinik FM Report module

Guidance for Claude Code when working in this Laravel repo. Read this fully
before running commands.

## What this module is
Uploads a Gnosis Laboratories lab PDF → parses values → clinician reviews &
confirms → scores against functional ranges → generates Patient + Practitioner
PDF reports. It handles **patient health data**, so PDPA + security rules are
non-negotiable.

> Output is clinical DECISION-SUPPORT, not a diagnosis. Never remove the
> disclaimer, the review gate, or the consent checks.

## Stack & conventions
- Laravel 11/12, Livewire, MySQL. PHP 8.2+.
- Packages: `livewire/livewire`, `smalot/pdfparser`, `barryvdh/laravel-dompdf`.
- Services in `app/Services`, engine data in `app/Support/FunctionalRanges.php`.
- Tune the medicine ONLY in `FunctionalRanges.php` — one source of truth.

## Hard safety rules (do NOT violate)
1. **Never** run `migrate:fresh`, `migrate:reset`, or drop tables — this is a
   patient database. Use additive migrations only.
2. Always run `php artisan migrate --pretend` and show the SQL BEFORE applying.
   Apply to local/staging first; production only with `--force` after backup.
3. Keep encrypted casts on `Patient.name/ic_number`, `LabReport.raw_text/
   draft_values` (PDPA s.40). Never log these fields.
4. Keep the clinician review gate: a `draft` report must never be viewable or
   exportable as PDF until `status = reviewed`.
5. Keep `LabReportPolicy` authorization on every report route (IDOR defence).
6. Never commit `.env` or secrets. Confirm `.gitignore` covers them.
7. Keep upload validation: real MIME `application/pdf`, size cap, UUID filename,
   private disk (not public webroot).

## Definition of done for any change
- `php -l` clean on changed files; `composer audit` clean.
- `php artisan migrate --pretend` reviewed.
- Existing tests pass (`php artisan test`).
- One-line note per change: which PDPA principle / security vector it touches.

## Useful commands
```
php artisan migrate --pretend
php artisan migrate           # local/staging
php artisan test
php artisan schedule:list     # confirm pdpa:purge scheduled
composer audit
```
