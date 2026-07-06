<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('appointments', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->string('service')->nullable();
            $t->text('notes')->nullable();
            $t->string('dob')->nullable();
            $t->string('gender')->nullable();
            $t->string('my_kad')->nullable();
            $t->string('status')->default('new');
            $t->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void { Schema::dropIfExists('appointments'); }
};
