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
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            
            // Basic Program Information
            $table->string('program_name');
            $table->string('program_code')->unique()->nullable();
            $table->enum('provider', ['DAP', 'CSC', 'Agency-specific', 'External', 'Internal', 'Other']);
            $table->string('provider_organization')->nullable();
            
            // Training Classification
            $table->enum('training_type', [
                'Orientation', 'Skills Development', 'Leadership', 'Technical', 
                'Compliance', 'Safety', 'Professional Development', 'Other'
            ]);
            $table->enum('category', [
                'Mandatory', 'Optional', 'Career Development', 'Compliance Required',
                'Continuing Education', 'Specialized Training', 'Other'
            ]);
            $table->enum('delivery_method', [
                'Face-to-face', 'Online', 'Blended', 'Workshop', 'Seminar', 'Conference', 'Other'
            ]);
            
            // Duration and Credits
            $table->integer('duration_hours');
            $table->decimal('credit_hours', 5, 2)->nullable();
            $table->integer('duration_days')->nullable();
            
            // Training Content
            $table->text('description');
            $table->text('learning_objectives')->nullable();
            $table->text('target_participants')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('curriculum_outline')->nullable();
            
            // Logistics and Capacity
            $table->decimal('cost_per_participant', 10, 2)->nullable();
            $table->integer('max_participants')->nullable();
            $table->string('venue')->nullable();
            $table->string('trainer_name')->nullable();
            $table->text('trainer_credentials')->nullable();
            
            // Accreditation and Recognition
            $table->enum('status', ['Active', 'Inactive', 'Pending Approval', 'Cancelled', 'Completed']);
            $table->string('accreditation_number')->nullable();
            $table->string('accrediting_body')->nullable();
            $table->date('accreditation_date')->nullable();
            $table->date('accreditation_expiry')->nullable();
            
            // Government Compliance
            $table->boolean('csc_recognized')->default(false);
            $table->boolean('dap_accredited')->default(false);
            $table->boolean('counts_towards_promotion')->default(false);
            $table->integer('promotion_points')->nullable();
            
            // Scheduling Information
            $table->date('enrollment_start')->nullable();
            $table->date('enrollment_end')->nullable();
            $table->date('training_start')->nullable();
            $table->date('training_end')->nullable();
            
            // Evaluation and Certification
            $table->boolean('has_certification')->default(false);
            $table->string('certificate_template')->nullable();
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->text('evaluation_criteria')->nullable();
            
            // Metadata
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('additional_data')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('provider');
            $table->index('training_type');
            $table->index('category');
            $table->index('status');
            $table->index(['training_start', 'training_end']);
            $table->index('csc_recognized');
            $table->index('dap_accredited');
            $table->fullText(['program_name', 'description', 'learning_objectives'], 'training_search_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};