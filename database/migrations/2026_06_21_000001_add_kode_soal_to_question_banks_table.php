<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->string('kode_soal')->nullable()->after('sub_category_id');
            $table->index(['sub_category_id', 'kode_soal']);
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropIndex(['sub_category_id', 'kode_soal']);
            $table->dropColumn('kode_soal');
        });
    }
};
