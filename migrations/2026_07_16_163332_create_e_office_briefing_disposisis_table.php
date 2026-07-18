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
        Schema::create('e_office_briefing_disposisis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('briefing_id')->nullable()->index();
            $table->uuid('pengirim_id')->nullable()->index();
            $table->uuid('penerima_id')->nullable()->index();
            
            $table->text('pesan_diteruskan')->nullable();
            $table->text('pesan_khusus')->nullable();
            $table->string('file_lampiran')->nullable();
            
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamp('dibalas_pada')->nullable();
            
            $table->text('balasan_teks')->nullable();
            $table->string('balasan_file')->nullable();
            $table->text('feedback_kadis')->nullable();
            
            $table->boolean('is_validated_by_atasan')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_office_briefing_disposisis');
    }
};
