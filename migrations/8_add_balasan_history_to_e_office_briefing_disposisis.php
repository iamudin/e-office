<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('e_office_briefing_disposisis', function (Blueprint $table) {
            if (!Schema::hasColumn('e_office_briefing_disposisis', 'balasan_history')) {
                $table->json('balasan_history')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('e_office_briefing_disposisis', function (Blueprint $table) {
            if (Schema::hasColumn('e_office_briefing_disposisis', 'balasan_history')) {
                $table->dropColumn('balasan_history');
            }
        });
    }
};
