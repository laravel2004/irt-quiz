<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // nullOnDelete: kalau superadmin menghapus soal, log jawaban tidak ikut hilang
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
            $table->date('period_key');
            $table->longText('question_snapshot')->nullable();
            $table->json('answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('xp_earned')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'quiz_id', 'period_key']);
            $table->index(['user_id', 'period_key']);
            $table->index('period_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
