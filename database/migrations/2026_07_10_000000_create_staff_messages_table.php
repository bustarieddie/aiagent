<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('staff_messages', function (Blueprint $t) {
            $t->id();
            $t->string('phone')->index();
            $t->text('body');
            $t->timestamp('sent_at');
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('staff_messages');
    }
};
