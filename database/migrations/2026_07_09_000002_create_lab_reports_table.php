<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lab_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('lab_no')->nullable()->index();
            $t->string('panel')->nullable();
            $t->date('collected_at')->nullable();
            $t->date('reported_at')->nullable();
            $t->string('source_pdf_path')->nullable(); // private disk, randomized name
            $t->text('raw_text')->nullable();          // encrypted cast (health data)
            $t->softDeletes();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('lab_reports'); }
};
