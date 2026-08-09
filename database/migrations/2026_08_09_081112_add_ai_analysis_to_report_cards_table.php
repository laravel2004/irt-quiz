<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->json('ai_analysis')->nullable()->after('report_data');
            $table->string('ai_analysis_status')->default('pending')->after('ai_analysis');
            // Nilai status: pending | processing | completed | failed
        });
    }

    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropColumn(['ai_analysis', 'ai_analysis_status']);
        });
    }
};
