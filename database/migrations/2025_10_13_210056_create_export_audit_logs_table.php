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
        Schema::create('export_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('export_type', 20); // 'single', 'batch'
            $table->bigInteger('file_size')->nullable();
            $table->string('file_name')->nullable();
            $table->string('export_format', 10); // 'xlsx', 'csv'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('export_parameters')->nullable(); // Store filter options, etc.
            $table->string('user_role', 20); // Track role for audit purposes
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['employee_id', 'created_at']);
            $table->index('status');
            $table->index('export_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_audit_logs');
    }
};
