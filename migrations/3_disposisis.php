<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disposisis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('pejabat_id')->constrained('pejabats')->cascadeOnDelete();
            $table->foreignUuid('arsip_id')->constrained('arsips')->cascadeOnDelete();
            $table->foreignUuid('whatsapp_pejabat')->nullablle();
            $table->dateTime('dibaca_pada')->nullable();
            $table->string('catatan')->nullable();
            $table->string('disposisi_pdf')->nullable();
            $table->string('balasan')->nullable();
            $table->dateTime('dibalas_pada')->nullable();
            $table->dateTime('diarsip_pada')->nullable();
            $table->dateTime('teruskan_ke_whatsapp_pada')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });

    }
    public function down()
    {
        Schema::dropIfExists('disposisis');

    }
};
