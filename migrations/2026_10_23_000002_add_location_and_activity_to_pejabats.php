<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pejabats', function (Blueprint $table) {
            if (!Schema::hasColumn('pejabats', 'last_lat')) {
                $table->decimal('last_lat', 10, 8)->nullable();
                $table->decimal('last_lng', 11, 8)->nullable();
                $table->float('last_accuracy')->nullable();
                $table->text('last_location_name')->nullable();
                $table->timestamp('last_active_at')->nullable();
                $table->string('last_activity')->nullable();
                $table->text('last_activity_url')->nullable();

                $table->decimal('login_lat', 10, 8)->nullable();
                $table->decimal('login_lng', 11, 8)->nullable();
                $table->float('login_accuracy')->nullable();
                $table->text('login_location_name')->nullable();
                $table->timestamp('login_at')->nullable();
            }
        });

        if (!Schema::hasTable('e_office_pejabat_locations')) {
            Schema::create('e_office_pejabat_locations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('pejabat_id')->constrained('pejabats')->cascadeOnDelete();
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->float('accuracy')->nullable();
                $table->text('location_name')->nullable();
                $table->string('activity')->nullable();
                $table->text('activity_url')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('e_office_pejabat_locations');
        Schema::table('pejabats', function (Blueprint $table) {
            $table->dropColumn([
                'last_lat',
                'last_lng',
                'last_accuracy',
                'last_location_name',
                'last_active_at',
                'last_activity',
                'last_activity_url',
                'login_lat',
                'login_lng',
                'login_accuracy',
                'login_location_name',
                'login_at',
            ]);
        });
    }
};
