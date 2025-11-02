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
        Schema::table('performance_evaluations', function (Blueprint $table) {
            // Link to OPCR workflow
            $table->foreignId('opcr_workflow_id')->nullable()->after('id')->constrained('opcr_workflows')->onDelete('set null');

            // Enhanced workflow state for 5-stage OPCR process
            $table->string('workflow_state', 50)->default('draft')->after('evaluation_status'); // draft, committed, in_progress, evaluation, final_approval
            $table->string('previous_state', 50)->nullable()->after('workflow_state'); // Track state changes

            // Office relationship
            $table->foreignId('office_id')->nullable()->after('employee_id')->constrained()->onDelete('set null');
            $table->foreignId('period_id')->nullable()->after('office_id')->constrained('performance_periods')->onDelete('set null');

            // OPCR-specific fields
            $table->string('opcr_type', 50)->default('individual')->after('workflow_state'); // individual, office
            $table->decimal('overall_qet_rating', 3, 2)->nullable()->after('overall_rating'); // QET-based overall rating
            $table->string('overall_adjectival_rating', 50)->nullable()->after('overall_qet_rating'); // Adjectival rating

            // OPCR workflow timestamps
            $table->timestamp('committed_at')->nullable()->after('submitted_at');
            $table->foreignId('committed_by')->nullable()->after('committed_at')->constrained('users')->onDelete('set null');
            $table->timestamp('assessment_started_at')->nullable()->after('approved_at');
            $table->timestamp('assessment_completed_at')->nullable()->after('assessment_started_at');
            $table->foreignId('assessor_id')->nullable()->after('assessment_completed_at')->constrained('users')->onDelete('set null');

            // Return workflow
            $table->text('return_reason')->nullable()->after('employee_comments');
            $table->timestamp('returned_at')->nullable()->after('return_reason');
            $table->foreignId('returned_by')->nullable()->after('returned_at')->constrained('users')->onDelete('set null');

            // Legacy compatibility
            $table->boolean('is_legacy_ipcr')->default(false)->after('leadership_potential');

            // Additional OPCR fields
            $table->text('performance_summary')->nullable()->after('development_plan');
            $table->text('recommendations')->nullable()->after('performance_summary');
            $table->json('mfo_ratings')->nullable()->after('recommendations'); // Store MFO-level ratings

            // Indexes
            $table->index(['opcr_workflow_id']);
            $table->index(['workflow_state', 'previous_state']);
            $table->index(['office_id', 'period_id', 'workflow_state']);
            $table->index(['opcr_type', 'workflow_state']);
            $table->index(['committed_by', 'committed_at']);
            $table->index(['assessor_id', 'assessment_completed_at'], 'pe_eval_assessor_assessment_index');
            $table->index('is_legacy_ipcr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_evaluations', function (Blueprint $table) {
            $table->dropForeign(['opcr_workflow_id']);
            $table->dropForeign(['office_id']);
            $table->dropForeign(['period_id']);
            $table->dropForeign(['committed_by']);
            $table->dropForeign(['assessor_id']);
            $table->dropForeign(['returned_by']);
            $table->dropColumn([
                'opcr_workflow_id',
                'workflow_state',
                'previous_state',
                'office_id',
                'period_id',
                'opcr_type',
                'overall_qet_rating',
                'overall_adjectival_rating',
                'committed_at',
                'committed_by',
                'assessment_started_at',
                'assessment_completed_at',
                'assessor_id',
                'return_reason',
                'returned_at',
                'returned_by',
                'is_legacy_ipcr',
                'performance_summary',
                'recommendations',
                'mfo_ratings'
            ]);
        });
    }
};
