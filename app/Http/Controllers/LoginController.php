<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Services\ResendClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller {
    const TTL_MINUTES = 10;

    public function show(Request $request) {
        if ($request->session()->get('admin_authed') === true) {
            return redirect()->route('admin.dashboard');
        }
        return view('login', ['stage' => 'email']);
    }

    /** Stage 1 — validate e-mail, generate OTP, send via Resend. */
    public function requestOtp(Request $request, ResendClient $mailer) {
        $data = $request->validate(['email' => 'required|string|email:rfc|max:255']);
        $email = strtolower(trim($data['email']));

        // Whitelist check
        $allowed = collect(explode(',', (string) config('services.allowed_emails', '')))
            ->map(fn ($e) => strtolower(trim($e)))
            ->filter()
            ->values()->all();
        if (!in_array($email, $allowed, true)) {
            return back()->withInput()->withErrors([
                'email' => 'E-mel ini tak diizinkan akses portal.',
            ]);
        }

        // Rate limit — max 10 OTP per e-mail per hour
        $key = 'otp:' . sha1($email);
        if (RateLimiter::tooManyAttempts($key, 10)) {
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

        // Send via Resend
        $subject = 'Kod Login Klinik Bustari Admin';
        $html = view('emails.otp', [
            'code' => $code,
            'ttl' => self::TTL_MINUTES,
        ])->render();
        $ok = $mailer->sendHtml($email, $subject, $html);
        if (!$ok) {
            return back()->withInput()->withErrors([
                'email' => 'Gagal hantar OTP via e-mel. Cuba semula atau hubungi admin.',
            ]);
        }

        $request->session()->put('otp_email', $email);
        return redirect()->route('login.verify.show');
    }

    public function showVerify(Request $request) {
        if (!$request->session()->has('otp_email')) {
            return redirect()->route('login');
        }
        return view('login', [
            'stage' => 'verify',
            'email' => $request->session()->get('otp_email'),
        ]);
    }

    public function verifyOtp(Request $request) {
        $data = $request->validate(['code' => 'required|string|size:6']);
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

    public function logout(Request $request) {
        $request->session()->flush();
        $request->session()->regenerate();
        return redirect()->route('login');
    }
}
