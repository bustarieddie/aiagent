<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('automation_rules', function (Blueprint $t) {
            $t->json('settings')->nullable()->after('action_config');
        });
    }

    public function down(): void {
        Schema::table('automation_rules', function (Blueprint $t) {
            $t->dropColumn('settings');
        });
    }
};
