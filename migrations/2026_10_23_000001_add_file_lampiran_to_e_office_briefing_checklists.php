<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('e_office_briefing_checklists') && !Schema::hasColumn('e_office_briefing_checklists', 'file_lampiran')) {
            Schema::table('e_office_briefing_checklists', function (Blueprint $table) {
                $table->string('file_lampiran')->nullable()->after('checked_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('e_office_briefing_checklists') && Schema::hasColumn('e_office_briefing_checklists', 'file_lampiran')) {
            Schema::table('e_office_briefing_checklists', function (Blueprint $table) {
                $table->dropColumn('file_lampiran');
            });
        }
    }
};
