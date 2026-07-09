<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PDPA s.40: IC number + health context are sensitive -> encrypted at rest.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->nullable()->index(); // multi-tenant scope for IDOR defence
            $t->text('name');            // encrypted cast
            $t->text('ic_number')->nullable(); // encrypted cast
            $t->char('sex', 1)->nullable();    // M|F
            $t->unsignedTinyInteger('age')->nullable();
            $t->softDeletes();           // s.10 retention / erasure
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('patients'); }
};
