# Prompts untuk Claude Code — pasang, migrate, push
# Copy-paste satu demi satu ke dalam sesi `claude` di dalam repo Laravel anda.
# Jalankan mengikut urutan. Claude Code akan minta kebenaran sebelum arahan yang mengubah.

## 0) Konteks
Baca CLAUDE.md, README.md dan DEPLOY.md dalam repo ini. Ringkaskan apa yang
modul ini buat dan senaraikan fail yang akan disentuh. Jangan ubah apa-apa lagi.

## 1) Pasang dependency
Pasang pakej ini dan kemas kini composer:
  composer require livewire/livewire smalot/pdfparser barryvdh/laravel-dompdf
Kemudian jalankan `php -l` pada semua fail PHP baharu dalam app/ dan betulkan
sebarang ralat sintaks. Tunjuk ringkasan.

## 2) Wiring
Daftarkan policy dalam AuthServiceProvider:
  Gate::policy(\App\Models\LabReport::class, \App\Policies\LabReportPolicy::class);
Pastikan komponen Livewire (UploadLabReport, ReviewLabReport) ditemui.
Tambah nilai PDPA_DPO_NAME, PDPA_DPO_EMAIL, PDPA_DPO_PHONE ke .env.example
(jangan sentuh .env sebenar). Pastikan disk 'local' bukan public untuk PDF.

## 3) Semak migration (WAJIB dry-run dulu)
Jalankan `php artisan migrate --pretend` dan tunjukkan SEMUA SQL yang akan
dijalankan. JANGAN apply lagi. Sahkan tiada DROP TABLE / migrate:fresh.

## 4) Migrate (local/staging sahaja)
Selepas saya sahkan SQL nampak betul, jalankan `php artisan migrate` pada
database TEMPATAN sahaja. Laporkan jadual yang dicipta.

## 5) Ujian hujung-ke-hujung
Cadangkan dan tulis satu ujian Pest/PHPUnit ringkas untuk
FunctionalMedicineInterpreter (skor + ratio) dan jalankan `php artisan test`.

## 6) Commit & push (branch, bukan terus ke main)
Buat branch `feat/fm-report`, `git add` hanya fail modul (jangan .env),
commit dengan mesej yang jelas, dan `git push -u origin feat/fm-report`.
Kemudian beri saya pautan untuk buka Pull Request. JANGAN merge sendiri.

## 7) Production (hanya bila saya arahkan, selepas PR diluluskan)
Ingatkan saya untuk backup DB dahulu. Kemudian pada server production sahaja:
  php artisan migrate --force
  php artisan config:cache route:cache view:cache
Sahkan `php artisan schedule:list` menunjukkan pdpa:purge.
