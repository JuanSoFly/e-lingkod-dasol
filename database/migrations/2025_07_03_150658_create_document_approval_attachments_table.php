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
        Schema::create('document_approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('document_approval_requests')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->integer('version')->default(1);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_current_version')->default(true);
            $table->json('metadata')->nullable(); // Additional file metadata
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['request_id', 'is_current_version'], 'da_attachments_request_current_idx');
            $table->index(['uploaded_by', 'created_at'], 'da_attachments_uploader_date_idx');
            $table->index('file_type', 'da_attachments_file_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approval_attachments');
    }
};