<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PDPA 2024: breach register (kept >=2y); Commissioner <=72h, individuals <=7d.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_breaches', function (Blueprint $t) {
            $t->id();
            $t->timestamp('detected_at');
            $t->text('description');
            $t->unsignedInteger('subjects_affected')->default(0);
            $t->boolean('significant_harm')->default(false);
            $t->timestamp('commissioner_notified_at')->nullable(); // <=72h
            $t->timestamp('subjects_notified_at')->nullable();     // <=7d
            $t->string('status')->default('open');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('data_breaches'); }
};
