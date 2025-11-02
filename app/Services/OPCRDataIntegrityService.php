<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use App\Models\OfficeAssignment;
use App\Models\PerformancePeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OPCRDataIntegrityService
{
    /**
     * Perform comprehensive data integrity check
     */
    public function performFullIntegrityCheck(): array
    {
        $results = [
            'timestamp' => now()->toISOString(),
            'checks' => [],
            'errors' => [],
            'warnings' => [],
            'summary' => [
                'total_checks' => 0,
                'passed_checks' => 0,
                'failed_checks' => 0,
                'warnings' => 0,
            ],
        ];

        // Run all integrity checks
        $checks = [
            'workflow_state_consistency' => $this->checkWorkflowStateConsistency(),
            'target_workflow_relationships' => $this->checkTargetWorkflowRelationships(),
            'rating_calculation_integrity' => $this->checkRatingCalculationIntegrity(),
            'office_assignment_integrity' => $this->checkOfficeAssignmentIntegrity(),
            'performance_period_integrity' => $this->checkPerformancePeriodIntegrity(),
            'mfo_si_relationship_integrity' => $this->checkMFOSIRelationshipIntegrity(),
            'orphaned_records' => $this->checkOrphanedRecords(),
            'rating_value_ranges' => $this->checkRatingValueRanges(),
            'workflow_completeness' => $this->checkWorkflowCompleteness(),
            'duplicate_prevention' => $this->checkDuplicatePrevention(),
        ];

        foreach ($checks as $checkName => $checkResult) {
            $results['checks'][$checkName] = $checkResult;
            $results['summary']['total_checks']++;

            if ($checkResult['status'] === 'passed') {
                $results['summary']['passed_checks']++;
            } elseif ($checkResult['status'] === 'failed') {
                $results['summary']['failed_checks']++;
                $results['errors'] = array_merge($results['errors'], $checkResult['errors'] ?? []);
            } elseif ($checkResult['status'] === 'warning') {
                $results['summary']['warnings']++;
                $results['warnings'] = array_merge($results['warnings'], $checkResult['warnings'] ?? []);
            }
        }

        // Log integrity check results
        Log::info('OPCR data integrity check completed', [
            'summary' => $results['summary'],
            'error_count' => count($results['errors']),
            'warning_count' => count($results['warnings']),
        ]);

        return $results;
    }

    /**
     * Check workflow state consistency
     */
    public function checkWorkflowStateConsistency(): array
    {
        $issues = [];

        // Check for workflows in invalid states
        $invalidStates = DB::table('opcr_workflows')
            ->whereNotIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation', 'final_approval', 'approved', 'returned'])
            ->get();

        foreach ($invalidStates as $workflow) {
            $issues[] = "Workflow ID {$workflow->id} has invalid state: {$workflow->workflow_state}";
        }

        // Check for workflows stuck in evaluation state for too long
        $stuckWorkflows = DB::table('opcr_workflows')
            ->where('workflow_state', 'evaluation')
            ->where('updated_at', '<', now()->subDays(30))
            ->get();

        foreach ($stuckWorkflows as $workflow) {
            $issues[] = "Workflow ID {$workflow->id} stuck in evaluation state for over 30 days";
        }

        // Check for inconsistent approval timestamps
        $inconsistentTimestamps = DB::table('opcr_workflows')
            ->whereNotNull('committed_at')
            ->whereNotNull('assessed_at')
            ->whereRaw('assessed_at < committed_at')
            ->get();

        foreach ($inconsistentTimestamps as $workflow) {
            $issues[] = "Workflow ID {$workflow->id} has inconsistent approval timestamps";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'All workflow states are consistent' : 'Found workflow state inconsistencies',
            'errors' => $issues,
            'details' => [
                'invalid_states_count' => $invalidStates->count(),
                'stuck_workflows_count' => $stuckWorkflows->count(),
                'inconsistent_timestamps_count' => $inconsistentTimestamps->count(),
            ],
        ];
    }

    /**
     * Check target-workflow relationships
     */
    public function checkTargetWorkflowRelationships(): array
    {
        $issues = [];

        // Check for orphaned targets (targets without valid workflows)
        $orphanedTargets = DB::table('performance_targets as pt')
            ->leftJoin('opcr_workflows as ow', 'pt.opcr_workflow_id', '=', 'ow.id')
            ->whereNull('ow.id')
            ->whereNotNull('pt.opcr_workflow_id')
            ->get();

        foreach ($orphanedTargets as $target) {
            $issues[] = "Target ID {$target->id} references non-existent workflow ID {$target->opcr_workflow_id}";
        }

        // Check for workflows without targets
        $workflowsWithoutTargets = DB::table('opcr_workflows')
            ->leftJoin('performance_targets as pt', 'opcr_workflows.id', '=', 'pt.opcr_workflow_id')
            ->whereNull('pt.id')
            ->whereNotIn('opcr_workflows.workflow_state', ['draft', 'returned'])
            ->get();

        foreach ($workflowsWithoutTargets as $workflow) {
            $issues[] = "Workflow ID {$workflow->id} has no performance targets";
        }

        // Check for targets with invalid MFO or SI relationships
        $invalidMFORelationships = DB::table('performance_targets as pt')
            ->leftJoin('major_final_outputs as mfo', 'pt.mfo_id', '=', 'mfo.id')
            ->whereNotNull('pt.mfo_id')
            ->whereNull('mfo.id')
            ->get();

        foreach ($invalidMFORelationships as $target) {
            $issues[] = "Target ID {$target->id} references non-existent MFO ID {$target->mfo_id}";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'All target-workflow relationships are valid' : 'Found relationship issues',
            'errors' => $issues,
            'details' => [
                'orphaned_targets_count' => $orphanedTargets->count(),
                'workflows_without_targets_count' => $workflowsWithoutTargets->count(),
                'invalid_mfo_relationships_count' => $invalidMFORelationships->count(),
            ],
        ];
    }

    /**
     * Check rating calculation integrity
     */
    public function checkRatingCalculationIntegrity(): array
    {
        $issues = [];
        $warnings = [];

        // Check for ratings with invalid QET calculations
        $ratings = DB::table('performance_ratings as pr')
            ->join('performance_targets as pt', 'pr.performance_target_id', '=', 'pt.id')
            ->select('pr.*', 'pt.opcr_workflow_id')
            ->get();

        foreach ($ratings as $rating) {
            $calculatedFinal = ($rating->quantity_rating + $rating->efficiency_rating + $rating->timeliness_rating) / 3;

            // Allow for small floating point differences
            if (abs($rating->final_rating - $calculatedFinal) > 0.01) {
                $issues[] = "Rating ID {$rating->id} has incorrect final rating calculation. " .
                    "Expected: {$calculatedFinal}, Found: {$rating->final_rating}";
            }

            // Check for extreme rating values
            if ($rating->final_rating > 5 || $rating->final_rating < 0) {
                $issues[] = "Rating ID {$rating->id} has invalid final rating: {$rating->final_rating}";
            }

            // Warn about all-zero ratings
            if ($rating->final_rating == 0) {
                $warnings[] = "Rating ID {$rating->id} has zero rating (possible data entry issue)";
            }
        }

        return [
            'status' => empty($issues) ? (empty($warnings) ? 'passed' : 'warning') : 'failed',
            'message' => empty($issues)
                ? (empty($warnings) ? 'All rating calculations are correct' : 'Rating calculations correct with warnings')
                : 'Found rating calculation errors',
            'errors' => $issues,
            'warnings' => $warnings,
            'details' => [
                'total_ratings_checked' => $ratings->count(),
                'calculation_errors' => count($issues),
                'zero_rating_warnings' => collect($warnings)->filter(function ($w) {
                    return strpos($w, 'zero rating') !== false;
                })->count(),
            ],
        ];
    }

    /**
     * Check office assignment integrity
     */
    public function checkOfficeAssignmentIntegrity(): array
    {
        $issues = [];

        // Check for assignments with invalid users
        $invalidUserAssignments = DB::table('office_assignments as oa')
            ->leftJoin('users as u', 'oa.user_id', '=', 'u.id')
            ->whereNotNull('oa.user_id')
            ->whereNull('u.id')
            ->get();

        foreach ($invalidUserAssignments as $assignment) {
            $issues[] = "Office assignment ID {$assignment->id} references non-existent user ID {$assignment->user_id}";
        }

        // Check for assignments with invalid offices
        $invalidOfficeAssignments = DB::table('office_assignments as oa')
            ->leftJoin('offices as o', 'oa.office_id', '=', 'o.id')
            ->whereNotNull('oa.office_id')
            ->whereNull('o.id')
            ->get();

        foreach ($invalidOfficeAssignments as $assignment) {
            $issues[] = "Office assignment ID {$assignment->id} references non-existent office ID {$assignment->office_id}";
        }

        // Check for duplicate active assignments
        $duplicateAssignments = DB::table('office_assignments')
            ->select('user_id', 'office_id', 'role', DB::raw('COUNT(*) as count'))
            ->where('is_active', true)
            ->groupBy('user_id', 'office_id', 'role')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateAssignments as $duplicate) {
            $issues[] = "Duplicate active assignment found: User {$duplicate->user_id}, Office {$duplicate->office_id}, Role {$duplicate->role}";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'All office assignments are valid' : 'Found office assignment issues',
            'errors' => $issues,
            'details' => [
                'invalid_user_assignments_count' => $invalidUserAssignments->count(),
                'invalid_office_assignments_count' => $invalidOfficeAssignments->count(),
                'duplicate_assignments_count' => $duplicateAssignments->count(),
            ],
        ];
    }

    /**
     * Check performance period integrity
     */
    public function checkPerformancePeriodIntegrity(): array
    {
        $issues = [];
        $warnings = [];

        // Check for multiple active periods
        $activePeriods = DB::table('performance_periods')
            ->where('is_active', true)
            ->get();

        if ($activePeriods->count() > 1) {
            $issues[] = 'Multiple performance periods are marked as active';
        }

        // Check for overlapping periods
        $overlappingPeriods = DB::select("
            SELECT p1.id as period1_id, p1.name as period1_name,
                   p2.id as period2_id, p2.name as period2_name
            FROM performance_periods p1
            JOIN performance_periods p2 ON (
                p1.id < p2.id AND
                p1.start_date <= p2.end_date AND
                p1.end_date >= p2.start_date
            )
        ");

        foreach ($overlappingPeriods as $overlap) {
            $issues[] = "Overlapping periods: {$overlap->period1_name} and {$overlap->period2_name}";
        }

        // Check for periods with invalid date ranges
        $invalidDateRanges = DB::table('performance_periods')
            ->whereRaw('start_date >= end_date')
            ->get();

        foreach ($invalidDateRanges as $period) {
            $issues[] = "Period ID {$period->id} has invalid date range (start >= end)";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'All performance periods are valid' : 'Found period integrity issues',
            'errors' => $issues,
            'details' => [
                'active_periods_count' => $activePeriods->count(),
                'overlapping_periods_count' => count($overlappingPeriods),
                'invalid_date_ranges_count' => $invalidDateRanges->count(),
            ],
        ];
    }

    /**
     * Check MFO-SI relationship integrity
     */
    public function checkMFOSIRelationshipIntegrity(): array
    {
        $issues = [];

        // Check for success indicators with invalid MFO references
        $invalidSIReferences = DB::table('success_indicators as si')
            ->leftJoin('major_final_outputs as mfo', 'si.mfo_id', '=', 'mfo.id')
            ->whereNotNull('si.mfo_id')
            ->whereNull('mfo.id')
            ->get();

        foreach ($invalidSIReferences as $si) {
            $issues[] = "Success Indicator ID {$si->id} references non-existent MFO ID {$si->mfo_id}";
        }

        // Check for MFOs without success indicators
        $mfosWithoutSI = DB::table('major_final_outputs as mfo')
            ->leftJoin('success_indicators as si', 'mfo.id', '=', 'si.mfo_id')
            ->whereNull('si.id')
            ->get();

        foreach ($mfosWithoutSI as $mfo) {
            $issues[] = "MFO ID {$mfo->id} ({$mfo->code}) has no success indicators";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'All MFO-SI relationships are valid' : 'Found MFO-SI relationship issues',
            'errors' => $issues,
            'details' => [
                'invalid_si_references_count' => $invalidSIReferences->count(),
                'mfos_without_si_count' => $mfosWithoutSI->count(),
            ],
        ];
    }

    /**
     * Check for orphaned records
     */
    public function checkOrphanedRecords(): array
    {
        $issues = [];

        // Check for ratings without targets
        $orphanedRatings = DB::table('performance_ratings as pr')
            ->leftJoin('performance_targets as pt', 'pr.performance_target_id', '=', 'pt.id')
            ->whereNull('pt.id')
            ->get();

        foreach ($orphanedRatings as $rating) {
            $issues[] = "Rating ID {$rating->id} references non-existent target ID {$rating->performance_target_id}";
        }

        // Check for targets without workflows
        $orphanedTargets = DB::table('performance_targets as pt')
            ->leftJoin('opcr_workflows as ow', 'pt.opcr_workflow_id', '=', 'ow.id')
            ->whereNotNull('pt.opcr_workflow_id')
            ->whereNull('ow.id')
            ->get();

        foreach ($orphanedTargets as $target) {
            $issues[] = "Target ID {$target->id} references non-existent workflow ID {$target->opcr_workflow_id}";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'No orphaned records found' : 'Found orphaned records',
            'errors' => $issues,
            'details' => [
                'orphaned_ratings_count' => $orphanedRatings->count(),
                'orphaned_targets_count' => $orphanedTargets->count(),
            ],
        ];
    }

    /**
     * Check rating value ranges
     */
    public function checkRatingValueRanges(): array
    {
        $issues = [];

        // Check for ratings outside valid range (0-5)
        $invalidRatings = DB::table('performance_ratings')
            ->where(function ($query) {
                $query->where('quantity_rating', '<', 0)
                      ->orWhere('quantity_rating', '>', 5)
                      ->orWhere('efficiency_rating', '<', 0)
                      ->orWhere('efficiency_rating', '>', 5)
                      ->orWhere('timeliness_rating', '<', 0)
                      ->orWhere('timeliness_rating', '>', 5)
                      ->orWhere('final_rating', '<', 0)
                      ->orWhere('final_rating', '>', 5);
            })
            ->get();

        foreach ($invalidRatings as $rating) {
            $issues[] = "Rating ID {$rating->id} has values outside valid range (0-5)";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'All rating values are within valid ranges' : 'Found invalid rating values',
            'errors' => $issues,
            'details' => [
                'invalid_ratings_count' => $invalidRatings->count(),
            ],
        ];
    }

    /**
     * Check workflow completeness
     */
    public function checkWorkflowCompleteness(): array
    {
        $warnings = [];

        // Check for workflows missing required data
        $incompleteWorkflows = DB::table('opcr_workflows')
            ->where('workflow_state', 'approved')
            ->where(function ($query) {
                $query->whereNull('committed_by')
                      ->orWhereNull('assessed_by')
                      ->orWhereNull('approved_by')
                      ->orWhereNull('overall_rating');
            })
            ->get();

        foreach ($incompleteWorkflows as $workflow) {
            $missingFields = [];
            if (is_null($workflow->committed_by)) $missingFields[] = 'committed_by';
            if (is_null($workflow->assessed_by)) $missingFields[] = 'assessed_by';
            if (is_null($workflow->approved_by)) $missingFields[] = 'approved_by';
            if (is_null($workflow->overall_rating)) $missingFields[] = 'overall_rating';

            $warnings[] = "Approved workflow ID {$workflow->id} is missing required fields: " . implode(', ', $missingFields);
        }

        return [
            'status' => empty($warnings) ? 'passed' : 'warning',
            'message' => empty($warnings) ? 'All approved workflows are complete' : 'Found incomplete approved workflows',
            'warnings' => $warnings,
            'details' => [
                'incomplete_workflows_count' => $incompleteWorkflows->count(),
            ],
        ];
    }

    /**
     * Check duplicate prevention
     */
    public function checkDuplicatePrevention(): array
    {
        $issues = [];

        // Check for duplicate OPCR workflows (same office and period)
        $duplicateOPCR = DB::table('opcr_workflows')
            ->select('office_id', 'period_id', DB::raw('COUNT(*) as count'))
            ->groupBy('office_id', 'period_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateOPCR as $duplicate) {
            $issues[] = "Multiple OPCR workflows found for Office ID {$duplicate->office_id} and Period ID {$duplicate->period_id}";
        }

        return [
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'No duplicate OPCR workflows found' : 'Found duplicate OPCR workflows',
            'errors' => $issues,
            'details' => [
                'duplicate_groups_count' => $duplicateOPCR->count(),
            ],
        ];
    }
}