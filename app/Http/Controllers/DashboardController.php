<?php

namespace App\Http\Controllers;

use App\Models\AiConfidenceEvent;
use App\Models\Booking;
use App\Models\ConversationFlag;
use App\Services\BotApi;
use Carbon\Carbon;

class DashboardController extends Controller {
    public function index(BotApi $bot) {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $weekAhead = $today->copy()->addDays(7);

        // Portal-side (MySQL)
        $draftPending = Booking::where('source', 'ai_draft')->where('status', 'pending')->count();
        $bookingsToday = Booking::whereBetween('created_at', [$today, $tomorrow])->count();
        $bookingsConfirmedNext7d = Booking::whereIn('status', ['pending', 'paid', 'completed'])
            ->whereBetween('booking_date', [$today->toDateString(), $weekAhead->toDateString()])
            ->count();
        $takeoverCount = ConversationFlag::where('human_takeover', true)->count();
        $aiDisabledCount = ConversationFlag::where('ai_enabled', false)
            ->where('human_takeover', false)->count();

        $recentLowConfidence = AiConfidenceEvent::orderByDesc('created_at')->take(10)->get();

        // Bot-side stats (graceful degradation)
        $botStats = [];
        try {
            $resp = $bot->get('/admin/api/stats');
            if ($resp->ok()) $botStats = $resp->json();
        } catch (\Throwable) {
            $botStats = ['error' => 'bot_unreachable'];
        }

        return view('admin.dashboard', [
            'today' => $today->toDateString(),
            'portal' => compact('draftPending', 'bookingsToday', 'bookingsConfirmedNext7d', 'takeoverCount', 'aiDisabledCount'),
            'bot' => $botStats,
            'recentLowConfidence' => $recentLowConfidence,
        ]);
    }
}
