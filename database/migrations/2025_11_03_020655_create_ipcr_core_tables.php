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
        Schema::create('ipcrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('period_id')->constrained('performance_periods')->cascadeOnDelete();
            $table->foreignId('opcr_workflow_id')->nullable()->constrained('opcr_workflows')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('head_of_office_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('pmt_validator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('final_approver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('draft');
            $table->decimal('total_weight', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('adjectival_rating', 100)->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->timestamp('head_reviewed_at')->nullable();
            $table->timestamp('pmt_validated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'period_id'], 'ipcr_employee_period_unique');
            $table->index(['office_id', 'status']);
            $table->index(['period_id', 'status']);
            $table->index('opcr_workflow_id');
            $table->index('supervisor_id');
            $table->index('pmt_validator_id');
        });

        Schema::create('ipcr_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('performance_target_id')->nullable()->constrained('performance_targets')->nullOnDelete();
            $table->foreignId('success_indicator_id')->nullable()->constrained('success_indicators')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(0);
            $table->string('measure', 150)->nullable();
            $table->decimal('target_quality', 10, 2)->nullable();
            $table->string('target_efficiency', 150)->nullable();
            $table->string('target_timeliness', 150)->nullable();
            $table->decimal('accomplished_quality', 10, 2)->nullable();
            $table->string('accomplished_efficiency', 150)->nullable();
            $table->string('accomplished_timeliness', 150)->nullable();
            $table->decimal('self_rating', 4, 2)->nullable();
            $table->json('self_rating_details')->nullable();
            $table->decimal('supervisor_rating', 4, 2)->nullable();
            $table->json('supervisor_rating_details')->nullable();
            $table->decimal('head_rating', 4, 2)->nullable();
            $table->json('head_rating_details')->nullable();
            $table->decimal('pmt_rating', 4, 2)->nullable();
            $table->json('pmt_rating_details')->nullable();
            $table->decimal('final_rating', 4, 2)->nullable();
            $table->json('final_rating_details')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ipcr_id', 'sequence']);
            $table->index('performance_target_id');
            $table->index('success_indicator_id');
        });

        Schema::create('ipcr_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('ipcr_item_id')->nullable()->constrained('ipcr_items')->cascadeOnDelete();
            $table->foreignId('rater_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rater_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('rater_role', 50);
            $table->string('rating_type', 50);
            $table->decimal('quality_rating', 4, 2)->nullable();
            $table->decimal('efficiency_rating', 4, 2)->nullable();
            $table->decimal('timeliness_rating', 4, 2)->nullable();
            $table->decimal('overall_rating', 4, 2)->nullable();
            $table->json('rating_details')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('rated_at')->nullable();
            $table->timestamps();

            $table->index(['ipcr_id', 'rating_type']);
            $table->index('ipcr_item_id');
            $table->index(['rater_role', 'rated_at']);
        });

        Schema::create('ipcr_workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipcr_id')->constrained('ipcrs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('from_state', 50)->nullable();
            $table->string('to_state', 50);
            $table->string('action', 100)->nullable();
            $table->string('performed_role', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();

            $table->index(['ipcr_id', 'performed_at']);
            $table->index('user_id');
            $table->index('to_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipcr_workflow_logs');
        Schema::dropIfExists('ipcr_ratings');
        Schema::dropIfExists('ipcr_items');
        Schema::dropIfExists('ipcrs');
    }
};
