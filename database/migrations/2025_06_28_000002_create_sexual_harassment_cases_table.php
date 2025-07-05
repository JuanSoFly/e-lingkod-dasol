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
        Schema::create('sexual_harassment_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->foreignId('complainant_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('complainant_name'); // In case complainant is not an employee
            $table->foreignId('respondent_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('respondent_name'); // In case respondent is not an employee
            $table->date('incident_date');
            $table->date('filed_date');
            $table->text('incident_description');
            $table->text('complainant_statement')->nullable();
            $table->text('respondent_statement')->nullable();
            $table->string('case_status')->default('filed'); // filed, under_investigation, dismissed, resolved, forwarded_to_court, pending_appeal
            $table->string('investigation_status')->nullable();
            $table->date('investigation_start_date')->nullable();
            $table->date('investigation_end_date')->nullable();
            $table->foreignId('investigating_officer_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->text('investigation_findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('resolution_type')->nullable(); // administrative_sanction, dismissal, no_violation, mediation, etc.
            $table->text('resolution_details')->nullable();
            $table->date('resolution_date')->nullable();
            $table->string('administrative_action')->nullable();
            $table->boolean('forwarded_to_court')->default(false);
            $table->date('court_filing_date')->nullable();
            $table->string('court_case_number')->nullable();
            $table->text('court_status')->nullable();
            $table->boolean('appeal_filed')->default(false);
            $table->date('appeal_date')->nullable();
            $table->text('appeal_status')->nullable();
            $table->json('witnesses')->nullable(); // Store witness information as JSON
            $table->json('evidence_files')->nullable(); // Store evidence file paths as JSON
            $table->text('remarks')->nullable();
            $table->foreignId('hr_officer_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('legal_officer_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->boolean('is_confidential')->default(true);
            $table->string('department_involved')->nullable();
            $table->string('office_location')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            // Indexes for better performance
            $table->index(['case_status', 'filed_date']);
            $table->index(['investigation_status', 'investigation_start_date'] , 'sh_inv_status_start_idx');
            $table->index(['case_status', 'resolution_date']);
            $table->index(['department_involved', 'filed_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sexual_harassment_cases');
    }
};