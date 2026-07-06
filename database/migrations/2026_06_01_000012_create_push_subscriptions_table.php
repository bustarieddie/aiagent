<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('push_subscriptions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('endpoint', 500)->unique();  // Push URLs stay under 500 chars in practice
            $t->string('p256dh');
            $t->string('auth');
            $t->string('doctor_name')->nullable();
            $t->string('session_id')->nullable();
            $t->string('label')->nullable();
            $t->text('user_agent')->nullable();
            $t->integer('failure_count')->default(0);
            $t->timestamp('last_seen_at')->useCurrent();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['session_id']);
            $t->index(['doctor_name']);
        });
    }
    public function down(): void { Schema::dropIfExists('push_subscriptions'); }
};
