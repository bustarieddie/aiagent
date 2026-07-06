<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_confidence_events', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('phone');
            $t->string('session_id')->nullable();
            $t->float('confidence');
            $t->string('intent')->nullable();
            $t->text('reason')->nullable();
            $t->string('message_id')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['phone', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('ai_confidence_events'); }
};
