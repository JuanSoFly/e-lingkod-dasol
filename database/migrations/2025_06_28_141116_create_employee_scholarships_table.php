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
        Schema::create('employee_scholarships', function (Blueprint $table) {
            $table->id();
            
            // Employee and Scholarship Relationship
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('scholarship_program_id')->constrained()->onDelete('cascade');
            
            // Application Information
            $table->date('application_date');
            $table->string('application_reference')->nullable();
            $table->enum('approval_status', [
                'Applied', 'Under Review', 'Interview Scheduled', 'Approved', 
                'Conditionally Approved', 'Rejected', 'Withdrawn', 'Deferred'
            ])->default('Applied');
            $table->text('application_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Approval and Authorization
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('agency_approver')->nullable();
            $table->timestamp('agency_approval_date')->nullable();
            $table->text('approval_conditions')->nullable();
            $table->string('approval_document_ref')->nullable();
            
            // Study Program Details
            $table->date('start_date')->nullable();
            $table->date('expected_completion')->nullable();
            $table->date('actual_completion')->nullable();
            $table->string('institution_name')->nullable();
            $table->string('institution_country')->default('Philippines');
            $table->string('course_title')->nullable();
            $table->string('degree_program')->nullable();
            $table->string('major_field')->nullable();
            $table->string('minor_field')->nullable();
            
            // Academic Progress
            $table->enum('academic_status', [
                'Not Started', 'Enrolled', 'In Progress', 'On Leave', 'Transferred',
                'Completed', 'Graduated', 'Dropped', 'Terminated', 'Deferred'
            ])->default('Not Started');
            $table->decimal('gpa', 3, 2)->nullable();
            $table->string('current_year_level')->nullable();
            $table->integer('units_completed')->nullable();
            $table->integer('total_units_required')->nullable();
            $table->decimal('completion_percentage', 5, 2)->nullable();
            
            // Thesis and Research
            $table->string('thesis_title')->nullable();
            $table->text('thesis_abstract')->nullable();
            $table->string('thesis_advisor')->nullable();
            $table->enum('thesis_status', [
                'Not Started', 'Proposal Stage', 'Data Collection', 'Writing', 
                'Under Review', 'Defense Scheduled', 'Defended', 'Completed'
            ])->nullable();
            $table->date('thesis_defense_date')->nullable();
            $table->text('research_contributions')->nullable();
            
            // Financial Information
            $table->decimal('total_scholarship_amount', 12, 2)->nullable();
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->decimal('amount_remaining', 12, 2)->nullable();
            $table->decimal('monthly_stipend', 8, 2)->nullable();
            $table->decimal('tuition_covered', 10, 2)->nullable();
            $table->decimal('other_allowances', 8, 2)->nullable();
            $table->text('financial_breakdown')->nullable();
            
            // Service Obligation
            $table->integer('service_obligation_years')->nullable();
            $table->decimal('bond_amount', 12, 2)->nullable();
            $table->date('service_start_date')->nullable();
            $table->date('service_end_date')->nullable();
            $table->enum('compliance_status', [
                'Not Applicable', 'Pending', 'In Compliance', 'Partially Complied', 
                'Non-Compliant', 'Exempted', 'Bond Paid'
            ])->default('Not Applicable');
            $table->integer('service_years_completed')->default(0);
            $table->integer('service_years_remaining')->nullable();
            $table->decimal('bond_balance', 12, 2)->nullable();
            
            // Performance and Monitoring
            $table->text('academic_achievements')->nullable();
            $table->text('awards_recognition')->nullable();
            $table->text('publications')->nullable();
            $table->text('conferences_attended')->nullable();
            $table->text('skills_developed')->nullable();
            $table->text('knowledge_application')->nullable();
            
            // Reporting Requirements
            $table->date('last_report_submitted')->nullable();
            $table->date('next_report_due')->nullable();
            $table->integer('reports_submitted')->default(0);
            $table->integer('reports_overdue')->default(0);
            $table->text('latest_report_summary')->nullable();
            $table->enum('reporting_compliance', ['Compliant', 'Overdue', 'Delayed', 'Not Required'])->default('Not Required');
            
            // Administrative Information
            $table->string('hr_officer')->nullable();
            $table->text('hr_notes')->nullable();
            $table->string('scholarship_coordinator')->nullable();
            $table->text('coordinator_notes')->nullable();
            $table->enum('record_status', ['Active', 'Completed', 'Terminated', 'Under Review', 'Archived']);
            
            // International Study Specifics
            $table->string('visa_status')->nullable();
            $table->date('visa_expiry')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->text('travel_documents')->nullable();
            $table->decimal('forex_allowance', 10, 2)->nullable();
            $table->string('embassy_contact')->nullable();
            
            // Post-Completion Information
            $table->date('graduation_date')->nullable();
            $table->string('final_grade')->nullable();
            $table->string('honors_received')->nullable();
            $table->text('graduation_requirements_met')->nullable();
            $table->string('diploma_status')->nullable();
            $table->text('post_study_career_plan')->nullable();
            $table->text('contribution_to_organization')->nullable();
            
            // Document Management
            $table->json('required_documents')->nullable();
            $table->json('submitted_documents')->nullable();
            $table->json('missing_documents')->nullable();
            $table->text('document_notes')->nullable();
            
            // Emergency and Support Information
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_email')->nullable();
            $table->text('support_services_needed')->nullable();
            $table->text('special_accommodations')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('employee_id');
            $table->index('scholarship_program_id');
            $table->index('approval_status');
            $table->index('academic_status');
            $table->index('compliance_status');
            $table->index('record_status');
            $table->index(['start_date', 'expected_completion']);
            $table->index('service_obligation_years');
            $table->index('reporting_compliance');
            $table->index('graduation_date');
            
            // Composite indexes for common queries
            $table->index(['employee_id', 'academic_status'], 'idx_emp_academic');
            $table->index(['scholarship_program_id', 'approval_status'], 'idx_program_approval');
            $table->index(['employee_id', 'compliance_status', 'service_obligation_years'], 'idx_emp_compliance_service');
            
            // Unique constraint to prevent duplicate applications
            $table->unique(['employee_id', 'scholarship_program_id'], 'unique_employee_scholarship_application');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_scholarships');
    }
};