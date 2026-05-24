<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns to exam_session_categories
        Schema::table('exam_session_categories', function (Blueprint $table) {
            $table->integer('duration')->default(0)->after('category_id'); // in minutes
            $table->integer('total_questions')->default(0)->after('duration');
            $table->integer('max_score_raw')->default(100)->after('total_questions');
            $table->integer('max_score_irt')->default(1000)->after('max_score_raw');
            $table->dropColumn('percentage');
        });

        // Drop columns from exam_sessions
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['duration', 'total_questions', 'max_score_raw', 'max_score_irt']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->integer('duration')->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('max_score_raw')->default(100);
            $table->integer('max_score_irt')->default(1000);
        });

        Schema::table('exam_session_categories', function (Blueprint $table) {
            $table->integer('percentage')->default(100);
            $table->dropColumn(['duration', 'total_questions', 'max_score_raw', 'max_score_irt']);
        });
    }
};
