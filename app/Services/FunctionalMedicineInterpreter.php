<?php

namespace App\Services;

use App\Support\FunctionalRanges as FR;

/**
 * Interpretation engine: scores each marker against functional ranges,
 * computes derived ratios, and assembles the ranked critical findings.
 *
 * This is clinical DECISION-SUPPORT, not a diagnosis. Output must always be
 * reviewed by a qualified clinician.
 */
class FunctionalMedicineInterpreter
{
    public const ENGINE_VERSION = '1.0.0';

    /**
     * @param array<string,float> $values  marker_key => numeric value
     * @param string $sex 'M'|'F'
     * @return array{markers:array,ratios:array,critical:array}
     */
    public function interpret(array $values, string $sex = 'M'): array
    {
        $markers = [];
        foreach ($values as $key => $value) {
            if (! FR::definition($key) || $value === null || $value === '') {
                continue;
            }
            $markers[$key] = $this->scoreMarker($key, (float) $value, $sex);
        }

        $ratios   = $this->ratios($values);
        $critical = $this->rankCritical($markers, $ratios);

        return compact('markers', 'ratios', 'critical');
    }

    private function scoreMarker(string $key, float $value, string $sex): array
    {
        $def  = FR::definition($key);
        $band = FR::band($key, $sex);
        [$low, $high] = $band ?? [null, null];

        $status = FR::STATUS_OPTIMAL;

        // Hard critical thresholds first.
        if (isset($def['critical_high']) && $value >= $def['critical_high']) {
            $status = FR::STATUS_CRITICAL;
        } elseif (isset($def['critical_low']) && $value <= $def['critical_low']) {
            $status = FR::STATUS_CRITICAL;
        } elseif ($low !== null && $value < $low) {
            $status = FR::STATUS_SUBOPTIMAL;
        } elseif ($high !== null && $value > $high) {
            $status = FR::STATUS_SUBOPTIMAL;
        }

        return [
            'key'      => $key,
            'label'    => $def['label'],
            'unit'     => $def['unit'],
            'system'   => $def['system'],
            'value'    => $value,
            'band'     => $band,
            'status'   => $status,
        ];
    }

    /** Derived ratios computed only when both components exist. */
    private function ratios(array $v): array
    {
        $out = [];
        $has = fn (string $k) => isset($v[$k]) && is_numeric($v[$k]) && (float) $v[$k] != 0.0;

        if ($has('free_t3') && $has('free_t4')) {
            $r = round($v['free_t3'] / $v['free_t4'], 3);
            $out['ft3_ft4'] = ['label' => 'FT3:FT4', 'value' => $r, 'target' => '>0.30',
                'status' => $r >= 0.30 ? FR::STATUS_OPTIMAL : FR::STATUS_SUBOPTIMAL];
        }
        if ($has('total_cholesterol') && $has('hdl')) {
            $r = round($v['total_cholesterol'] / $v['hdl'], 2);
            $out['tc_hdl'] = ['label' => 'TC:HDL', 'value' => $r, 'target' => '<3.5',
                'status' => $r < 3.5 ? FR::STATUS_OPTIMAL : FR::STATUS_SUBOPTIMAL];
        }
        if ($has('triglycerides') && $has('hdl')) {
            $r = round($v['triglycerides'] / $v['hdl'], 2);
            $out['tg_hdl'] = ['label' => 'TG:HDL', 'value' => $r, 'target' => '<1.0 (>2.0 critical)',
                'status' => $r > 2.0 ? FR::STATUS_CRITICAL : ($r < 1.0 ? FR::STATUS_OPTIMAL : FR::STATUS_SUBOPTIMAL)];
        }
        if ($has('glucose') && $has('insulin')) {
            $r = round(($v['glucose'] * $v['insulin']) / 22.5, 2);
            $out['homa_ir'] = ['label' => 'HOMA-IR', 'value' => $r, 'target' => '<1.5',
                'status' => $r >= 2.9 ? FR::STATUS_CRITICAL : ($r < 1.5 ? FR::STATUS_OPTIMAL : FR::STATUS_SUBOPTIMAL)];
        }
        return $out;
    }

    /** Rank CRITICAL markers + critical ratios by urgency (severity of excursion). */
    private function rankCritical(array $markers, array $ratios): array
    {
        $crit = [];
        foreach ($markers as $m) {
            if ($m['status'] === FR::STATUS_CRITICAL) {
                $crit[] = $m;
            }
        }
        foreach ($ratios as $key => $r) {
            if ($r['status'] === FR::STATUS_CRITICAL) {
                $crit[] = ['key' => $key, 'label' => $r['label'], 'value' => $r['value'],
                    'unit' => '', 'system' => 'Ratio', 'band' => null, 'status' => FR::STATUS_CRITICAL];
            }
        }
        return $crit;
    }
}
