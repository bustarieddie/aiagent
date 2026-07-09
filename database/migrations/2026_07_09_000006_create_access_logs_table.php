<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PDPA s.9 + breach evidence: log WHO accessed WHAT, never the payload.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable();
            $t->string('action');          // viewed|exported|generated|updated|deleted
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->ipAddress('ip')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('access_logs'); }
};
