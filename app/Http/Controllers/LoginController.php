<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Services\WaSenderClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller {
    const TTL_MINUTES = 10;

    public function show(Request $request) {
        if ($request->session()->get('admin_authed') === true) {
            return redirect()->route('admin.dashboard');
        }
        return view('login', ['stage' => 'phone']);
    }

    /** Stage 1 — validate phone, generate OTP, send via WhatsApp. */
    public function requestOtp(Request $request, WaSenderClient $wa) {
        $data = $request->validate(['phone' => 'required|string|min:8']);
        $phone = $wa->normalizeE164($data['phone']);

        // Whitelist check
        $allowed = collect(explode(',', (string) config('services.allowed_phones', '')))
            ->map(fn ($p) => $wa->normalizeE164(trim($p)))
            ->filter()
            ->values()->all();
        if (!in_array($phone, $allowed, true)) {
            return back()->withInput()->withErrors([
                'phone' => 'Nombor ini tak diizinkan akses portal.',
            ]);
        }

        // Rate limit — max 10 OTP per phone per hour
        $key = 'otp:' . sha1($phone);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'phone' => "Terlalu banyak permintaan. Cuba lagi dalam {$seconds} saat.",
            ]);
        }
        RateLimiter::hit($key, 3600);

        // Generate 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'ip' => $request->ip(),
        ]);

        // Send via WhatsApp
        $msg = "🔐 Klinik Bustari Admin\n\nKod login sekali guna: *{$code}*\n\nSah selama " . self::TTL_MINUTES . " minit.\nJangan share dengan sesiapa.";
        $ok = $wa->sendText($phone, $msg);
        if (!$ok) {
            return back()->withInput()->withErrors([
                'phone' => 'Gagal hantar OTP via WhatsApp. Cuba semula atau hubungi admin.',
            ]);
        }

        $request->session()->put('otp_phone', $phone);
        return redirect()->route('login.verify.show');
    }

    public function showVerify(Request $request) {
        if (!$request->session()->has('otp_phone')) {
            return redirect()->route('login');
        }
        return view('login', [
            'stage' => 'verify',
            'phone' => $request->session()->get('otp_phone'),
        ]);
    }

    public function verifyOtp(Request $request) {
        $data = $request->validate(['code' => 'required|string|size:6']);
        $phone = $request->session()->get('otp_phone');
        if (!$phone) return redirect()->route('login');

        $otp = OtpCode::where('phone', $phone)
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$otp) {
            return back()->withErrors(['code' => 'Kod salah atau dah expired. Cuba semula.']);
        }

        $otp->update(['used_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put('admin_authed', true);
        $request->session()->put('admin_phone', $phone);
        $request->session()->forget('otp_phone');

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request) {
        $request->session()->flush();
        $request->session()->regenerate();
        return redirect()->route('login');
    }
}
