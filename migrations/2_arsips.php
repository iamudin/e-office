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
        Schema::create('arsips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('surat_dari')->nullable();
            $table->string('nomor_surat')->nullable();
            $table->string('tanggal_surat')->nullable();
            $table->string('tanggal_terima')->nullable();
            $table->string('nomor_agenda')->nullable()->comment("Otomatis Sistem");
            $table->string('sifat')->nullable();
            $table->string('file_surat')->nullable();
            $table->string('file_arsip')->nullable();
            $table->string('hal')->nullable();
            $table->string('harapan')->nullable();
            $table->string('catatan')->nullable();
            $table->dateTime('paraf_kasubagumum_pada')->nullable();
            $table->dateTime('diteruskan_ke_kadis')->nullable();
            $table->dateTime('disposisi_pada')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });

    }
    public function down()
    {
        Schema::dropIfExists('arsips');

    }
};
