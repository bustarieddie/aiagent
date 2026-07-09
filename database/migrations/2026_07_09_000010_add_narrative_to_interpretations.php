<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('interpretations', function (Blueprint $t) {
            $t->json('narrative')->nullable()->after('critical'); // nodes/order/protocol
        });
    }
    public function down(): void
    {
        Schema::table('interpretations', function (Blueprint $t) {
            $t->dropColumn('narrative');
        });
    }
};
