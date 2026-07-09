<?php
namespace App\Services;

use App\Support\FunctionalRanges as FR;

/**
 * Derives the report's narrative sections (dysregulation nodes, IFM order of
 * repair, protocol pointers) from the SCORED engine output using transparent
 * heuristics. This is decision-support scaffolding for the clinician to edit —
 * NOT an autonomous diagnosis or prescription. Every item is generic and
 * clinician-gated; pharmacology stays in the practitioner edition only.
 */
class NarrativeBuilder
{
    /** @param array $interp output of FunctionalMedicineInterpreter::interpret() */
    public function build(array $interp): array
    {
        $status = $this->statusMap($interp);   // marker_key/ratio_key => status
        $nodes  = $this->nodes($status);
        return [
            'nodes'          => $nodes,
            'order_of_repair'=> $this->orderOfRepair($nodes),
            'protocol'       => $this->protocol($nodes),
        ];
    }

    private function statusMap(array $interp): array
    {
        $map = [];
        foreach ($interp['markers'] ?? [] as $k => $m) { $map[$k] = $m['status']; }
        foreach ($interp['ratios'] ?? [] as $k => $r) { $map[$k] = $r['status']; }
        return $map;
    }

    private function is(array $s, string $k, array $levels): bool
    {
        return isset($s[$k]) && in_array($s[$k], $levels, true);
    }

    private function nodes(array $s): array
    {
        $C = [FR::STATUS_CRITICAL];
        $CS = [FR::STATUS_CRITICAL, FR::STATUS_SUBOPTIMAL];
        $nodes = [];

        if ($this->is($s, 'hba1c', $C) || $this->is($s, 'glucose', $C)) {
            $nodes[] = ['key' => 'glycaemia', 'label' => 'Uncontrolled glycaemia',
                'priority' => 'URGENT', 'note' => 'Requires prompt conventional diabetes management.'];
        }
        if ($this->is($s, 'homa_ir', $CS)) {
            $nodes[] = ['key' => 'insulin_resistance', 'label' => 'Insulin resistance / metabolic',
                'priority' => 'HIGH', 'note' => 'Central metabolic driver of lipids, hepatic fat, urate.'];
        }
        if ($this->is($s, 'hs_crp', $CS)) {
            $nodes[] = ['key' => 'inflammation', 'label' => 'Systemic inflammation',
                'priority' => $this->is($s,'hs_crp',$C) ? 'URGENT' : 'HIGH',
                'note' => 'Elevated hs-CRP — exclude infective/inflammatory source if markedly high.'];
        }
        if ($this->is($s, 'ldl', $CS) || $this->is($s, 'non_hdl', $CS) || $this->is($s, 'tg_hdl', $C) || $this->is($s, 'triglycerides', $CS)) {
            $nodes[] = ['key' => 'cardiovascular', 'label' => 'Cardiovascular / lipid',
                'priority' => 'HIGH', 'note' => 'Atherogenic pattern — stage CV risk (ApoB/Lp(a)); TG >5.6 = pancreatitis risk.'];
        }
        if ($this->is($s, 'homocysteine', $CS)) {
            $nodes[] = ['key' => 'methylation', 'label' => 'Methylation / homocysteine',
                'priority' => 'HIGH', 'note' => 'B12/folate/B6 status; additive vascular risk.'];
        }
        if ($this->is($s, 'vitamin_d', $CS)) {
            $nodes[] = ['key' => 'vitamin_d', 'label' => 'Vitamin D deficiency',
                'priority' => 'HIGH', 'note' => 'Repletion supports insulin sensitivity and immune function.'];
        }
        if ($this->is($s, 'ft3_ft4', $CS) || $this->is($s, 'free_t3', $CS)) {
            $nodes[] = ['key' => 'thyroid_conversion', 'label' => 'Thyroid conversion (low-T3)',
                'priority' => 'MAINTENANCE', 'note' => 'Often secondary to inflammation/illness — reassess after root correction.'];
        }
        if ($this->is($s, 'alt', $CS) || $this->is($s, 'ggt', $CS)) {
            $nodes[] = ['key' => 'hepatic', 'label' => 'Hepatic / MASLD',
                'priority' => 'MAINTENANCE', 'note' => 'Metabolic-associated steatosis — reversible; confirm with FIB-4/USS.'];
        }
        if ($this->is($s, 'cortisol_am', $C)) {
            $nodes[] = ['key' => 'hpa', 'label' => 'HPA / adrenal',
                'priority' => 'URGENT', 'note' => 'Low AM cortisol — confirm timing; exclude adrenal insufficiency.'];
        }
        if ($this->is($s, 'uric_acid', $CS)) {
            $nodes[] = ['key' => 'urate', 'label' => 'Urate',
                'priority' => 'MAINTENANCE', 'note' => 'Hyperuricaemia — lifestyle; HLA-B*58:01 before allopurinol in SEA patients.'];
        }
        return $nodes;
    }

    private function orderOfRepair(array $nodes): array
    {
        $rank = ['URGENT' => 0, 'HIGH' => 1, 'MAINTENANCE' => 2];
        usort($nodes, fn ($a, $b) => $rank[$a['priority']] <=> $rank[$b['priority']]);
        $out = [];
        foreach ($nodes as $i => $n) {
            $out[] = ['step' => $i + 1, 'node' => $n['label'], 'priority' => $n['priority'], 'why' => $n['note']];
        }
        return $out;
    }

    /** Generic, clinician-gated protocol pointers keyed to nodes. */
    private function protocol(array $nodes): array
    {
        $lib = [
            'glycaemia'          => 'Conventional diabetes Rx (likely insulin if severe); complication screen (ACR/fundi/feet).',
            'insulin_resistance' => 'Low-GI diet, resistance + aerobic training, weight loss 5–10%; myo-inositol, magnesium; consider metformin (clinician).',
            'inflammation'       => 'Identify/treat source; omega-3; recheck hs-CRP.',
            'cardiovascular'     => 'Diet/fibre; omega-3; ApoB-guided statin per risk (clinician); fibrate if TG severe.',
            'methylation'        => 'Methyl-B12 + methylfolate + B6; retest homocysteine.',
            'vitamin_d'          => 'D3 loading then maintenance; recheck 3 months.',
            'thyroid_conversion' => 'Selenium/zinc adequacy; treat upstream inflammation; no primary thyroid Rx.',
            'hepatic'            => 'Alcohol/fructose reduction; weight loss; hepatic USS.',
            'hpa'                => 'Confirm cortisol/ACTH; sleep, meal timing, stress care; refer if insufficiency.',
            'urate'              => 'Hydration, reduce fructose/alcohol/purine; monitor.',
        ];
        $out = [];
        foreach ($nodes as $n) {
            if (isset($lib[$n['key']])) {
                $out[] = ['node' => $n['label'], 'actions' => $lib[$n['key']]];
            }
        }
        return $out;
    }
}
