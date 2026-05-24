<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_questions', function (Blueprint $table) {
            $table->decimal('difficulty', 8, 4)->nullable()->after('question_bank_id');
        });
    }

    public function down(): void
    {
        Schema::table('session_questions', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });
    }
};
