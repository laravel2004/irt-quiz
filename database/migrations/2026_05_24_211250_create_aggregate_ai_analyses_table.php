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
        Schema::create('aggregate_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_session_id')->constrained()->onDelete('cascade');
            $table->json('analysis_data');
            $table->timestamps();
            
            // Allow multiple aggregate analyses over time? No, usually just one per user per session,
            // we can overwrite it if they take it again, or keep a single record.
            // Let's add a unique constraint so we can easily update or create.
            $table->unique(['user_id', 'exam_session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aggregate_ai_analyses');
    }
};
