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
        Schema::create('document_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('document_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'returned'])->default('draft');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->integer('current_step')->default(1);
            $table->json('workflow_config')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['requester_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index(['document_type', 'status']);
            $table->index(['deadline', 'status']);
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approval_requests');
    }
};