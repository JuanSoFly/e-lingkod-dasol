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
        Schema::create('document_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('document_approval_requests')->cascadeOnDelete();
            $table->integer('step_number');
            $table->string('step_name');
            $table->foreignId('approver_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('approver_role')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable(); // Additional data for the step
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['request_id', 'step_number']);
            $table->index(['approver_id', 'status']);
            $table->index(['status', 'deadline']);
            $table->unique(['request_id', 'step_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approval_steps');
    }
};