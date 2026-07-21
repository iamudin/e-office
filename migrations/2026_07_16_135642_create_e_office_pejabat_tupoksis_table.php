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
        Schema::create('e_office_pejabat_tupoksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pejabat_id');
            $table->text('tugas_fungsi');
            $table->timestamps();
            
            // Assuming earsip_pejabats table uses UUID
            // If it doesn't, we should adjust accordingly, but other earsip tables use HasUuids
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_office_pejabat_tupoksis');
    }
};
