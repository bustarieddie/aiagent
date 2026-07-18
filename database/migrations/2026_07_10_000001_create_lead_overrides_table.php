<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('lead_overrides', function (Blueprint $t) {
            $t->string('phone')->primary();
            $t->string('crm_stage')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('lead_overrides');
    }
};
