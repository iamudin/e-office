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
        Schema::table('earsip_briefing_disposisis', function (Blueprint $table) {
            $table->boolean('is_validated_by_atasan')->default(false)->after('dibalas_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('earsip_briefing_disposisis', function (Blueprint $table) {
            $table->dropColumn('is_validated_by_atasan');
        });
    }
};
