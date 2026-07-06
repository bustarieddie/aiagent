<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('automation_rules', function (Blueprint $t) {
            $t->string('slug')->nullable()->unique()->after('id');
            $t->boolean('is_system')->default(false)->after('is_active');
            $t->string('icon', 8)->nullable()->after('name');
            $t->text('description')->nullable()->after('icon');
            $t->string('schedule_label')->nullable()->after('description');
            $t->string('schedule_cron')->nullable()->after('schedule_label');
            $t->unsignedInteger('runs_last_7d')->default(0)->after('fire_count');
            $t->unsignedInteger('sent_last_7d')->default(0)->after('runs_last_7d');
        });
    }

    public function down(): void {
        Schema::table('automation_rules', function (Blueprint $t) {
            $t->dropColumn([
                'slug', 'is_system', 'icon', 'description',
                'schedule_label', 'schedule_cron',
                'runs_last_7d', 'sent_last_7d',
            ]);
        });
    }
};
