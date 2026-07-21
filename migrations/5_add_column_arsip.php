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
        Schema::table('arsips', function (Blueprint $table) {
            if (!Schema::hasColumn('arsips', 'kadis_id')) {
                $table->foreignUUId('kadis_id')->nullable();
            }
        });

    }
    public function down()
    {

    }
};
