<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('otp_codes', function (Blueprint $t) {
            $t->id();
            $t->string('email');
            $t->string('code', 6);
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->string('ip')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['email', 'code']);
            $t->index('expires_at');
        });
    }
    public function down(): void { Schema::dropIfExists('otp_codes'); }
};
