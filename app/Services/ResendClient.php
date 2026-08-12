<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resend transactional-email sender.
 *
 * Endpoint: POST https://api.resend.com/emails
 *   Headers: Authorization: Bearer <RESEND_API_KEY>
 *   Body:    { "from": "...", "to": ["..."], "subject": "...", "html": "..." }
 *
 * Uses the Resend HTTP API directly (mirroring WaSenderClient) so no extra
 * mail transport package is required. The sender address must belong to a
 * domain verified in the Resend dashboard.
 */
class ResendClient {
    protected string $apiKey;
    protected string $endpoint = 'https://api.resend.com/emails';

    public function __construct() {
        $this->apiKey = (string) config('services.resend.key', '');
    }

    /** Send an HTML e-mail. Returns true on 2xx, false otherwise. */
    public function sendHtml(string $to, string $subject, string $html): bool {
        if (empty($this->apiKey)) return false;

        $fromAddress = (string) config('services.resend.from', config('mail.from.address'));
        $fromName = (string) config('mail.from.name', 'Klinik Bustari');
        $from = $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress;

        try {
            $resp = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(10)
                ->post($this->endpoint, [
                    'from' => $from,
                    'to' => [$to],
                    'subject' => $subject,
                    'html' => $html,
                ]);

            if (!$resp->successful()) {
                // Never log the recipient address or code (PDPA — minimise
                // personal-data logging); status only.
                Log::warning('Resend OTP send failed', ['status' => $resp->status()]);
            }
            return $resp->successful();
        } catch (\Throwable $e) {
            Log::warning('Resend OTP send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
