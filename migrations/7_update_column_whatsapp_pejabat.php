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
        Schema::table('disposisis', function (Blueprint $table) {
            if (!Schema::hasColumn( 'disposisis', 'whatsapp_pejabat')) {
                    $table->string('whatsapp_pejabat')->nullable()->change();

            }
            });

    }
    public function down()
    {

    }
};
