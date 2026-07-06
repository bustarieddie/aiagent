<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('key')->unique();
            $t->text('value');
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};
