<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lab_report_id')->constrained()->cascadeOnDelete();
            $t->string('marker_key');       // internal key e.g. 'hba1c'
            $t->decimal('value', 12, 4)->nullable();
            $t->string('unit')->nullable();
            $t->string('status')->nullable(); // OPTIMAL|SUBOPTIMAL|CRITICAL|NOTE
            $t->timestamps();
            $t->index(['lab_report_id', 'marker_key']);
        });
    }
    public function down(): void { Schema::dropIfExists('lab_results'); }
};
