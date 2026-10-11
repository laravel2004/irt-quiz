<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->timestamp('last_weekly_reset_at')->nullable()->after('xp_per_correct');
        });

        // Set nilai awal pada baris setting yang sudah ada
        DB::table('quiz_settings')->update([
            'last_weekly_reset_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn('last_weekly_reset_at');
        });
    }
};
