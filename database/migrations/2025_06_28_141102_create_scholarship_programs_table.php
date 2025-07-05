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
        Schema::create('scholarship_programs', function (Blueprint $table) {
            $table->id();
            
            // Basic Program Information
            $table->string('program_name');
            $table->string('program_code')->unique()->nullable();
            $table->string('funding_agency');
            $table->string('implementing_agency')->nullable();
            
            // Scholarship Classification
            $table->enum('scholarship_type', [
                'Full Scholarship', 'Partial Scholarship', 'Study Leave with Pay', 
                'Study Leave without Pay', 'Educational Assistance', 'Research Grant',
                'Conference/Seminar Grant', 'Skills Training Grant'
            ]);
            $table->enum('degree_level', [
                'Certificate', 'Diploma', 'Associate Degree', 'Bachelor Degree', 
                'Master Degree', 'Doctoral Degree', 'Post-Doctoral', 'Professional Development',
                'Short Course', 'Conference/Seminar'
            ]);
            $table->string('field_of_study')->nullable();
            $table->text('preferred_courses')->nullable();
            $table->text('restricted_courses')->nullable();
            
            // Duration and Timeline
            $table->integer('duration_years')->nullable();
            $table->integer('duration_months')->nullable();
            $table->integer('duration_weeks')->nullable();
            $table->date('program_start')->nullable();
            $table->date('program_end')->nullable();
            $table->date('application_deadline')->nullable();
            $table->date('selection_date')->nullable();
            
            // Financial Coverage
            $table->decimal('total_budget', 12, 2)->nullable();
            $table->decimal('per_scholar_budget', 10, 2)->nullable();
            $table->boolean('covers_tuition')->default(false);
            $table->boolean('covers_living_allowance')->default(false);
            $table->boolean('covers_transportation')->default(false);
            $table->boolean('covers_books_materials')->default(false);
            $table->boolean('covers_research_expenses')->default(false);
            $table->boolean('covers_conference_fees')->default(false);
            $table->decimal('monthly_allowance', 8, 2)->nullable();
            $table->decimal('book_allowance', 8, 2)->nullable();
            $table->decimal('thesis_allowance', 8, 2)->nullable();
            $table->decimal('travel_allowance', 8, 2)->nullable();
            
            // Eligibility Criteria
            $table->text('eligibility_criteria');
            $table->integer('min_years_service')->nullable();
            $table->integer('max_years_service')->nullable();
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->decimal('min_performance_rating', 3, 2)->nullable();
            $table->text('position_requirements')->nullable();
            $table->text('educational_requirements')->nullable();
            $table->text('health_requirements')->nullable();
            
            // Selection Process
            $table->text('selection_criteria')->nullable();
            $table->text('application_requirements')->nullable();
            $table->boolean('requires_entrance_exam')->default(false);
            $table->boolean('requires_interview')->default(false);
            $table->boolean('requires_medical_exam')->default(false);
            $table->boolean('requires_psychological_exam')->default(false);
            $table->text('evaluation_process')->nullable();
            $table->integer('available_slots')->nullable();
            $table->integer('reserved_slots')->nullable();
            
            // Service Obligation and Bond
            $table->integer('service_obligation_years')->nullable();
            $table->decimal('bond_amount', 12, 2)->nullable();
            $table->text('bond_conditions')->nullable();
            $table->text('service_agreement_terms')->nullable();
            $table->boolean('allows_early_termination')->default(false);
            $table->decimal('early_termination_penalty', 10, 2)->nullable();
            $table->text('exemption_conditions')->nullable();
            
            // Program Administration
            $table->enum('status', ['Active', 'Inactive', 'Suspended', 'Under Review', 'Cancelled']);
            $table->string('program_manager')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('application_process')->nullable();
            $table->string('application_form_url')->nullable();
            
            // Government Compliance
            $table->string('legal_basis')->nullable(); // RA, Presidential Decree, etc.
            $table->string('implementing_rules')->nullable();
            $table->boolean('daps_approved')->default(false);
            $table->boolean('csc_approved')->default(false);
            $table->boolean('dbm_approved')->default(false);
            $table->text('approval_documents')->nullable();
            
            // Reporting and Monitoring
            $table->boolean('requires_progress_reports')->default(true);
            $table->enum('reporting_frequency', ['Monthly', 'Quarterly', 'Semestral', 'Annual', 'As Required'])->default('Quarterly');
            $table->boolean('requires_final_report')->default(true);
            $table->boolean('requires_thesis_submission')->default(false);
            $table->text('monitoring_requirements')->nullable();
            
            // Success Metrics
            $table->decimal('target_completion_rate', 5, 2)->nullable();
            $table->decimal('actual_completion_rate', 5, 2)->nullable();
            $table->integer('total_scholars_graduated')->default(0);
            $table->integer('total_scholars_active')->default(0);
            $table->text('success_indicators')->nullable();
            
            // International Programs
            $table->boolean('is_international')->default(false);
            $table->text('participating_countries')->nullable();
            $table->text('partner_institutions')->nullable();
            $table->boolean('requires_visa')->default(false);
            $table->boolean('requires_language_proficiency')->default(false);
            $table->text('language_requirements')->nullable();
            
            // Metadata
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('additional_data')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('funding_agency');
            $table->index('scholarship_type');
            $table->index('degree_level');
            $table->index('field_of_study');
            $table->index('status');
            $table->index(['application_deadline', 'status']);
            $table->index('is_international');
            $table->index('available_slots');
            $table->index(['program_start', 'program_end']);
            $table->fullText(['program_name', 'field_of_study', 'eligibility_criteria'], 'scholarship_search_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_programs');
    }
};