<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whats_app_sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('label');
            $t->string('doctor_name')->nullable();
            $t->string('phone')->nullable();
            $t->string('status')->default('pending');
            $t->text('prompt_addendum')->nullable();
            $t->dateTime('last_seen_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('whats_app_sessions'); }
};
