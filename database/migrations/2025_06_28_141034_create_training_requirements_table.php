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
        Schema::create('training_requirements', function (Blueprint $table) {
            $table->id();
            
            // Position and Training Relationship
            $table->string('position_title');
            $table->string('department')->nullable();
            $table->string('salary_grade_level')->nullable();
            $table->foreignId('training_program_id')->constrained()->onDelete('cascade');
            
            // Requirement Classification
            $table->enum('requirement_type', [
                'Mandatory', 'Required for Promotion', 'Continuing Education', 
                'Compliance', 'Specialized', 'Optional but Recommended'
            ]);
            $table->enum('priority_level', ['High', 'Medium', 'Low']);
            
            // Frequency and Timing
            $table->enum('frequency', ['One-time', 'Annual', 'Biennial', 'Every 3 Years', 'Every 5 Years', 'As Needed']);
            $table->integer('deadline_months')->nullable(); // Months after appointment/promotion
            $table->integer('grace_period_days')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Compliance and Exemptions
            $table->text('exemption_criteria')->nullable();
            $table->text('alternative_compliance')->nullable();
            $table->boolean('affects_promotion')->default(false);
            $table->boolean('affects_evaluation')->default(false);
            $table->decimal('compliance_penalty', 5, 2)->nullable(); // Performance rating penalty
            
            // Career Level Requirements
            $table->enum('career_level', [
                'Entry Level', 'Junior', 'Senior', 'Supervisory', 'Managerial', 
                'Executive', 'All Levels'
            ])->default('All Levels');
            $table->integer('min_years_experience')->nullable();
            $table->integer('max_years_experience')->nullable();
            
            // Training Completion Requirements
            $table->decimal('min_passing_score', 5, 2)->nullable();
            $table->integer('min_attendance_percentage')->default(100);
            $table->boolean('requires_certification')->default(false);
            $table->boolean('requires_pre_assessment')->default(false);
            $table->boolean('requires_post_assessment')->default(false);
            
            // Government Compliance
            $table->boolean('csc_mandated')->default(false);
            $table->boolean('dap_required')->default(false);
            $table->string('legal_basis')->nullable(); // CSC Resolution, RA, etc.
            $table->text('compliance_notes')->nullable();
            
            // Status and Metadata
            $table->enum('status', ['Active', 'Inactive', 'Under Review', 'Superseded']);
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('additional_requirements')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('position_title');
            $table->index('department');
            $table->index('requirement_type');
            $table->index('priority_level');
            $table->index('frequency');
            $table->index('career_level');
            $table->index('status');
            $table->index('csc_mandated');
            $table->index('affects_promotion');
            $table->index(['effective_date', 'expiry_date']);
            
            // Composite index for common queries
            $table->index(['position_title', 'requirement_type', 'status'], 'training_req_pos_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_requirements');
    }
};