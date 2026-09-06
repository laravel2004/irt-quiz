<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_session_categories', function (Blueprint $table) {
            $table->decimal('min_score_irt', 12, 2)->default(0)->after('max_score_raw');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->decimal('predicate_kurang_min', 12, 2)->default(0);
            $table->decimal('predicate_memadai_min', 12, 2)->default(0);
            $table->decimal('predicate_baik_min', 12, 2)->default(0);
            $table->decimal('predicate_istimewa_min', 12, 2)->default(0);
        });

        DB::table('exam_sessions')->orderBy('id')->each(function ($session) {
            $totalMax = (float) DB::table('exam_session_categories')
                ->where('exam_session_id', $session->id)
                ->sum('max_score_irt');

            DB::table('exam_sessions')->where('id', $session->id)->update([
                'predicate_kurang_min' => 0,
                'predicate_memadai_min' => round($totalMax * 0.50, 2),
                'predicate_baik_min' => round($totalMax * 0.70, 2),
                'predicate_istimewa_min' => round($totalMax * 0.85, 2),
            ]);
        });

        DB::table('exam_results')->whereNotNull('ai_analysis')->update(['ai_analysis' => null]);
        DB::table('aggregate_ai_analyses')->delete();
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'predicate_kurang_min',
                'predicate_memadai_min',
                'predicate_baik_min',
                'predicate_istimewa_min',
            ]);
        });

        Schema::table('exam_session_categories', function (Blueprint $table) {
            $table->dropColumn('min_score_irt');
        });
    }
};
