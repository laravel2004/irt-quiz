<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Hapus duplikat — pertahankan baris TERBARU (id terbesar = jawaban terakhir dikirim)
            // BUKAN id terkecil, supaya jawaban lama tidak menimpa jawaban baru
            DB::statement('
                DELETE t1 FROM user_answers t1
                INNER JOIN user_answers t2
                    ON  t1.participant_id    = t2.participant_id
                    AND t1.question_bank_id  = t2.question_bank_id
                    AND t1.id < t2.id
            ');
        });

        Schema::table('user_answers', function (Blueprint $table) {
            $table->unique(['participant_id', 'question_bank_id'], 'ua_participant_question_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropUnique('ua_participant_question_unique');
        });
    }
};
