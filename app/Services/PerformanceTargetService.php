<?php

namespace App\Services;

use App\Models\PerformanceTarget;
use App\Models\PerformanceRating;
use App\Models\MajorFinalOutput;
use App\Models\SuccessIndicator;
use App\Models\OPCRWorkflow;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceTargetService
{
    private MFOHierarchyService $mfoHierarchyService;
    private QETRatingCalculationService $ratingService;
    private AuditTrailService $auditTrailService;

    public function __construct(
        MFOHierarchyService $mfoHierarchyService,
        QETRatingCalculationService $ratingService,
        AuditTrailService $auditTrailService
    ) {
        $this->mfoHierarchyService = $mfoHierarchyService;
        $this->ratingService = $ratingService;
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Create performance target with MFO hierarchy support
     */
    public function createPerformanceTarget(array $data): PerformanceTarget
    {
        return DB::transaction(function () use ($data) {
            // Handle legacy vs new MFO structure
            $targetData = $this->processMFOData($data);

            $target = PerformanceTarget::create($targetData);

            // Log target creation
            $this->auditTrailService->logOPCRActivity(
                'performance_target_created',
                null,
                [
                    'target_id' => $target->id,
                    'employee_id' => $target->employee_id,
                    'period_id' => $target->period_id,
                    'mfo_id' => $target->mfo_id,
                    'success_indicator_id' => $target->success_indicator_id,
                    'is_opcr_compatible' => !empty($target->mfo_id),
                ]
            );

            // Clear relevant cache
            $this->clearTargetCache($target);

            return $target;
        });
    }

    /**
     * Update performance target with MFO hierarchy support
     */
    public function updatePerformanceTarget(PerformanceTarget $target, array $data): PerformanceTarget
    {
        return DB::transaction(function () use ($target, $data) {
            $oldData = $target->toArray();

            // Handle MFO data updates
            $updateData = $this->processMFOData($data, $target);

            $target->update($updateData);

            // Log target update
            $this->auditTrailService->logOPCRActivity(
                'performance_target_updated',
                null,
                [
                    'target_id' => $target->id,
                    'old_data' => $oldData,
                    'new_data' => $updateData,
                    'changes_made' => array_diff_assoc($updateData, $oldData),
                ]
            );

            // Clear relevant cache
            $this->clearTargetCache($target);

            return $target;
        });
    }

    /**
     * Process MFO data for legacy compatibility
     */
    private function processMFOData(array $data, ?PerformanceTarget $existingTarget = null): array
    {
        $processedData = $data;

        // Handle MFO hierarchy data
        if (!empty($data['mfo_id'])) {
            $processedData['mfo_id'] = $data['mfo_id'];
            $processedData['success_indicator_id'] = $data['success_indicator_id'] ?? null;

            // Clear legacy fields for consistency
            unset($processedData['target_category']);
            unset($processedData['target_description']);
        } elseif (!empty($data['success_indicator_id'])) {
            // Derive MFO from success indicator
            $successIndicator = SuccessIndicator::find($data['success_indicator_id']);
            if ($successIndicator) {
                $processedData['mfo_id'] = $successIndicator->mfo_id;
                $processedData['success_indicator_id'] = $data['success_indicator_id'];
            }
        } else {
            // Legacy mode - handle old structure
            if (!empty($data['target_category']) && !empty($data['target_description'])) {
                // Try to find or create matching MFO structure
                $processedData = $this->convertLegacyToMFO($data, $existingTarget);
            }
        }

        return $processedData;
    }

    /**
     * Convert legacy target data to MFO structure
     */
    private function convertLegacyToMFO(array $data, ?PerformanceTarget $existingTarget = null): array
    {
        // This is a simplified conversion - in practice, you might need more sophisticated logic
        $officeId = $data['office_id'] ?? null;

        if (!$officeId) {
            throw new \InvalidArgumentException('Office ID is required for legacy target conversion');
        }

        // Try to find existing MFO that matches the category
        $mfo = MajorFinalOutput::where('office_id', $officeId)
            ->where('title', 'like', '%' . $data['target_category'] . '%')
            ->where('is_active', true)
            ->first();

        if (!$mfo) {
            // Create a generic MFO for legacy data
            $mfo = $this->mfoHierarchyService->createMFO(
                Office::find($officeId),
                [
                    'code' => $this->mfoHierarchyService->generateMFOCode(Office::find($officeId)),
                    'title' => $data['target_category'],
                    'description' => 'Auto-generated MFO from legacy target data',
                    'level' => 1,
                ]
            );
        }

        // Create success indicator if needed
        $successIndicator = SuccessIndicator::where('mfo_id', $mfo->id)
            ->where('title', 'like', '%' . $data['target_description'] . '%')
            ->where('is_active', true)
            ->first();

        if (!$successIndicator) {
            $successIndicator = SuccessIndicator::create([
                'mfo_id' => $mfo->id,
                'code' => $this->mfoHierarchyService->generateSuccessIndicatorCode($mfo),
                'title' => $data['target_description'],
                'description' => $data['target_description'],
                'target_quantity' => $data['target_quantity'] ?? null,
                'target_efficiency' => $data['target_efficiency'] ?? null,
                'target_timeliness' => $data['target_timeliness'] ?? null,
                'created_by' => auth()->id(),
                'is_active' => true,
            ]);
        }

        return array_merge($data, [
            'mfo_id' => $mfo->id,
            'success_indicator_id' => $successIndicator->id,
            'legacy_conversion' => true,
            'original_category' => $data['target_category'] ?? null,
            'original_description' => $data['target_description'] ?? null,
        ]);
    }

    /**
     * Get targets with MFO hierarchy
     */
    public function getTargetsWithMFOHierarchy(array $filters = []): Collection
    {
        $query = PerformanceTarget::with([
            'employee',
            'period',
            'mfo',
            'successIndicator',
            'ratings'
        ]);

        // Apply filters
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (!empty($filters['office_id'])) {
            $query->whereHas('mfo', function ($q) use ($filters) {
                $q->where('office_id', $filters['office_id']);
            });
        }

        if (!empty($filters['mfo_id'])) {
            $query->where('mfo_id', $filters['mfo_id']);
        }

        if (!empty($filters['success_indicator_id'])) {
            $query->where('success_indicator_id', $filters['success_indicator_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get targets by workflow
     */
    public function getTargetsByWorkflow(OPCRWorkflow $workflow): Collection
    {
        return PerformanceTarget::where('employee_id', $workflow->committedBy->employee->id ?? null)
            ->where('period_id', $workflow->period_id)
            ->with(['mfo', 'successIndicator', 'ratings'])
            ->get()
            ->filter(function ($target) use ($workflow) {
                // Filter by office MFOs
                return $target->mfo && $target->mfo->office_id === $workflow->office_id;
            });
    }

    /**
     * Create targets from OPCR workflow
     */
    public function createTargetsFromWorkflow(OPCRWorkflow $workflow): array
    {
        $results = [
            'created' => [],
            'updated' => [],
            'errors' => [],
        ];

        if (!$workflow->committedBy || !$workflow->committedBy->employee) {
            $results['errors'][] = 'No valid employee found for workflow';
            return $results;
        }

        $employee = $workflow->committedBy->employee;

        // Get MFOs and success indicators for the office
        $mfos = MajorFinalOutput::with('activeSuccessIndicators')
            ->where('office_id', $workflow->office_id)
            ->where('is_active', true)
            ->get();

        foreach ($mfos as $mfo) {
            foreach ($mfo->activeSuccessIndicators as $successIndicator) {
                try {
                    // Check if target already exists
                    $existingTarget = PerformanceTarget::where('employee_id', $employee->id)
                        ->where('period_id', $workflow->period_id)
                        ->where('success_indicator_id', $successIndicator->id)
                        ->first();

                    $targetData = [
                        'employee_id' => $employee->id,
                        'period_id' => $workflow->period_id,
                        'mfo_id' => $mfo->id,
                        'success_indicator_id' => $successIndicator->id,
                        'target_quantity' => $successIndicator->target_quantity,
                        'target_efficiency' => $successIndicator->target_efficiency,
                        'target_timeliness' => $successIndicator->target_timeliness,
                        'opcr_workflow_id' => $workflow->id,
                    ];

                    if ($existingTarget) {
                        $existingTarget->update($targetData);
                        $results['updated'][] = $existingTarget->id;
                    } else {
                        $target = PerformanceTarget::create($targetData);
                        $results['created'][] = $target->id;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Error processing MFO {$mfo->id}, SI {$successIndicator->id}: " . $e->getMessage();
                }
            }
        }

        // Log bulk target creation
        $this->auditTrailService->logOPCRActivity(
            'targets_created_from_workflow',
            null,
            [
                'workflow_id' => $workflow->id,
                'employee_id' => $employee->id,
                'period_id' => $workflow->period_id,
                'office_id' => $workflow->office_id,
                'created_count' => count($results['created']),
                'updated_count' => count($results['updated']),
                'errors_count' => count($results['errors']),
            ]
        );

        return $results;
    }

    /**
     * Update target accomplishments from success indicators
     */
    public function updateTargetAccomplishments(PerformanceTarget $target): bool
    {
        if (!$target->successIndicator) {
            return false;
        }

        $successIndicator = $target->successIndicator;

        return $target->update([
            'accomplished_quantity' => $successIndicator->accomplished_quantity,
            'accomplished_efficiency' => $successIndicator->accomplished_efficiency,
            'accomplished_timeliness' => $successIndicator->accomplished_timeliness,
            'performance_percentage' => $successIndicator->performance_percentage,
            'is_target_met' => $successIndicator->is_target_met,
        ]);
    }

    /**
     * Calculate target performance summary
     */
    public function calculateTargetPerformance(PerformanceTarget $target): array
    {
        if (!$target->successIndicator) {
            return [
                'target_rating' => null,
                'performance_percentage' => 0,
                'is_target_met' => false,
                'qet_ratings' => [],
            ];
        }

        $successIndicator = $target->successIndicator;
        $ratings = $successIndicator->ratings()->latest()->first();

        return [
            'target_rating' => $ratings?->average_rating,
            'performance_percentage' => $successIndicator->performance_percentage ?? 0,
            'is_target_met' => $successIndicator->is_target_met ?? false,
            'qet_ratings' => [
                'quantity' => $successIndicator->rating_quantity,
                'efficiency' => $successIndicator->rating_efficiency,
                'timeliness' => $successIndicator->rating_timeliness,
            ],
            'accomplishments' => [
                'quantity' => $successIndicator->accomplished_quantity,
                'efficiency' => $successIndicator->accomplished_efficiency,
                'timeliness' => $successIndicator->accomplished_timeliness,
            ],
            'targets' => [
                'quantity' => $successIndicator->target_quantity,
                'efficiency' => $successIndicator->target_efficiency,
                'timeliness' => $successIndicator->target_timeliness,
            ],
        ];
    }

    /**
     * Get target statistics for dashboard
     */
    public function getTargetStatistics(array $filters = []): array
    {
        $query = PerformanceTarget::query();

        if (!empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (!empty($filters['office_id'])) {
            $query->whereHas('mfo', function ($q) use ($filters) {
                $q->where('office_id', $filters['office_id']);
            });
        }

        $totalTargets = $query->count();
        $targetsWithRatings = $query->whereHas('ratings')->count();

        // Calculate performance metrics
        $targets = $query->with(['successIndicator', 'ratings'])->get();
        $metTargets = $targets->filter(function ($target) {
            return $target->successIndicator && $target->successIndicator->is_target_met;
        })->count();

        $averageRating = $targets->map(function ($target) {
            return $target->ratings->avg('average_rating');
        })->filter()->avg();

        return [
            'total_targets' => $totalTargets,
            'rated_targets' => $targetsWithRatings,
            'rating_completion_rate' => $totalTargets > 0 ? round(($targetsWithRatings / $totalTargets) * 100, 2) : 0,
            'met_targets' => $metTargets,
            'target_completion_rate' => $totalTargets > 0 ? round(($metTargets / $totalTargets) * 100, 2) : 0,
            'average_rating' => round($averageRating ?? 0, 2),
        ];
    }

    /**
     * Migrate legacy targets to MFO structure
     */
    public function migrateLegacyTargets(int $batchSize = 100): array
    {
        $results = [
            'migrated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $legacyTargets = PerformanceTarget::whereNull('mfo_id')
            ->whereNotNull('target_category')
            ->whereNotNull('target_description')
            ->limit($batchSize)
            ->get();

        foreach ($legacyTargets as $target) {
            try {
                $this->convertLegacyToMFO($target->toArray(), $target);
                $results['migrated']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Target {$target->id}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Validate target data
     */
    public function validateTargetData(array $data, ?PerformanceTarget $existingTarget = null): array
    {
        $errors = [];

        // Required fields
        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required';
        }

        if (empty($data['period_id'])) {
            $errors['period_id'] = 'Performance period is required';
        }

        // MFO structure validation
        if (empty($data['mfo_id']) && empty($data['success_indicator_id'])) {
            if (empty($data['target_category']) || empty($data['target_description'])) {
                $errors['mfo_structure'] = 'Either MFO/Success Indicator or legacy category/description is required';
            }
        }

        // Numeric validation
        if (!empty($data['target_quantity']) && !is_numeric($data['target_quantity'])) {
            $errors['target_quantity'] = 'Target quantity must be numeric';
        }

        if (!empty($data['accomplished_quantity']) && !is_numeric($data['accomplished_quantity'])) {
            $errors['accomplished_quantity'] = 'Accomplished quantity must be numeric';
        }

        // Check for duplicates (excluding current target if updating)
        $duplicateQuery = PerformanceTarget::where('employee_id', $data['employee_id'])
            ->where('period_id', $data['period_id']);

        if (!empty($data['success_indicator_id'])) {
            $duplicateQuery->where('success_indicator_id', $data['success_indicator_id']);
        }

        if ($existingTarget) {
            $duplicateQuery->where('id', '!=', $existingTarget->id);
        }

        if ($duplicateQuery->exists()) {
            $errors['duplicate'] = 'A target for this employee, period, and success indicator already exists';
        }

        return $errors;
    }

    /**
     * Get validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'period_id' => 'required|exists:performance_periods,id',
            'mfo_id' => 'nullable|exists:major_final_outputs,id',
            'success_indicator_id' => 'nullable|exists:success_indicators,id',
            'target_category' => 'required_without:mfo_id,success_indicator_id|string|max:255',
            'target_description' => 'required_without:mfo_id,success_indicator_id|string',
            'target_quantity' => 'nullable|numeric|min:0',
            'target_efficiency' => 'nullable|string|max:100',
            'target_timeliness' => 'nullable|string|max:100',
            'accomplished_quantity' => 'nullable|numeric|min:0',
            'accomplished_efficiency' => 'nullable|string|max:100',
            'accomplished_timeliness' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom validation messages
     */
    public static function getValidationMessages(): array
    {
        return [
            'employee_id.required' => 'Employee is required',
            'employee_id.exists' => 'Selected employee is invalid',
            'period_id.required' => 'Performance period is required',
            'period_id.exists' => 'Selected performance period is invalid',
            'mfo_id.exists' => 'Selected MFO is invalid',
            'success_indicator_id.exists' => 'Selected success indicator is invalid',
            'target_category.required_without' => 'Target category is required when no MFO or success indicator is specified',
            'target_description.required_without' => 'Target description is required when no MFO or success indicator is specified',
            'target_quantity.numeric' => 'Target quantity must be a number',
            'target_quantity.min' => 'Target quantity must be at least 0',
            'accomplished_quantity.numeric' => 'Accomplished quantity must be a number',
            'accomplished_quantity.min' => 'Accomplished quantity must be at least 0',
        ];
    }

    /**
     * Clear target cache
     */
    private function clearTargetCache(PerformanceTarget $target): void
    {
        Cache::forget("employee_targets_{$target->employee_id}_{$target->period_id}");
        Cache::forget("period_statistics_{$target->period_id}");
    }

    /**
     * Get targets by employee and period with caching
     */
    public function getEmployeeTargets(int $employeeId, int $periodId): Collection
    {
        return Cache::remember("employee_targets_{$employeeId}_{$periodId}", 3600, function () use ($employeeId, $periodId) {
            return PerformanceTarget::where('employee_id', $employeeId)
                ->where('period_id', $periodId)
                ->with(['mfo', 'successIndicator', 'ratings'])
                ->get();
        });
    }

    /**
     * Archive targets for completed workflows
     */
    public function archiveTargetsForWorkflow(OPCRWorkflow $workflow): int
    {
        $archivedCount = PerformanceTarget::where('opcr_workflow_id', $workflow->id)
            ->update([
                'archived' => true,
                'archived_at' => now(),
                'archived_workflow_state' => $workflow->workflow_state,
            ]);

        // Log archiving activity
        $this->auditTrailService->logOPCRActivity(
            'targets_archived',
            null,
            [
                'workflow_id' => $workflow->id,
                'archived_count' => $archivedCount,
                'workflow_state' => $workflow->workflow_state,
            ]
        );

        return $archivedCount;
    }
}