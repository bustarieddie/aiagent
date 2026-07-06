<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('otp_codes', function (Blueprint $t) {
            $t->renameColumn('email', 'phone');
        });
    }
    public function down(): void {
        Schema::table('otp_codes', function (Blueprint $t) {
            $t->renameColumn('phone', 'email');
        });
    }
};
