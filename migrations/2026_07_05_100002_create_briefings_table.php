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
        Schema::create('e_office_briefings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('judul');
            $table->text('pesan')->nullable();
            $table->string('file_lampiran')->nullable();
            $table->string('status')->default('draft');
            $table->text('feedback_kadis')->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_office_briefings');
    }
};
