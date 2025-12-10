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
        // Create offices table
        if (!Schema::hasTable('offices')) {
            Schema::create('offices', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('code', 50)->unique();
                $table->text('description')->nullable();
                $table->string('head_of_office', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('parent_office_code', 50)->nullable();
                $table->integer('level')->default(1);
                $table->string('address')->nullable();
                $table->string('contact_number', 50)->nullable();
                $table->string('email', 255)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['code', 'is_active']);
                $table->index('parent_office_code');
            });
        }

        // Create success_indicators table
        if (!Schema::hasTable('success_indicators')) {
            Schema::create('success_indicators', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('major_final_output_id');
                $table->string('category', 100)->nullable();
                $table->string('type', 50)->nullable(); // Output, Outcome, etc.
                $table->integer('weight')->default(1);
                $table->integer('target_quality')->nullable();
                $table->string('unit_of_measure', 50)->nullable();
                $table->integer('target_quality_score')->nullable();
                $table->integer('target_efficiency_score')->nullable();
                $table->integer('target_timeliness_score')->nullable();
                $table->date('target_date')->nullable();
                $table->integer('accomplished_quality')->nullable();
                $table->integer('accomplished_quality_score')->nullable();
                $table->integer('accomplished_efficiency_score')->nullable();
                $table->integer('accomplished_timeliness_score')->nullable();
                $table->text('accomplishment_remarks')->nullable();
                $table->text('evidence_file_path')->nullable();
                $table->text('challenges_encountered')->nullable();
                $table->string('status', 50)->default('pending'); // pending, in_progress, completed
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('major_final_output_id')->references('id')->on('major_final_outputs')->onDelete('cascade');
                $table->index(['major_final_output_id', 'status']);
                $table->index(['category', 'type']);
            });
        }

        // Create office_assignments table
        if (!Schema::hasTable('office_assignments')) {
            Schema::create('office_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('office_id');
                $table->string('role', 50); // Department Head, Assessor, Final Approver
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('office_id')->references('id')->on('offices')->onDelete('cascade');
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

                $table->unique(['user_id', 'office_id', 'role']);
                $table->index(['office_id', 'role', 'is_active']);
            });
        }

        // Create opcr_workflows table
        if (!Schema::hasTable('opcr_workflows')) {
            Schema::create('opcr_workflows', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('summary')->nullable();
                $table->unsignedBigInteger('office_id')->nullable();
                $table->unsignedBigInteger('period_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('department_head_id')->nullable();
                $table->unsignedBigInteger('assessor_id')->nullable();
                $table->unsignedBigInteger('final_approver_id')->nullable();
                $table->string('workflow_state', 50)->default('draft'); // draft, committed, in_progress, evaluation, final_approval
                $table->date('commitment_date')->nullable();
                $table->date('accomplishment_date')->nullable();
                $table->date('evaluation_date')->nullable();
                $table->date('final_approval_date')->nullable();
                $table->decimal('overall_rating', 5, 2)->nullable();
                $table->string('adjectival_rating', 50)->nullable(); // Outstanding, Very Satisfactory, etc.
                $table->text('final_remarks')->nullable();
                $table->text('department_head_remarks')->nullable();
                $table->text('assessor_remarks')->nullable();
                $table->text('final_approver_remarks')->nullable();
                $table->text('file_attachments')->nullable(); // JSON array of file paths
                $table->boolean('is_archived')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('office_id')->references('id')->on('offices')->onDelete('set null');
                $table->foreign('period_id')->references('id')->on('performance_periods')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('department_head_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('assessor_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('final_approver_id')->references('id')->on('users')->onDelete('set null');

                $table->index(['office_id', 'period_id', 'workflow_state']);
                $table->index(['workflow_state', 'is_archived']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opcr_workflows');
        Schema::dropIfExists('office_assignments');
        Schema::dropIfExists('success_indicators');
        Schema::dropIfExists('offices');
    }
};
