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
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['pilihan_ganda', 'benar_salah', 'multiple_choice']);
            $table->text('question_text');
            $table->string('question_image')->nullable();
            $table->json('options')->nullable(); // nullable for benar_salah if we handle it differently
            $table->json('correct_answer'); // Store as JSON for multiple choice
            $table->float('difficulty')->default(0.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
