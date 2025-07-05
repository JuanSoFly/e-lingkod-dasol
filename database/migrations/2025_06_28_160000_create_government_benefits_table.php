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
        Schema::create('government_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Benefit Type - GSIS, PhilHealth, Pag-IBIG, SSS
            $table->enum('benefit_type', ['GSIS', 'PhilHealth', 'Pag-IBIG', 'SSS'])->index();
            
            // Member Information
            $table->string('member_number', 50)->nullable()->index();
            $table->date('enrollment_date')->nullable();
            $table->enum('enrollment_status', ['active', 'inactive', 'suspended', 'terminated'])->default('active')->index();
            
            // Coverage and Rates
            $table->enum('coverage_type', ['basic', 'premium', 'dependent', 'family'])->default('basic');
            $table->decimal('coverage_amount', 15, 2)->nullable();
            $table->decimal('employee_contribution_rate', 8, 4)->nullable(); // Percentage rate
            $table->decimal('employer_contribution_rate', 8, 4)->nullable(); // Percentage rate
            $table->decimal('monthly_contribution_cap', 10, 2)->nullable(); // Maximum monthly contribution
            
            // Beneficiaries
            $table->json('primary_beneficiaries')->nullable(); // Array of beneficiary details
            $table->json('secondary_beneficiaries')->nullable(); // Array of beneficiary details
            
            // Loan Information
            $table->boolean('has_active_loan')->default(false)->index();
            $table->decimal('loan_balance', 15, 2)->default(0);
            $table->decimal('monthly_loan_payment', 10, 2)->default(0);
            $table->date('loan_start_date')->nullable();
            $table->date('loan_maturity_date')->nullable();
            $table->decimal('loan_interest_rate', 8, 4)->nullable();
            
            // Claims and Processing
            $table->integer('active_claims_count')->default(0)->index();
            $table->decimal('total_claims_amount', 15, 2)->default(0);
            $table->date('last_claim_date')->nullable();
            $table->enum('processing_status', ['up_to_date', 'pending_update', 'requires_verification', 'suspended'])->default('up_to_date')->index();
            
            // Additional Metadata
            $table->json('additional_details')->nullable(); // For benefit-specific data
            $table->text('remarks')->nullable();
            $table->date('last_verified_date')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Audit fields
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['employee_id', 'benefit_type'], 'employee_benefit_type_idx');
            $table->index(['benefit_type', 'enrollment_status'], 'benefit_status_idx');
            $table->index(['enrollment_date', 'benefit_type'], 'enrollment_benefit_idx');
            $table->index(['has_active_loan', 'benefit_type'], 'loan_benefit_idx');
            $table->index(['processing_status', 'last_verified_date'], 'processing_verification_idx');
            
            // Unique constraint to prevent duplicate enrollments
            $table->unique(['employee_id', 'benefit_type'], 'unique_employee_benefit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('government_benefits');
    }
};