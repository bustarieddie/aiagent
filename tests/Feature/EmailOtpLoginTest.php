<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the e-mail OTP admin login flow (request → verify), the allowed-list
 * gate, and the single-use / expiry rules. Resend HTTP calls are faked so no
 * mail leaves the test process.
 */
class EmailOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.allowed_emails' => 'admin@klinikbustari.com',
            'services.resend.key' => 'test-key',
            'services.resend.from' => 'no-reply@klinikbustari.com',
            'mail.from.address' => 'no-reply@klinikbustari.com',
            'mail.from.name' => 'Klinik Bustari',
        ]);
    }

    public function test_login_page_asks_for_email(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('E-mel')
            ->assertSee('Hantar Kod OTP via E-mel');
    }

    public function test_request_otp_sends_email_for_whitelisted_address(): void
    {
        Http::fake(['api.resend.com/*' => Http::response(['id' => 'email_123'], 200)]);

        // Mixed-case input must be normalised to the lower-cased whitelist entry.
        $response = $this->post('/login', ['email' => 'Admin@KlinikBustari.com']);

        $response->assertRedirect(route('login.verify.show'));
        $this->assertDatabaseHas('otp_codes', ['email' => 'admin@klinikbustari.com']);
        $this->assertSame('admin@klinikbustari.com', session('otp_email'));

        Http::assertSent(fn ($req) => str_contains($req->url(), 'api.resend.com/emails'));
    }

    public function test_request_otp_rejects_non_whitelisted_email(): void
    {
        Http::fake();

        $this->post('/login', ['email' => 'stranger@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('otp_codes', 0);
        Http::assertNothingSent();
    }

    public function test_request_otp_validates_email_format(): void
    {
        Http::fake();

        $this->post('/login', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('otp_codes', 0);
        Http::assertNothingSent();
    }

    public function test_verify_logs_in_with_valid_code(): void
    {
        $email = 'admin@klinikbustari.com';
        OtpCode::create([
            'email' => $email,
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'ip' => '127.0.0.1',
        ]);

        $response = $this->withSession(['otp_email' => $email])
            ->post('/login/verify', ['code' => '123456']);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('admin_authed'));
        $this->assertSame($email, session('admin_email'));
        $this->assertNull(session('otp_email'));
        $this->assertNotNull(OtpCode::first()->used_at, 'code should be marked used');
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $email = 'admin@klinikbustari.com';
        OtpCode::create([
            'email' => $email,
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession(['otp_email' => $email])
            ->post('/login/verify', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull(OtpCode::first()->used_at);
        $this->assertNull(session('admin_authed'));
    }

    public function test_verify_rejects_expired_code(): void
    {
        $email = 'admin@klinikbustari.com';
        OtpCode::create([
            'email' => $email,
            'code' => '123456',
            'expires_at' => now()->subMinute(),
        ]);

        $this->withSession(['otp_email' => $email])
            ->post('/login/verify', ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('admin_authed'));
    }

    public function test_used_code_cannot_be_replayed(): void
    {
        $email = 'admin@klinikbustari.com';
        OtpCode::create([
            'email' => $email,
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'used_at' => now(),
        ]);

        $this->withSession(['otp_email' => $email])
            ->post('/login/verify', ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('admin_authed'));
    }
}
