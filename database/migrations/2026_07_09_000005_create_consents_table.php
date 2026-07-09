<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PDPA s.6/s.7/s.40: consent recorded as an immutable event, incl. EXPLICIT
// consent for sensitive health data. Never a bare boolean on the patient row.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $t) {
            $t->id();
            $t->morphs('consentable');                 // patient
            $t->string('purpose');                     // 'health_processing','report_generation'
            $t->string('basis')->default('explicit_consent');
            $t->boolean('granted');
            $t->string('notice_version');
            $t->ipAddress('ip')->nullable();
            $t->string('user_agent')->nullable();
            $t->timestamp('granted_at')->nullable();
            $t->timestamp('withdrawn_at')->nullable(); // s.38
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('consents'); }
};
