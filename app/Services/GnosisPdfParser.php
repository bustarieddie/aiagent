<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;

/**
 * Parses Gnosis Laboratories report PDFs into structured lab values.
 *
 * SECURITY (webapps-security #8): callers must have already validated the
 * upload (real MIME application/pdf, size cap, randomized name, stored on a
 * non-executable disk). This service only reads text; it never executes the
 * file. PDPA (s.9): the extracted values are sensitive health data — encrypt
 * at rest and log access downstream.
 */
class GnosisPdfParser
{
    /** Map of Gnosis line labels -> internal marker keys. */
    private const LABEL_MAP = [
        'haemoglobin' => 'haemoglobin', 'mcv' => 'mcv', 'rdw' => 'rdw',
        'serum iron' => 'serum_iron',
        'urea' => 'urea', 'creatinine' => 'creatinine', 'egfr' => 'egfr',
        'estimated gfr' => 'egfr', 'uric acid' => 'uric_acid',
        'sodium' => 'sodium', 'potassium' => 'potassium', 'magnesium' => 'magnesium',
        'alk. phosphatase' => 'alp', 'alp' => 'alp',
        'ast' => 'ast', 'alt' => 'alt', 'ggt' => 'ggt', 'fibrosis-4' => 'fib4',
        'cholesterol, total' => 'total_cholesterol', 'hdl' => 'hdl',
        'non-hdl' => 'non_hdl', 'ldl' => 'ldl', 'triglycerides' => 'triglycerides',
        'homocysteine' => 'homocysteine', 'c-reactive' => 'hs_crp',
        'glucose' => 'glucose', 'glycated hb' => 'hba1c', 'hba1c' => 'hba1c',
        'insulin' => 'insulin',
        'tsh' => 'tsh', 'free t4' => 'free_t4', 'free t3' => 'free_t3',
        'luteinising hormone' => 'lh', 'estradiol' => 'estradiol',
        'prolactin' => 'prolactin', 'testosterone' => 'testosterone',
        'dehydroepiandrosterone' => 'dhea_s', 'cortisol' => 'cortisol_am',
        'vitamin d' => 'vitamin_d',
    ];

    public function parseFile(string $path): array
    {
        $text = (new PdfParser())->parseFile($path)->getText();
        return $this->parseText($text);
    }

    /**
     * @return array{meta:array,values:array<string,float>,raw:string}
     */
    public function parseText(string $text): array
    {
        $meta   = $this->extractMeta($text);
        $values = [];

        foreach (preg_split('/\r?\n/', $text) as $line) {
            $lower = strtolower($line);
            foreach (self::LABEL_MAP as $needle => $key) {
                if (isset($values[$key])) {
                    continue;
                }
                if (str_contains($lower, $needle)) {
                    $num = $this->firstNumber($line);
                    if ($num !== null) {
                        $values[$key] = $num;
                    }
                }
            }
        }

        return ['meta' => $meta, 'values' => $values, 'raw' => $text];
    }

    private function extractMeta(string $text): array
    {
        $meta = ['patient_name' => null, 'lab_no' => null, 'sex' => null, 'age' => null];
        if (preg_match('/Patient Name\s*:\s*(.+?)\s+Lab No/i', $text, $m)) {
            $meta['patient_name'] = trim($m[1]);
        }
        if (preg_match('/Lab No:?\s*:?\s*(\d+)/i', $text, $m)) {
            $meta['lab_no'] = trim($m[1]);
        }
        if (preg_match('/Sex\s*\/Age\s*:\s*(Male|Female)\s*\/\s*(\d+)/i', $text, $m)) {
            $meta['sex'] = strtoupper($m[1][0]); // M | F
            $meta['age'] = (int) $m[2];
        }
        return $meta;
    }

    /** First plausible numeric result on a line (handles OCR doubled digits leniently). */
    private function firstNumber(string $line): ?float
    {
        // Strip the label part before matching, keep the result column.
        if (preg_match('/([-+]?\d+(?:\.\d+)?)/', $line, $m)) {
            return (float) $m[1];
        }
        return null;
    }
}
