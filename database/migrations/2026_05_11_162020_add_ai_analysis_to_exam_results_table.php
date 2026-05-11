<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_results', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->text('ai_analysis')->nullable()->after('irt_score');
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn('ai_analysis');
        });
    }
};
