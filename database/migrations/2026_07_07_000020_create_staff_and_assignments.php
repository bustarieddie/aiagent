<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('staff_members', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedTinyInteger('weight')->default(1);      // for weighted RR (unused MVP)
            $t->unsignedInteger('assigned_count')->default(0);  // cached
            $t->timestamps();
        });

        Schema::create('lead_assignments', function (Blueprint $t) {
            $t->id();
            $t->string('phone')->unique();
            $t->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $t->string('method')->default('manual');            // auto | manual
            $t->timestamp('assigned_at')->nullable();
            $t->timestamps();
            $t->index('staff_member_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('lead_assignments');
        Schema::dropIfExists('staff_members');
    }
};
