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
        Schema::create('e_office_briefing_checklists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('briefing_id')->nullable()->index();
            $table->text('kegiatan');
            $table->boolean('is_checked')->default(false);
            $table->uuid('penerima_id')->nullable()->index();
            $table->uuid('checked_by_pejabat_id')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_office_briefing_checklists');
    }
};
