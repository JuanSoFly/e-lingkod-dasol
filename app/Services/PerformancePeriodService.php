<?php

namespace App\Services;

use App\Models\PerformancePeriod;
use App\Models\OPCRWorkflow;
use App\Models\Office;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PerformancePeriodService
{
    private OPCRManagementService $opcrManagementService;
    private AuditTrailService $auditTrailService;

    public function __construct(
        OPCRManagementService $opcrManagementService,
        AuditTrailService $auditTrailService
    ) {
        $this->opcrManagementService = $opcrManagementService;
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Create a new performance period
     */
    public function createPerformancePeriod(array $data): PerformancePeriod
    {
        return DB::transaction(function () use ($data) {
            $period = PerformancePeriod::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'type' => $data['type'] ?? 'annual',
                'is_active' => $data['is_active'] ?? true,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Log period creation
            $this->auditTrailService->logOPCRActivity(
                'performance_period_created',
                null, // No specific workflow model
                [
                    'period_id' => $period->id,
                    'period_name' => $period->name,
                    'start_date' => $period->start_date,
                    'end_date' => $period->end_date,
                    'type' => $period->type,
                ]
            );

            // Clear cache
            $this->clearPeriodCache();

            return $period;
        });
    }

    /**
     * Update performance period
     */
    public function updatePerformancePeriod(PerformancePeriod $period, array $data): PerformancePeriod
    {
        return DB::transaction(function () use ($period, $data) {
            $oldData = $period->toArray();

            $period->update([
                'name' => $data['name'] ?? $period->name,
                'description' => $data['description'] ?? $period->description,
                'start_date' => $data['start_date'] ?? $period->start_date,
                'end_date' => $data['end_date'] ?? $period->end_date,
                'type' => $data['type'] ?? $period->type,
                'is_active' => $data['is_active'] ?? $period->is_active,
                'metadata' => array_merge($period->metadata ?? [], $data['metadata'] ?? []),
            ]);

            // Log period update
            $this->auditTrailService->logOPCRActivity(
                'performance_period_updated',
                null,
                [
                    'period_id' => $period->id,
                    'period_name' => $period->name,
                    'old_data' => $oldData,
                    'new_data' => $data,
                ]
            );

            // Clear cache
            $this->clearPeriodCache();

            return $period;
        });
    }

    /**
     * Get active performance period
     */
    public function getActivePeriod(): ?PerformancePeriod
    {
        return Cache::remember('active_performance_period', 3600, function () {
            return PerformancePeriod::where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                })
                ->orderBy('start_date', 'desc')
                ->first();
        });
    }

    /**
     * Get all performance periods
     */
    public function getAllPeriods(bool $includeInactive = false): Collection
    {
        $query = PerformancePeriod::orderBy('start_date', 'desc');

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Get period by ID with workflow statistics
     */
    public function getPeriodWithStatistics(int $periodId): array
    {
        $period = PerformancePeriod::findOrFail($periodId);

        $workflows = OPCRWorkflow::where('period_id', $periodId)
            ->with(['office'])
            ->get();

        $totalWorkflows = $workflows->count();
        $completedWorkflows = $workflows->where('workflow_state', 'final_approval')->count();
        $averageRating = $workflows->whereNotNull('overall_rating')->avg('overall_rating');

        $workflowsByState = $workflows->groupBy('workflow_state')
            ->map(fn($group) => $group->count())
            ->toArray();

        $workflowsByOffice = $workflows->groupBy('office_id')
            ->map(function ($group) {
                return [
                    'office_name' => $group->first()->office->name,
                    'total_workflows' => $group->count(),
                    'completed_workflows' => $group->where('workflow_state', 'final_approval')->count(),
                    'average_rating' => $group->whereNotNull('overall_rating')->avg('overall_rating'),
                ];
            });

        return [
            'period' => $period,
            'statistics' => [
                'total_workflows' => $totalWorkflows,
                'completed_workflows' => $completedWorkflows,
                'completion_rate' => $totalWorkflows > 0 ? round(($completedWorkflows / $totalWorkflows) * 100, 2) : 0,
                'average_rating' => round($averageRating ?? 0, 2),
                'workflows_by_state' => $workflowsByState,
                'workflows_by_office' => $workflowsByOffice,
            ],
        ];
    }

    /**
     * Get periods for dropdown/select options
     */
    public function getPeriodsForSelection(): Collection
    {
        return PerformancePeriod::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($period) {
                return [
                    'id' => $period->id,
                    'name' => $period->name,
                    'date_range' => $period->start_date->format('M d, Y') . ' - ' . $period->end_date->format('M d, Y'),
                    'type' => $period->type,
                    'is_current' => $this->isCurrentPeriod($period),
                ];
            });
    }

    /**
     * Check if period is current
     */
    public function isCurrentPeriod(PerformancePeriod $period): bool
    {
        $now = now();
        return $period->is_active &&
               $period->start_date->lte($now) &&
               $period->end_date->gte($now);
    }

    /**
     * Get current period or create default
     */
    public function getCurrentOrCreateDefault(): PerformancePeriod
    {
        $current = $this->getActivePeriod();

        if ($current) {
            return $current;
        }

        // Create default annual period
        return $this->createPerformancePeriod([
            'name' => now()->year . ' Annual Performance Period',
            'description' => 'Default annual performance period for ' . now()->year,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'type' => 'annual',
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate period
     */
    public function deactivatePeriod(PerformancePeriod $period): bool
    {
        return DB::transaction(function () use ($period) {
            // Check if there are active workflows in this period
            $activeWorkflows = OPCRWorkflow::where('period_id', $period->id)
                ->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation'])
                ->count();

            if ($activeWorkflows > 0) {
                throw new \InvalidArgumentException('Cannot deactivate period with active workflows. Please complete or archive all workflows first.');
            }

            $period->update(['is_active' => false]);

            // Log period deactivation
            $this->auditTrailService->logOPCRActivity(
                'performance_period_deactivated',
                null,
                [
                    'period_id' => $period->id,
                    'period_name' => $period->name,
                    'active_workflows_count' => $activeWorkflows,
                ]
            );

            // Clear cache
            $this->clearPeriodCache();

            return true;
        });
    }

    /**
     * Create period workflows for all offices
     */
    public function createPeriodWorkflows(PerformancePeriod $period): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'errors' => [],
        ];

        $offices = Office::where('is_active', true)->get();

        foreach ($offices as $office) {
            try {
                // Check if workflow already exists for this office and period
                $existingWorkflow = OPCRWorkflow::where('office_id', $office->id)
                    ->where('period_id', $period->id)
                    ->first();

                if ($existingWorkflow) {
                    $results['failed'][] = $office->id;
                    $results['errors'][$office->id] = 'Workflow already exists for this office and period';
                    continue;
                }

                // Create new workflow
                $workflow = $this->opcrManagementService->createOPCRWorkflow([
                    'title' => $period->name . ' - ' . $office->name,
                    'office_id' => $office->id,
                    'period_id' => $period->id,
                    'summary' => 'OPCR for ' . $office->name . ' - ' . $period->name,
                ]);

                $results['success'][] = [
                    'office_id' => $office->id,
                    'office_name' => $office->name,
                    'workflow_id' => $workflow->id,
                ];

            } catch (\Exception $e) {
                $results['failed'][] = $office->id;
                $results['errors'][$office->id] = $e->getMessage();
            }
        }

        // Log bulk workflow creation
        $this->auditTrailService->logOPCRActivity(
            'period_workflows_created',
            null,
            [
                'period_id' => $period->id,
                'period_name' => $period->name,
                'offices_count' => $offices->count(),
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed']),
            ]
        );

        return $results;
    }

    /**
     * Get period statistics for dashboard
     */
    public function getPeriodStatistics(): array
    {
        $activePeriod = $this->getActivePeriod();

        if (!$activePeriod) {
            return [
                'active_period' => null,
                'total_periods' => PerformancePeriod::count(),
                'active_workflows' => 0,
                'completion_rate' => 0,
            ];
        }

        $workflows = OPCRWorkflow::where('period_id', $activePeriod->id)->get();
        $completedWorkflows = $workflows->where('workflow_state', 'final_approval')->count();

        return [
            'active_period' => [
                'id' => $activePeriod->id,
                'name' => $activePeriod->name,
                'start_date' => $activePeriod->start_date->toDateString(),
                'end_date' => $activePeriod->end_date->toDateString(),
                'days_remaining' => $activePeriod->end_date->diffInDays(now()),
            ],
            'total_periods' => PerformancePeriod::count(),
            'active_workflows' => $workflows->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation'])->count(),
            'completed_workflows' => $completedWorkflows,
            'total_workflows' => $workflows->count(),
            'completion_rate' => $workflows->count() > 0 ? round(($completedWorkflows / $workflows->count()) * 100, 2) : 0,
        ];
    }

    /**
     * Archive period workflows
     */
    public function archivePeriodWorkflows(PerformancePeriod $period): array
    {
        $workflows = OPCRWorkflow::where('period_id', $period->id)
            ->where('workflow_state', 'final_approval')
            ->get();

        $archivedCount = 0;
        $errors = [];

        foreach ($workflows as $workflow) {
            try {
                $workflow->archive();
                $archivedCount++;
            } catch (\Exception $e) {
                $errors[] = "Workflow {$workflow->id}: " . $e->getMessage();
            }
        }

        // Log archiving activity
        $this->auditTrailService->logOPCRActivity(
            'period_workflows_archived',
            null,
            [
                'period_id' => $period->id,
                'period_name' => $period->name,
                'total_workflows' => $workflows->count(),
                'archived_count' => $archivedCount,
                'errors' => $errors,
            ]
        );

        return [
            'total_workflows' => $workflows->count(),
            'archived_count' => $archivedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Get period completion trend
     */
    public function getPeriodCompletionTrend(int $months = 12): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfMonth();

        $periods = PerformancePeriod::whereBetween('start_date', [$startDate, $endDate])
            ->orderBy('start_date')
            ->get();

        return $periods->map(function ($period) {
            $workflows = OPCRWorkflow::where('period_id', $period->id)->get();
            $completedWorkflows = $workflows->where('workflow_state', 'final_approval')->count();

            return [
                'period_name' => $period->name,
                'month' => $period->start_date->format('Y-m'),
                'total_workflows' => $workflows->count(),
                'completed_workflows' => $completedWorkflows,
                'completion_rate' => $workflows->count() > 0 ? round(($completedWorkflows / $workflows->count()) * 100, 2) : 0,
                'average_rating' => $workflows->whereNotNull('overall_rating')->avg('overall_rating'),
            ];
        })->toArray();
    }

    /**
     * Validate period dates
     */
    public function validatePeriodDates(array $data): array
    {
        $errors = [];

        if (empty($data['start_date'])) {
            $errors['start_date'] = 'Start date is required';
        }

        if (empty($data['end_date'])) {
            $errors['end_date'] = 'End date is required';
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            if ($startDate->gte($endDate)) {
                $errors['end_date'] = 'End date must be after start date';
            }

            // Check for overlapping periods
            $overlappingPeriods = PerformancePeriod::where('is_active', true)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->when(!empty($data['period_id']), function ($query) use ($data) {
                    $query->where('id', '!=', $data['period_id']);
                })
                ->count();

            if ($overlappingPeriods > 0) {
                $errors['dates'] = 'Period dates overlap with existing active periods';
            }
        }

        return $errors;
    }

    /**
     * Get validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'type' => 'required|in:annual,semiannual,quarterly,custom',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom validation messages
     */
    public static function getValidationMessages(): array
    {
        return [
            'name.required' => 'Period name is required',
            'start_date.required' => 'Start date is required',
            'end_date.required' => 'End date is required',
            'end_date.after' => 'End date must be after start date',
            'type.required' => 'Period type is required',
            'type.in' => 'Period type must be one of: annual, semiannual, quarterly, custom',
        ];
    }

    /**
     * Clear period cache
     */
    private function clearPeriodCache(): void
    {
        Cache::forget('active_performance_period');
        Cache::forget('period_statistics');
    }

    /**
     * Check if period has workflows requiring attention
     */
    public function getPeriodAlerts(PerformancePeriod $period): array
    {
        $workflows = OPCRWorkflow::where('period_id', $period->id)->get();
        $alerts = [];

        // Check for overdue workflows
        $overdueWorkflows = $workflows->filter(function ($workflow) {
            $daysSinceUpdate = $workflow->updated_at->diffInDays(now());
            $overdueThreshold = match ($workflow->workflow_state) {
                'draft' => 7,
                'in_progress' => 5,
                'evaluation' => 3,
                default => 10,
            };
            return $daysSinceUpdate > $overdueThreshold;
        });

        if ($overdueWorkflows->count() > 0) {
            $alerts[] = [
                'type' => 'overdue_workflows',
                'message' => "{$overdueWorkflows->count()} workflow(s) are overdue",
                'count' => $overdueWorkflows->count(),
                'severity' => 'high',
            ];
        }

        // Check for workflows nearing deadline
        if ($period->end_date->diffInDays(now()) <= 30) {
            $activeWorkflows = $workflows->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation']);
            if ($activeWorkflows->count() > 0) {
                $alerts[] = [
                    'type' => 'impending_deadline',
                    'message' => "Period ends in {$period->end_date->diffInDays(now())} days with {$activeWorkflows->count()} active workflow(s)",
                    'days_remaining' => $period->end_date->diffInDays(now()),
                    'active_workflows' => $activeWorkflows->count(),
                    'severity' => 'medium',
                ];
            }
        }

        // Check for low completion rate
        $completionRate = $workflows->count() > 0
            ? ($workflows->where('workflow_state', 'final_approval')->count() / $workflows->count()) * 100
            : 0;

        if ($completionRate < 50 && $workflows->count() > 0) {
            $alerts[] = [
                'type' => 'low_completion_rate',
                'message' => "Completion rate is only " . round($completionRate, 1) . "%",
                'completion_rate' => round($completionRate, 1),
                'severity' => 'medium',
            ];
        }

        return $alerts;
    }

    /**
     * Get periods with OPCR status for the index page
     */
    public function getPeriodsWithOPCRStatus(array $filters = []): Collection
    {
        $query = PerformancePeriod::with(['opcrWorkflows' => function ($query) {
            $query->with(['office']);
        }]);

        // Apply filters
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'closed':
                    $query->where('status', 'closed');
                    break;
                case 'upcoming':
                    $query->where('start_date', '>', now());
                    break;
            }
        }

        if (!empty($filters['office_id'])) {
            $query->whereHas('opcrWorkflows', function ($query) use ($filters) {
                $query->where('office_id', $filters['office_id']);
            });
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        $periods = $query->orderBy('start_date', 'desc')->get();

        // Add OPCR status to each period
        return $periods->map(function ($period) {
            $workflows = $period->opcrWorkflows ?? collect();
            $totalWorkflows = $workflows->count();
            $completedWorkflows = $workflows->where('workflow_state', 'final_approval')->count();
            $inProgressWorkflows = $workflows->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation'])->count();

            $period->opcr_status = [
                'total_workflows' => $totalWorkflows,
                'completed_workflows' => $completedWorkflows,
                'in_progress_workflows' => $inProgressWorkflows,
                'completion_rate' => $totalWorkflows > 0 ? round(($completedWorkflows / $totalWorkflows) * 100, 2) : 0,
                'status' => $this->getPeriodStatus($period, $workflows),
            ];

            return $period;
        });
    }

    /**
     * Get available years for filtering
     */
    public function getAvailableYears(): Collection
    {
        return PerformancePeriod::selectRaw('DISTINCT YEAR(start_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');
    }

    /**
     * Get period status options
     */
    public function getStatusOptions(): array
    {
        return [
            'active' => 'Active Periods',
            'inactive' => 'Inactive Periods',
            'closed' => 'Closed Periods',
            'upcoming' => 'Upcoming Periods',
        ];
    }

    /**
     * Get default period settings
     */
    public function getDefaultPeriodSettings(): array
    {
        return [
            'type' => 'annual',
            'is_active' => true,
            'notification_settings' => [
                'email_department_heads' => true,
                'notification_message' => 'New performance period has been created. Please start your OPCR submissions.',
            ],
            'workflow_settings' => [
                'auto_create_workflows' => true,
                'require_approval' => true,
                'allow_revision' => true,
            ],
        ];
    }

    /**
     * Get workflow settings for a period
     */
    public function getWorkflowSettings(PerformancePeriod $period): array
    {
        $workflows = OPCRWorkflow::where('period_id', $period->id)->get();

        return [
            'period_id' => $period->id,
            'period_name' => $period->name,
            'total_offices' => Office::where('is_active', true)->count(),
            'workflows_created' => $workflows->count(),
            'workflows_in_progress' => $workflows->whereIn('workflow_state', ['draft', 'committed', 'in_progress', 'evaluation'])->count(),
            'workflows_completed' => $workflows->where('workflow_state', 'final_approval')->count(),
            'auto_creation_enabled' => $workflows->count() >= Office::where('is_active', true)->count() * 0.8,
        ];
    }

    /**
     * Create period (alias for createPerformancePeriod)
     */
    public function createPeriod(array $data): PerformancePeriod
    {
        return $this->createPerformancePeriod($data);
    }

    /**
     * Update period (alias for updatePerformancePeriod)
     */
    public function updatePeriod(PerformancePeriod $period, array $data): PerformancePeriod
    {
        return $this->updatePerformancePeriod($period, $data);
    }

    /**
     * Delete period with validation
     */
    public function deletePeriod(PerformancePeriod $period): array
    {
        $workflows = OPCRWorkflow::where('period_id', $period->id)->count();

        if ($workflows > 0) {
            return [
                'deleted' => false,
                'message' => "Cannot delete period with {$workflows} active workflow(s). Please archive workflows first."
            ];
        }

        $periodName = $period->name;
        $period->delete();

        // Log deletion
        $this->auditTrailService->logOPCRActivity(
            'performance_period_deleted',
            null,
            [
                'period_id' => $period->id,
                'period_name' => $periodName,
                'workflows_count' => $workflows,
            ]
        );

        // Clear cache
        $this->clearPeriodCache();

        return [
            'deleted' => true,
            'message' => "Performance period '{$periodName}' deleted successfully"
        ];
    }

    /**
     * Activate period with notifications
     */
    public function activatePeriod(PerformancePeriod $period, array $settings): array
    {
        DB::transaction(function () use ($period, $settings) {
            $period->update(['is_active' => true]);

            // Create workflows for all active offices if enabled
            if ($settings['workflow_settings']['auto_create_workflows'] ?? false) {
                $this->createPeriodWorkflows($period);
            }

            // Send notifications if enabled
            $notificationsSent = 0;
            if ($settings['notification_settings']['email_department_heads'] ?? false) {
                $notificationsSent = $this->sendPeriodActivationNotifications($period, $settings);
            }

            return [
                'activated_workflows' => Office::where('is_active', true)->count(),
                'notifications_sent' => $notificationsSent,
            ];
        });
    }

    /**
     * Close period and archive workflows
     */
    public function closePeriod(PerformancePeriod $period, array $settings): array
    {
        DB::transaction(function () use ($period, $settings) {
            $result = $this->archivePeriodWorkflows($period);

            $period->update([
                'is_active' => false,
                'status' => 'closed'
            ]);

            return [
                'archived_workflows' => $result['archived_count'],
                'final_report' => $settings['final_report'] ?? null,
                'errors' => $result['errors'],
            ];
        });
    }

    /**
     * Perform bulk operations on periods
     */
    public function performBulkOperation(array $data): array
    {
        $operation = $data['operation'];
        $periodIds = $data['period_ids'];
        $settings = $data['operation_settings'] ?? [];

        $results = [
            'affected_periods' => [],
            'details' => []
        ];

        foreach ($periodIds as $periodId) {
            try {
                $period = PerformancePeriod::findOrFail($periodId);

                switch ($operation) {
                    case 'activate':
                        $result = $this->activatePeriod($period, $settings);
                        break;
                    case 'close':
                        $result = $this->closePeriod($period, $settings);
                        break;
                    case 'extend':
                        $result = $this->extendPeriod($period, $settings);
                        break;
                    case 'archive':
                        $result = $this->archivePeriodWorkflows($period);
                        break;
                    default:
                        throw new \InvalidArgumentException("Unknown operation: {$operation}");
                }

                $results['affected_periods'][] = [
                    'period_id' => $periodId,
                    'period_name' => $period->name,
                    'operation' => $operation,
                    'success' => true,
                    'result' => $result
                ];

            } catch (\Exception $e) {
                $results['affected_periods'][] = [
                    'period_id' => $periodId,
                    'operation' => $operation,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $results['details']['errors'][] = "Failed to {$operation} period {$periodId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Duplicate a period
     */
    public function duplicatePeriod(PerformancePeriod $period, array $data): PerformancePeriod
    {
        return DB::transaction(function () use ($period, $data) {
            $newPeriod = PerformancePeriod::create([
                'year' => date('Y', strtotime($data['new_start_date'])),
                'semester' => $this->extractSemesterFromDate($data['new_start_date']),
                'name' => $data['new_name'],
                'start_date' => $data['new_start_date'],
                'end_date' => $data['new_end_date'],
                'status' => 'active',
                'is_active' => true,
            ]);

            // Copy workflow configuration if requested
            if ($data['copy_settings']['copy_workflow_configuration'] ?? false) {
                $this->copyWorkflowConfiguration($period, $newPeriod);
            }

            // Copy office assignments if requested
            if ($data['copy_settings']['copy_office_assignments'] ?? false) {
                $this->copyOfficeAssignments($period, $newPeriod);
            }

            // Log duplication
            $this->auditTrailService->logOPCRActivity(
                'performance_period_duplicated',
                null,
                [
                    'original_period_id' => $period->id,
                    'original_period_name' => $period->name,
                    'new_period_id' => $newPeriod->id,
                    'new_period_name' => $newPeriod->name,
                    'copy_settings' => $data['copy_settings'],
                ]
            );

            return $newPeriod;
        });
    }

    /**
     * Export period data
     */
    public function exportPeriodData(PerformancePeriod $period, array $options): string
    {
        $format = $options['format'] ?? 'pdf';
        $includeData = $options['include_data'] ?? [];
        $officeId = $options['office_id'] ?? null;

        $data = [
            'period' => $period->toArray(),
            'workflows' => [],
            'analytics' => [],
            'evaluations' => []
        ];

        if ($includeData['workflows'] ?? true) {
            $workflowQuery = OPCRWorkflow::where('period_id', $period->id)
                ->with(['office', 'currentState']);

            if ($officeId) {
                $workflowQuery->where('office_id', $officeId);
            }

            $data['workflows'] = $workflowQuery->get()->toArray();
        }

        if ($includeData['analytics'] ?? true) {
            $data['analytics'] = $this->getPeriodWithStatistics($period->id);
        }

        if ($includeData['evaluations'] ?? true) {
            $data['evaluations'] = $this->getPeriodEvaluations($period->id, $officeId);
        }

        return $this->generateExportFile($data, $format, $period->name);
    }

    /**
     * Get calendar data for periods
     */
    public function getCalendarData(array $filters = []): array
    {
        $query = PerformancePeriod::query();

        if (!empty($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }

        if (!empty($filters['office_id'])) {
            $query->whereHas('opcrWorkflows', function ($query) use ($filters) {
                $query->where('office_id', $filters['office_id']);
            });
        }

        $periods = $query->get();

        return $periods->map(function ($period) {
            return [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'status' => $this->getPeriodStatus($period),
                'workflows_count' => $period->opcrWorkflows()->count(),
                'completion_rate' => $this->getPeriodCompletionRate($period),
            ];
        })->toArray();
    }

    /**
     * Get period analytics
     */
    public function getPeriodAnalytics(PerformancePeriod $period, array $options = []): array
    {
        $metrics = $options['metrics'] ?? ['submissions', 'approvals', 'ratings', 'timeline'];
        $officeId = $options['office_id'] ?? null;
        $analytics = [];

        $workflowQuery = OPCRWorkflow::where('period_id', $period->id);
        if ($officeId) {
            $workflowQuery->where('office_id', $officeId);
        }
        $workflows = $workflowQuery->get();

        if (in_array('submissions', $metrics)) {
            $analytics['submissions'] = $this->getSubmissionAnalytics($workflows);
        }

        if (in_array('approvals', $metrics)) {
            $analytics['approvals'] = $this->getApprovalAnalytics($workflows);
        }

        if (in_array('ratings', $metrics)) {
            $analytics['ratings'] = $this->getRatingAnalytics($period);
        }

        if (in_array('timeline', $metrics)) {
            $analytics['timeline'] = $this->getTimelineAnalytics($workflows);
        }

        if (in_array('office_performance', $metrics)) {
            $analytics['office_performance'] = $this->getOfficePerformanceAnalytics($period, $officeId);
        }

        return $analytics;
    }

    /**
     * Get workflow summary for period
     */
    public function getWorkflowSummary(PerformancePeriod $period): array
    {
        $workflows = OPCRWorkflow::where('period_id', $period->id)->get();

        $byState = $workflows->groupBy('workflow_state')
            ->map(fn($group) => $group->count())
            ->toArray();

        $byOffice = $workflows->groupBy('office_id')
            ->map(function ($group) {
                return [
                    'office_name' => $group->first()->office->name,
                    'total_workflows' => $group->count(),
                    'completed_workflows' => $group->where('workflow_state', 'final_approval')->count(),
                    'average_rating' => $group->whereNotNull('overall_rating')->avg('overall_rating'),
                ];
            })
            ->toArray();

        return [
            'total_workflows' => $workflows->count(),
            'by_state' => $byState,
            'by_office' => $byOffice,
            'completion_rate' => $this->getPeriodCompletionRate($period),
        ];
    }

    // Helper methods

    /**
     * Get period status based on workflows and dates
     */
    private function getPeriodStatus(PerformancePeriod $period, Collection $workflows = null): string
    {
        if ($workflows === null) {
            $workflows = $period->opcrWorkflows ?? collect();
        }

        if (!$period->is_active) {
            return 'inactive';
        }

        if ($period->status === 'closed') {
            return 'closed';
        }

        if ($period->start_date->gt(now())) {
            return 'upcoming';
        }

        if ($period->end_date->lt(now())) {
            return 'expired';
        }

        if ($workflows->where('workflow_state', 'final_approval')->count() >= $workflows->count() * 0.8) {
            return 'nearing_completion';
        }

        return 'active';
    }

    /**
     * Get period completion rate
     */
    private function getPeriodCompletionRate(PerformancePeriod $period): float
    {
        $totalWorkflows = OPCRWorkflow::where('period_id', $period->id)->count();
        $completedWorkflows = OPCRWorkflow::where('period_id', $period->id)
            ->where('workflow_state', 'final_approval')
            ->count();

        return $totalWorkflows > 0 ? round(($completedWorkflows / $totalWorkflows) * 100, 2) : 0;
    }

    /**
     * Extend period end date
     */
    private function extendPeriod(PerformancePeriod $period, array $settings): array
    {
        $extensionDays = $settings['extension_days'] ?? 30;
        $newEndDate = $period->end_date->addDays($extensionDays);

        $period->update(['end_date' => $newEndDate]);

        return [
            'old_end_date' => $period->end_date->format('Y-m-d'),
            'new_end_date' => $newEndDate->format('Y-m-d'),
            'extension_days' => $extensionDays,
        ];
    }

    /**
     * Extract semester from date
     */
    private function extractSemesterFromDate(string $date): string
    {
        $month = date('n', strtotime($date));
        return $month <= 6 ? '1st' : '2nd';
    }

    /**
     * Copy workflow configuration
     */
    private function copyWorkflowConfiguration(PerformancePeriod $sourcePeriod, PerformancePeriod $targetPeriod): void
    {
        // Implementation for copying workflow templates
        // This would copy MFO templates, rating scales, etc.
    }

    /**
     * Copy office assignments
     */
    private function copyOfficeAssignments(PerformancePeriod $sourcePeriod, PerformancePeriod $targetPeriod): void
    {
        // Implementation for copying office assignments to new period
    }

    /**
     * Send period activation notifications
     */
    private function sendPeriodActivationNotifications(PerformancePeriod $period, array $settings): int
    {
        // Implementation for sending notifications
        return 0; // Return count of notifications sent
    }

    /**
     * Generate export file
     */
    private function generateExportFile(array $data, string $format, string $periodName): string
    {
        // Implementation for generating export files
        return ''; // Return file path
    }

    /**
     * Get submission analytics
     */
    private function getSubmissionAnalytics(Collection $workflows): array
    {
        return [
            'total_submissions' => $workflows->count(),
            'on_time_submissions' => $workflows->whereNotNull('submitted_at')->count(),
            'late_submissions' => $workflows->whereNull('submitted_at')->count(),
        ];
    }

    /**
     * Get approval analytics
     */
    private function getApprovalAnalytics(Collection $workflows): array
    {
        return [
            'total_approvals' => $workflows->where('workflow_state', 'final_approval')->count(),
            'pending_approvals' => $workflows->whereIn('workflow_state', ['evaluation', 'review'])->count(),
            'returned_for_revision' => $workflows->where('workflow_state', 'returned_for_revision')->count(),
        ];
    }

    /**
     * Get rating analytics
     */
    private function getRatingAnalytics(PerformancePeriod $period): array
    {
        // Implementation would join with performance ratings
        return [
            'average_rating' => 0,
            'rating_distribution' => [],
        ];
    }

    /**
     * Get timeline analytics
     */
    private function getTimelineAnalytics(Collection $workflows): array
    {
        return [
            'average_completion_time' => 0,
            'fastest_completion' => null,
            'slowest_completion' => null,
        ];
    }

    /**
     * Get office performance analytics
     */
    private function getOfficePerformanceAnalytics(PerformancePeriod $period, ?int $officeId = null): array
    {
        $query = OPCRWorkflow::where('period_id', $period->id);
        if ($officeId) {
            $query->where('office_id', $officeId);
        }
        $workflows = $query->with('office')->get();

        return $workflows->groupBy('office_id')
            ->map(function ($group) {
                return [
                    'office_name' => $group->first()->office->name,
                    'performance_score' => $group->avg('overall_rating') ?? 0,
                    'completion_rate' => ($group->where('workflow_state', 'final_approval')->count() / $group->count()) * 100,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get period evaluations
     */
    private function getPeriodEvaluations(int $periodId, ?int $officeId = null): array
    {
        // Implementation would fetch performance evaluations
        return [];
    }
}