<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller {
    /** OTP TTL in minutes. */
    const TTL_MINUTES = 10;

    /** Stage 1 — show email form. */
    public function show(Request $request) {
        if ($request->session()->get('admin_authed') === true) {
            return redirect()->route('admin.dashboard');
        }
        return view('login', ['stage' => 'email']);
    }

    /** Stage 1 handler — validate email, generate OTP, send via mail. */
    public function requestOtp(Request $request) {
        $data = $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($data['email']));

        // Whitelist check
        $allowed = array_map('trim', explode(',', (string) config('services.allowed_emails', '')));
        $allowed = array_filter(array_map('strtolower', $allowed));
        if (!in_array($email, $allowed, true)) {
            return back()->withInput()->withErrors([
                'email' => 'Email ini tak diizinkan akses portal.',
            ]);
        }

        // Rate limit — max 3 OTP per email per hour
        $key = 'otp:' . sha1($email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Terlalu banyak permintaan. Cuba lagi dalam {$seconds} saat.",
            ]);
        }
        RateLimiter::hit($key, 3600);

        // Generate 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpCode::create([
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'ip' => $request->ip(),
        ]);

        // Send email
        try {
            Mail::to($email)->send(new OtpMail($code, self::TTL_MINUTES));
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors([
                'email' => 'Gagal hantar email. Cuba semula.',
            ]);
        }

        $request->session()->put('otp_email', $email);
        return redirect()->route('login.verify.show');
    }

    /** Stage 2 — show OTP input form. */
    public function showVerify(Request $request) {
        if (!$request->session()->has('otp_email')) {
            return redirect()->route('login');
        }
        return view('login', ['stage' => 'verify', 'email' => $request->session()->get('otp_email')]);
    }

    /** Stage 2 handler — validate OTP + set session. */
    public function verifyOtp(Request $request) {
        $data = $request->validate([
            'code' => 'required|string|size:6',
        ]);
        $email = $request->session()->get('otp_email');
        if (!$email) return redirect()->route('login');

        $otp = OtpCode::where('email', $email)
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
        $request->session()->put('admin_email', $email);
        $request->session()->forget('otp_email');

        return redirect()->route('admin.dashboard');
    }

    /** Logout. */
    public function logout(Request $request) {
        $request->session()->flush();
        $request->session()->regenerate();
        return redirect()->route('login');
    }
}
