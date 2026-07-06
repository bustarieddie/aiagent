<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('broadcasts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('mode')->default('freeform');       // freeform | meta_template
            $t->json('audience_filter')->nullable();       // tier/stage/service/source
            $t->text('message_body');
            $t->string('meta_template_name')->nullable();
            $t->unsignedInteger('delay_ms')->default(1500);
            $t->string('status')->default('pending');      // pending | running | done | cancelled | failed
            $t->unsignedInteger('total_count')->default(0);
            $t->unsignedInteger('sent_count')->default(0);
            $t->unsignedInteger('failed_count')->default(0);
            $t->unsignedInteger('skipped_count')->default(0);
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('broadcast_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('broadcast_id')->constrained()->cascadeOnDelete();
            $t->string('phone');
            $t->string('status')->default('pending');      // pending | sent | failed | skipped
            $t->string('failure_reason')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
            $t->index(['broadcast_id', 'status']);
            $t->index('phone');
        });
    }

    public function down(): void {
        Schema::dropIfExists('broadcast_recipients');
        Schema::dropIfExists('broadcasts');
    }
};
