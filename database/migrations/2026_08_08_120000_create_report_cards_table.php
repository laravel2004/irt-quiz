<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade'); // admin yang generate
            $table->json('session_ids');   // [1, 3, 5] - daftar exam_session_id yang dipilih
            $table->string('status')->default('processing'); // processing | completed | failed
            $table->json('report_data')->nullable(); // hasil raport dalam format JSON
            $table->text('error_message')->nullable(); // pesan error jika gagal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
