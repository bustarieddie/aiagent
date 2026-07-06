<?php

namespace App\Http\Controllers;

use App\Services\BotApi;

class MediaController extends Controller {
    /** GET — stream media bytes from bot with proper Content-Type. */
    public function show(string $phoneDir, string $filename, BotApi $bot) {
        $resp = $bot->streamMedia($phoneDir, $filename);
        return response($resp->body(), $resp->status())
            ->header('Content-Type', $resp->header('Content-Type', 'application/octet-stream'))
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /** DELETE — bot removes file + clears media_url in inbox_messages. */
    public function destroy(string $phoneDir, string $filename, BotApi $bot) {
        $resp = $bot->delete("/admin/api/media/{$phoneDir}/{$filename}");
        return response($resp->body(), $resp->status())->header('Content-Type', 'application/json');
    }
}
