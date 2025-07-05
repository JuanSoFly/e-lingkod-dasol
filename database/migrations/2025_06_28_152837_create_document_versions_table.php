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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            
            // Core version tracking
            $table->foreignId('original_document_id')->constrained('employee_documents')->onDelete('cascade');
            $table->foreignId('parent_version_id')->nullable()->constrained('document_versions')->onDelete('cascade');
            $table->integer('version_number')->default(1);
            $table->boolean('is_current_version')->default(false);
            
            // File information
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('mime_type', 100);
            $table->string('file_hash', 64); // SHA-256 hash for integrity checks
            $table->string('checksum', 32); // MD5 checksum for quick comparison
            
            // Version metadata
            $table->text('change_reason')->nullable();
            $table->text('version_notes')->nullable();
            $table->json('comparison_metadata')->nullable(); // Store diff/comparison data
            
            // Upload and approval tracking
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('uploaded_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'superseded'])->default('pending');
            $table->text('approval_notes')->nullable();
            
            // Rollback capability
            $table->boolean('can_rollback')->default(true);
            $table->timestamp('rollback_until')->nullable(); // Expiry date for rollback capability
            
            // Audit trail
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance (MySQL optimized)
            $table->index(['original_document_id', 'version_number'], 'idx_doc_version');
            $table->index(['original_document_id', 'is_current_version'], 'idx_current_version');
            $table->index(['uploaded_by', 'uploaded_at'], 'idx_uploader_date');
            $table->index(['approval_status', 'approved_at'], 'idx_approval_status');
            $table->index('file_hash', 'idx_file_hash');
            $table->index('parent_version_id', 'idx_parent_version');
            
            // Unique constraint to ensure only one current version per document
            $table->unique(['original_document_id', 'is_current_version'], 'unique_current_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};