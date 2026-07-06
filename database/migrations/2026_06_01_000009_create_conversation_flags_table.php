<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conversation_flags', function (Blueprint $t) {
            $t->string('phone')->primary();
            $t->boolean('ai_enabled')->default(true);
            $t->boolean('human_takeover')->default(false);
            $t->string('status')->default('open');
            $t->boolean('pinned')->default(false);
            $t->text('staff_tags')->nullable();
            $t->text('last_note_by_staff')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['human_takeover', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('conversation_flags'); }
};
