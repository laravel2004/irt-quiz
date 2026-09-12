<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('participant_score_display')->default('irt')->after('is_lock_quiz');
            $table->decimal('predicate_raw_kurang_min', 12, 2)->default(0);
            $table->decimal('predicate_raw_memadai_min', 12, 2)->default(0);
            $table->decimal('predicate_raw_baik_min', 12, 2)->default(0);
            $table->decimal('predicate_raw_istimewa_min', 12, 2)->default(0);
        });

        DB::table('exam_sessions')->orderBy('id')->each(function ($session) {
            $totalMax = (float) DB::table('exam_session_categories')
                ->where('exam_session_id', $session->id)
                ->sum('max_score_raw');

            DB::table('exam_sessions')->where('id', $session->id)->update([
                'predicate_raw_kurang_min' => 0,
                'predicate_raw_memadai_min' => round($totalMax * 0.50, 2),
                'predicate_raw_baik_min' => round($totalMax * 0.70, 2),
                'predicate_raw_istimewa_min' => round($totalMax * 0.85, 2),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'participant_score_display',
                'predicate_raw_kurang_min',
                'predicate_raw_memadai_min',
                'predicate_raw_baik_min',
                'predicate_raw_istimewa_min',
            ]);
        });
    }
};
