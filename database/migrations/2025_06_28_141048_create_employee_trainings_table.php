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
        Schema::create('employee_trainings', function (Blueprint $table) {
            $table->id();
            
            // Employee and Training Relationship
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_program_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_requirement_id')->nullable()->constrained()->onDelete('set null');
            
            // Enrollment Information
            $table->date('enrollment_date');
            $table->enum('enrollment_status', [
                'Enrolled', 'Waitlisted', 'Approved', 'Rejected', 'Withdrawn', 'Transferred'
            ])->default('Enrolled');
            $table->string('enrollment_reference')->nullable();
            $table->text('enrollment_notes')->nullable();
            
            // Training Schedule
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('cohort_name')->nullable();
            
            // Completion and Performance
            $table->enum('completion_status', [
                'Not Started', 'In Progress', 'Completed', 'Failed', 'Withdrawn', 
                'Deferred', 'Incomplete', 'Cancelled'
            ])->default('Not Started');
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->integer('hours_attended')->nullable();
            $table->integer('sessions_attended')->nullable();
            $table->integer('total_sessions')->nullable();
            
            // Assessment and Grading
            $table->decimal('grade', 5, 2)->nullable();
            $table->decimal('pre_assessment_score', 5, 2)->nullable();
            $table->decimal('post_assessment_score', 5, 2)->nullable();
            $table->decimal('practical_exam_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->enum('passing_status', ['Passed', 'Failed', 'Pending', 'Not Applicable'])->nullable();
            
            // Certification
            $table->string('certificate_number')->nullable();
            $table->date('certificate_date')->nullable();
            $table->date('certificate_expiry')->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->boolean('certificate_verified')->default(false);
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Training Delivery
            $table->string('trainer_name')->nullable();
            $table->text('trainer_credentials')->nullable();
            $table->enum('delivery_mode', [
                'Face-to-face', 'Online', 'Blended', 'Self-paced', 'Virtual Classroom'
            ])->nullable();
            $table->string('platform_used')->nullable(); // Zoom, Teams, LMS, etc.
            
            // Financial Information
            $table->decimal('training_cost', 10, 2)->nullable();
            $table->enum('cost_center', [
                'Employee Personal', 'Department Budget', 'Training Fund', 
                'Government Scholarship', 'External Sponsor', 'Free'
            ])->nullable();
            $table->string('funding_source')->nullable();
            $table->decimal('allowance_received', 8, 2)->nullable();
            $table->decimal('transportation_allowance', 8, 2)->nullable();
            
            // Evaluation and Feedback
            $table->integer('evaluation_rating')->nullable(); // 1-5 rating of training
            $table->text('feedback')->nullable();
            $table->text('training_recommendations')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->text('improvement_suggestions')->nullable();
            
            // Post-Training Application
            $table->text('learning_application_plan')->nullable();
            $table->text('skills_acquired')->nullable();
            $table->text('knowledge_gained')->nullable();
            $table->boolean('knowledge_applied')->default(false);
            $table->text('application_examples')->nullable();
            $table->date('application_assessment_date')->nullable();
            
            // Compliance and Recognition
            $table->boolean('mandatory_compliance')->default(false);
            $table->date('compliance_deadline')->nullable();
            $table->boolean('counts_towards_promotion')->default(false);
            $table->integer('promotion_points_earned')->nullable();
            $table->decimal('cme_credits_earned', 5, 2)->nullable(); // Continuing Medical Education
            $table->decimal('cpe_credits_earned', 5, 2)->nullable(); // Continuing Professional Education
            
            // Administrative Information
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('hr_officer')->nullable();
            $table->text('hr_notes')->nullable();
            $table->enum('record_status', ['Active', 'Archived', 'Under Review', 'Disputed']);
            
            // Digital Learning Tracking
            $table->decimal('online_progress_percentage', 5, 2)->nullable();
            $table->integer('modules_completed')->nullable();
            $table->integer('total_modules')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->json('learning_analytics')->nullable(); // Track detailed learning data
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('employee_id');
            $table->index('training_program_id');
            $table->index('enrollment_status');
            $table->index('completion_status');
            $table->index(['start_date', 'end_date']);
            $table->index('certificate_number');
            $table->index('mandatory_compliance');
            $table->index('counts_towards_promotion');
            $table->index('record_status');
            $table->index('certificate_expiry');
            
            // Composite indexes for common queries
            $table->index(['employee_id', 'completion_status'], 'idx_emp_completion');
            $table->index(['training_program_id', 'completion_status'], 'idx_program_completion');
            $table->index(['employee_id', 'mandatory_compliance', 'completion_status'], 'idx_emp_mandatory_status');
            
            // Unique constraint to prevent duplicate enrollments
            $table->unique(['employee_id', 'training_program_id', 'start_date'], 'unique_employee_training_enrollment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_trainings');
    }
};