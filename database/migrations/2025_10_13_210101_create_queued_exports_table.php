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
        Schema::create('queued_exports', function (Blueprint $table) {
            $table->id();
            $table->string('job_id', 255)->unique(); // Laravel job identifier
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('employee_ids'); // Array of employee IDs to export
            $table->string('export_type', 20); // 'single', 'batch'
            $table->string('export_format', 10); // 'xlsx', 'csv'
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('file_path')->nullable(); // Path to generated file
            $table->string('file_name')->nullable(); // Original filename
            $table->bigInteger('file_size')->nullable(); // Size in bytes
            $table->timestamp('estimated_completion')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('export_parameters')->nullable(); // Filter options, format preferences
            $table->integer('progress_percentage')->default(0); // For progress tracking
            $table->integer('total_employees')->default(0);
            $table->integer('processed_employees')->default(0);
            $table->string('user_role', 20); // Track role for security
            $table->text('download_url')->nullable(); // Signed URL for download
            $table->timestamp('download_expires_at')->nullable(); // When download link expires
            $table->timestamps();

            // Indexes for performance
            $table->index('job_id');
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queued_exports');
    }
};
