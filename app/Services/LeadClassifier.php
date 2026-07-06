<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Reads a lead's WhatsApp conversation and asks Claude Haiku to pick
 * the most likely `service_interested` label (khatan / minor_surgery /
 * knee_pain / diabetes). Returns null when the conversation is too thin
 * or the model comes back unsure — we prefer leaving the field empty
 * over guessing.
 */
class LeadClassifier {
    protected BotApi $bot;
    protected string $apiKey;
    protected string $model;

    public function __construct(BotApi $bot) {
        $this->bot = $bot;
        $this->apiKey = (string) config('services.anthropic.api_key', '');
        $this->model  = (string) config('services.anthropic.model', 'claude-haiku-4-5-20251001');
    }

    public function classify(string $phone): ?array {
        if (empty($this->apiKey)) return null;

        $resp = $this->bot->get('/admin/api/conversations', ['phone' => $phone, 'limit' => 100]);
        if (!$resp->ok()) return null;
        $messages = $resp->json()['messages'] ?? [];

        $patientText = collect($messages)
            ->filter(fn ($m) => ($m['direction'] ?? '') === 'in')
            ->pluck('body')
            ->filter()
            ->take(30)
            ->implode("\n---\n");

        if (strlen(trim($patientText)) < 5) return null;

        $prompt = "Anda AI klasifikasi servis klinik. Baca mesej pesakit di bawah dan pilih SATU servis yang paling sesuai.\n\n"
            . "MESEJ PESAKIT (dari WhatsApp):\n{$patientText}\n\n"
            . "PILIHAN SERVIS:\n"
            . "- khatan (circumcision anak/dewasa)\n"
            . "- minor_surgery (buang lipoma, ketumbuhan, kista, tanggal kuku, jahitan luka)\n"
            . "- knee_pain (sakit lutut, osteoarthritis, cedera sendi, PRP)\n"
            . "- diabetes (gula tinggi, HbA1c, insulin, program diabetes reset)\n"
            . "- unknown (tak cukup info atau tak berkaitan)\n\n"
            . "Return JSON sahaja, no explanation:\n"
            . '{"service": "...", "confidence": "high|medium|low", "reason": "1 ayat pendek BM"}';

        try {
            $r = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 200,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
            if (!$r->successful()) return null;

            $text = $r->json()['content'][0]['text'] ?? '';
            if (!preg_match('/\{.*\}/s', $text, $m)) return null;
            $parsed = json_decode($m[0], true);
            if (!is_array($parsed)) return null;

            $service = $parsed['service'] ?? null;
            $conf    = $parsed['confidence'] ?? 'low';
            $reason  = $parsed['reason'] ?? '';
            $valid   = ['khatan', 'minor_surgery', 'knee_pain', 'diabetes'];

            if (!in_array($service, $valid, true) || $conf === 'low') {
                return ['service' => null, 'confidence' => $conf, 'reason' => $reason];
            }

            return ['service' => $service, 'confidence' => $conf, 'reason' => $reason];
        } catch (\Throwable) {
            return null;
        }
    }
}
