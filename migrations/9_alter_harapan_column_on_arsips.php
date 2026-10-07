<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            DB::statement('ALTER TABLE arsips MODIFY harapan TEXT NULL');
        });
    }

    public function down()
    {
        Schema::table('arsips', function (Blueprint $table) {
            DB::statement('ALTER TABLE arsips MODIFY harapan VARCHAR(191) NULL');
        });
    }
};
