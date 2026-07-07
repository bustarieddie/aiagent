<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('telegram_monitor_states', function (Blueprint $t) {
            $t->string('phone')->primary();
            $t->string('last_key')->nullable();   // "<ts>|<body-hash>" of the last forwarded message
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('telegram_monitor_states');
    }
};
