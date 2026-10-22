<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('e_office_briefing_checklists') && !Schema::hasColumn('e_office_briefing_checklists', 'status')) {
            Schema::table('e_office_briefing_checklists', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('kegiatan');
            });
            \Illuminate\Support\Facades\DB::table('e_office_briefing_checklists')
                ->where('is_checked', true)
                ->update(['status' => 'selesai']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('e_office_briefing_checklists') && Schema::hasColumn('e_office_briefing_checklists', 'status')) {
            Schema::table('e_office_briefing_checklists', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
