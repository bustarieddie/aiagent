<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('panels', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();   // "Panel ID" from the payor report
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('is_active');
        });
    }

    public function down(): void {
        Schema::dropIfExists('panels');
    }
};
