<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Ipcr;
use App\Models\IpcrItem;
use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\OPCRWorkflow;
use App\Models\PerformanceTarget;
use App\Models\PerformancePeriod;
use App\Models\WeightDistributionRule;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IpcrCascadingService
{
    public function __construct(
        protected IpcrAssignmentService $assignmentService,
        protected IpcrNotificationService $notificationService,
    ) {
    }

    /**
     * Cascade an OPCR workflow to IPCRs for the given performance period.
     *
     * @param  OPCRWorkflow  $workflow
     * @param  array  $options
     * @return Collection<int, Ipcr>
     */
    public function cascade(OPCRWorkflow $workflow, array $options = []): Collection
    {
        $period = $workflow->period instanceof PerformancePeriod
            ? $workflow->period
            : PerformancePeriod::find($options['period_id'] ?? null);

        if (!$period) {
            throw new ModelNotFoundException('Performance period is required for IPCR cascading.');
        }

        $targets = $workflow->targets()->with(['successIndicator'])->get();

        if ($targets->isEmpty()) {
            return collect();
        }

        $assignees = $this->assignmentService->determineAssignees($workflow, $options);

        if ($assignees->isEmpty()) {
            return collect();
        }

        $resolvedHeadId = $this->resolveHeadOfOfficeId($workflow, $options);
        $resolvedFinalApproverId = $this->resolveFinalApproverId($workflow, $options);

        $generatedIpcrs = DB::transaction(function () use ($assignees, $targets, $workflow, $period, $options, $resolvedHeadId, $resolvedFinalApproverId) {
            return $assignees->map(function (array $assignment) use ($targets, $workflow, $period, $options, $resolvedHeadId, $resolvedFinalApproverId) {
                /** @var Employee $employee */
                $employee = $assignment['employee'];
                $supervisor = $assignment['supervisor'];

                $ipcr = Ipcr::withTrashed()->firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period_id' => $period->id,
                    ],
                    [
                        'office_id' => $employee->office_id ?? $workflow->office_id,
                        'opcr_workflow_id' => $workflow->id,
                        'supervisor_id' => $supervisor?->id,
                        'head_of_office_id' => $resolvedHeadId,
                        'status' => IpcrWorkflowService::STATE_DRAFT,
                        'is_auto_generated' => true,
                        'total_weight' => 0,
                    ]
                );

                if ($ipcr->trashed()) {
                    $ipcr->restore();
                }

                $ipcr->fill([
                    'office_id' => $employee->office_id ?? $workflow->office_id,
                    'opcr_workflow_id' => $workflow->id,
                    'supervisor_id' => $supervisor?->id,
                    'head_of_office_id' => $resolvedHeadId,
                    'pmt_validator_id' => $options['pmt_validator_id'] ?? null,
                    'final_approver_id' => $resolvedFinalApproverId,
                    'metadata' => array_merge($ipcr->metadata ?? [], [
                        'generated_at' => now()->toDateTimeString(),
                        'generator' => 'IpcrCascadingService',
                    ]),
                ])->save();

                $this->syncItems($ipcr, $targets, $options);

                return $ipcr->fresh('items');
            });
        });

        $this->notificationService->notifyCascadeCompletion($workflow, $generatedIpcrs, $options);

        return $generatedIpcrs;
    }

    protected function syncItems(Ipcr $ipcr, EloquentCollection $targets, array $options = []): void
    {
        $existingItems = $ipcr->items()->get()->keyBy('performance_target_id');

        $distribution = $this->calculateWeightDistribution($ipcr, $targets, $options);

        $totalWeight = 0;

        $targets->each(function (PerformanceTarget $target) use (&$existingItems, $ipcr, $distribution, &$totalWeight, $options) {
            $payload = [
                'ipcr_id' => $ipcr->id,
                'performance_target_id' => $target->id,
                'success_indicator_id' => $target->success_indicator_id,
                'title' => $target->objective ?? $target->success_indicator ?? 'Target',
                'description' => $target->target,
                'weight' => $distribution[$target->id] ?? 0,
                'measure' => $target->success_indicator,
                'target_quality' => $target->target_quality,
                'target_efficiency' => $target->target_efficiency,
                'target_timeliness' => $target->target_timeliness,
                'sequence' => $target->id,
                'metadata' => array_merge(
                    $existingItems->get($target->id)?->metadata ?? [],
                    [
                        'source' => 'opcr_cascade',
                        'opcr_workflow_id' => $target->opcr_workflow_id,
                    ]
                ),
            ];

            /** @var IpcrItem $item */
            $item = $ipcr->items()->updateOrCreate(
                [
                    'ipcr_id' => $ipcr->id,
                    'performance_target_id' => $target->id,
                ],
                $payload
            );

            $item->performanceLinks()->updateOrCreate(
                [
                    'linked_type' => PerformanceTarget::class,
                    'linked_id' => $target->id,
                ],
                [
                    'ipcr_id' => $item->ipcr->id,
                    'link_category' => 'opcr-target',
                    'description' => 'Cascade from OPCR workflow',
                    'created_by' => $options['initiated_by'] ?? null,
                ]
            );

            $item->ipcr->mappings()->updateOrCreate(
                [
                    'opcr_workflow_id' => $target->opcr_workflow_id,
                    'performance_target_id' => $target->id,
                    'ipcr_item_id' => $item->id,
                ],
                [
                    'weight_percentage' => $item->weight,
                    'cascade_level' => 'office-to-individual',
                    'allocation_strategy' => $distribution['strategy'],
                ]
            );

            $totalWeight += $item->weight;
        });

        // Remove items that are no longer part of the OPCR
        $ipcr->items()
            ->whereNotIn('performance_target_id', $targets->pluck('id'))
            ->delete();

        $ipcr->update([
            'total_weight' => round($totalWeight, 2),
        ]);
    }

    /**
     * Calculate weight distribution for the employee based on rules and OPCR weights.
     *
     * @param  Ipcr  $ipcr
     * @param  EloquentCollection<PerformanceTarget>  $targets
     */
    protected function calculateWeightDistribution(Ipcr $ipcr, EloquentCollection $targets, array $options = []): array
    {
        $officeRules = WeightDistributionRule::query()
            ->where('office_id', $ipcr->office_id)
            ->active()
            ->orderBy('priority')
            ->get();

        $strategy = 'proportional';

        if ($officeRules->isNotEmpty()) {
            $strategy = Arr::first($officeRules)?->allocation_method ?? $strategy;
        }

        $weights = [];

        $totalTargetWeight = max(1, $targets->sum(function (PerformanceTarget $target) {
            return $target->weight ?: 0;
        }));

        foreach ($targets as $target) {
            $baseWeight = $target->weight ?: (100 / max(1, $targets->count()));

            if ($strategy === 'proportional') {
                $weights[$target->id] = round(($baseWeight / $totalTargetWeight) * 100, 2);
            } elseif ($strategy === 'equal') {
                $weights[$target->id] = round(100 / max(1, $targets->count()), 2);
            } else {
                $weights[$target->id] = round($baseWeight, 2);
            }
        }

        // Normalize to ensure exact 100%
        $factor = 100 / max(0.01, array_sum($weights));
        foreach ($weights as $id => $value) {
            $weights[$id] = round($value * $factor, 2);
        }

        $weights['strategy'] = $strategy;

        return $weights;
    }

    protected function resolveHeadOfOfficeId(OPCRWorkflow $workflow, array $options): ?int
    {
        if (!empty($options['head_of_office_id'])) {
            return $options['head_of_office_id'];
        }

        if ($workflow->office?->department_head_id) {
            return $workflow->office->department_head_id;
        }

        return OfficeAssignment::query()
            ->current()
            ->forOffice($workflow->office_id)
            ->byRole(OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->value('employee_id');
    }

    protected function resolveFinalApproverId(OPCRWorkflow $workflow, array $options): ?int
    {
        if (!empty($options['final_approver_id'])) {
            return $options['final_approver_id'];
        }

        if ($workflow->finalApprover?->employee?->id) {
            return $workflow->finalApprover->employee->id;
        }

        return OfficeAssignment::query()
            ->current()
            ->forOffice($workflow->office_id)
            ->byRole(OfficeAssignment::ROLE_FINAL_APPROVER)
            ->value('employee_id');
    }
}
