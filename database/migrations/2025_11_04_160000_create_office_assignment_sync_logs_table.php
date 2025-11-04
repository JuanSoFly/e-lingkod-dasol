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
        Schema::create('office_assignment_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');

            // Sync operation details
            $table->string('operation_type'); // 'synchronize', 'deactivate', 'create'
            $table->json('details')->nullable(); // Store sync details

            // Office changes
            $table->foreignId('old_office_id')->nullable()->constrained('offices')->onDelete('set null');
            $table->foreignId('new_office_id')->nullable()->constrained('offices')->onDelete('set null');

            // Assignment tracking
            $table->json('deactivated_assignments')->nullable(); // Array of assignment IDs
            $table->json('created_assignments')->nullable(); // Array of assignment IDs
            $table->json('updated_assignments')->nullable(); // Array of assignment IDs

            // Status and timestamps
            $table->string('status')->default('completed'); // 'completed', 'failed', 'partial'
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['employee_id', 'created_at']);
            $table->index(['performed_by', 'created_at']);
            $table->index(['operation_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_assignment_sync_logs');
    }
};