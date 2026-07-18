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
        Schema::table('e_office_briefing_disposisis', function (Blueprint $table) {
            $table->text('pesan_khusus')->nullable()->after('pesan_diteruskan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_office_briefing_disposisis', function (Blueprint $table) {
            $table->dropColumn('pesan_khusus');
        });
    }
};
