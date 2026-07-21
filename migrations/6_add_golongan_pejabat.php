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
        Schema::table('pejabats', function (Blueprint $table) {
            if (!Schema::hasColumn( 'pejabats', 'pangkat_golongan')) {
                    $table->string('pangkat_golongan')->nullable();

            }
            });

    }
    public function down()
    {

    }
};
