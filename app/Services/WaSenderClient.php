<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * WaSenderAPI text-message sender.
 *
 * Endpoint: POST https://www.wasenderapi.com/api/send-message
 *   Headers: Authorization: Bearer <API_KEY>
 *   Body:    { "to": "+60123456789", "text": "..." }
 */
class WaSenderClient {
    protected string $apiKey;
    protected string $endpoint = 'https://www.wasenderapi.com/api/send-message';

    public function __construct() {
        $this->apiKey = (string) config('services.wasender.api_key', '');
    }

    /** Send a text message. Returns true on 2xx, false otherwise. */
    public function sendText(string $phone, string $text): bool {
        if (empty($this->apiKey)) return false;

        $phone = $this->normalizeE164($phone);
        try {
            $resp = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(10)
                ->post($this->endpoint, [
                    'to' => $phone,
                    'text' => $text,
                ]);
            return $resp->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Normalize a Malaysian phone number to +60XXXXXXXXX.
     * Accepts: 0123456789, +60123456789, 60123456789, 123456789
     */
    public function normalizeE164(string $phone): string {
        $d = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($d, '+')) $d = substr($d, 1);
        if (str_starts_with($d, '00')) $d = substr($d, 2);
        if (str_starts_with($d, '0')) $d = '60' . substr($d, 1);
        elseif (str_starts_with($d, '1') && strlen($d) >= 9 && strlen($d) <= 11) $d = '60' . $d;
        return '+' . $d;
    }
}
