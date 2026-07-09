<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interpretations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lab_report_id')->constrained()->cascadeOnDelete();
            $t->string('engine_version');
            $t->json('ratios')->nullable();
            $t->json('critical')->nullable();
            $t->string('patient_pdf_path')->nullable();
            $t->string('practitioner_pdf_path')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('interpretations'); }
};
