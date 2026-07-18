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
        Schema::create('briefing_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('briefing_id')->constrained('briefings')->onDelete('cascade');
            $table->foreignId('parent_task_id')->nullable()->constrained('briefing_tasks')->onDelete('cascade');
            $table->string('task_name');
            $table->text('description')->nullable();
            
            // Siapa yang memberi tugas (Atasan)
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            // Siapa yang menerima tugas (Bawahan)
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            
            // Status: pending, in_progress, awaiting_approval, approved, rejected
            $table->enum('status', ['pending', 'in_progress', 'awaiting_approval', 'approved', 'rejected'])->default('pending');
            
            // Respons dari bawahan (catatan dan file lampiran)
            $table->text('response_note')->nullable();
            $table->string('response_file')->nullable();
            
            // Catatan dari atasan saat approve/reject
            $table->text('manager_note')->nullable();
            
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
        Schema::dropIfExists('briefing_tasks');
    }
};
