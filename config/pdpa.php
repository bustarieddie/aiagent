<?php
// PDPA (Act 709 + 2024 Amendments) configuration.
return [
    'notice_version' => '2026-07',
    // DPO is MANDATORY under the 2024 amendment once thresholds are met (health
    // data is sensitive; a clinic will almost always need one).
    'dpo' => [
        'name'  => env('PDPA_DPO_NAME', 'Data Protection Officer'),
        'email' => env('PDPA_DPO_EMAIL', 'dpo@klinikbustari.com'),
        'phone' => env('PDPA_DPO_PHONE', ''),
    ],
    // s.10 retention. Health records typically retained per MOH/clinic policy;
    // set deliberately and auto-purge past it. Adjust to your legal advice.
    'retention_days' => [
        'lab_report' => 365 * 7,
        'access_log' => 365 * 2,   // breach evidence kept >= 2 years
        'dsr'        => 365 * 2,
    ],
    // s.129 cross-border inventory. Keep personal/health data in MY/ASEAN.
    'processors' => [
        'database' => ['region' => 'my', 'dpa' => true, 'purpose' => 'primary store'],
        'storage'  => ['region' => 'my', 'dpa' => true, 'purpose' => 'PDF storage'],
    ],
];
