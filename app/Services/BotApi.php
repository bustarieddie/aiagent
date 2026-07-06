<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for the Python bot's /admin/api/* endpoints.
 *
 * Handles auth (X-Admin-Key header) and base URL config in one place so
 * controllers stay tidy. Returns the raw Illuminate Http Response so
 * callers can json(), status(), etc.
 */
class BotApi {
    protected string $baseUrl;
    protected string $adminKey;

    public function __construct() {
        $this->baseUrl = rtrim(config('services.bot.url', ''), '/');
        $this->adminKey = config('services.bot.admin_key', '');
    }

    protected function http(): PendingRequest {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['X-Admin-Key' => $this->adminKey])
            ->acceptJson()
            ->timeout(15);
    }

    public function get(string $path, array $query = []): Response {
        return $this->http()->get($path, $query);
    }

    public function post(string $path, array $body = []): Response {
        return $this->http()->post($path, $body);
    }

    public function patch(string $path, array $body = []): Response {
        return $this->http()->patch($path, $body);
    }

    public function delete(string $path): Response {
        return $this->http()->delete($path);
    }

    /** Stream a media file's raw bytes (for the media proxy route). */
    public function streamMedia(string $phoneDir, string $filename): Response {
        return $this->http()->withOptions(['stream' => false])
            ->get("/admin/api/media/{$phoneDir}/{$filename}");
    }
}
