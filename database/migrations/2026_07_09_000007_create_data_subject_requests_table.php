<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PDPA s.30-43: access(21-day SLA)/correction/withdraw/portability/erasure.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_subject_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');    // access|correction|withdraw|portability|object|erasure
            $t->string('status')->default('open');
            $t->text('detail')->nullable();
            $t->timestamp('due_at')->nullable();      // created_at + 21 days
            $t->timestamp('fulfilled_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('data_subject_requests'); }
};
