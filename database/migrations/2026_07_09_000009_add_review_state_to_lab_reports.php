<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Clinician review gate: a report stays 'draft' (parsed but unscored) until a
// clinician verifies the extracted values against the source PDF and confirms.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('lab_reports', function (Blueprint $t) {
            $t->string('status')->default('draft')->after('panel'); // draft|reviewed
            $t->json('draft_values')->nullable()->after('raw_text'); // parsed, pre-review
            $t->foreignId('reviewed_by')->nullable()->after('draft_values')
              ->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }
    public function down(): void
    {
        Schema::table('lab_reports', function (Blueprint $t) {
            $t->dropConstrainedForeignId('reviewed_by');
            $t->dropColumn(['status', 'draft_values', 'reviewed_at']);
        });
    }
};
