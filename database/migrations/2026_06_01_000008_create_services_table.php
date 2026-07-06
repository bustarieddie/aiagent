<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('name_en')->nullable();
            $t->string('category');
            $t->text('short_desc')->nullable();
            $t->text('long_desc')->nullable();
            $t->integer('duration_min')->nullable();
            $t->text('price_tiers')->default('[]');
            $t->integer('base_price_myr')->nullable();
            $t->string('cta_label')->nullable();
            $t->string('cta_intent')->nullable();
            $t->boolean('visible_on_site')->default(true);
            $t->boolean('visible_to_ai')->default(true);
            $t->integer('sort_order')->default(100);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['category', 'visible_on_site']);
        });
    }
    public function down(): void { Schema::dropIfExists('services'); }
};
