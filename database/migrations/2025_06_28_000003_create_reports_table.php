<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates the reports table to track all generated CSC reports,
     * their metadata, file paths, and submission tracking. This table integrates
     * with the CSCReportingService and supports the existing employee management system.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            
            // Report Basic Information
            $table->string('report_number')->unique(); // Auto-generated unique identifier like CSC-2025-001
            $table->string('report_type'); // 'accession', 'separation', 'dibar', 'harassment', 'ighr'
            $table->string('title'); // Human-readable title
            $table->text('description')->nullable(); // Optional description
            
            // Report Period Information
            $table->integer('report_year'); // Year the report covers
            $table->integer('report_month')->nullable(); // Month for monthly reports (null for annual)
            $table->date('period_start')->nullable(); // Start date of reporting period
            $table->date('period_end')->nullable(); // End date of reporting period
            
            // Department & Scope Filtering
            $table->string('department')->nullable(); // Specific department filter (null = all departments)
            $table->json('filters')->nullable(); // Additional filters applied in JSON format
            
            // Report Status & Workflow
            $table->enum('status', [
                'generating', 
                'generated', 
                'reviewed', 
                'approved', 
                'submitted', 
                'acknowledged', 
                'failed', 
                'cancelled'
            ])->default('generating');
            
            // File Storage Information
            $table->string('file_format')->nullable(); // 'pdf', 'excel', 'both'
            $table->string('pdf_file_path')->nullable(); // Path to generated PDF file
            $table->string('excel_file_path')->nullable(); // Path to generated Excel file
            $table->bigInteger('file_size')->nullable(); // Total file size in bytes
            $table->string('file_hash')->nullable(); // File integrity hash (SHA-256)
            
            // Report Data & Statistics
            $table->json('report_data')->nullable(); // Full report data in JSON format
            $table->json('summary_statistics')->nullable(); // Key metrics and summary data
            $table->integer('total_records')->default(0); // Total records included in report
            
            // CSC Submission Tracking
            $table->datetime('submitted_at')->nullable(); // When report was submitted to CSC
            $table->string('submission_method')->nullable(); // 'online', 'email', 'physical'
            $table->string('submission_reference')->nullable(); // CSC acknowledgment number
            $table->text('submission_notes')->nullable(); // Notes about submission process
            $table->datetime('acknowledged_at')->nullable(); // When CSC acknowledged receipt
            
            // User & Audit Information
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade'); // Who generated the report
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null'); // Who reviewed it
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Who approved it
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null'); // Who submitted it
            
            // Generation Metadata
            $table->datetime('generation_started_at')->nullable(); // When generation began
            $table->datetime('generation_completed_at')->nullable(); // When generation finished
            $table->integer('generation_duration_seconds')->nullable(); // How long generation took
            $table->text('generation_log')->nullable(); // Log of generation process
            $table->text('error_message')->nullable(); // Error details if generation failed
            
            // Version Control
            $table->integer('version')->default(1); // Report version number
            $table->foreignId('parent_report_id')->nullable()->constrained('reports')->onDelete('set null'); // Reference to previous version
            $table->boolean('is_current_version')->default(true); // Whether this is the current version
            
            // Compliance & Legal
            $table->boolean('contains_confidential_data')->default(false); // Privacy flag
            $table->string('confidentiality_level')->default('public'); // 'public', 'internal', 'confidential', 'restricted'
            $table->date('retention_until')->nullable(); // When report can be deleted
            $table->text('legal_notes')->nullable(); // Legal or compliance notes
            
            // System Fields
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft deletes
            
            // Add database indexes for MySQL optimization
            $table->index(['report_type', 'status'], 'idx_reports_type_status');
            $table->index(['report_year', 'report_month'], 'idx_reports_period');
            $table->index(['department', 'status'], 'idx_reports_dept_status');
            $table->index(['generated_by', 'created_at'], 'idx_reports_user_created');
            $table->index(['status', 'submitted_at'], 'idx_reports_status_submitted');
            $table->index(['report_type', 'report_year', 'report_month'], 'idx_reports_type_period');
            $table->index('submission_reference', 'idx_reports_submission_ref');
            $table->index(['is_current_version', 'parent_report_id'], 'idx_reports_version_parent');
            $table->index(['contains_confidential_data', 'confidentiality_level'], 'idx_reports_confidentiality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};