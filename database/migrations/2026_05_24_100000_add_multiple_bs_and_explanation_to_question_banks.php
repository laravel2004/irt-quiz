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
        // Add new enum value 'multiple_benar_salah' to type column
        DB::statement("ALTER TABLE question_banks MODIFY COLUMN type ENUM('pilihan_ganda', 'benar_salah', 'multiple_choice', 'multiple_benar_salah') NOT NULL");

        Schema::table('question_banks', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('correct_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });

        DB::statement("ALTER TABLE question_banks MODIFY COLUMN type ENUM('pilihan_ganda', 'benar_salah', 'multiple_choice') NOT NULL");
    }
};
