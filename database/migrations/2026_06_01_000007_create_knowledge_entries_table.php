<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('knowledge_entries', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('key')->unique();
            $t->text('value');
            $t->string('lang')->default('bm');
            $t->string('category');
            $t->text('notes')->nullable();
            $t->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['category', 'lang']);
        });
    }
    public function down(): void { Schema::dropIfExists('knowledge_entries'); }
};
