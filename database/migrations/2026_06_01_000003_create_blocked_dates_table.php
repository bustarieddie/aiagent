<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('blocked_dates', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('date')->unique();
            $t->string('reason')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void { Schema::dropIfExists('blocked_dates'); }
};
