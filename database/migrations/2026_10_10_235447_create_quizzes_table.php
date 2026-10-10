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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('jenjang');
            $table->string('type')->default('multiple_choice');
            $table->longText('question_text');
            $table->string('question_image')->nullable();
            $table->json('options')->nullable();
            $table->json('correct_answer')->nullable();
            $table->integer('difficulty')->default(3);
            $table->decimal('score_correct', 8, 2)->default(1);
            $table->decimal('score_incorrect', 8, 2)->default(0);
            $table->longText('explanation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
