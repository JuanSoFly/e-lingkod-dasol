<?php

namespace App\Services;

use App\Models\OPCRWorkflow;
use App\Models\PerformanceEvaluation;
use App\Models\OfficeAssignment;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\OPCRWorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Facades\Activity;

class OPCRWorkflowService
{
    private OPCRManagementService $opcrManagementService;
    private QETRatingCalculationService $ratingService;
    private OPCRDataValidationService $validationService;

    public function __construct(
        OPCRManagementService $opcrManagementService,
        QETRatingCalculationService $ratingService,
        OPCRDataValidationService $validationService
    ) {
        $this->opcrManagementService = $opcrManagementService;
        $this->ratingService = $ratingService;
        $this->validationService = $validationService;
    }

    /**
     * Initialize OPCR workflow for a department head
     */
    public function initializeWorkflow(array $data): OPCRWorkflow
    {
        return DB::transaction(function () use ($data) {
            $workflow = $this->opcrManagementService->createOPCRWorkflow($data);

            // Notify relevant users via event
            \App\Events\OPCR\OPCRWorkflowStateChanged::dispatch(
                $workflow,
                'none',
                'draft',
                ['action' => 'initialize']
            );

            Activity::log('OPCR workflow initialized', [
                'opcr_workflow_id' => $workflow->id,
                'office_id' => $workflow->office_id,
                'initiated_by' => Auth::id(),
            ]);

            return $workflow;
        });
    }

    /**
     * Commit OPCR by department head
     */
    public function commitWorkflow(OPCRWorkflow $workflow, array $commitData): bool
    {
        return DB::transaction(function () use ($workflow, $commitData) {
            // Validate commitment
            $validation = $this->validationService->validateCommitment($workflow, $commitData);
            if (!$validation['valid']) {
                throw new \InvalidArgumentException($validation['message']);
            }

            $previousState = $workflow->workflow_state;

            // Update workflow state
            $success = $this->opcrManagementService->updateWorkflowState(
                $workflow,
                OPCRWorkflow::STATE_COMMITTED,
                $commitData
            );

            if ($success) {
                // Dispatch event for notification
                \App\Events\OPCR\OPCRWorkflowStateChanged::dispatch(
                    $workflow,
                    $previousState,
                    OPCRWorkflow::STATE_COMMITTED
                );

                Activity::log('OPCR workflow committed', [
                    'opcr_workflow_id' => $workflow->id,
                    'committed_by' => Auth::id(),
                ]);
            }

            return $success;
        });
    }

    /**
     * Submit workflow for evaluation
     */
    public function submitForEvaluation(OPCRWorkflow $workflow, array $submissionData): bool
    {
        return DB::transaction(function () use ($workflow, $submissionData) {
            // Validate submission
            $validation = $this->validationService->validateSubmission($workflow, $submissionData);
            if (!$validation['valid']) {
                throw new \InvalidArgumentException($validation['message']);
            }

            $previousState = $workflow->workflow_state;

            // Update workflow state
            $success = $this->opcrManagementService->updateWorkflowState(
                $workflow,
                OPCRWorkflow::STATE_IN_PROGRESS,
                $submissionData
            );

            if ($success) {
                // Dispatch event
                \App\Events\OPCR\OPCRWorkflowStateChanged::dispatch(
                    $workflow,
                    $previousState,
                    OPCRWorkflow::STATE_IN_PROGRESS
                );

                Activity::log('OPCR workflow submitted for evaluation', [
                    'opcr_workflow_id' => $workflow->id,
                    'submitted_by' => Auth::id(),
                ]);
            }

            return $success;
        });
    }

    /**
     * Handle evaluation completion and create PerformanceEvaluation record
     */
    public function handleEvaluationCompletion(OPCRWorkflow $workflow, array $evaluationData, $user): bool
    {
        return DB::transaction(function () use ($workflow, $evaluationData, $user) {
            // Create or update PerformanceEvaluation record
            $performanceEvaluation = $this->createPerformanceEvaluation($workflow, $evaluationData, $user);

            // Update workflow with evaluation linkage
            $workflow->update([
                'performance_evaluation_id' => $performanceEvaluation->id,
                'updated_at' => now(),
            ]);

            // Check if evaluation is complete and advance state if needed
            if ($this->isEvaluationComplete($workflow)) {
                $this->transitionState($workflow, OPCRWorkflow::STATE_FINAL_APPROVAL, [
                    'assessed_by' => $user->id,
                    'assessed_at' => now(),
                ]);

                Activity::log('OPCR evaluation completed and advanced to final approval', [
                    'opcr_workflow_id' => $workflow->id,
                    'performance_evaluation_id' => $performanceEvaluation->id,
                    'evaluated_by' => $user->id,
                ]);

                return true;
            }

            Activity::log('OPCR evaluation updated but not yet complete', [
                'opcr_workflow_id' => $workflow->id,
                'performance_evaluation_id' => $performanceEvaluation->id,
                'evaluated_by' => $user->id,
            ]);

            return false;
        });
    }

