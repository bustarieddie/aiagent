<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('slots', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('date');
            $t->string('time');
            $t->integer('max_bookings')->default(3);
            $t->boolean('is_blocked')->default(false);
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['date', 'time']);
        });
    }
    public function down(): void { Schema::dropIfExists('slots'); }
};
