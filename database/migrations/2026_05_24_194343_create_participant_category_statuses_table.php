<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_category_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_participant_id')
                  ->constrained('exam_session_participants')
                  ->cascadeOnDelete()
                  ->name('part_cat_status_participant_id_foreign');
            $table->foreignId('exam_session_category_id')
                  ->constrained('exam_session_categories')
                  ->cascadeOnDelete()
                  ->name('part_cat_status_category_id_foreign');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            
            // Ensures a participant can only have one status record per session category
            $table->unique(['exam_session_participant_id', 'exam_session_category_id'], 'part_cat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_category_statuses');
    }
};
