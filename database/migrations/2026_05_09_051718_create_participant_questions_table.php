<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('exam_session_participants')->onDelete('cascade');
            $table->foreignId('question_bank_id')->constrained('question_banks')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_questions');
    }
};
