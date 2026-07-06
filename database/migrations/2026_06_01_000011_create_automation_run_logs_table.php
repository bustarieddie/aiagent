<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('automation_run_logs', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('automation_key');
            $t->timestamp('run_at')->useCurrent();
            $t->string('status');
            $t->integer('sent_count')->default(0);
            $t->text('error_msg')->nullable();
            $t->integer('duration_ms')->nullable();
            $t->index(['automation_key', 'run_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('automation_run_logs'); }
};
