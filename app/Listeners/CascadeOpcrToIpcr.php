<?php

namespace App\Listeners;

use App\Events\OpcrWorkflowApproved;
use App\Services\IpcrCascadingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CascadeOpcrToIpcr implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 2;

    public function __construct(
        protected IpcrCascadingService $cascadingService,
    ) {
    }

    public function handle(OpcrWorkflowApproved $event): void
    {
        $workflow = $event->workflow
            ->fresh([
                'office.departmentHead',
                'period',
                'targets.successIndicator',
                'finalApprover.employee',
            ]);

        if (!$workflow || !$workflow->period) {
            return;
        }

        if ($workflow->workflow_state !== \App\Models\OPCRWorkflow::STATE_FINAL_APPROVAL) {
            return;
        }

        if ($workflow->targets->isEmpty()) {
            return;
        }

        $metadata = $workflow->metadata ?? [];
        $lastCascadeAt = Arr::get($metadata, 'ipcr_cascade_at');
        $lastCascadeAtCarbon = $lastCascadeAt ? Carbon::parse($lastCascadeAt) : null;

        if (!$event->forceCascade && $lastCascadeAtCarbon) {
            // Skip if nothing changed since last cascade
            if (empty($workflow->updated_at) || !$workflow->updated_at->gt($lastCascadeAtCarbon)) {
                return;
            }

            // Skip auto-cascade when workflow changed after last cascade unless forced
            if ($workflow->updated_at->gt($lastCascadeAtCarbon)) {
                return;
            }
        }

        $options = array_filter([
            'period_id' => $workflow->period_id,
            'initiated_by' => $event->approvedBy?->id,
            'head_of_office_id' => $workflow->office?->department_head_id,
            'final_approver_id' => $workflow->finalApprover?->employee?->id,
        ]);

        try {
            $ipcrs = $this->cascadingService->cascade($workflow, $options);
        } catch (\Throwable $exception) {
            Log::error('Failed to cascade OPCR to IPCR', [
                'workflow_id' => $workflow->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $workflow->forceFill([
            'metadata' => array_merge($metadata, [
                'ipcr_cascade_at' => now()->toDateTimeString(),
                'ipcr_cascade_count' => $ipcrs->count(),
            ]),
        ])->save();
    }
}
