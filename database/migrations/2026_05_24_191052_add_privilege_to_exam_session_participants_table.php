<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_session_participants', function (Blueprint $table) {
            $table->enum('privilege', ['general', 'premium'])->default('general')->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('exam_session_participants', function (Blueprint $table) {
            $table->dropColumn('privilege');
        });
    }
};
