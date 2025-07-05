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
        Schema::create('benefit_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('government_benefit_id')->constrained('government_benefits')->onDelete('cascade');
            
            // Contribution Period
            $table->year('contribution_year')->index();
            $table->tinyInteger('contribution_month')->index(); // 1-12
            $table->date('payroll_date')->index(); // Date when contribution was calculated
            $table->date('due_date')->index(); // When contribution should be remitted
            
            // Salary Base
            $table->decimal('basic_salary', 12, 2); // Basic salary for the period
            $table->decimal('additional_compensation', 12, 2)->default(0); // Allowances, bonuses, etc.
            $table->decimal('total_compensation', 12, 2); // Total compensation subject to contribution
            $table->decimal('contribution_base', 12, 2); // Actual amount used for calculation (may be capped)
            
            // Contribution Amounts
            $table->decimal('employee_contribution_rate', 8, 4); // Rate applied
            $table->decimal('employer_contribution_rate', 8, 4); // Rate applied
            $table->decimal('employee_contribution_amount', 10, 2); // Calculated amount
            $table->decimal('employer_contribution_amount', 10, 2); // Calculated amount
            $table->decimal('total_contribution_amount', 10, 2); // Total contribution
            
            // Loan Deductions
            $table->decimal('loan_payment_amount', 10, 2)->default(0);
            $table->decimal('interest_amount', 8, 2)->default(0);
            $table->decimal('penalty_amount', 8, 2)->default(0);
            
            // Payment Tracking
            $table->enum('payment_status', ['pending', 'paid', 'overdue', 'partial', 'waived'])->default('pending')->index();
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 100)->nullable(); // OR number, bank reference, etc.
            $table->enum('payment_method', ['payroll_deduction', 'bank_transfer', 'check', 'cash', 'online'])->nullable();
            
            // Late Payment Handling
            $table->integer('days_overdue')->default(0)->index();
            $table->decimal('late_penalty_rate', 8, 4)->default(0); // Monthly penalty rate
            $table->decimal('late_penalty_amount', 8, 2)->default(0);
            $table->decimal('interest_on_penalty', 8, 2)->default(0);
            
            // Adjustments and Corrections
            $table->boolean('is_adjustment')->default(false)->index();
            $table->string('adjustment_reason', 200)->nullable();
            $table->decimal('adjustment_amount', 10, 2)->default(0);
            $table->foreignId('adjusted_from_id')->nullable()->constrained('benefit_contributions')->onDelete('set null');
            
            // Government Remittance Tracking
            $table->enum('remittance_status', ['not_remitted', 'remitted', 'partial_remitted', 'under_investigation'])->default('not_remitted')->index();
            $table->date('remittance_date')->nullable();
            $table->string('remittance_reference', 100)->nullable();
            $table->decimal('remittance_amount', 12, 2)->nullable();
            
            // Premium and Rate Changes
            $table->json('rate_changes')->nullable(); // Track rate changes during the period
            $table->json('premium_adjustments')->nullable(); // Premium schedule adjustments
            
            // Compliance and Audit
            $table->boolean('is_compliant')->default(true)->index();
            $table->json('compliance_issues')->nullable(); // Array of compliance problems
            $table->text('remarks')->nullable();
            $table->date('last_verified_date')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Payroll Integration
            $table->string('payroll_batch_id', 50)->nullable()->index(); // Link to payroll processing batch
            $table->boolean('included_in_payroll')->default(false)->index();
            $table->json('payroll_details')->nullable(); // Additional payroll-specific data
            
            // Audit fields
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Performance Indexes
            $table->index(['employee_id', 'contribution_year', 'contribution_month'], 'employee_period_idx');
            $table->index(['government_benefit_id', 'contribution_year'], 'benefit_year_idx');
            $table->index(['payroll_date', 'payment_status'], 'payroll_status_idx');
            $table->index(['due_date', 'payment_status'], 'due_status_idx');
            $table->index(['remittance_status', 'remittance_date'], 'remittance_idx');
            $table->index(['is_compliant', 'last_verified_date'], 'compliance_idx');
            $table->index(['days_overdue', 'late_penalty_amount'], 'overdue_penalty_idx');
            $table->index(['payroll_batch_id', 'included_in_payroll'], 'payroll_batch_idx');
            
            // Unique constraint to prevent duplicate contributions for same period
            $table->unique([
                'employee_id', 
                'government_benefit_id', 
                'contribution_year', 
                'contribution_month',
                'payroll_date'
            ], 'unique_contribution_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefit_contributions');
    }
};