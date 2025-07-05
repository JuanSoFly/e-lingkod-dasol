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
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            
            // Basic policy identification
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Policy scope - who this applies to
            $table->json('employment_statuses')->nullable(); // ['regular', 'contractual', 'probationary', 'casual']
            $table->json('positions')->nullable(); // specific positions or departments
            $table->string('employee_type')->nullable(); // government, private, etc.
            
            // Leave type configuration
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            
            // Entitlement rules
            $table->decimal('max_days_per_year', 5, 2)->default(0);
            $table->decimal('max_days_per_month', 5, 2)->nullable();
            $table->decimal('max_consecutive_days', 5, 2)->nullable();
            $table->decimal('min_days_per_application', 5, 2)->default(0.5);
            
            // Eligibility requirements
            $table->integer('minimum_tenure_months')->default(0); // months before eligible
            $table->boolean('requires_medical_certificate')->default(false);
            $table->integer('medical_cert_required_days')->nullable(); // days threshold
            
            // Accrual and pro-rating
            $table->enum('accrual_method', ['monthly', 'quarterly', 'annually', 'on_hire'])->default('annually');
            $table->decimal('monthly_accrual_rate', 5, 2)->nullable();
            $table->boolean('allow_prorated_first_year')->default(true);
            $table->boolean('allow_negative_balance')->default(false);
            
            // Carry-over rules
            $table->boolean('allow_carryover')->default(false);
            $table->decimal('max_carryover_days', 5, 2)->nullable();
            $table->date('carryover_expiry_date')->nullable(); // when carried over credits expire
            
            // Application rules
            $table->integer('min_advance_notice_days')->default(0);
            $table->integer('max_advance_notice_days')->nullable();
            $table->json('blocked_dates')->nullable(); // blackout periods
            $table->json('required_documents')->nullable(); // ['medical_cert', 'travel_order', etc.]
            
            // Approval workflow configuration
            $table->boolean('requires_approval')->default(true);
            $table->json('approval_hierarchy')->nullable(); // ['immediate_supervisor', 'department_head', 'hr']
            $table->boolean('auto_approve_threshold')->default(false);
            $table->decimal('auto_approve_days', 5, 2)->nullable(); // auto approve if <= this many days
            
            // Government-specific configurations
            $table->boolean('is_government_policy')->default(true);
            $table->string('legal_basis')->nullable(); // CSC MC, RA, etc.
            $table->boolean('csc_reportable')->default(true);
            
            // Gender-specific rules (maternity, paternity)
            $table->enum('gender_restriction', ['none', 'male', 'female'])->default('none');
            
            // Effective period
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            
            // Audit fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance (excluding JSON columns - MySQL limitation)
            $table->index(['leave_type_id', 'is_active', 'effective_start_date'] , 'lp_type_active_start_idx');
            $table->index(['effective_start_date', 'effective_end_date']);
            $table->index(['is_active', 'is_government_policy']);
            $table->index(['employee_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};