<!DOCTYPE html><html lang="ms"><head><meta charset="utf-8"><title>Notis Privasi / Privacy Notice</title></head>
<body style="font-family:system-ui;max-width:760px;margin:2rem auto;line-height:1.5;color:#23282e;">
<h1>Notis Privasi &middot; Privacy Notice</h1>
<p style="color:#6a7079;">Versi / Version: {{ config('pdpa.notice_version') }} &middot; PDPA 2010 (Act 709) + Pindaan 2024</p>

<h2>Bahasa Melayu</h2>
<p>Klinik ini mengumpul data kesihatan anda (keputusan ujian makmal, nombor Kad
Pengenalan, umur, jantina) untuk tujuan <strong>tafsiran klinikal dan penjagaan
kesihatan sahaja</strong>. Data sensitif disimpan secara disulitkan dan akses
dihadkan kepada kakitangan yang dibenarkan.</p>
<ul>
  <li>Anda berhak untuk <strong>mengakses, membetul, menarik balik persetujuan,
  memindah (portability) dan memohon pemadaman</strong> data anda.</li>
  <li>Data anda <strong>tidak</strong> dikongsi di luar tujuan di atas tanpa
  persetujuan baharu, dan disimpan di Malaysia.</li>
  <li>Data disimpan hanya selama tempoh yang diperlukan, kemudian dipadam
  (dasar penyimpanan {{ (int) (config('pdpa.retention_days.lab_report')/365) }} tahun).</li>
</ul>
<p>Pegawai Perlindungan Data (DPO): {{ config('pdpa.dpo.email') }}.</p>

<h2>English</h2>
<p>This clinic collects your health data (lab results, IC number, age, sex) for
<strong>clinical interpretation and healthcare purposes only</strong>. Sensitive
data is encrypted at rest and access is restricted to authorised staff.</p>
<ul>
  <li>You may <strong>access, correct, withdraw consent, port and request
  erasure</strong> of your data.</li>
  <li>Your data is <strong>not</strong> shared beyond the purpose above without
  fresh consent and is stored in Malaysia.</li>
  <li>Data is retained only as long as necessary, then deleted.</li>
</ul>
<p>Data Protection Officer (DPO): {{ config('pdpa.dpo.email') }}.</p>
</body></html>
