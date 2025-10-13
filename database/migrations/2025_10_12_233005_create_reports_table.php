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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Report Identification
            $table->string('report_number')->unique();
            $table->string('report_type');
            $table->string('title');
            $table->text('description')->nullable();

            // Report Period
            $table->integer('report_year');
            $table->integer('report_month')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('department')->nullable();

            // Report Content & Data
            $table->json('filters')->nullable();
            $table->json('report_data');
            $table->json('summary_statistics')->nullable();
            $table->integer('total_records')->default(0);

            // File Management
            $table->enum('file_format', ['pdf', 'excel', 'both'])->default('pdf');
            $table->string('pdf_file_path')->nullable();
            $table->string('excel_file_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('file_hash')->nullable();

            // Workflow Status
            $table->enum('status', [
                'generating', 'generated', 'reviewed', 'approved',
                'submitted', 'acknowledged', 'failed'
            ])->default('generating');

            // Submission Tracking
            $table->timestamp('submitted_at')->nullable();
            $table->enum('submission_method', ['online', 'email', 'physical'])->nullable();
            $table->string('submission_reference')->nullable();
            $table->text('submission_notes')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            // User Assignments
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');

            // Generation Tracking
            $table->timestamp('generation_started_at');
            $table->timestamp('generation_completed_at')->nullable();
            $table->integer('generation_duration_seconds')->nullable();
            $table->text('generation_log')->nullable();
            $table->text('error_message')->nullable();

            // Version Control
            $table->integer('version')->default(1);
            $table->foreignId('parent_report_id')->nullable()->constrained('reports')->onDelete('cascade');
            $table->boolean('is_current_version')->default(true);

            // Security & Confidentiality
            $table->boolean('contains_confidential_data')->default(false);
            $table->enum('confidentiality_level', ['public', 'internal', 'confidential', 'restricted'])->default('public');
            $table->date('retention_until')->nullable();
            $table->text('legal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['report_type', 'report_year', 'report_month']);
            $table->index(['status']);
            $table->index(['department']);
            $table->index(['submitted_at']);
            $table->index(['generated_by']);
            $table->index(['parent_report_id']);
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
