<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Login moves from WhatsApp OTP to e-mail OTP (delivered via Resend), so the
// identity column stored per one-time code is now an e-mail address.
// Additive rename only — no data loss, no table drop (CLAUDE.md rule 1).
return new class extends Migration {
    public function up(): void {
        Schema::table('otp_codes', function (Blueprint $t) {
            $t->renameColumn('phone', 'email');
        });
    }
    public function down(): void {
        Schema::table('otp_codes', function (Blueprint $t) {
            $t->renameColumn('email', 'phone');
        });
    }
};
