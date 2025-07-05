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
        Schema::create('career_progression', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Position information
            $table->string('from_position')->nullable();
            $table->string('to_position');
            $table->string('from_department')->nullable();
            $table->string('to_department');
            
            // Promotion details
            $table->date('promotion_date')->index();
            $table->enum('promotion_type', [
                'Regular Promotion',
                'Merit Promotion', 
                'Lateral Transfer',
                'Reassignment',
                'Detail',
                'Secondment',
                'Acting Capacity',
                'Officer-in-Charge',
                'Temporary Assignment',
                'Demotion',
                'Other'
            ])->index();
            
            // Salary grade progression
            $table->integer('salary_grade_from')->nullable()->index();
            $table->integer('salary_grade_to')->index();
            $table->integer('step_increment_from')->nullable();
            $table->integer('step_increment_to');
            
            // Monthly salary progression (for tracking)
            $table->decimal('monthly_salary_from', 10, 2)->nullable();
            $table->decimal('monthly_salary_to', 10, 2);
            
            // Official documentation
            $table->string('appointing_authority');
            $table->string('order_number')->nullable();
            $table->string('order_series')->nullable()->comment('e.g., 2025 for the year');
            $table->date('order_date')->nullable();
            $table->date('effective_date')->index();
            
            // Status and verification
            $table->enum('status', [
                'Active',
                'Completed',
                'Cancelled',
                'Pending',
                'Superseded'
            ])->default('Active')->index();
            
            // Employment status changes
            $table->enum('from_employment_status', [
                'Regular',
                'Contractual', 
                'Casual',
                'Co-terminus',
                'Job Order',
                'Contract of Service',
                'Temporary',
                'Probationary',
                'Other'
            ])->nullable();
            
            $table->enum('to_employment_status', [
                'Regular',
                'Contractual', 
                'Casual',
                'Co-terminus',
                'Job Order',
                'Contract of Service',
                'Temporary',
                'Probationary',
                'Other'
            ]);
            
            // Nature of appointment
            $table->enum('nature_of_appointment', [
                'Original',
                'Promotion',
                'Transfer',
                'Reappointment',
                'Reinstatement',
                'Reemployment',
                'Detail',
                'Secondment',
                'Other'
            ])->nullable();
            
            // Additional details
            $table->text('justification')->nullable()->comment('Reason for promotion/transfer');
            $table->text('remarks')->nullable();
            $table->boolean('is_temporary')->default(false);
            $table->date('temporary_until')->nullable();
            
            // Document tracking
            $table->string('appointment_document_path')->nullable();
            $table->boolean('is_csc_approved')->default(false);
            $table->date('csc_approval_date')->nullable();
            $table->string('csc_approval_number')->nullable();
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance optimization
            $table->index(['employee_id', 'promotion_date']);
            $table->index(['promotion_type', 'status']);
            $table->index(['effective_date', 'status']);
            $table->index(['salary_grade_to', 'step_increment_to']);
            $table->index('appointing_authority');
            $table->index(['from_position', 'to_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_progression');
    }
};