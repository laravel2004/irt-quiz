<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_settings', function (Blueprint $table) {
            $table->id();
            $table->string('reset_time', 5)->default('00:00');
            $table->unsignedTinyInteger('questions_per_day')->default(1);
            $table->unsignedInteger('xp_per_correct')->default(10);
            $table->timestamps();
        });

        // Tabel ini hanya punya 1 baris.
        DB::table('quiz_settings')->insert([
            'reset_time' => '00:00',
            'questions_per_day' => 1,
            'xp_per_correct' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_settings');
    }
};
