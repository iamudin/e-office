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
            if (!Schema::hasColumn('e_office_briefing_disposisis', 'notif_wa_09_at')) {
                $table->timestamp('notif_wa_09_at')->nullable()->after('is_validated_by_atasan');
            }
            if (!Schema::hasColumn('e_office_briefing_disposisis', 'notif_wa_14_at')) {
                $table->timestamp('notif_wa_14_at')->nullable()->after('notif_wa_09_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('e_office_briefing_disposisis', function (Blueprint $table) {
            $table->dropColumn(['notif_wa_09_at', 'notif_wa_14_at']);
        });
    }
};
