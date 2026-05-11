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
        Schema::table('exam_sessions', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('discussion_pdf')->nullable()->after('max_score_irt');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn('discussion_pdf');
        });
    }
};
