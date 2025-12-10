<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create PerformanceEvaluation records for existing OPCR workflows
        $this->createMissingPerformanceEvaluations();

        // Update OPCR workflows with evaluation linkage
        $this->updateOPCRWorkflowEvaluationLinkage();

        // Fix workflows stuck in 'evaluation' state
        $this->fixStuckWorkflows();
    }

    /**
     * Create PerformanceEvaluation records for existing OPCR workflows that don't have them
     */
    private function createMissingPerformanceEvaluations(): void
    {
        // Get all OPCR workflows without performance evaluation linkage
        $workflowsWithoutEvaluation = DB::table('opcr_workflows')
            ->whereNull('performance_evaluation_id')
            ->get();

        foreach ($workflowsWithoutEvaluation as $workflow) {
            // Get any employee to associate with the evaluation
            // Use the first employee as a fallback since this is for organizational OPCR
            $employee = DB::table('employees')
                ->whereNull('deleted_at')
                ->limit(1)
                ->first();

            if (!$employee) {
                Log::error('No employees found to create PerformanceEvaluation', [
                    'workflow_id' => $workflow->id,
                ]);
                continue;
            }

            // Calculate overall rating from workflow targets if available
            $overallRating = $this->calculateWorkflowOverallRating($workflow->id);

            // Create PerformanceEvaluation record
            $evaluationId = DB::table('performance_evaluations')->insertGetId([
                'opcr_workflow_id' => $workflow->id,
                'employee_id' => $employee->id,
                'office_id' => $workflow->office_id,
                'period_id' => $workflow->period_id,
                'evaluation_period' => $this->getEvaluationPeriod($workflow->period_id),
                'evaluation_date' => now()->format('Y-m-d'),
                'period_start' => $this->getPeriodStartDate($workflow->period_id),
                'period_end' => $this->getPeriodEndDate($workflow->period_id),
                'overall_rating' => $overallRating,
                'overall_qet_rating' => $overallRating,
                'overall_adjectival_rating' => $this->getAdjectivalRating($overallRating),
                'goal_achievement_percentage' => $overallRating * 20, // Convert to percentage
                'evaluation_status' => 'completed',
                'workflow_state' => $workflow->workflow_state === 'evaluation' ? 'final_approval' : $workflow->workflow_state,
                'opcr_type' => 'organizational',
                'evaluator_id' => $workflow->assessed_by,
                'assessor_id' => $workflow->assessed_by,
                'assessment_completed_at' => $workflow->assessed_at ?? now(),
                'committed_at' => $workflow->committed_at,
                'committed_by' => $workflow->committed_by,
                'approved_by' => $workflow->approved_by,
                'approved_at' => $workflow->approved_at,
                'submitted_at' => $workflow->submitted_at,
                'created_at' => $workflow->created_at,
                'updated_at' => now(),
            ]);

            // Update OPCR workflow with evaluation linkage
            DB::table('opcr_workflows')
                ->where('id', $workflow->id)
                ->update([
                    'performance_evaluation_id' => $evaluationId,
                    'updated_at' => now(),
                ]);

            // Log the creation
            Log::info('Created PerformanceEvaluation for OPCR workflow', [
                'workflow_id' => $workflow->id,
                'evaluation_id' => $evaluationId,
                'employee_id' => $employee->id,
                'office_id' => $workflow->office_id,
                'period_id' => $workflow->period_id,
            ]);
        }

        Log::info('PerformanceEvaluation records created for OPCR workflows', [
            'workflows_processed' => $workflowsWithoutEvaluation->count(),
        ]);
    }

    /**
     * Update OPCR workflows with proper evaluation linkage
     */
    private function updateOPCRWorkflowEvaluationLinkage(): void
    {
        // Update workflows that might have performance evaluations but no linkage
        DB::statement('
            UPDATE opcr_workflows ow
            SET performance_evaluation_id = (
                SELECT MIN(pe.id)
                FROM performance_evaluations pe
                WHERE pe.opcr_workflow_id = ow.id
            )
            WHERE performance_evaluation_id IS NULL
            AND EXISTS (
                SELECT 1 FROM performance_evaluations pe
                WHERE pe.opcr_workflow_id = ow.id
            )
        ');
    }

    /**
     * Fix workflows stuck in 'evaluation' state by moving them to final_approval
     */
    private function fixStuckWorkflows(): void
    {
        // Get workflows stuck in evaluation state that have complete evaluations
        $stuckWorkflows = DB::table('opcr_workflows')
            ->where('workflow_state', 'evaluation')
            ->whereNotNull('performance_evaluation_id')
            ->get();

        foreach ($stuckWorkflows as $workflow) {
            // Check if the evaluation is complete by verifying targets have accomplishments
            $evaluationComplete = DB::table('performance_targets')
                ->where('opcr_workflow_id', $workflow->id)
                ->whereNotNull('accomplished_quality')
                ->whereNotNull('performance_percentage')
                ->count() > 0;

            if ($evaluationComplete) {
                DB::table('opcr_workflows')
                    ->where('id', $workflow->id)
                    ->update([
                        'workflow_state' => 'final_approval',
                        'updated_at' => now(),
                    ]);

                // Also update the corresponding performance evaluation
                DB::table('performance_evaluations')
                    ->where('id', $workflow->performance_evaluation_id)
                    ->update([
                        'workflow_state' => 'final_approval',
                        'evaluation_status' => 'completed',
                        'updated_at' => now(),
                    ]);

                Log::info('Fixed stuck OPCR workflow', [
                    'workflow_id' => $workflow->id,
                    'evaluation_id' => $workflow->performance_evaluation_id,
                    'previous_state' => 'evaluation',
                    'new_state' => 'final_approval',
                ]);
            }
        }

        Log::info('Fixed stuck OPCR workflows', [
            'workflows_fixed' => $stuckWorkflows->count(),
        ]);
    }

    /**
     * Calculate overall rating for a workflow based on its targets
     */
    private function calculateWorkflowOverallRating($workflowId): float
    {
        $ratings = DB::table('performance_ratings')
            ->join('performance_targets', 'performance_ratings.target_id', '=', 'performance_targets.id')
            ->where('performance_targets.opcr_workflow_id', $workflowId)
            ->whereNotNull('performance_ratings.final_rating')
            ->pluck('performance_ratings.final_rating');

        if ($ratings->isEmpty()) {
            return 3.0; // Default satisfactory rating
        }

        return round($ratings->avg(), 2);
    }

    /**
     * Get evaluation period description
     */
    private function getEvaluationPeriod($periodId): string
    {
        $period = DB::table('performance_periods')
            ->where('id', $periodId)
            ->first();

        return $period ? $period->name : 'Annual Evaluation 2024';
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate($periodId): string
    {
        $period = DB::table('performance_periods')
            ->where('id', $periodId)
            ->first();

        return $period ? $period->start_date : '2024-01-01';
    }

    /**
     * Get period end date
     */
    private function getPeriodEndDate($periodId): string
    {
        $period = DB::table('performance_periods')
            ->where('id', $periodId)
            ->first();

        return $period ? $period->end_date : '2024-12-31';
    }

    /**
     * Get adjectival rating based on numeric rating
     */
    private function getAdjectivalRating($rating): string
    {
        return match(true) {
            $rating >= 4.5 => 'Outstanding',
            $rating >= 3.5 => 'Very Satisfactory',
            $rating >= 2.5 => 'Satisfactory',
            $rating >= 1.5 => 'Unsatisfactory',
            default => 'Poor',
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove performance_evaluation_id from OPCR workflows
        DB::table('opcr_workflows')
            ->whereNotNull('performance_evaluation_id')
            ->update(['performance_evaluation_id' => null]);

        // Delete performance evaluations that were created for OPCR workflows
        DB::table('performance_evaluations')
            ->whereNotNull('opcr_workflow_id')
            ->delete();
    }
};
