<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('booking_ref')->unique();
            $t->string('guardian_name');
            $t->string('patient_name');
            $t->integer('patient_age');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->string('booking_date');
            $t->string('booking_time');
            $t->text('notes')->nullable();
            $t->string('category')->nullable();
            $t->string('status')->default('pending');
            $t->string('payment_status')->default('pending');
            $t->string('bill_code')->nullable();
            $t->string('bill_ref')->nullable();
            $t->integer('amount')->default(0);
            $t->string('source')->nullable()->default('manual');
            $t->string('draft_from_phone')->nullable();
            $t->string('draft_from_session')->nullable();
            $t->string('draft_to_phone')->nullable();
            $t->string('draft_reviewed_by')->nullable();
            $t->dateTime('draft_reviewed_at')->nullable();
            $t->text('ai_collected_data')->nullable();
            $t->text('draft_ai_message')->nullable();
            $t->text('draft_edited_message')->nullable();
            $t->string('draft_edited_by')->nullable();
            $t->dateTime('draft_edited_at')->nullable();
            $t->timestamps();
            $t->index(['source', 'status', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('bookings'); }
};
