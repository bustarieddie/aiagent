<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('message_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('category')->nullable();  // reminder | promo | followup | general
            $t->text('body');
            $t->unsignedInteger('use_count')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('message_templates');
    }
};
