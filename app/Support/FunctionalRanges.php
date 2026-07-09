<?php

namespace App\Support;

/**
 * Functional-medicine scoring engine data.
 *
 * Functional/optimal ranges are NARROWER than lab reference ranges and are
 * decision-support only (not universally validated). Markers are scored
 * OPTIMAL / SUBOPTIMAL / CRITICAL against these bands. Sex-specific bands are
 * keyed 'M' / 'F'. This is clinical decision-support, NOT a diagnosis.
 */
class FunctionalRanges
{
    public const MARKERS = [
        // Thyroid
        'tsh'     => ['label' => 'TSH', 'unit' => 'mIU/L', 'system' => 'Thyroid', 'optimal' => [1.0, 2.0]],
        'free_t4' => ['label' => 'Free T4', 'unit' => 'pmol/L', 'system' => 'Thyroid', 'optimal' => [15, 18]],
        'free_t3' => ['label' => 'Free T3', 'unit' => 'pmol/L', 'system' => 'Thyroid', 'optimal' => [5.0, 7.0]],
        // Cardiovascular / Lipid
        'total_cholesterol' => ['label' => 'Total Cholesterol', 'unit' => 'mmol/L', 'system' => 'Cardiovascular', 'optimal' => [4.1, 5.0]],
        'ldl'     => ['label' => 'LDL-C', 'unit' => 'mmol/L', 'system' => 'Cardiovascular', 'optimal' => [1.8, 2.6]],
        'hdl'     => ['label' => 'HDL-C', 'unit' => 'mmol/L', 'system' => 'Cardiovascular', 'optimal' => [1.6, null]],
        'non_hdl' => ['label' => 'Non-HDL-C', 'unit' => 'mmol/L', 'system' => 'Cardiovascular', 'optimal' => [null, 2.8]],
        'triglycerides' => ['label' => 'Triglycerides', 'unit' => 'mmol/L', 'system' => 'Cardiovascular', 'optimal' => [null, 1.1], 'critical_high' => 5.6],
        'homocysteine'  => ['label' => 'Homocysteine', 'unit' => 'umol/L', 'system' => 'Cardiovascular', 'optimal' => [6, 8], 'critical_high' => 15],
        'hs_crp'  => ['label' => 'hs-CRP', 'unit' => 'mg/L', 'system' => 'Inflammatory', 'optimal' => [null, 0.5], 'critical_high' => 10],
        // Adrenal / HPA
        'cortisol_am' => ['label' => 'Cortisol (AM)', 'unit' => 'nmol/L', 'system' => 'Adrenal', 'optimal' => [414, 552], 'critical_low' => 140],
        'dhea_s'  => ['label' => 'DHEA-S', 'unit' => 'ug/dl', 'system' => 'Adrenal', 'optimal' => [150, 350]],
        // Glycaemic / Insulin
        'glucose' => ['label' => 'Fasting Glucose', 'unit' => 'mmol/L', 'system' => 'Glycaemic', 'optimal' => [4.2, 5.0], 'critical_high' => 11.1],
        'hba1c'   => ['label' => 'HbA1c', 'unit' => '%', 'system' => 'Glycaemic', 'optimal' => [4.8, 5.4], 'critical_high' => 10.0],
        'insulin' => ['label' => 'Fasting Insulin', 'unit' => 'uIU/ml', 'system' => 'Glycaemic', 'optimal' => [2, 5]],
        // Renal / Metabolic
        'urea'       => ['label' => 'Urea', 'unit' => 'mmol/L', 'system' => 'Renal', 'optimal' => [3.5, 6.5]],
        'creatinine' => ['label' => 'Creatinine', 'unit' => 'umol/L', 'system' => 'Renal', 'optimal' => [54, 95]],
        'egfr'       => ['label' => 'eGFR', 'unit' => 'ml/min/1.73m2', 'system' => 'Renal', 'optimal' => [90, null]],
        'uric_acid'  => ['label' => 'Uric Acid', 'unit' => 'umol/L', 'system' => 'Renal', 'optimal' => [null, 360]],
        'sodium'     => ['label' => 'Sodium', 'unit' => 'mmol/L', 'system' => 'Renal', 'optimal' => [137, 142], 'critical_low' => 125],
        'potassium'  => ['label' => 'Potassium', 'unit' => 'mmol/L', 'system' => 'Renal', 'optimal' => [3.8, 4.5]],
        'magnesium'  => ['label' => 'Magnesium', 'unit' => 'mmol/L', 'system' => 'Renal', 'optimal' => [0.85, 1.05]],
        // Liver
        'alp'  => ['label' => 'ALP', 'unit' => 'U/L', 'system' => 'Liver', 'optimal' => [70, 100]],
        'alt'  => ['label' => 'ALT (SGPT)', 'unit' => 'U/L', 'system' => 'Liver', 'optimal' => [null, 26]],
        'ast'  => ['label' => 'AST (SGOT)', 'unit' => 'U/L', 'system' => 'Liver', 'optimal' => [null, 26]],
        'ggt'  => ['label' => 'GGT', 'unit' => 'U/L', 'system' => 'Liver', 'optimal' => [10, 26]],
        'fib4' => ['label' => 'FIB-4', 'unit' => '', 'system' => 'Liver', 'optimal' => [null, 1.3]],
        // Haematology / Nutrient
        'haemoglobin' => ['label' => 'Haemoglobin', 'unit' => 'g/dL', 'system' => 'Haematology', 'sex' => ['M' => [14.5, 16.5], 'F' => [12.0, 15.0]]],
        'mcv' => ['label' => 'MCV', 'unit' => 'fl', 'system' => 'Haematology', 'optimal' => [82, 89]],
        'rdw' => ['label' => 'RDW', 'unit' => '%', 'system' => 'Haematology', 'optimal' => [null, 14.5]],
        'serum_iron' => ['label' => 'Serum Iron', 'unit' => 'umol/L', 'system' => 'Nutrient', 'optimal' => [17, 28]],
        'vitamin_d'  => ['label' => 'Vitamin D (25-OH)', 'unit' => 'ng/ml', 'system' => 'Nutrient', 'optimal' => [50, 80], 'critical_low' => 20],
        // Hormones
        'testosterone' => ['label' => 'Testosterone (total)', 'unit' => 'nmol/L', 'system' => 'Hormones', 'sex' => ['M' => [12, 24], 'F' => [0.35, 2.6]]],
        'estradiol'    => ['label' => 'Estradiol (E2)', 'unit' => 'pmol/L', 'system' => 'Hormones', 'sex' => ['M' => [40, 115.6]]],
        'lh'           => ['label' => 'LH', 'unit' => 'mIU/ml', 'system' => 'Hormones', 'optimal' => [3, 8]],
        'prolactin'    => ['label' => 'Prolactin', 'unit' => 'mIU/L', 'system' => 'Hormones', 'optimal' => [56, 278]],
    ];

    public const STATUS_OPTIMAL    = 'OPTIMAL';
    public const STATUS_SUBOPTIMAL = 'SUBOPTIMAL';
    public const STATUS_CRITICAL   = 'CRITICAL';
    public const STATUS_NOTE       = 'NOTE';

    public static function definition(string $key): ?array
    {
        return self::MARKERS[$key] ?? null;
    }

    public static function band(string $key, string $sex = 'M'): ?array
    {
        $def = self::definition($key);
        if (! $def) return null;
        return $def['sex'][$sex] ?? $def['optimal'] ?? null;
    }
}
