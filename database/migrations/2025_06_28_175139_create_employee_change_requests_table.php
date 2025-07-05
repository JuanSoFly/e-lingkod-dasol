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
        Schema::create('employee_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('change_type', 100); // personal_info, contact_info, emergency_contact, etc.
            $table->string('field_name', 100); // Which field is being changed
            $table->text('current_value')->nullable(); // Current value
            $table->text('requested_value'); // New requested value
            $table->text('justification'); // Reason for the change
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'implemented'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Supporting documents
            $table->json('supporting_documents')->nullable(); // Paths to uploaded files
            $table->text('document_notes')->nullable();
            
            // Review process
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Implementation
            $table->foreignId('implemented_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('implemented_at')->nullable();
            $table->text('implementation_notes')->nullable();
            
            // Additional metadata
            $table->json('additional_data')->nullable(); // For complex change requests
            $table->boolean('requires_approval')->default(true);
            $table->boolean('auto_implementable')->default(false);
            $table->date('effective_date')->nullable(); // When the change should take effect
            
            // Audit trail
            $table->json('audit_trail')->nullable(); // Complete history of status changes
            $table->json('validation_errors')->nullable(); // Any validation issues
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['employee_id', 'status']);
            $table->index(['requested_by_user_id', 'created_at']);
            $table->index(['change_type', 'status']);
            $table->index(['field_name', 'status']);
            $table->index('effective_date');
            $table->index(['requires_approval', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_change_requests');
    }
};
