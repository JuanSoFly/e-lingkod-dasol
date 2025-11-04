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
        Schema::create('opcr_ipcr_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opcr_workflow_id')->nullable()->constrained('opcr_workflows')->nullOnDelete();
            $table->foreignId('performance_target_id')->nullable()->constrained('performance_targets')->nullOnDelete();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('ipcr_item_id')->nullable()->constrained('ipcr_items')->cascadeOnDelete();
            $table->string('allocation_strategy', 100)->nullable();
            $table->decimal('weight_percentage', 5, 2)->default(0);
            $table->string('cascade_level', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['opcr_workflow_id', 'performance_target_id', 'ipcr_id', 'ipcr_item_id'], 'opcr_ipcr_mapping_unique');
            $table->index(['ipcr_id', 'weight_percentage']);
        });

        Schema::create('weight_distribution_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_name');
            $table->string('applies_to_role', 100)->nullable();
            $table->string('allocation_method', 100);
            $table->decimal('default_weight', 5, 2)->nullable();
            $table->decimal('minimum_weight', 5, 2)->nullable();
            $table->decimal('maximum_weight', 5, 2)->nullable();
            $table->unsignedSmallInteger('priority')->default(10);
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'is_active', 'priority']);
            $table->index(['applies_to_role', 'is_active']);
            $table->index(['effective_start_date', 'effective_end_date'], 'weight_rules_effective_dates_index');
        });

        Schema::create('workflow_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attachment_type', 100)->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id'], 'workflow_attachable_index');
            $table->index(['attachment_type', 'uploaded_at']);
        });

        Schema::create('mid_period_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('ipcr_item_id')->nullable()->constrained('ipcr_items')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('adjustment_type', 100);
            $table->json('original_values')->nullable();
            $table->json('proposed_values')->nullable();
            $table->text('justification')->nullable();
            $table->text('decision_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ipcr_id', 'status']);
            $table->index(['requested_by', 'requested_at']);
            $table->index(['approved_by', 'approved_at']);
        });

        Schema::create('performance_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('ipcr_item_id')->nullable()->constrained('ipcr_items')->cascadeOnDelete();
            $table->string('linked_type');
            $table->unsignedBigInteger('linked_id');
            $table->string('link_category', 100)->nullable();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['linked_type', 'linked_id'], 'performance_links_polymorphic_index');
            $table->index(['ipcr_id', 'link_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_links');
        Schema::dropIfExists('mid_period_adjustments');
        Schema::dropIfExists('workflow_attachments');
        Schema::dropIfExists('weight_distribution_rules');
        Schema::dropIfExists('opcr_ipcr_mappings');
    }
};
