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
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            
            // Source record (the document or record initiating the link)
            $table->morphs('source'); // source_type, source_id
            
            // Target record (the record being linked to)
            $table->morphs('target'); // target_type, target_id
            
            // Link metadata
            $table->enum('link_type', [
                'appointment_document',     // Links documents to appointments
                'training_certificate',     // Links training certificates to training records
                'leave_supporting_doc',     // Links supporting documents to leave applications
                'performance_evidence',     // Links evidence documents to performance reviews
                'education_credential',     // Links educational documents to education records
                'work_experience_proof',    // Links documents to work experience records
                'medical_certificate',     // Links medical docs to leave/health records
                'disciplinary_document',    // Links documents to disciplinary actions
                'promotion_document',       // Links documents to promotion records
                'separation_document',      // Links documents to separation records
                'custom_link'              // For custom relationships
            ]);
            
            $table->enum('relationship_strength', ['weak', 'medium', 'strong'])->default('medium');
            $table->boolean('is_automatic')->default(false); // Whether link was created automatically
            $table->boolean('is_bidirectional')->default(true); // Whether relationship works both ways
            $table->boolean('is_primary')->default(false); // Primary link for the relationship type
            
            // Link validation and metadata
            $table->json('link_metadata')->nullable(); // Store additional relationship data
            $table->text('link_reason')->nullable(); // Why this link was created
            $table->decimal('confidence_score', 5, 4)->nullable(); // Auto-linking confidence (0-1)
            $table->json('validation_rules')->nullable(); // Rules for maintaining link consistency
            
            // Status and lifecycle
            $table->enum('status', ['active', 'inactive', 'pending_validation', 'broken'])->default('active');
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('validation_notes')->nullable();
            
            // Auto-linking intelligence
            $table->json('matching_criteria')->nullable(); // Criteria used for auto-linking
            $table->boolean('requires_manual_approval')->default(false);
            $table->timestamp('last_verified_at')->nullable();
            $table->integer('verification_count')->default(0);
            
            // Audit trail
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance (MySQL optimized)
            $table->index(['source_type', 'source_id'], 'idx_source');
            $table->index(['target_type', 'target_id'], 'idx_target');
            $table->index(['link_type', 'status'], 'idx_link_type_status');
            $table->index(['is_automatic', 'confidence_score'], 'idx_auto_confidence');
            $table->index(['status', 'last_verified_at'], 'idx_status_verified');
            $table->index(['created_by', 'created_at'], 'idx_creator_date');
            $table->index('is_primary', 'idx_primary_links');
            
            // Composite indexes for common queries
            $table->index(['source_type', 'source_id', 'link_type'], 'idx_source_link_type');
            $table->index(['target_type', 'target_id', 'link_type'], 'idx_target_link_type');
            $table->index(['link_type', 'is_primary', 'status'], 'idx_primary_active_links');
            
            // Prevent duplicate links
            $table->unique([
                'source_type', 'source_id', 
                'target_type', 'target_id', 
                'link_type'
            ], 'unique_document_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};