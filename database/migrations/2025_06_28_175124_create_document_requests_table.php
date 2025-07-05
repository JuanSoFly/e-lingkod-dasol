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
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('document_type', 100); // certificate_of_employment, service_record, etc.
            $table->string('document_name', 255);
            $table->text('purpose')->nullable(); // Purpose of the document request
            $table->text('description')->nullable(); // Additional details
            $table->enum('status', ['pending', 'processing', 'ready', 'completed', 'rejected'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('needed_by')->nullable(); // When the document is needed
            $table->json('additional_data')->nullable(); // For custom requirements
            
            // Processing fields
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Delivery/pickup information
            $table->enum('delivery_method', ['pickup', 'email', 'courier'])->default('pickup');
            $table->string('delivery_address')->nullable();
            $table->string('delivery_contact')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Document generation fields
            $table->string('generated_file_path')->nullable();
            $table->string('generated_file_name')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            $table->timestamp('expires_at')->nullable(); // For temporary document access
            
            // Audit fields
            $table->json('audit_trail')->nullable(); // Status change history
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['employee_id', 'status']);
            $table->index(['requested_by_user_id', 'created_at']);
            $table->index(['status', 'priority']);
            $table->index(['document_type', 'status']);
            $table->index('needed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
