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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE question_banks MODIFY COLUMN type ENUM('pilihan_ganda', 'benar_salah', 'multiple_choice', 'multiple_benar_salah', 'essai_singkat') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE question_banks MODIFY COLUMN type ENUM('pilihan_ganda', 'benar_salah', 'multiple_choice', 'multiple_benar_salah') NOT NULL");
        }
    }
};
