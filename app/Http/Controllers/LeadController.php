<?php

namespace App\Http\Controllers;

use App\Services\BotApi;
use Illuminate\Http\Request;

class LeadController extends Controller {
    public function index() {
        return view('admin.leads');
    }

    public function list(Request $request, BotApi $bot) {
        $resp = $bot->get('/admin/api/leads', $request->query());
        return response($resp->body(), $resp->status())
            ->header('Content-Type', 'application/json');
    }

    public function update(Request $request, string $phone, BotApi $bot) {
        $resp = $bot->patch('/admin/api/leads/' . urlencode($phone), $request->all());
        return response($resp->body(), $resp->status())
            ->header('Content-Type', 'application/json');
    }
}
