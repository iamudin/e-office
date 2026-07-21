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
        Schema::create('pejabats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nip')->nullable();
            $table->string('nama')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('nohp')->nullable();
            $table->string('urutan')->nullable();
            $table->tinyInteger('penerima_disposisi')->default(0);
            $table->string('alias_jabatan')->nullable()->comment('OPERATOR','KASUBAGUMUM','KADIS','SEKRETARIS','KABID');
            $table->timestamps();
            $table->softDeletes();

        });

    }
    public function down()
    {
        Schema::dropIfExists('pejabats');

    }
};