    /**
     * Create PerformanceEvaluation record for OPCR workflow
     */
    private function createPerformanceEvaluation(OPCRWorkflow $workflow, array $evaluationData, $user): PerformanceEvaluation
    {
        // Calculate overall rating from evaluation data
        $overallRating = $this->calculateOverallRating($evaluationData);

        // Get or find an appropriate employee for this evaluation
        $employee = $this->getEmployeeForEvaluation($workflow);

        return PerformanceEvaluation::updateOrCreate(
            [
                'opcr_workflow_id' => $workflow->id,
            ],
            [
                'employee_id' => $employee->id,
                'office_id' => $workflow->office_id,
                'period_id' => $workflow->period_id,
                'evaluation_period' => $this->getEvaluationPeriodFromWorkflow($workflow),
                'evaluation_date' => now()->format('Y-m-d'),
                'period_start' => $this->getPeriodStartDate($workflow->period_id),
                'period_end' => $this->getPeriodEndDate($workflow->period_id),
                'overall_rating' => $overallRating,
                'overall_qet_rating' => $overallRating,
                'overall_adjectival_rating' => $this->getRatingCategory($overallRating),
                'goal_achievement_percentage' => $overallRating * 20, // Convert to percentage
                'achievements' => $evaluationData['overall_remarks'] ?? null,
                'areas_for_improvement' => $evaluationData['recommendations'] ?? null,
                'evaluator_comments' => $evaluationData['overall_remarks'] ?? null,
                'evaluation_status' => 'completed',
                'workflow_state' => 'final_approval',
                'opcr_type' => 'organizational',
                'evaluator_id' => $user->id,
                'assessor_id' => $user->id,
                'assessment_completed_at' => now(),
                'committed_at' => $workflow->committed_at,
                'committed_by' => $workflow->committed_by,
                'submitted_at' => now(),
                'created_at' => $workflow->created_at,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Calculate overall rating from evaluation data
     */
    private function calculateOverallRating(array $evaluationData): float
    {
        $totalRatings = [];

        if (isset($evaluationData['evaluations'])) {
            foreach ($evaluationData['evaluations'] as $evaluation) {
                if (isset($evaluation['quality_rating'], $evaluation['efficiency_rating'], $evaluation['timeliness_rating'])) {
                    $averageRating = round(
                        (
                            $evaluation['quality_rating'] +
                            $evaluation['efficiency_rating'] +
                            $evaluation['timeliness_rating']
                        ) / 3,
                        2
                    );
                    $totalRatings[] = $averageRating;
                }
            }
        }

        return !empty($totalRatings) ? round(array_sum($totalRatings) / count($totalRatings), 2) : 3.0;
    }

    /**
     * Get appropriate employee for evaluation
     */
    private function getEmployeeForEvaluation(OPCRWorkflow $workflow): Employee
    {
        // Try to get employee from office assignments first
        $officeAssignment = DB::table('office_assignments')
            ->where('office_id', $workflow->office_id)
            ->whereNotNull('employee_id')
            ->where('is_active', true)
            ->first();

        if ($officeAssignment) {
            return Employee::findOrFail($officeAssignment->employee_id);
        }

        // Fallback to any employee
        return Employee::whereNull('deleted_at')->firstOrFail();
    }

    /**
     * Get evaluation period from workflow
     */
    private function getEvaluationPeriodFromWorkflow(OPCRWorkflow $workflow): string
    {
        $period = DB::table('performance_periods')
            ->where('id', $workflow->period_id)
            ->first();

        return $period ? $period->name : 'Annual Evaluation';
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
     * Get rating category based on numerical rating
     */
    private function getRatingCategory(float $rating): string
    {
        if ($rating >= 4.51) return 'Outstanding';
        if ($rating >= 3.76) return 'Very Satisfactory';
        if ($rating >= 3.01) return 'Satisfactory';
        if ($rating >= 2.51) return 'Fairly Satisfactory';
        if ($rating >= 1.51) return 'Poor';
        return 'Very Poor';
    }

    /**
     * Check if evaluation is complete for all targets
     */
    private function isEvaluationComplete(OPCRWorkflow $workflow): bool
    {
        // Load workflow with targets and their success indicators
        $workflow->load(['targets.successIndicator']);

        $totalTargets = $workflow->targets->count();

        if ($totalTargets === 0) {
            return false;
        }

        $completedTargets = 0;

        foreach ($workflow->targets as $target) {
            $successIndicator = $target->successIndicator;

            // Check if all required evaluation data is present
            if ($successIndicator &&
                $successIndicator->accomplished_quality !== null &&
                $successIndicator->accomplished_efficiency !== null &&
                $successIndicator->accomplished_timeliness !== null &&
                $successIndicator->rating_quality !== null &&
                $successIndicator->rating_efficiency !== null &&
                $successIndicator->rating_timeliness !== null &&
                $successIndicator->average_rating !== null) {
                $completedTargets++;
            }
        }

        // Evaluation is complete if all targets have been evaluated
        return $completedTargets === $totalTargets;
    }

    /**
     * Transition workflow state
     */
    public function transitionState(OPCRWorkflow $workflow, string $newState, array $data = []): bool
    {
        return DB::transaction(function () use ($workflow, $newState, $data) {
            $previousState = $workflow->workflow_state;

            // Allow idempotent state transitions so the final approver can persist data
            if ($previousState === $newState) {
                $workflow->update(array_merge($data, ['workflow_state' => $newState]));

                activity()
                    ->performedOn($workflow)
                    ->causedBy(Auth::user())
                    ->withProperties([
                        'from_state' => $previousState,
                        'to_state' => $newState,
                        'opcr_workflow_id' => $workflow->id,
                        'data' => $data,
                        'idempotent' => true,
                    ])
                    ->log('OPCR workflow state metadata updated');

                return true;
            }

            // Validate state transition using validation service
            $this->validationService->validateWorkflowStateTransition($workflow, $newState);

            // Prepare state-specific data
            $stateData = $this->getStateSpecificData($newState);

            // Update workflow with new state and provided data
            $workflow->update(array_merge($data, $stateData, [
                'workflow_state' => $newState,
            ]));

            // Log the state transition
            activity()
                ->performedOn($workflow)
                ->causedBy(Auth::user())
                ->withProperties([
                    'from_state' => $previousState,
                    'to_state' => $newState,
                    'opcr_workflow_id' => $workflow->id,
                    'data' => $data,
                ])
                ->log('OPCR workflow state transition');

            // Dispatch event for notifications
            \App\Events\OPCR\OPCRWorkflowStateChanged::dispatch(
                $workflow,
                $previousState,
                $newState,
                $data
            );

            return true;
        });
    }

    /**
     * Get state-specific data for workflow update
     */
    private function getStateSpecificData(string $state): array
    {
        $userId = Auth::id();
        $now = now();

        return match ($state) {
            OPCRWorkflow::STATE_PLANNING_REVIEW => [
                'committed_by' => $userId,
                'committed_at' => $now,
            ],
            OPCRWorkflow::STATE_PMT_REVIEW => [
                // Planning reviewer details are attached by caller
            ],
            OPCRWorkflow::STATE_COMMITTED => [
                // Commitment metadata set during planning submission to preserve Department Head as committer.
            ],
            OPCRWorkflow::STATE_IN_PROGRESS => [
                'submitted_by' => $userId,
                'submitted_at' => $now,
            ],
            OPCRWorkflow::STATE_EVALUATION => [
                'assessed_by' => $userId,
                'assessed_at' => $now,
            ],
            OPCRWorkflow::STATE_FINAL_APPROVAL => [
                // Final approval metadata is captured when the Final Approver confirms the OPCR.
            ],
            OPCRWorkflow::STATE_RETURNED => [
                'returned_by' => $userId,
                'returned_at' => $now,
            ],
            default => [],
        };
    }

    /**
     * Get state history for workflow
     */
    public function getStateHistory(OPCRWorkflow $workflow): array
    {
        // Get the timeline from the model
        $timeline = $workflow->timeline;

        // Get activity log entries for more detailed history
        $activityLogs = \Spatie\Activitylog\Models\Activity::where('subject_type', OPCRWorkflow::class)
            ->where('subject_id', $workflow->id)
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activity) {
                $properties = $activity->properties ?? [];
                $toState = $properties['to_state'] ?? null;

                return [
                    'user' => $activity->causer ? $activity->causer->name : 'System',
                    'state' => $this->getStateDisplayName($toState),
                    'date' => $activity->created_at->format('M j, Y - g:i A'),
                    'color' => $this->getStateColor($toState),
                    'comments' => $this->formatActivityComments($activity),
                ];
            })
            ->toArray();

        // Convert timeline entries to the expected format
        $formattedTimeline = collect($timeline)->map(function ($item) {
            return [
                'user' => $item['user'] ? $item['user']->name : 'System',
                'state' => $item['event'],
                'date' => $item['timestamp'] ? \Carbon\Carbon::parse($item['timestamp'])->format('M j, Y - g:i A') : 'Unknown',
                'color' => $this->getEventColor($item['type']),
                'comments' => $item['description'] ?? null,
            ];
        })->toArray();

        // Merge and format history
        $mergedHistory = collect($activityLogs)
            ->merge($formattedTimeline)
            ->unique(function ($item) {
                // Create unique key based on date and state
                return $item['date'] . '_' . $item['state'];
            })
            ->sortByDesc('date')
            ->values()
            ->toArray();

        return $mergedHistory;
    }

    /**
     * Get state display name
     */
    private function getStateDisplayName(?string $state): string
    {
        return match ($state) {
            OPCRWorkflow::STATE_DRAFT => 'Draft',
            OPCRWorkflow::STATE_COMMITTED => 'Committed',
            OPCRWorkflow::STATE_IN_PROGRESS => 'In Progress',
            OPCRWorkflow::STATE_EVALUATION => 'Under Evaluation',
            OPCRWorkflow::STATE_FINAL_APPROVAL => 'Approved',
            OPCRWorkflow::STATE_RETURNED => 'Returned for Revision',
            default => $state ?? 'Unknown',
        };
    }

    /**
     * Get state color
     */
    private function getStateColor(?string $state): string
    {
        return match ($state) {
            OPCRWorkflow::STATE_DRAFT => 'gray',
            OPCRWorkflow::STATE_COMMITTED => 'blue',
            OPCRWorkflow::STATE_IN_PROGRESS => 'yellow',
            OPCRWorkflow::STATE_EVALUATION => 'orange',
            OPCRWorkflow::STATE_FINAL_APPROVAL => 'green',
            OPCRWorkflow::STATE_RETURNED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get event color based on type
     */
    private function getEventColor(?string $type): string
    {
        return match ($type) {
            'creation' => 'gray',
            'commitment' => 'blue',
            'submission' => 'yellow',
            'assessment', 'evaluation_complete' => 'orange',
            'approval' => 'green',
            'return' => 'red',
            default => 'gray',
        };
    }

    /**
     * Format activity comments
     */
    private function formatActivityComments($activity): ?string
    {
        $properties = $activity->properties ?? [];

        if (isset($properties['from_state'], $properties['to_state'])) {
            $fromState = $this->getStateDisplayName($properties['from_state']);
            $toState = $this->getStateDisplayName($properties['to_state']);
            return "Changed from {$fromState} to {$toState}";
        }

        return $activity->description;
    }

    /**
     * Get workflow analytics for dashboard
     */
    public function getWorkflowAnalytics(): array
    {
        $totalWorkflows = OPCRWorkflow::count();
        $completedWorkflows = OPCRWorkflow::where('workflow_state', OPCRWorkflow::STATE_FINAL_APPROVAL)->count();
        $activeWorkflows = OPCRWorkflow::active()->count();
        
        $stateDistribution = OPCRWorkflow::selectRaw('workflow_state, count(*) as count')
            ->groupBy('workflow_state')
            ->pluck('count', 'workflow_state')
            ->toArray();
            
        return [
            'total' => $totalWorkflows,
            'completed' => $completedWorkflows,
            'active' => $activeWorkflows,
            'completion_rate' => $totalWorkflows > 0 ? round(($completedWorkflows / $totalWorkflows) * 100, 1) : 0,
            'distribution' => $stateDistribution
        ];
    }
}
