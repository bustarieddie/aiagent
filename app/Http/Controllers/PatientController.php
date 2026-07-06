<?php

namespace App\Http\Controllers;

use App\Services\BotApi;
use Illuminate\Http\Request;

class PatientController extends Controller {
    public function index() {
        return view('admin.patients');
    }

    public function show(string $phone) {
        return view('admin.patient-detail', ['phone' => $phone]);
    }

    // JSON API proxies to bot
    public function list(Request $request, BotApi $bot) {
        $resp = $bot->get('/admin/api/patients', $request->query());
        return response($resp->body(), $resp->status())->header('Content-Type', 'application/json');
    }

    public function detail(string $phone, BotApi $bot) {
        $resp = $bot->get('/admin/api/patients/' . urlencode($phone));
        return response($resp->body(), $resp->status())->header('Content-Type', 'application/json');
    }

    public function update(Request $request, string $phone, BotApi $bot) {
        $resp = $bot->patch('/admin/api/patients/' . urlencode($phone), $request->all());
        return response($resp->body(), $resp->status())->header('Content-Type', 'application/json');
    }
}
