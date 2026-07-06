<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('automation_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('trigger_type');           // keyword_in | no_reply_hours | new_lead
            $t->json('trigger_config')->nullable(); // {"keywords":["harga"]} | {"hours":24} | {}
            $t->string('action_type');            // send_message | set_stage | set_tier | takeover
            $t->json('action_config')->nullable();  // {"message":"..."} | {"stage":"contacted"} | {"tier":"warm"}
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('fire_count')->default(0);
            $t->timestamp('last_fired_at')->nullable();
            $t->timestamps();
            $t->index(['is_active', 'trigger_type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('automation_rules');
    }
};
