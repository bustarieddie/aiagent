# Deploy — push & migrate

> These commands run in **your** environment (repo + server + DB). They cannot be
> run from the assistant's sandbox: it has no access to your Git remote, server,
> or the patient database — and running migrations against a health-data DB is an
> action you must perform and review yourself.

## 1. Add the files to your Laravel app

Copy the `app/`, `config/`, `database/`, `resources/`, `routes/` files from this
bundle into your Laravel project (merge with existing `routes/web.php` /
`routes/console.php`).

```bash
composer require livewire/livewire smalot/pdfparser barryvdh/laravel-dompdf
```

Register the Livewire components (if not auto-discovered) and the policy in
`AppServiceProvider` / `AuthServiceProvider`:

```php
// AuthServiceProvider::boot()
Gate::policy(\App\Models\LabReport::class, \App\Policies\LabReportPolicy::class);
```

## 2. Push to your repo

```bash
git checkout -b feat/fm-report
git add app config database resources routes composer.json composer.lock
git commit -m "feat: functional-medicine report module (PDPA + review gate)"
git push -u origin feat/fm-report
# open a PR, review, merge
```

## 3. Migrate — safely

```bash
# ALWAYS back up the database first (health data).
php artisan migrate --pretend        # dry-run: prints the SQL, changes nothing
php artisan migrate                  # apply on staging first
# after verifying on staging:
php artisan migrate --force          # production (non-interactive)
```

Migrations added (10 total): patients, lab_reports (+ review-state), lab_results,
interpretations (+ narrative), consents, access_logs, data_subject_requests,
data_breaches.

## 3b. (Optional) Seed sample data to test end-to-end

```bash
cp .env.example .env && php artisan key:generate   # if not already set up
php artisan db:seed --class=Database\\Seeders\\FmSampleSeeder
```
Creates 6 anonymised demo patients (real value profiles) so you can open a
report and download both PDFs without needing source lab PDFs.

## 4. Post-deploy checks

```bash
php artisan schedule:list            # confirm pdpa:purge is scheduled
php artisan config:cache route:cache view:cache
composer audit                       # dependency CVEs
```

- Confirm `APP_DEBUG=false`, HTTPS forced, DB + storage in a **MY** region.
- Set `PDPA_DPO_*` in `.env`; ensure a DPO is appointed.
- Smoke test: upload a Gnosis PDF → review screen → correct a value → finalise →
  view report → download both PDFs. Confirm a draft cannot be exported.
